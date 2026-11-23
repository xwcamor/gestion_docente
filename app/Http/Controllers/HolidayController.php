<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\CourseModality;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $professorId = auth()->user()->professor->id;
        $base = Holiday::where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professorId));

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

        return view('holidays.index', compact('holidays', 'years'));
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

        $data['professor_id'] = auth()->user()->professor->id;
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
