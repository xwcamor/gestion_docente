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
     * Builds exactly one scheduled talk for each eligible group work date.
     * The official catalogue is cyclic: after its last topic, it starts again
     * from the first topic. Completed talks keep their original date and topic.
     *
     * Pending records without a usable date stay in storage for possible reuse,
     * but they are not part of the scheduled list shown to the user.
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

        if (!$topics) {
            throw new RuntimeException('El catálogo institucional no contiene temas disponibles.');
        }

        $rangeConfigured = $this->hasWorkloadWindow($group);
        $eligibleDates = $rangeConfigured ? $this->assignedDates($group) : [];
        $eligibleDateLookup = array_fill_keys($eligibleDates, true);
        $participantCount = (int) $group->students()->count();
        $existing = $group->fiveMinuteTalks()->orderBy('position')->get();

        $completedByDate = [];
        $pendingByDate = [];
        $reusablePending = collect();

        foreach ($existing as $talk) {
            $date = $talk->scheduled_date?->toDateString();

            if ($talk->status === 'done') {
                if ($date !== null) {
                    $completedByDate[$date] = $talk;
                }

                continue;
            }

            if ($date !== null && isset($eligibleDateLookup[$date])) {
                $pendingByDate[$date] = $talk;
            } else {
                $reusablePending->push($talk);
            }
        }

        $completedEligibleCount = count(array_intersect(
            array_keys($completedByDate),
            $eligibleDates
        ));
        $pendingDateCount = max(0, count($eligibleDates) - $completedEligibleCount);

        DB::transaction(function () use (
            $group,
            $existing,
            $reusablePending,
            $pendingByDate,
            $completedByDate,
            $eligibleDates,
            $participantCount,
            $topics
        ) {
            $now = now();

            // Use the current roster only as an initial value. Once a talk has
            // its own participant count, preserve it as an editable execution record.
            DB::table('group_five_minute_talks')
                ->where('teaching_group_id', $group->id)
                ->whereNull('participant_count')
                ->update([
                    'participant_count' => $participantCount,
                    'updated_at' => $now,
                ]);

            // Pending dates removed from the workload range become reusable,
            // rather than remaining visible as stale or undated agenda entries.
            $obsoleteIds = $reusablePending
                ->whereNotNull('scheduled_date')
                ->pluck('id')
                ->all();

            if ($obsoleteIds) {
                DB::table('group_five_minute_talks')
                    ->whereIn('id', $obsoleteIds)
                    ->update([
                        'scheduled_date' => null,
                        'updated_at' => $now,
                    ]);
            }

            $maxPosition = (int) $existing->max('position');
            $newRows = [];

            foreach ($eligibleDates as $dateIndex => $date) {
                // A completed talk owns its date and is never overwritten.
                if (isset($completedByDate[$date])) {
                    continue;
                }

                $topic = $topics[$dateIndex % count($topics)];
                $talk = $pendingByDate[$date] ?? $reusablePending->shift();

                if ($talk) {
                    $changes = [];

                    if ($talk->topic !== $topic) {
                        $changes['topic'] = $topic;
                    }

                    if ($talk->scheduled_date?->toDateString() !== $date) {
                        $changes['scheduled_date'] = $date;
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
                    'status' => 'pending',
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

        $rangeStart = $group->workload_start_date && $group->workload_end_date
            ? $group->workload_start_date
            : $group->cycle->start_date;
        $rangeEnd = $group->workload_start_date && $group->workload_end_date
            ? $group->workload_end_date
            : $group->cycle->end_date;

        return [
            'range_configured' => $rangeConfigured,
            'range_start' => $rangeStart?->toDateString(),
            'range_end' => $rangeEnd?->toDateString(),
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
            'available_dates_count' => $pendingDateCount,
            'scheduled_pending_count' => $pendingDateCount,
            'unassigned_count' => 0,
            'catalogue_topic_count' => count($topics),
            'catalogue_cycles' => count($topics) > 0
                ? (int) ceil(count($eligibleDates) / count($topics))
                : 0,
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