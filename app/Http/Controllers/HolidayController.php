<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\Holiday;
use App\Services\AuditLogger;
use App\Services\CalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HolidayController extends Controller
{
    private const SCOPES = ['NATIONAL', 'REGIONAL', 'INSTITUTIONAL', 'SECTOR'];

    public function index(Request $request)
    {
        $professorId = (int) $request->user()->professor->id;
        $presentation = $request->input('presentation') === 'calendar' ? 'calendar' : 'list';
        $base = Holiday::query()
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professorId));

        $calendarMonth = max(1, min(12, $request->integer('calendar_month', now()->month)));
        $calendarYear = max(2000, min(2100, $request->integer('calendar_year', now()->year)));
        $monthStart = Carbon::create($calendarYear, $calendarMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $calendarHolidays = (clone $base)
            ->whereBetween('holiday_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('holiday_date')
            ->orderBy('name')
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
                'isToday' => $day->isToday(),
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

        $query = (clone $base)->orderBy('holiday_date')->orderBy('name');
        if ($request->filled('year')) {
            $query->whereYear('holiday_date', $request->integer('year'));
        }
        if ($request->filled('scope') && in_array($request->input('scope'), self::SCOPES, true)) {
            $query->where('scope', $request->input('scope'));
        }
        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('source_note', 'like', "%{$term}%");
            });
        }
        if ($request->input('impact') === 'blocking') {
            $query->where('blocks_classes', true);
        } elseif ($request->input('impact') === 'informative') {
            $query->where('blocks_classes', false);
        }

        $holidays = $query->paginate(20)->withQueryString();
        $years = (clone $base)->selectRaw('YEAR(holiday_date) as year')->distinct()->orderByDesc('year')->pluck('year');

        $personalCount = (clone $base)->where('professor_id', $professorId)->count();
        $generalCount = (clone $base)->whereNull('professor_id')->count();
        $blockingCount = (clone $base)->where('blocks_classes', true)->count();
        $upcomingCount = (clone $base)->whereDate('holiday_date', '>=', now()->toDateString())->count();

        return view('holidays.index', compact(
            'holidays',
            'years',
            'presentation',
            'calendarMonth',
            'calendarYear',
            'monthStart',
            'calendarDays',
            'previousMonthUrl',
            'nextMonthUrl',
            'personalCount',
            'generalCount',
            'blockingCount',
            'upcomingCount'
        ));
    }

    public function store(Request $request, CalendarService $calendar, AuditLogger $audit)
    {
        $isAdmin = $request->user()->isAdmin();
        $data = $this->validatedData($request, $isAdmin);
        $isGlobal = $isAdmin && ($data['calendar_scope'] ?? 'PERSONAL') === 'GLOBAL';
        $professorId = $isGlobal ? null : (int) $request->user()->professor->id;

        $this->ensureNoDuplicate($data['holiday_date'], $data['name'], $professorId);

        $holiday = DB::transaction(function () use ($data, $professorId, $isGlobal, $isAdmin, $calendar) {
            $holiday = Holiday::create([
                'professor_id' => $professorId,
                'holiday_date' => $data['holiday_date'],
                'name' => trim($data['name']),
                'scope' => $data['scope'],
                'blocks_classes' => (bool) $data['blocks_classes'],
                'source_url' => $data['source_url'] ?? null,
                'source_note' => $data['source_note'] ?? null,
                'verified' => $isGlobal && $isAdmin && !empty($data['verified']),
            ]);

            $recalculated = $holiday->blocks_classes
                ? $this->rebuildAffectedModalities($calendar, [$holiday->holiday_date->toDateString()], $professorId)
                : 0;

            return [$holiday, $recalculated];
        });

        [$holiday, $recalculated] = $holiday;
        $audit->record('feriados', 'created', 'Se registró '.$holiday->name.' para el '. $holiday->holiday_date->format('d/m/Y'), $holiday, null, [
            'holiday_date' => $holiday->holiday_date->toDateString(),
            'name' => $holiday->name,
            'scope' => $holiday->scope,
            'blocks_classes' => (bool) $holiday->blocks_classes,
            'general' => $holiday->professor_id === null,
        ]);

        $message = 'Fecha registrada correctamente.';
        if ($recalculated > 0) {
            $message .= " Se actualizaron {$recalculated} calendarios de modalidad afectados.";
        }

        return redirect()->route('holidays.index', $this->returnFilters($request))
            ->with('success', $message);
    }

    public function update(Request $request, Holiday $holiday, CalendarService $calendar, AuditLogger $audit)
    {
        $this->authorizeHoliday($request, $holiday);
        $isAdmin = $request->user()->isAdmin();
        $data = $this->validatedData($request, $isAdmin, $holiday);
        $this->ensureNoDuplicate(
            $data['holiday_date'],
            $data['name'],
            $holiday->professor_id,
            $holiday->id
        );

        $before = [
            'holiday_date' => $holiday->holiday_date->toDateString(),
            'name' => $holiday->name,
            'scope' => $holiday->scope,
            'blocks_classes' => (bool) $holiday->blocks_classes,
            'source_url' => $holiday->source_url,
            'source_note' => $holiday->source_note,
            'verified' => (bool) $holiday->verified,
        ];

        [$recalculated, $holiday] = DB::transaction(function () use ($holiday, $data, $isAdmin, $calendar) {
            $oldDate = $holiday->holiday_date->toDateString();
            $wasBlocking = (bool) $holiday->blocks_classes;

            $holiday->update([
                'holiday_date' => $data['holiday_date'],
                'name' => trim($data['name']),
                'scope' => $data['scope'],
                'blocks_classes' => (bool) $data['blocks_classes'],
                'source_url' => $data['source_url'] ?? null,
                'source_note' => $data['source_note'] ?? null,
                'verified' => $holiday->professor_id === null && $isAdmin
                    ? (bool) ($data['verified'] ?? false)
                    : false,
            ]);

            $recalculated = ($wasBlocking || $holiday->blocks_classes)
                ? $this->rebuildAffectedModalities(
                    $calendar,
                    [$oldDate, $holiday->holiday_date->toDateString()],
                    $holiday->professor_id
                )
                : 0;

            return [$recalculated, $holiday->fresh()];
        });

        $audit->record('feriados', 'updated', 'Se actualizó '.$holiday->name, $holiday, $before, [
            'holiday_date' => $holiday->holiday_date->toDateString(),
            'name' => $holiday->name,
            'scope' => $holiday->scope,
            'blocks_classes' => (bool) $holiday->blocks_classes,
            'source_url' => $holiday->source_url,
            'source_note' => $holiday->source_note,
            'verified' => (bool) $holiday->verified,
        ]);

        $message = 'Fecha actualizada correctamente.';
        if ($recalculated > 0) {
            $message .= " Se actualizaron {$recalculated} calendarios de modalidad y sus documentos quedaron pendientes de regeneración.";
        }

        return redirect()->route('holidays.index', $this->returnFilters($request))
            ->with('success', $message);
    }

    public function destroy(Request $request, Holiday $holiday, CalendarService $calendar, AuditLogger $audit)
    {
        $this->authorizeHoliday($request, $holiday);
        $before = [
            'holiday_date' => $holiday->holiday_date->toDateString(),
            'name' => $holiday->name,
            'scope' => $holiday->scope,
            'blocks_classes' => (bool) $holiday->blocks_classes,
            'general' => $holiday->professor_id === null,
        ];
        $date = $holiday->holiday_date->toDateString();
        $professorId = $holiday->professor_id;
        $name = $holiday->name;
        $wasBlocking = (bool) $holiday->blocks_classes;

        $recalculated = DB::transaction(function () use ($holiday, $calendar, $date, $professorId, $wasBlocking) {
            $holiday->delete();

            return $wasBlocking
                ? $this->rebuildAffectedModalities($calendar, [$date], $professorId)
                : 0;
        });

        $audit->record('feriados', 'deleted', 'Se eliminó '.$name, $holiday, $before, null);

        $message = 'Fecha eliminada correctamente.';
        if ($recalculated > 0) {
            $message .= " Se restauraron {$recalculated} calendarios de modalidad. Revisa los documentos marcados para regeneración.";
        }

        return redirect()->route('holidays.index', $this->returnFilters($request))
            ->with('success', $message);
    }

    private function validatedData(Request $request, bool $isAdmin, ?Holiday $holiday = null): array
    {
        if (!$isAdmin) {
            $request->merge(['calendar_scope' => 'PERSONAL', 'verified' => false]);
        }

        return $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(self::SCOPES)],
            'blocks_classes' => ['required', 'boolean'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'source_note' => ['nullable', 'string', 'max:2000'],
            'calendar_scope' => ['sometimes', Rule::in(['PERSONAL', 'GLOBAL'])],
            'verified' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Escribe el nombre de la fecha.',
            'scope.in' => 'Selecciona un ámbito válido.',
            'source_url.url' => 'La fuente debe ser una URL válida.',
        ]);
    }

    private function ensureNoDuplicate(string $date, string $name, ?int $professorId, ?int $ignoreId = null): void
    {
        $duplicate = Holiday::query()
            ->whereDate('holiday_date', $date)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where(function ($query) use ($professorId) {
                $professorId === null
                    ? $query->whereNull('professor_id')
                    : $query->where('professor_id', $professorId);
            })
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe una fecha con ese nombre en ese calendario.',
            ]);
        }
    }

    private function authorizeHoliday(Request $request, Holiday $holiday): void
    {
        $isOwner = $holiday->professor_id !== null
            && (int) $holiday->professor_id === (int) $request->user()->professor->id;
        $canManageGeneral = $holiday->professor_id === null && $request->user()->isAdmin();

        abort_unless($isOwner || $canManageGeneral, 404);
    }

    /**
     * Recalcula modalidades que tienen una clase programada en alguna de las fechas.
     * Con professorId null se trata de un feriado general y afecta a todos.
     */
    private function rebuildAffectedModalities(CalendarService $calendar, array $dates, ?int $professorId): int
    {
        $dates = collect($dates)->filter()->unique()->values();
        if ($dates->isEmpty()) {
            return 0;
        }

        $query = CourseModality::query()
            ->where('active', true)
            ->whereHas('assignment.group', function ($groups) use ($professorId) {
                if ($professorId !== null) {
                    $groups->where('professor_id', $professorId);
                }
            })
            ->where(function ($builder) use ($dates) {
                foreach ($dates as $date) {
                    $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
                    $builder->orWhere(function ($dateQuery) use ($date, $dayOfWeek) {
                        $dateQuery->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date)
                            ->whereHas('schedules', fn ($schedules) => $schedules
                                ->where('block_type', 'CLASS')
                                ->where('day_of_week', $dayOfWeek));
                    });
                }
            });

        $modalities = $query->get();
        foreach ($modalities as $modality) {
            $calendar->rebuild($modality);
        }

        return $modalities->count();
    }

    private function returnFilters(Request $request): array
    {
        return array_filter([
            'presentation' => $request->input('presentation', 'list'),
            'calendar_month' => $request->integer('calendar_month') ?: null,
            'calendar_year' => $request->integer('calendar_year') ?: null,
            'q' => $request->input('q'),
            'year' => $request->input('year'),
            'scope' => $request->input('scope'),
            'impact' => $request->input('impact'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
