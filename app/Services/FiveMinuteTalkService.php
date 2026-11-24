<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\FiveMinuteTalkTopic;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiveMinuteTalkService
{
    public function ensureScheduled(Cycle $cycle): void
    {
        $catalogue = $this->catalogue();
        $this->ensureProfessorCatalogue((int) $cycle->professor_id, $catalogue);

        $topics = FiveMinuteTalkTopic::query()
            ->where('professor_id', $cycle->professor_id)
            ->orderBy('position')
            ->pluck('title')
            ->all();

        $existing = $cycle->fiveMinuteTalks()->orderBy('position')->get();

        if ($existing->isEmpty()) {
            $dates = $this->assignedDates($cycle);
            $rows = [];
            $now = now();

            foreach ($topics as $index => $topic) {
                $rows[] = [
                    'cycle_id' => $cycle->id,
                    'topic' => $topic,
                    'scheduled_date' => $dates[$index] ?? null,
                    'status' => 'pending',
                    'participant_count' => null,
                    'observations' => null,
                    'position' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 400) as $chunk) {
                DB::table('cycle_five_minute_talks')->insert($chunk);
            }
            return;
        }

        $completedDates = $existing
            ->where('status', 'done')
            ->pluck('scheduled_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $availableDates = array_values(array_diff($this->assignedDates($cycle), $completedDates));
        $pending = $existing->where('status', '!=', 'done')->values();

        DB::transaction(function () use ($pending, $availableDates) {
            foreach ($pending as $talk) {
                $talk->update(['scheduled_date' => null]);
            }
            foreach ($pending as $index => $talk) {
                $talk->update(['scheduled_date' => $availableDates[$index] ?? null]);
            }
        });
    }

    public function availableDays(Cycle $cycle): array
    {
        $days = [];
        foreach ($this->assignedDates($cycle) as $date) {
            $days[Carbon::parse($date)->dayOfWeekIso] = true;
        }
        $result = array_keys($days);
        sort($result);
        return $result;
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

    private function assignedDates(Cycle $cycle): array
    {
        $modalities = CourseModality::query()
            ->with(['schedules' => fn ($query) => $query->where('block_type', 'CLASS')])
            ->whereHas('assignment.group', fn ($query) => $query->where('cycle_id', $cycle->id))
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        $dates = [];
        foreach ($modalities as $modality) {
            $start = $this->boundedDate($modality->start_date, $cycle->start_date, true);
            $end = $this->boundedDate($modality->end_date, $cycle->end_date, false);
            if (!$start || !$end || $start->gt($end)) {
                continue;
            }

            $weekdays = $modality->schedules->pluck('day_of_week')->map(fn ($day) => (int) $day)->unique()->all();
            if (!$weekdays) {
                continue;
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (in_array($date->dayOfWeekIso, $weekdays, true)) {
                    $dates[$date->toDateString()] = true;
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
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $cycle->professor_id))
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return array_values(array_diff($dateKeys, $holidays));
    }

    private function boundedDate($courseDate, $cycleDate, bool $isStart): ?Carbon
    {
        if (!$courseDate || !$cycleDate) {
            return null;
        }

        $course = Carbon::parse($courseDate)->startOfDay();
        $cycle = Carbon::parse($cycleDate)->startOfDay();

        return $isStart
            ? ($course->greaterThan($cycle) ? $course : $cycle)
            : ($course->lessThan($cycle) ? $course : $cycle);
    }
}
