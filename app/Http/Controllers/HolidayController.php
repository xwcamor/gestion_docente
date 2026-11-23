<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\CourseModality;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $professorId = auth()->user()->professor->id;
        $presentation = $request->input('presentation') === 'calendar' ? 'calendar' : 'list';
        $base = Holiday::where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professorId));

        $calendarMonth = max(1, min(12, $request->integer('calendar_month', now()->month)));
        $calendarYear = max(2000, min(2100, $request->integer('calendar_year', now()->year)));
        $monthStart = Carbon::create($calendarYear, $calendarMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $calendarHolidays = (clone $base)
            ->whereBetween('holiday_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('holiday_date')
            ->get()
            ->groupBy(fn (Holiday $holiday) => $holiday->holiday_date->toDateString());

        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $calendarDays = [];

        foreach (CarbonPeriod::create($calendarStart, $calendarEnd) as $day) {
            $key = $day->toDateString();
            $calendarDays[] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $calendarMonth,
                'holidays' => $calendarHolidays->get($key, collect()),
            ];
        }

        $previousMonth = $monthStart->copy()->subMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $previousMonthUrl = route('holidays.index', array_merge($request->except('page', 'calendar_month', 'calendar_year'), [
            'calendar_month' => $previousMonth->month,
            'calendar_year' => $previousMonth->year,
        ]));
        $nextMonthUrl = route('holidays.index', array_merge($request->except('page', 'calendar_month', 'calendar_year'), [
            'calendar_month' => $nextMonth->month,
            'calendar_year' => $nextMonth->year,
        ]));

        $query = (clone $base)->orderBy('holiday_date');
        if ($request->filled('year')) {
            $query->whereYear('holiday_date', $request->integer('year'));
        }
        if ($request->filled('scope')) {
            $query->where('scope', $request->input('scope'));
        }
        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where('name', 'like', "%{$term}%");
        }

        $holidays = $query->paginate(40)->withQueryString();
        $years = $base->selectRaw('YEAR(holiday_date) as year')->distinct()->orderByDesc('year')->pluck('year');

        return view('holidays.index', compact(
            'holidays',
            'years',
            'presentation',
            'calendarMonth',
            'calendarYear',
            'monthStart',
            'calendarDays',
            'previousMonthUrl',
            'nextMonthUrl'
        ));
    }

    public function store(Request $request, CalendarService $calendar)
    {
        $data = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:255',
            'scope' => 'required|in:NATIONAL,REGIONAL,INSTITUTIONAL,SECTOR',
            'blocks_classes' => 'required|boolean',
            'source_url' => 'nullable|url|max:500',
        ]);

        $professorId = auth()->user()->professor->id;
        $normalizedName = mb_strtolower(trim($data['name']));
        $duplicate = Holiday::whereDate('holiday_date', $data['holiday_date'])
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professorId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un feriado o cierre con ese nombre en esta fecha.',
            ]);
        }

        $data['name'] = trim($data['name']);
        $data['professor_id'] = $professorId;
        $data['verified'] = false;
        $holiday = Holiday::create($data);

        $day = Carbon::parse($holiday->holiday_date)->dayOfWeekIso;
        $modalities = CourseModality::whereHas('assignment.group', fn ($query) => $query->where('professor_id', $data['professor_id']))
            ->whereDate('start_date', '<=', $holiday->holiday_date)
            ->whereDate('end_date', '>=', $holiday->holiday_date)
            ->whereHas('schedules', fn ($query) => $query->where('day_of_week', $day)->where('block_type', 'CLASS'))
            ->with('assignment.group')
            ->get();

        $recalculated = 0;
        if ($holiday->blocks_classes) {
            foreach ($modalities as $modality) {
                $calendar->rebuild($modality);
                $recalculated++;
            }
        }

        $message = 'Fecha registrada correctamente.';
        if ($recalculated) {
            $message .= " Se actualizaron {$recalculated} modalidades afectadas. Los documentos anteriores quedaron marcados para regeneración.";
        }

        return back()->with('success', $message);
    }
}
