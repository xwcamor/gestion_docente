<?php
namespace App\Services;

use App\Models\CourseModality;
use App\Models\Holiday;
use App\Models\ClassSession;
use App\Models\GeneratedDocument;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarService
{
    public function rebuild(CourseModality $modality): array
    {
        if (!$modality->active) {
            throw ValidationException::withMessages(['modality' => 'No se puede generar un calendario para una modalidad desactivada.']);
        }

        if (!$modality->start_date || !$modality->end_date || $modality->end_date->lt($modality->start_date)) {
            throw ValidationException::withMessages(['start_date' => 'Indique un inicio y fin válidos para la modalidad.']);
        }
        $schedules = $modality->schedules()->where('block_type', 'CLASS')->orderBy('day_of_week')->orderBy('start_time')->get();
        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages(['schedule' => 'Agregue al menos un horario de clase antes de calcular las sesiones.']);
        }
        $assignment = $modality->assignment()->with(['group','curriculum.weeks.task.operations','curriculum.weeks.contentItems'])->firstOrFail();
        $professorId = $assignment->group->professor_id;
        $curriculum = $assignment->curriculum;
        $courseStart = $modality->start_date->copy();
        $courseWeekAnchor = $courseStart->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $holidays = Holiday::query()
            ->whereBetween('holiday_date', [$modality->start_date->toDateString(), $modality->end_date->toDateString()])
            ->where('blocks_classes', true)
            ->where(function ($q) use ($professorId) { $q->whereNull('professor_id')->orWhere('professor_id', $professorId); })
            ->get()->keyBy(fn ($h) => $h->holiday_date->toDateString());

        $candidates = [];
        $holidayRows = [];
        foreach ($schedules as $schedule) {
            $repeatEveryWeeks = max(1, (int) ($schedule->repeat_interval_weeks ?? 1));
            $firstOccurrence = $modality->start_date->copy();
            while ((int) $firstOccurrence->dayOfWeekIso !== (int) $schedule->day_of_week) {
                $firstOccurrence->addDay();
            }

            foreach (CarbonPeriod::create($modality->start_date, $modality->end_date) as $day) {
                if ((int) $day->dayOfWeekIso !== (int) $schedule->day_of_week) continue;

                $weeksSinceFirstOccurrence = intdiv($firstOccurrence->diffInDays($day), 7);
                if ($weeksSinceFirstOccurrence % $repeatEveryWeeks !== 0) continue;

                $key = $day->toDateString();
                $duration = max(0, (int) ((strtotime($schedule->end_time) - strtotime($schedule->start_time)) / 60));
                if (isset($holidays[$key])) {
                    $holidayRows[] = ['schedule' => $schedule, 'date' => $key, 'duration' => $duration, 'holiday' => $holidays[$key]];
                    continue;
                }
                $candidates[] = ['schedule' => $schedule, 'date' => $key, 'duration' => $duration];
            }
        }
        usort($candidates, fn ($a, $b) => [$a['date'], $a['schedule']->start_time] <=> [$b['date'], $b['schedule']->start_time]);
        $scheduled = []; $usedMinutes = 0; $counter = 0;
        foreach ($candidates as $candidate) {
            $counter++;
            $day = \Carbon\Carbon::parse($candidate['date']);
            $sessionWeekStart = $day->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $weekOffsetDays = (int) $courseWeekAnchor->diffInDays($sessionWeekStart, false);
            $curriculumWeekNumber = max(1, 1 + intdiv(max(0, $weekOffsetDays), 7));
            $curriculumWeek = $curriculum?->weeks->firstWhere('week_number', $curriculumWeekNumber);
            $scheduled[] = [...$candidate, 'number' => $counter, 'curriculum_week' => $curriculumWeek, 'curriculum_week_number' => $curriculumWeekNumber];
            $usedMinutes += $candidate['duration'];
        }
        $weeklyMinutes = 0;
        foreach ($schedules as $weeklySchedule) {
            $duration = max(0, (int) ((strtotime($weeklySchedule->end_time) - strtotime($weeklySchedule->start_time)) / 60));
            $weeklyMinutes += $duration / max(1, (int) ($weeklySchedule->repeat_interval_weeks ?? 1));
        }
        $weeklyHours = round($weeklyMinutes / 60, 2);
        $weeklyDifference = $modality->total_hours !== null ? round((float) $modality->total_hours - $weeklyHours, 2) : null;
        $activeKeys = [];
        DB::transaction(function () use ($modality, $scheduled, $holidayRows, $curriculum, &$activeKeys) {
            // Evita un SELECT por cada sesión: se leen una vez y se reutilizan.
            // Se conserva Eloquent::save() para que AuditObserver siga registrando los cambios.
            $existingSessions = ClassSession::query()
                ->where('course_modality_id', $modality->id)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($session) =>
                    ($session->modality_schedule_id ?? 0).'|'.$session->session_date->toDateString()
                );

            foreach ($scheduled as $row) {
                $key = $row['schedule']->id.'|'.$row['date'];
                $activeKeys[$key] = true;
                $session = $existingSessions->get($key) ?? new ClassSession([
                    'course_modality_id' => $modality->id,
                    'modality_schedule_id' => $row['schedule']->id,
                    'session_date' => $row['date'],
                ]);
                $week = $row['curriculum_week'];
                $task = $week?->task;
                $operations = $task?->operations ?? collect();
                $sourceItems = $week?->contentItems ?? collect();
                $approvedSuggestions = $sourceItems->where('source_kind', 'AI_SUGGESTION')->where('approved', true)->filter(function ($item) use ($modality) {
                    $target = $item->metadata['target_modality'] ?? 'BOTH';
                    return $target === 'BOTH' || $target === $modality->modality;
                });
                $planPrefix = $modality->modality === 'TEC' ? 'TEC' : 'TALL';
                $planTopic = $approvedSuggestions->firstWhere('category', $planPrefix.'_TOPIC')?->content;
                $planObjective = $approvedSuggestions->firstWhere('category', $planPrefix.'_OBJECTIVE')?->content;
                $sourceTopic = trim((string) ($week?->source_topic ?? ''));
                $topic = $week?->is_evaluation
                    ? 'Evaluación curricular'
                    : ($planTopic ?: ($sourceTopic ?: ($task?->title ?: null)));
                $activityLines = [];
                if ($week) {
                    $activityLines[] = 'SEMANA DEL CURSO '.$week->week_number.($week->source_week_number ? ' (SEMANA ORIGINAL MALLA '.$week->source_week_number.')' : '');
                    $activityLines[] = 'Horas malla T/P/V: '.($week->hours_theory ?? '—').' / '.($week->hours_practice ?? '—').' / '.($week->hours_virtual ?? '—');
                }
                if ($task) $activityLines[] = $task->task_code.': '.$task->title;
                if ($sourceTopic !== '') $activityLines[] = 'TEMA OFICIAL HOJA DE PROGRAMACIÓN: '.$sourceTopic;
                if ($planTopic) $activityLines[] = 'TEMA '.$planPrefix.': '.$planTopic;
                if ($planObjective) $activityLines[] = 'OBJETIVO '.$planPrefix.': '.$planObjective;
                if ($operations->isNotEmpty()) {
                    $activityLines[] = 'OPERACIONES:';
                    foreach ($operations as $operation) $activityLines[] = $operation->operation_code.' - '.$operation->description;
                }
                foreach (['TECHNOLOGICAL'=>'CONOCIMIENTOS TECNOLÓGICOS','COMPLEMENTARY'=>'CONOCIMIENTOS COMPLEMENTARIOS','VIRTUAL'=>'FORMACIÓN VIRTUAL'] as $category => $label) {
                    $items = $sourceItems->where('category', $category);
                    if ($items->isNotEmpty()) {
                        $activityLines[] = $label.':';
                        foreach ($items as $item) $activityLines[] = '• '.$item->content;
                    }
                }
                foreach ([$planPrefix.'_ACTIVITY'=>'ACTIVIDADES '.$planPrefix,$planPrefix.'_EXERCISE'=>'EJERCICIOS '.$planPrefix,$planPrefix.'_MATERIAL'=>'MATERIALES '.$planPrefix,$planPrefix.'_EVIDENCE'=>'EVIDENCIAS '.$planPrefix] as $category => $label) {
                    $items = $approvedSuggestions->where('category', $category);
                    if ($items->isNotEmpty()) {
                        $activityLines[] = $label.':';
                        foreach ($items as $item) $activityLines[] = '• '.$item->content;
                    }
                }
                foreach (['EXERCISE'=>'EJERCICIOS PROPUESTOS (APROBADOS)','MATERIAL'=>'MATERIALES PROPUESTOS (APROBADOS)','EVIDENCE'=>'EVIDENCIAS PROPUESTAS (APROBADAS)'] as $category => $label) {
                    $items = $approvedSuggestions->where('category', $category);
                    if ($items->isNotEmpty()) {
                        $activityLines[] = $label.':';
                        foreach ($items as $item) $activityLines[] = '• '.$item->content;
                    }
                }
                $session->fill([
                    'session_number' => $row['number'], 'duration_minutes' => $row['duration'],
                    'status' => 'scheduled', 'system_note' => null,
                    'curriculum_week_id' => $week?->id, 'curriculum_week_number' => $row['curriculum_week_number'],
                ]);
                $hasManualContent = $session->exists && $session->content_origin === 'manual'
                    && (filled($session->topic) || filled($session->objective) || filled($session->activities) || filled($session->evidence));
                if (!$hasManualContent && $week) {
                    $session->topic = $topic ?: $session->topic;
                    $session->objective = $planObjective ?: ($curriculum?->general_objective ?: $session->objective);
                    $session->activities = $activityLines ? implode("\n", $activityLines) : $session->activities;
                    $session->evidence = $approvedSuggestions->whereIn('category', ['EVIDENCE', $planPrefix.'_EVIDENCE'])->isNotEmpty()
                        ? $approvedSuggestions->whereIn('category', ['EVIDENCE', $planPrefix.'_EVIDENCE'])->pluck('content')->implode("\n")
                        : ($session->evidence ?: null);
                    $session->content_origin = 'curriculum';
                }
                $session->save();
            }
            foreach ($holidayRows as $row) {
                $key = $row['schedule']->id.'|'.$row['date'];
                $activeKeys[$key] = true;
                $session = $existingSessions->get($key) ?? new ClassSession([
                    'course_modality_id' => $modality->id,
                    'modality_schedule_id' => $row['schedule']->id,
                    'session_date' => $row['date'],
                ]);
                $session->fill([
                    'session_number' => 0,
                    'duration_minutes' => $row['duration'],
                    'status' => 'holiday',
                    'system_note' => $row['holiday']->name,
                ]);
                $session->save();
            }
            foreach ($existingSessions as $session) {
                $key = ($session->modality_schedule_id ?? 0).'|'.$session->session_date->toDateString();
                if (!isset($activeKeys[$key]) && $session->status !== 'cancelled') {
                    $session->update(['status' => 'cancelled', 'system_note' => 'Fuera del calendario recalculado; contenido conservado.']);
                }
            }
            foreach (GeneratedDocument::where('course_modality_id', $modality->id)->get() as $document) {
                $meta = $document->metadata ?? [];
                $meta['stale'] = true;
                $meta['stale_reason'] = 'El calendario de la modalidad fue recalculado después de generar este Excel.';
                $document->update(['metadata' => $meta]);
            }
        });
        $excludedDates = collect($holidayRows)
            ->groupBy('date')
            ->map(fn ($rows, $date) => [
                'date' => $date,
                'name' => $rows->first()['holiday']->name,
            ])
            ->values()
            ->all();

        return [
            'scheduled' => count($scheduled), 'holidays' => count($excludedDates),
            'class_hours_in_range' => round($usedMinutes / 60, 2),
            'weekly_hours_from_schedule' => $weeklyHours,
            'declared_weekly_hours' => $modality->total_hours !== null ? (float) $modality->total_hours : null,
            'weekly_hours_difference' => $weeklyDifference,
            'curriculum_weeks_mapped' => $curriculum ? $curriculum->weeks->count() : 0,
            'excluded_dates' => $excludedDates,
        ];
    }
}
