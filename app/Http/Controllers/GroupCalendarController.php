<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\CourseModality;
use App\Models\Holiday;
use App\Models\TeachingGroup;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $monthStart = Carbon::createFromFormat('!Y-m-d', $month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Reutiliza los índices existentes de asignaciones y modalidades.
        $assignmentIds = DB::table('course_assignments')
            ->select('id')
            ->where('teaching_group_id', $group->id);

        $modalities = CourseModality::query()
            ->select([
                'id',
                'course_assignment_id',
                'modality',
                'nrc',
                'start_date',
                'end_date',
            ])
            ->with([
                'assignment:id,course_id',
                'assignment.course:id,code,name',
                'schedules:id,course_modality_id,block_type,day_of_week,start_time,end_time,classroom,instruction_type',
            ])
            ->withExists([
                'sessions as has_generated_sessions' => fn ($query) => $query->where('status', 'scheduled'),
            ])
            ->whereIn('course_assignment_id', $assignmentIds)
            ->when($modalityFilter, fn ($query) => $query->where('modality', $modalityFilter))
            ->orderBy('modality')
            ->orderBy('id')
            ->get();

        $modalityIds = $modalities->modelKeys();

        // La lista de IDs permite aprovechar sessions_modality_date_status_idx
        // sin subconsultas anidadas por cada sesión.
        $sessions = empty($modalityIds)
            ? collect()
            : ClassSession::query()
                ->select([
                    'class_sessions.id',
                    'class_sessions.course_modality_id',
                    'class_sessions.modality_schedule_id',
                    'class_sessions.session_date',
                ])
                ->leftJoin(
                    'modality_schedules as calendar_schedule',
                    'calendar_schedule.id',
                    '=',
                    'class_sessions.modality_schedule_id'
                )
                ->where('class_sessions.status', 'scheduled')
                ->whereBetween('class_sessions.session_date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ])
                ->whereIn('class_sessions.course_modality_id', $modalityIds)
                ->with([
                    'modality:id,course_assignment_id,modality',
                    'modality.assignment:id,course_id',
                    'modality.assignment.course:id,code,name',
                    'schedule:id,start_time,end_time,classroom,instruction_type',
                ])
                ->orderBy('class_sessions.session_date')
                ->orderBy('calendar_schedule.start_time')
                ->orderBy('class_sessions.id')
                ->get();

        $holidays = Holiday::query()
            ->select(['id', 'holiday_date', 'name'])
            ->where('blocks_classes', true)
            ->whereBetween('holiday_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->where(fn ($query) => $query
                ->whereNull('professor_id')
                ->orWhere('professor_id', $group->professor_id))
            ->orderBy('holiday_date')
            ->get();

        $sessionsByDate = $sessions->groupBy(
            fn ($session) => $session->session_date->toDateString()
        );
        $holidaysByDate = $holidays->groupBy(
            fn ($holiday) => $holiday->holiday_date->toDateString()
        );

        // Las sesiones ya llegan ordenadas por hora. Se detiene la búsqueda
        // cuando el siguiente bloque empieza después del fin del bloque actual.
        $conflicts = [];
        $conflictingSessionIds = [];

        foreach ($sessionsByDate as $date => $daySessions) {
            $withSchedules = $daySessions->filter(fn ($session) => $session->schedule)->values();
            $count = $withSchedules->count();

            for ($leftIndex = 0; $leftIndex < $count; $leftIndex++) {
                $left = $withSchedules[$leftIndex];
                $leftStart = substr((string) $left->schedule->start_time, 0, 5);
                $leftEnd = substr((string) $left->schedule->end_time, 0, 5);

                for ($rightIndex = $leftIndex + 1; $rightIndex < $count; $rightIndex++) {
                    $right = $withSchedules[$rightIndex];
                    $rightStart = substr((string) $right->schedule->start_time, 0, 5);

                    if ($rightStart >= $leftEnd) {
                        break;
                    }

                    $rightEnd = substr((string) $right->schedule->end_time, 0, 5);

                    if ($leftStart < $rightEnd) {
                        $conflicts[] = [
                            'date' => $date,
                            'left' => $left,
                            'right' => $right,
                        ];
                        $conflictingSessionIds[(int) $left->id] = true;
                        $conflictingSessionIds[(int) $right->id] = true;
                    }
                }
            }
        }

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

        $group->loadMissing('cycle');

        return view('groups.calendar', compact(
            'group',
            'month',
            'monthLabel',
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
