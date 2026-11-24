<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\FiveMinuteTalkTopic;
use App\Models\Holiday;
use App\Models\TeachingGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiveMinuteTalkService
{
    /**
     * Builds the group talk plan from the supervisor workload window and the
     * actual class days in the group's modalities.
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
            'range_start' => $group->workload_start_date?->toDateString(),
            'range_end' => $group->workload_end_date?->toDateString(),
            'eligible_dates_count' => count($eligibleDates),
            'available_dates_count' => count($availableDates),
            'scheduled_pending_count' => count(array_filter($dateAssignments)),
            'unassigned_count' => $pending->count() - count(array_filter($dateAssignments)),
        ];
    }

    private function hasWorkloadWindow(TeachingGroup $group): bool
    {
        if (
            !$group->workload_start_date
            || !$group->workload_end_date
            || $group->workload_end_date->lt($group->workload_start_date)
            || empty($group->workload_days)
            || !filled($group->workload_start_time)
            || !filled($group->workload_end_time)
        ) {
            return false;
        }

        return substr((string) $group->workload_start_time, 0, 5)
            < substr((string) $group->workload_end_time, 0, 5);
    }

    /**
     * Evenly distributes ordered pending topics across the available dates.
     * If there are fewer class dates than topics, it schedules one topic per
     * date and leaves the remaining topics pending without a date.
     *
     * @return array<int, string|null> Talk ID mapped to its assigned date.
     */
    private function distributeDates($pending, array $availableDates): array
    {
        $pending = $pending->values();
        $talkCount = $pending->count();
        $dateCount = count($availableDates);
        $assignments = [];

        if ($talkCount === 0) {
            return $assignments;
        }

        $assignableCount = min($talkCount, $dateCount);

        for ($index = 0; $index < $assignableCount; $index++) {
            if ($talkCount > $dateCount) {
                // Not enough dates: preserve topic order and use every possible day.
                $dateIndex = $index;
            } elseif ($talkCount === 1) {
                // A single talk belongs near the middle of the workload period.
                $dateIndex = intdiv($dateCount - 1, 2);
            } else {
                // Spread all talks from the beginning to the end of the period.
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

        return $topics;
    }

    /**
     * Unique class dates for this group only. A date is usable when at least
     * one of the group's modalities has a CLASS block that weekday.
     */
    /**
     * Candidate dates must satisfy all four conditions:
     * - fall within the group's workload start/end dates and the academic cycle;
     * - be among the weekdays assigned by the supervisor;
     * - have an actual CLASS block in at least one modality during its own dates;
     * - overlap the supervisor's availability time and are not blocked holidays.
     */
    private function assignedDates(TeachingGroup $group): array
    {
        $cycle = $group->cycle;
        $workloadStart = Carbon::parse($group->workload_start_date)->startOfDay();
        $workloadEnd = Carbon::parse($group->workload_end_date)->startOfDay();

        if ($cycle->start_date && $workloadStart->lt($cycle->start_date)) {
            $workloadStart = $cycle->start_date->copy()->startOfDay();
        }

        if ($cycle->end_date && $workloadEnd->gt($cycle->end_date)) {
            $workloadEnd = $cycle->end_date->copy()->startOfDay();
        }

        if ($workloadStart->gt($workloadEnd)) {
            return [];
        }

        $workloadDays = collect($group->workload_days)
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->values()
            ->all();

        $availabilityStart = substr((string) $group->workload_start_time, 0, 5);
        $availabilityEnd = substr((string) $group->workload_end_time, 0, 5);

        $modalities = CourseModality::query()
            ->with(['schedules' => fn ($query) => $query->where('block_type', 'CLASS')])
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id))
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $workloadEnd->toDateString())
            ->where('end_date', '>=', $workloadStart->toDateString())
            ->get();

        $dates = [];

        foreach ($modalities as $modality) {
            if ($modality->start_date->gt($workloadStart)) {
                $modalityStart = $modality->start_date->copy()->startOfDay();
            } else {
                $modalityStart = $workloadStart->copy();
            }

            if ($modality->end_date->lt($workloadEnd)) {
                $modalityEnd = $modality->end_date->copy()->startOfDay();
            } else {
                $modalityEnd = $workloadEnd->copy();
            }

            if ($modalityStart->gt($modalityEnd)) {
                continue;
            }

            foreach ($modality->schedules as $schedule) {
                $weekday = (int) $schedule->day_of_week;
                if (!in_array($weekday, $workloadDays, true)) {
                    continue;
                }

                $classStart = substr((string) $schedule->start_time, 0, 5);
                $classEnd = substr((string) $schedule->end_time, 0, 5);

                // A talk lasts five minutes, so require at least five minutes
                // where the class block and supervisor availability overlap.
                $classStartMinutes = ((int) substr($classStart, 0, 2) * 60) + (int) substr($classStart, 3, 2);
                $classEndMinutes = ((int) substr($classEnd, 0, 2) * 60) + (int) substr($classEnd, 3, 2);
                $availabilityStartMinutes = ((int) substr($availabilityStart, 0, 2) * 60) + (int) substr($availabilityStart, 3, 2);
                $availabilityEndMinutes = ((int) substr($availabilityEnd, 0, 2) * 60) + (int) substr($availabilityEnd, 3, 2);

                $overlapMinutes = min($classEndMinutes, $availabilityEndMinutes)
                    - max($classStartMinutes, $availabilityStartMinutes);

                if ($overlapMinutes < 5) {
                    continue;
                }

                for ($date = $modalityStart->copy(); $date->lte($modalityEnd); $date->addDay()) {
                    if ($date->dayOfWeekIso === $weekday) {
                        $dates[$date->toDateString()] = true;
                    }
                }
            }
        }

        $dateKeys = array_keys($dates);
        sort($dateKeys);

        if (!$dateKeys) {
            return [];
        }

        $holidays = Holiday::query()
            ->where('blocks_classes', true)
            ->whereIn('holiday_date', $dateKeys)
            ->where(fn ($query) => $query
                ->whereNull('professor_id')
                ->orWhere('professor_id', $group->professor_id))
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return array_values(array_diff($dateKeys, $holidays));
    }
}