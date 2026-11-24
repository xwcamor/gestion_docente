<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\Holiday;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourseModalityController extends Controller
{
    public function show(CourseModality $modality)
    {
        $this->owned($modality);
        $modality->load(['assignment.course', 'assignment.group.cycle', 'schedules']);

        $import = $modality->importBatches()->latest()->first();
        $enrollmentsCount = $modality->enrollments()->count();
        $sessionCount = $modality->sessions()->count();
        $scheduledSessionCount = $modality->sessions()->where('status', 'scheduled')->count();
        $documentCount = $modality->documents()->count();

        $hasNrc = filled($modality->nrc);
        $hasCourseDates = $modality->start_date !== null && $modality->end_date !== null;
        $hasAnySchedule = $modality->schedules->isNotEmpty();
        $hasClassSchedule = $modality->schedules->contains(
            fn ($schedule) => $schedule->block_type === 'CLASS'
        );
        // Los bloques asincrónicos no necesitan generar sesiones de clase.
        $hasGeneratedCalendar = $hasClassSchedule
            ? $scheduledSessionCount > 0
            : $hasAnySchedule;
        $configurationSteps = collect([
            $hasNrc,
            $hasCourseDates,
            $hasAnySchedule,
            $hasGeneratedCalendar,
        ])->filter()->count();
        $configurationPercent = (int) round(($configurationSteps / 4) * 100);

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
        $sessionContentPercent = $scheduledSessionCount > 0
            ? (int) round(($sessionsWithContentCount / $scheduledSessionCount) * 100)
            : 0;

        $sessions = $modality->sessions()
            ->orderBy('session_date')
            ->orderBy('session_number')
            ->paginate(25, ['*'], 'sessions_page')
            ->withQueryString();

        $documents = $modality->documents()
            ->latest()
            ->paginate(10, ['*'], 'documents_page')
            ->withQueryString();

        $holidayDates = Holiday::query()
            ->where('blocks_classes', true)
            ->where(function ($query) use ($modality) {
                $query->whereNull('professor_id')
                    ->orWhere('professor_id', $modality->assignment->group->professor_id);
            })
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        return view('modalities.show', compact(
            'modality',
            'import',
            'holidayDates',
            'enrollmentsCount',
            'sessionCount',
            'scheduledSessionCount',
            'documentCount',
            'configurationSteps',
            'configurationPercent',
            'hasClassSchedule',
            'hasAnySchedule',
            'sessionsWithContentCount',
            'sessionContentPercent',
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
            'total_hours' => 'nullable|numeric|min:0',
            'days' => 'nullable|array',
            'days.*' => 'integer|min:1|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'classroom' => 'nullable|string|max:100',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'nullable|integer|min:1|max:7',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.classroom' => 'nullable|string|max:100',
            'schedules.*.block_type' => 'nullable|in:CLASS,ASYNC',
        ]);

        $modality->update([
            'nrc' => $data['nrc'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
            'curriculum_start_week' => 1,
        ]);

        $modality->schedules()->delete();

        foreach (($data['schedules'] ?? []) as $schedule) {
            if (empty($schedule['day_of_week']) || empty($schedule['start_time']) || empty($schedule['end_time']) || $schedule['end_time'] <= $schedule['start_time']) {
                continue;
            }

            $modality->schedules()->create([
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'classroom' => $schedule['classroom'] ?? null,
                'block_type' => $schedule['block_type'] ?? 'CLASS',
            ]);
        }

        if (empty($data['schedules']) && !empty($data['days']) && isset($data['start_time'], $data['end_time']) && $data['end_time'] > $data['start_time']) {
            foreach (array_unique($data['days']) as $day) {
                $modality->schedules()->create([
                    'day_of_week' => $day,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'classroom' => $data['classroom'] ?? null,
                    'block_type' => 'CLASS',
                ]);
            }
        }

        $calendarMessage = 'Los datos quedaron guardados.';
        if ($modality->start_date && $modality->end_date && $modality->schedules()->where('block_type', 'CLASS')->exists()) {
            $result = $calendar->rebuild($modality);
            $calendarMessage .= " Calendario recalculado automáticamente: {$result['scheduled']} sesiones de clase y {$result['holidays']} fechas bloqueadas por feriado. Los Excel anteriores quedaron marcados para regeneración.";
        } else {
            $calendarMessage .= ' Completa el rango de fechas y al menos un horario de clase para generar las sesiones.';
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
