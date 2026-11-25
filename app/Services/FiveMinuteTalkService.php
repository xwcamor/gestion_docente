<?php

namespace App\Services;

use App\Models\FiveMinuteTalkTopic;
use App\Models\Holiday;
use App\Models\TeachingGroup;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiveMinuteTalkService
{
    /**
     * Las charlas son una actividad del grupo, pero su calendario nace de
     * los horarios reales de todos sus cursos. Solo se generan fechas hasta
     * hoy y dentro del periodo de cada modalidad. Una fecha compartida por
     * varios cursos representa una sola charla para el grupo.
     *
     * Las fechas históricas se consideran dictadas automáticamente. Si el
     * docente modifica expresamente el estado, status_source=MANUAL protege
     * esa decisión frente a los siguientes recálculos.
     */
    public function ensureScheduled(TeachingGroup $group): array
    {
        $group->loadMissing(['cycle', 'professor']);

        $catalogue = $this->catalogue();
        $this->ensureProfessorCatalogue((int) $group->professor_id, $catalogue);

        $topics = FiveMinuteTalkTopic::query()
            ->where('professor_id', $group->professor_id)
            ->orderBy('position')
            ->pluck('title')
            ->all();

        if (!$topics) {
            throw new RuntimeException('El catálogo institucional no contiene temas disponibles.');
        }

        $modalities = $group->assignments()
            ->with(['modalities' => fn ($query) => $query->where('active', true)->with(['schedules' => fn ($schedules) => $schedules->where('block_type', 'CLASS')])])
            ->get()
            ->flatMap(fn ($assignment) => $assignment->modalities)
            ->values();

        $schedule = $this->assignedDates($group, $modalities);
        $eligibleDates = $schedule['dates'];
        $eligibleDateLookup = array_fill_keys($eligibleDates, true);
        $participantCount = (int) $group->students()->count();

        $existing = $group->fiveMinuteTalks()
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        $byDate = $existing
            ->filter(fn ($talk) => $talk->scheduled_date !== null)
            ->keyBy(fn ($talk) => $talk->scheduled_date->toDateString());

        DB::transaction(function () use (
            $group,
            $existing,
            $byDate,
            $eligibleDates,
            $eligibleDateLookup,
            $participantCount,
            $topics
        ) {
            $now = now();
            $maxPosition = (int) $existing->max('position');
            $newRows = [];

            // Las charlas automáticas antiguas que ya no coinciden con los
            // horarios vigentes se dejan sin fecha. Las ejecuciones manuales
            // se conservan como historial.
            $obsoleteAutoIds = $existing
                ->filter(fn ($talk) =>
                    $talk->scheduled_date !== null
                    && !isset($eligibleDateLookup[$talk->scheduled_date->toDateString()])
                    && ($talk->status_source ?? 'AUTO') !== 'MANUAL'
                )
                ->pluck('id')
                ->all();

            if ($obsoleteAutoIds) {
                DB::table('group_five_minute_talks')
                    ->whereIn('id', $obsoleteAutoIds)
                    ->update([
                        'scheduled_date' => null,
                        'updated_at' => $now,
                    ]);
            }

            foreach ($eligibleDates as $dateIndex => $date) {
                $topic = $topics[$dateIndex % count($topics)];
                $talk = $byDate->get($date);

                if ($talk) {
                    $changes = [];

                    if ((int) $talk->participant_count !== $participantCount) {
                        $changes['participant_count'] = $participantCount;
                    }

                    // Solo los registros automáticos reciben estado y tema
                    // derivados del calendario. Una edición manual se respeta.
                    if (($talk->status_source ?? 'AUTO') !== 'MANUAL') {
                        if ($talk->status !== 'done') {
                            $changes['status'] = 'done';
                        }
                        if (($talk->status_source ?? 'AUTO') !== 'AUTO') {
                            $changes['status_source'] = 'AUTO';
                        }
                        if ($talk->topic !== $topic) {
                            $changes['topic'] = $topic;
                        }
                    }

                    if ($changes) {
                        $changes['updated_at'] = $now;
                        DB::table('group_five_minute_talks')
                            ->where('id', $talk->id)
                            ->update($changes);
                    }

                    continue;
                }

                $maxPosition++;
                $newRows[] = [
                    'teaching_group_id' => $group->id,
                    'topic' => $topic,
                    'scheduled_date' => $date,
                    'status' => 'done',
                    'status_source' => 'AUTO',
                    'participant_count' => $participantCount,
                    'observations' => null,
                    'position' => $maxPosition,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($newRows, 400) as $chunk) {
                DB::table('group_five_minute_talks')->insert($chunk);
            }
        });

        $talks = $group->fiveMinuteTalks()
            ->whereIn('scheduled_date', $eligibleDates ?: ['1900-01-01'])
            ->get();

        $pendingCount = $talks->where('status', 'pending')->count();
        $days = collect($eligibleDates)
            ->map(fn ($date) => Carbon::parse($date)->dayOfWeekIso)
            ->unique()
            ->sort()
            ->values();

        return [
            'range_configured' => $schedule['configured'],
            'range_start' => $schedule['range_start'],
            'range_end' => $schedule['range_end'],
            'uses_cycle_range' => false,
            'uses_default_weekdays' => false,
            'weekdays' => $days->all(),
            'weekday_labels' => $days
                ->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day])
                ->all(),
            'eligible_dates_count' => count($eligibleDates),
            'available_dates_count' => $pendingCount,
            'scheduled_pending_count' => $pendingCount,
            'completed_count' => $talks->where('status', 'done')->count(),
            'unassigned_count' => 0,
            'catalogue_topic_count' => count($topics),
            'catalogue_cycles' => count($eligibleDates) > 0
                ? (int) ceil(count($eligibleDates) / count($topics))
                : 0,
            'dates' => $eligibleDates,
        ];
    }

    /**
     * Construye la unión de fechas reales de clase de todas las modalidades
     * del grupo. Cada curso conserva su propia fecha de inicio, término,
     * recurrencia semanal y feriados. Las fechas futuras nunca se exportan.
     *
     * @return array{configured: bool, range_start: ?string, range_end: ?string, dates: array<int, string>}
     */
    private function assignedDates(TeachingGroup $group, $modalities): array
    {
        $today = Carbon::today();
        $dateKeys = [];
        $starts = [];
        $ends = [];
        $configured = false;

        foreach ($modalities as $modality) {
            if (
                !$modality->start_date
                || !$modality->end_date
                || $modality->end_date->lt($modality->start_date)
            ) {
                continue;
            }

            $schedules = $modality->schedules
                ->where('block_type', 'CLASS')
                ->values();

            if ($schedules->isEmpty()) {
                continue;
            }

            $configured = true;
            $start = $modality->start_date->copy()->startOfDay();
            $end = $modality->end_date->copy()->startOfDay();

            if ($group->cycle?->start_date && $start->lt($group->cycle->start_date)) {
                $start = $group->cycle->start_date->copy()->startOfDay();
            }

            if ($group->cycle?->end_date && $end->gt($group->cycle->end_date)) {
                $end = $group->cycle->end_date->copy()->startOfDay();
            }

            if ($end->gt($today)) {
                $end = $today->copy();
            }

            if ($start->gt($end)) {
                continue;
            }

            $starts[] = $start->toDateString();
            $ends[] = $end->toDateString();

            foreach ($schedules as $schedule) {
                $weekday = (int) $schedule->day_of_week;
                if ($weekday < 1 || $weekday > 7) {
                    continue;
                }

                $firstOccurrence = $modality->start_date->copy()->startOfDay();
                while ((int) $firstOccurrence->dayOfWeekIso !== $weekday) {
                    $firstOccurrence->addDay();
                }

                $repeatEveryWeeks = max(1, (int) ($schedule->repeat_interval_weeks ?? 1));

                foreach (CarbonPeriod::create($start, $end) as $day) {
                    if ((int) $day->dayOfWeekIso !== $weekday) {
                        continue;
                    }

                    $weeksSinceFirstOccurrence = intdiv(
                        $firstOccurrence->diffInDays($day),
                        7
                    );

                    if ($weeksSinceFirstOccurrence % $repeatEveryWeeks !== 0) {
                        continue;
                    }

                    $dateKeys[$day->toDateString()] = true;
                }
            }
        }

        $dates = array_keys($dateKeys);
        sort($dates);

        if ($dates) {
            $holidays = Holiday::query()
                ->where('blocks_classes', true)
                ->whereBetween('holiday_date', [$dates[0], $dates[count($dates) - 1]])
                ->where(fn ($query) => $query
                    ->whereNull('professor_id')
                    ->orWhere('professor_id', $group->professor_id))
                ->pluck('holiday_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->all();

            $dates = array_values(array_diff($dates, $holidays));
        }

        return [
            'configured' => $configured,
            'range_start' => $starts ? min($starts) : null,
            'range_end' => $ends ? max($ends) : null,
            'dates' => $dates,
        ];
    }

    /**
     * Keeps the professor's institutional topic catalogue synchronized.
     * Group execution history is stored separately and is not overwritten.
     */
    private function ensureProfessorCatalogue(int $professorId, array $catalogue): void
    {
        $existing = FiveMinuteTalkTopic::query()
            ->where('professor_id', $professorId)
            ->orderBy('position')
            ->pluck('title')
            ->all();

        if ($existing === $catalogue) {
            return;
        }

        DB::transaction(function () use ($professorId, $catalogue) {
            FiveMinuteTalkTopic::where('professor_id', $professorId)->delete();
            $now = now();
            $rows = [];

            foreach ($catalogue as $index => $title) {
                $rows[] = [
                    'professor_id' => $professorId,
                    'title' => $title,
                    'position' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 400) as $chunk) {
                DB::table('five_minute_talk_topics')->insert($chunk);
            }
        });
    }

    /**
     * Reads and validates the institutional five-minute-talk topic catalogue.
     *
     * @return array<int, string>
     */
    private function catalogue(): array
    {
        $path = base_path('system_files/charla_5_minutos_catalogo.json');

        if (!is_file($path)) {
            throw new RuntimeException('No se encuentra el catálogo institucional de charlas de 5 minutos.');
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (!is_array($data) || empty($data['areas']) || !is_array($data['areas'])) {
            throw new RuntimeException('El catálogo institucional de charlas tiene un formato inválido.');
        }

        $topics = [];

        foreach ($data['areas'] as $area) {
            foreach (($area['topics'] ?? []) as $title) {
                $title = trim((string) $title);

                if ($title !== '' && mb_strlen($title) <= 255) {
                    $topics[] = $title;
                }
            }
        }

        if (!$topics) {
            throw new RuntimeException('El catálogo institucional no contiene temas disponibles.');
        }

        return array_values(array_unique($topics));
    }
}
