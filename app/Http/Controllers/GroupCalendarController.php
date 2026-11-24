<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\CourseModality;
use App\Models\Holiday;
use App\Models\TeachingGroup;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GroupCalendarController extends Controller
{
    public function index(Request $request, TeachingGroup $group)
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'modality' => ['nullable', 'in:TEC,TALL'],
        ]);

        $month = $filters['month'] ?? now()->format('Y-m');
        $modalityFilter = $filters['modality'] ?? null;
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $modalities = CourseModality::query()
            ->with(['assignment.course', 'schedules'])
            ->withCount(['sessions as scheduled_sessions_count' => fn ($query) => $query->where('status', 'scheduled')])
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id))
            ->when($modalityFilter, fn ($query) => $query->where('modality', $modalityFilter))
            ->orderBy('modality')
            ->orderBy('id')
            ->get();

        $sessions = ClassSession::query()
            ->with(['modality.assignment.course', 'schedule'])
            ->where('status', 'scheduled')
            ->whereBetween('session_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('modality', function ($query) use ($group, $modalityFilter) {
                $query->whereHas('assignment', fn ($assignment) => $assignment->where('teaching_group_id', $group->id));

                if ($modalityFilter) {
                    $query->where('modality', $modalityFilter);
                }
            })
            ->get()
            ->sortBy(fn ($session) => $session->session_date->toDateString().'|'.substr((string) ($session->schedule?->start_time ?? '99:99'), 0, 5))
            ->values();

        $holidays = Holiday::query()
            ->where('blocks_classes', true)
            ->whereBetween('holiday_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $group->professor_id))
            ->orderBy('holiday_date')
            ->get();

        $sessionsByDate = $sessions->groupBy(fn ($session) => $session->session_date->toDateString());
        $holidaysByDate = $holidays->groupBy(fn ($holiday) => $holiday->holiday_date->toDateString());

        $conflicts = [];
        $conflictingSessionIds = [];

        foreach ($sessionsByDate as $date => $daySessions) {
            $withSchedules = $daySessions->filter(fn ($session) => $session->schedule)->values();

            for ($leftIndex = 0; $leftIndex < $withSchedules->count(); $leftIndex++) {
                $left = $withSchedules[$leftIndex];
                $leftStart = substr((string) $left->schedule->start_time, 0, 5);
                $leftEnd = substr((string) $left->schedule->end_time, 0, 5);

                for ($rightIndex = $leftIndex + 1; $rightIndex < $withSchedules->count(); $rightIndex++) {
                    $right = $withSchedules[$rightIndex];
                    $rightStart = substr((string) $right->schedule->start_time, 0, 5);
                    $rightEnd = substr((string) $right->schedule->end_time, 0, 5);

                    if ($leftStart < $rightEnd && $rightStart < $leftEnd) {
                        $conflicts[] = ['date' => $date, 'left' => $left, 'right' => $right];
                        $conflictingSessionIds[] = (int) $left->id;
                        $conflictingSessionIds[] = (int) $right->id;
                    }
                }
            }
        }

        $conflictingSessionIds = array_values(array_unique($conflictingSessionIds));

        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $calendarDays = [];

        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $calendarDays[] = $day->copy();
        }

        $configuredModalities = $modalities->filter(fn ($modality) =>
            $modality->start_date
            && $modality->end_date
            && $modality->schedules->contains(fn ($schedule) => $schedule->block_type === 'CLASS')
        );

        $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $monthLabel = ucfirst($monthStart->copy()->locale('es')->translatedFormat('F Y'));
        $previousMonth = $monthStart->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonthNoOverflow()->format('Y-m');

        $group->load('cycle');

        return view('groups.calendar', compact(
            'group',
            'month',
            'monthLabel',
            'monthStart',
            'previousMonth',
            'nextMonth',
            'modalityFilter',
            'modalities',
            'configuredModalities',
            'sessions',
            'sessionsByDate',
            'holidaysByDate',
            'conflicts',
            'conflictingSessionIds',
            'calendarDays',
            'weekdays'
        ));
    }

    public function rebuild(Request $request, TeachingGroup $group, CalendarService $calendar)
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'modality' => ['nullable', 'in:TEC,TALL'],
        ]);

        $modalities = CourseModality::query()
            ->with(['assignment.course', 'schedules'])
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id))
            ->when($filters['modality'] ?? null, fn ($query, $value) => $query->where('modality', $value))
            ->orderBy('modality')
            ->orderBy('id')
            ->get();

        $rebuiltCount = 0;
        $sessionCount = 0;
        $pending = [];

        foreach ($modalities as $modality) {
            $hasClassSchedule = $modality->schedules->contains(
                fn ($schedule) => $schedule->block_type === 'CLASS'
            );

            if (
                !$modality->start_date
                || !$modality->end_date
                || $modality->end_date->lt($modality->start_date)
                || !$hasClassSchedule
            ) {
                $pending[] = $modality->assignment->course->name.' ('.$modality->modality.')';
                continue;
            }

            $result = $calendar->rebuild($modality);
            $rebuiltCount++;
            $sessionCount += $result['scheduled'];
        }

        $month = $filters['month'] ?? now()->format('Y-m');
        $modalityFilter = $filters['modality'] ?? null;
        $destination = route('groups.calendar', [
            'group' => $group,
            'month' => $month,
            'modality' => $modalityFilter,
        ]);

        if ($rebuiltCount === 0) {
            return redirect($destination)->with(
                'warning',
                'No hay modalidades listas para calcular. Completa fechas y horarios en cada curso.'
            );
        }

        $message = "Se recalcularon {$rebuiltCount} modalidades y se programaron {$sessionCount} sesiones.";

        if ($pending) {
            $message .= ' Pendientes: '.implode(', ', $pending).'.';
        }

        $message .= ' Los Excel generados anteriormente quedaron marcados para revisión.';

        return redirect($destination)->with('success', $message);
    }
}
