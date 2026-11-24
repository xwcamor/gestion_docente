<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\Holiday;
use App\Services\CalendarService;
use App\Services\FiveMinuteTalkService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourseModalityController extends Controller
{
    public function show(CourseModality $modality, FiveMinuteTalkService $talkService)
    {
        $this->owned($modality);
        $modality->load(['assignment.course', 'assignment.group.cycle', 'schedules']);
        $talkService->ensureScheduled($modality->assignment->group);

        $enrollmentsCount = $modality->enrollments()->count();
        $sessionCount = $modality->sessions()->count();
        $scheduledSessionCount = $modality->sessions()->where('status', 'scheduled')->count();
        $documentCount = $modality->documents()->count();

        $hasCourseDates = $modality->start_date !== null && $modality->end_date !== null;
        $classSchedules = $modality->schedules
            ->where('block_type', 'CLASS')
            ->sortBy(fn ($schedule) => sprintf('%d|%s', $schedule->day_of_week, $schedule->start_time))
            ->values();
        $hasAnySchedule = $classSchedules->isNotEmpty();
        $hasClassSchedule = $hasAnySchedule;
        $schedulesByDay = $classSchedules->groupBy('day_of_week');
        $weeklyMinutes = $classSchedules->sum(fn ($schedule) => $schedule->duration_minutes);
        $weeklyHoursCalculated = round($weeklyMinutes / 60, 2);
        $sessionsWithContentCount = $modality->sessions()
            ->where('status', 'scheduled')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('topic')->where('topic', '<>', '');
                })->orWhere(function ($q) {
                    $q->whereNotNull('objective')->where('objective', '<>', '');
                })->orWhere(function ($q) {
                    $q->whereNotNull('activities')->where('activities', '<>', '');
                })->orWhere(function ($q) {
                    $q->whereNotNull('evidence')->where('evidence', '<>', '');
                });
            })
            ->count();
        $calendarStatus = $hasCourseDates && $hasAnySchedule && $scheduledSessionCount > 0
            ? 'complete'
            : (($hasCourseDates || $hasAnySchedule) ? 'partial' : 'pending');
        $studentsStatus = $enrollmentsCount > 0 ? 'complete' : 'pending';
        $documentsStatus = $documentCount > 0 ? 'complete' : 'pending';
        $sessionsStatus = $scheduledSessionCount === 0
            ? 'pending'
            : ($sessionsWithContentCount >= $scheduledSessionCount ? 'complete' : 'partial');

        $sessions = $modality->sessions()
            ->orderBy('session_date')
            ->orderBy('session_number')
            ->paginate(25, ['*'], 'sessions_page')
            ->withQueryString();

        $documents = $modality->documents()
            ->latest()
            ->paginate(10, ['*'], 'documents_page')
            ->withQueryString();

        $blockedHolidaysQuery = Holiday::query()
            ->where('blocks_classes', true)
            ->where(function ($query) use ($modality) {
                $query->whereNull('professor_id')
                    ->orWhere('professor_id', $modality->assignment->group->professor_id);
            });
        if ($modality->start_date && $modality->end_date) {
            $blockedHolidaysQuery->whereBetween('holiday_date', [
                $modality->start_date->toDateString(),
                $modality->end_date->toDateString(),
            ]);
        }
        $blockedHolidays = $blockedHolidaysQuery
            ->orderBy('holiday_date')
            ->get(['holiday_date', 'name'])
            ->map(fn ($holiday) => [
                'date' => Carbon::parse($holiday->holiday_date)->toDateString(),
                'name' => $holiday->name,
            ])
            ->values();
        $holidayDates = $blockedHolidays->pluck('date')->all();

        return view('modalities.show', compact(
            'modality',
            'holidayDates',
            'blockedHolidays',
            'schedulesByDay',
            'weeklyHoursCalculated',
            'enrollmentsCount',
            'sessionCount',
            'scheduledSessionCount',
            'documentCount',
            'calendarStatus',
            'studentsStatus',
            'documentsStatus',
            'sessionsStatus',
            'hasClassSchedule',
            'hasAnySchedule',
            'sessionsWithContentCount',
            'sessions',
            'documents'
        ));
    }

    public function update(Request $request, CourseModality $modality, CalendarService $calendar)
    {
        $this->owned($modality);

        $data = $request->validate([
            'nrc' => 'nullable|string|max:30',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'schedules_present' => 'nullable|in:1',
            'schedules' => 'nullable|array',
            'schedules.*.days' => 'nullable|array',
            'schedules.*.days.*' => 'integer|min:1|max:7',
            'schedules.*.day_of_week' => 'nullable|integer|min:1|max:7',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.classroom' => 'nullable|string|max:100',
            'schedules.*.instruction_type' => 'nullable|in:THEORY,LAB',
            // Compatibilidad temporal con el formulario anterior.
            'days' => 'nullable|array',
            'days.*' => 'integer|min:1|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'classroom' => 'nullable|string|max:100',
        ]);

        $normalizedSchedules = [];
        if ($request->has('schedules_present')) {
            foreach (($data['schedules'] ?? []) as $index => $schedule) {
                // El tipo por defecto no convierte una fila vacía en un bloque incompleto.
                $hasValues = !empty($schedule['days'] ?? [])
                    || filled($schedule['day_of_week'] ?? null)
                    || filled($schedule['start_time'] ?? null)
                    || filled($schedule['end_time'] ?? null)
                    || filled($schedule['classroom'] ?? null);
                if (! $hasValues) {
                    continue;
                }

                $selectedDays = array_values(array_unique(array_map('intval', $schedule['days'] ?? [])));
                if (!$selectedDays && !empty($schedule['day_of_week'])) {
                    $selectedDays = [(int) $schedule['day_of_week']];
                }

                if (!$selectedDays || empty($schedule['start_time']) || empty($schedule['end_time'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "schedules.{$index}.days" => 'Selecciona al menos un día e indica la hora de inicio y fin del bloque.',
                    ]);
                }

                if ($schedule['end_time'] <= $schedule['start_time']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "schedules.{$index}.end_time" => 'La hora de fin debe ser posterior a la hora de inicio.',
                    ]);
                }

                $startMinute = (int) substr($schedule['start_time'], 3, 2);
                $endMinute = (int) substr($schedule['end_time'], 3, 2);
                if ($startMinute % 15 !== 0 || $endMinute % 15 !== 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "schedules.{$index}.start_time" => 'Usa intervalos de 15 minutos (por ejemplo, 09:00, 09:15 o 09:30).',
                    ]);
                }

                foreach ($selectedDays as $day) {
                    $normalizedSchedules[] = [
                        'day_of_week' => $day,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'classroom' => $schedule['classroom'] ?? null,
                        'instruction_type' => $schedule['instruction_type'] ?? 'THEORY',
                    ];
                }
            }
        } elseif (! empty($data['days']) && isset($data['start_time'], $data['end_time']) && $data['end_time'] > $data['start_time']) {
            foreach (array_unique($data['days']) as $day) {
                $normalizedSchedules[] = [
                    'day_of_week' => (int) $day,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'classroom' => $data['classroom'] ?? null,
                    'instruction_type' => 'THEORY',
                ];
            }
        }

        $dayNames = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        foreach (collect($normalizedSchedules)->groupBy('day_of_week') as $day => $blocks) {
            $ordered = $blocks->sortBy('start_time')->values();
            for ($index = 1; $index < $ordered->count(); $index++) {
                if ($ordered[$index]['start_time'] < $ordered[$index - 1]['end_time']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'schedules' => 'Hay bloques que se cruzan el mismo día. Revisa el horario del '.$dayNames[$day].'.',
                    ]);
                }
            }
        }

        $weeklyMinutes = collect($normalizedSchedules)->sum(
            fn ($schedule) => max(0, (strtotime($schedule['end_time']) - strtotime($schedule['start_time'])) / 60)
        );
        $weeklyHours = round($weeklyMinutes / 60, 2);

        \Illuminate\Support\Facades\DB::transaction(function () use ($modality, $data, $normalizedSchedules, $weeklyHours) {
            $modality->update([
                'nrc' => $data['nrc'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'total_hours' => $weeklyHours,
                'curriculum_start_week' => 1,
            ]);

            // Los registros nuevos son siempre clases presenciales: teoría o laboratorio.
            // Los antiguos bloques ASYNC desaparecen al guardar esta configuración.
            $modality->schedules()->delete();
            foreach ($normalizedSchedules as $schedule) {
                $modality->schedules()->create($schedule + ['block_type' => 'CLASS']);
            }
        });

        $calendarMessage = 'Datos guardados. Carga semanal calculada: '.number_format($weeklyHours, 2).' horas.';
        if ($modality->start_date && $modality->end_date && $normalizedSchedules) {
            $result = $calendar->rebuild($modality);
            $calendarMessage .= " Calendario recalculado: {$result['scheduled']} bloques de clase; {$result['holidays']} fechas feriadas excluidas. Los Excel anteriores quedaron marcados para regeneración.";
        } else {
            $calendarMessage .= ' Completa el rango de fechas y agrega al menos un bloque para generar las sesiones.';
        }

        return redirect()->route('modalities.show', $modality)->with('success', $calendarMessage);
    }

    public function rebuild(CourseModality $modality, CalendarService $calendar)
    {
        $this->owned($modality);
        $result = $calendar->rebuild($modality);

        $message = "Calendario actualizado: {$result['scheduled']} sesiones presenciales, {$result['holidays']} feriados; {$result['class_hours_in_range']} horas presenciales en el rango.";
        if ($result['weekly_hours_difference'] !== null && abs($result['weekly_hours_difference']) > 0.01) {
            $message .= " La carga semanal calculada es {$result['weekly_hours_from_schedule']} h y la declarada es {$result['declared_weekly_hours']} h; revisa la diferencia.";
        }

        return redirect()->route('modalities.show', $modality)->with('success', $message);
    }

    private function owned(CourseModality $modality): void
    {
        abort_unless(
            $modality->assignment()
                ->whereHas('group', fn ($query) => $query->where('professor_id', auth()->user()->professor->id))
                ->exists(),
            404
        );
    }
}
