<?php

namespace App\Services;

use App\Models\FiveMinuteTalkTopic;
use App\Models\Holiday;
use App\Models\TeachingGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiveMinuteTalkService
{
    /**
     * Builds the group talk plan from the workload date range and assigned
     * weekdays, without requiring each course modality to have class blocks.
     *
     * Pending talks are spread as evenly as possible over the available dates.
     * Completed talks keep their historical dates and are never moved.
     */
    public function ensureScheduled(TeachingGroup $group): array
    {
        $group->loadMissing('cycle');
        $catalogue = $this->catalogue();
        $this->ensureProfessorCatalogue((int) $group->professor_id, $catalogue);

        $topics = FiveMinuteTalkTopic::query()
            ->where('professor_id', $group->professor_id)
            ->orderBy('position')
            ->pluck('title')
            ->all();

        $existing = $group->fiveMinuteTalks()->orderBy('position')->get();

        if ($existing->isEmpty()) {
            $now = now();
            $rows = [];

            foreach ($topics as $index => $topic) {
                $rows[] = [
                    'teaching_group_id' => $group->id,
                    'topic' => $topic,
                    'scheduled_date' => null,
                    'status' => 'pending',
                    'participant_count' => null,
                    'observations' => null,
                    'position' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 400) as $chunk) {
                DB::table('group_five_minute_talks')->insert($chunk);
            }

            $existing = $group->fiveMinuteTalks()->orderBy('position')->get();
        } else {
            // Add new official topics without overwriting historical group records.
            $known = $existing->pluck('topic')
                ->map(fn ($title) => mb_strtolower(trim((string) $title)))
                ->all();
            $maxPosition = (int) $existing->max('position');
            $now = now();
            $newRows = [];

            foreach ($topics as $topic) {
                $normalized = mb_strtolower(trim($topic));
                if (in_array($normalized, $known, true)) {
                    continue;
                }

                $maxPosition++;
                $newRows[] = [
                    'teaching_group_id' => $group->id,
                    'topic' => $topic,
                    'scheduled_date' => null,
                    'status' => 'pending',
                    'participant_count' => null,
                    'observations' => null,
                    'position' => $maxPosition,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $known[] = $normalized;
            }

            foreach (array_chunk($newRows, 400) as $chunk) {
                if ($chunk) {
                    DB::table('group_five_minute_talks')->insert($chunk);
                }
            }

            if ($newRows) {
                $existing = $group->fiveMinuteTalks()->orderBy('position')->get();
            }
        }

        $rangeConfigured = $this->hasWorkloadWindow($group);
        $eligibleDates = $rangeConfigured ? $this->assignedDates($group) : [];

        $completedDates = $existing
            ->where('status', 'done')
            ->pluck('scheduled_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        // A completed talk reserves its date; pending talks use the remaining slots.
        $availableDates = array_values(array_diff($eligibleDates, $completedDates));
        $pending = $existing->where('status', '!=', 'done')->values();
        $dateAssignments = $this->distributeDates($pending, $availableDates);

        // Avoid writes on every page view when the workload and dates have not changed.
        $hasChanges = $pending->contains(
            fn ($talk) => $talk->scheduled_date?->toDateString() !== ($dateAssignments[$talk->id] ?? null)
        );

        if ($hasChanges) {
            DB::transaction(function () use ($pending, $dateAssignments) {
                // Temporarily clear pending dates so the unique (group, date) index
                // cannot reject dates being exchanged between talks.
                foreach ($pending as $talk) {
                    if ($talk->scheduled_date !== null) {
                        $talk->update(['scheduled_date' => null]);
                    }
                }

                foreach ($pending as $talk) {
                    $nextDate = $dateAssignments[$talk->id] ?? null;

                    if ($nextDate !== null) {
                        $talk->update(['scheduled_date' => $nextDate]);
                    }
                }
            });
        }

        return [
            'range_configured' => $rangeConfigured,
            'range_start' => ($group->workload_start_date && $group->workload_end_date
                ? $group->workload_start_date
                : $group->cycle->start_date)?->toDateString(),
            'range_end' => ($group->workload_start_date && $group->workload_end_date
                ? $group->workload_end_date
                : $group->cycle->end_date)?->toDateString(),
            'uses_cycle_range' => $group->workload_start_date === null || $group->workload_end_date === null,
            'uses_default_weekdays' => empty($group->workload_days),
            'weekdays' => empty($group->workload_days)
                ? [1, 2, 3, 4, 5]
                : collect($group->workload_days)->map(fn ($day) => (int) $day)->unique()->values()->all(),
            'weekday_labels' => collect(empty($group->workload_days) ? [1, 2, 3, 4, 5] : $group->workload_days)
                ->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? null)
                ->filter()
                ->values()
                ->all(),
            'eligible_dates_count' => count($eligibleDates),
            'available_dates_count' => count($availableDates),
            'scheduled_pending_count' => count(array_filter($dateAssignments)),
            'unassigned_count' => $pending->count() - count(array_filter($dateAssignments)),
        ];
    }

    private function hasWorkloadWindow(TeachingGroup $group): bool
    {
        $hasGroupRange = $group->workload_start_date !== null
            && $group->workload_end_date !== null;
        $start = $hasGroupRange ? $group->workload_start_date : $group->cycle->start_date;
        $end = $hasGroupRange ? $group->workload_end_date : $group->cycle->end_date;

        return $start !== null && $end !== null && !$end->lt($start);
    }

    /**
     * Evenly distributes ordered pending topics across available work days.
     * If there are fewer days than talks, one talk is placed on each available
     * date and the remaining topics stay pending without a date.
     *
     * @return array<int, string|null> Talk ID mapped to its assigned date.
     */
    private function distributeDates($pending, array $availableDates): array
    {
        $pending = $pending->values();
        $talkCount = $pending->count();
        $dateCount = count($availableDates);
        $assignments = [];

        if ($talkCount === 0 || $dateCount === 0) {
            return $assignments;
        }

        $assignableCount = min($talkCount, $dateCount);

        for ($index = 0; $index < $assignableCount; $index++) {
            if ($talkCount > $dateCount) {
                $dateIndex = $index;
            } elseif ($talkCount === 1) {
                $dateIndex = intdiv($dateCount - 1, 2);
            } else {
                $dateIndex = (int) round(
                    $index * ($dateCount - 1) / ($talkCount - 1)
                );
            }

            $assignments[(int) $pending[$index]->id] = $availableDates[$dateIndex];
        }

        for ($index = $assignableCount; $index < $talkCount; $index++) {
            $assignments[(int) $pending[$index]->id] = null;
        }

        return $assignments;
    }

    /**
     * Talk dates are derived from the group's supervisor workload window,
     * not from the schedule state of an individual course modality.
     * Each selected weekday inside the workload range is a candidate.
     * Cycle boundaries and blocking holidays are always respected.
     */
    private function assignedDates(TeachingGroup $group): array
    {
        $cycle = $group->cycle;
        $usesGroupRange = $group->workload_start_date !== null && $group->workload_end_date !== null;
        $start = ($usesGroupRange ? $group->workload_start_date : $cycle->start_date)?->copy()->startOfDay();
        $end = ($usesGroupRange ? $group->workload_end_date : $cycle->end_date)?->copy()->startOfDay();

        if (!$start || !$end) {
            return [];
        }

        if ($cycle->start_date && $start->lt($cycle->start_date)) {
            $start = $cycle->start_date->copy()->startOfDay();
        }

        if ($cycle->end_date && $end->gt($cycle->end_date)) {
            $end = $cycle->end_date->copy()->startOfDay();
        }

        if ($start->gt($end)) {
            return [];
        }

        // Prefer supervisor-selected weekdays. If none were specified, use
        // Monday to Friday as the neutral automatic default, not modality blocks.
        $workloadDays = empty($group->workload_days)
            ? [1, 2, 3, 4, 5]
            : collect($group->workload_days)
                ->map(fn ($day) => (int) $day)
                ->filter(fn ($day) => $day >= 1 && $day <= 7)
                ->unique()
                ->values()
                ->all();

        if (!$workloadDays) {
            return [];
        }

        $dateKeys = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeekIso, $workloadDays, true)) {
                $dateKeys[] = $date->toDateString();
            }
        }

        if (!$dateKeys) {
            return [];
        }

        $holidays = Holiday::query()
            ->where('blocks_classes', true)
            ->whereBetween('holiday_date', [$dateKeys[0], $dateKeys[count($dateKeys) - 1]])
            ->where(fn ($query) => $query
                ->whereNull('professor_id')
                ->orWhere('professor_id', $group->professor_id))
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return array_values(array_diff($dateKeys, $holidays));
    }



    /**
     * Keeps the professor's topic catalogue synchronized with the official
     * institutional source. Group execution records are stored separately, so
     * refreshing this source catalogue does not alter historical group talks.
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
     * Reads and validates the official five-minute-talk topics catalog.
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