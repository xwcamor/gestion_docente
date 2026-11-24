<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\FiveMinuteTalkTopic;
use App\Models\Holiday;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FiveMinuteTalkService
{
    /**
     * Updates the professor's reusable topic catalogue and distributes a
     * snapshot of those topics over the real assigned teaching dates of a cycle.
     */
    public function updateAndDistribute(
        Cycle $cycle,
        array $days,
        ?string $topicsText = null,
        ?UploadedFile $file = null
    ): array {
        $days = collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (!$days) {
            throw ValidationException::withMessages([
                'days' => 'Selecciona al menos un día para programar las charlas.',
            ]);
        }

        $incomingTopics = $file
            ? $this->topicsFromSpreadsheet($file)
            : $this->topicsFromText($topicsText ?? '');

        DB::transaction(function () use ($cycle, $days, $incomingTopics) {
            if ($incomingTopics) {
                FiveMinuteTalkTopic::where('professor_id', $cycle->professor_id)->delete();

                foreach ($incomingTopics as $position => $title) {
                    FiveMinuteTalkTopic::create([
                        'professor_id' => $cycle->professor_id,
                        'title' => $title,
                        'position' => $position + 1,
                    ]);
                }
            }

            $topics = FiveMinuteTalkTopic::query()
                ->where('professor_id', $cycle->professor_id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('title')
                ->all();

            if (!$topics) {
                throw ValidationException::withMessages([
                    'topics' => 'Pega los temas del documento fijo o adjunta el Excel que contiene la lista.',
                ]);
            }

            $cycle->update(['five_minute_talk_days' => $days]);

            $dates = $this->assignedDates($cycle, $days);
            $cycle->fiveMinuteTalks()->delete();

            $rows = [];
            $timestamp = now();
            foreach ($topics as $index => $topic) {
                $rows[] = [
                    'cycle_id' => $cycle->id,
                    'topic' => $topic,
                    'scheduled_date' => $dates[$index] ?? null,
                    'position' => $index + 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('cycle_five_minute_talks')->insert($chunk);
            }
        });

        $assigned = $cycle->fiveMinuteTalks()->whereNotNull('scheduled_date')->count();
        $pending = $cycle->fiveMinuteTalks()->whereNull('scheduled_date')->count();

        return [
            'topics' => $assigned + $pending,
            'assigned' => $assigned,
            'pending' => $pending,
            'available_dates' => count($this->assignedDates($cycle->fresh(), $days)),
        ];
    }

    /**
     * Weekdays with at least one real course schedule inside its own teaching dates.
     */
    public function availableDays(Cycle $cycle): array
    {
        $dates = $this->assignedDates($cycle, [1, 2, 3, 4, 5, 6, 7]);
        $days = [];

        foreach ($dates as $date) {
            $day = Carbon::parse($date)->dayOfWeekIso;
            $days[$day] = true;
        }

        $available = array_keys($days);
        sort($available);

        return $available;
    }

    private function assignedDates(Cycle $cycle, array $selectedDays): array
    {
        $modalities = CourseModality::query()
            ->with('schedules')
            ->whereHas('assignment.group', fn ($query) => $query->where('cycle_id', $cycle->id))
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        $dates = [];
        foreach ($modalities as $modality) {
            $start = $this->cycleBoundedDate($modality->start_date, $cycle->start_date, true);
            $end = $this->cycleBoundedDate($modality->end_date, $cycle->end_date, false);

            if (!$start || !$end || $start->gt($end)) {
                continue;
            }

            $scheduledDays = $modality->schedules
                ->pluck('day_of_week')
                ->map(fn ($day) => (int) $day)
                ->intersect($selectedDays)
                ->unique()
                ->all();

            if (!$scheduledDays) {
                continue;
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (in_array($date->dayOfWeekIso, $scheduledDays, true)) {
                    $dates[$date->toDateString()] = true;
                }
            }
        }

        $dateKeys = array_keys($dates);
        sort($dateKeys);

        if (!$dateKeys) {
            return [];
        }

        $holidayDates = Holiday::query()
            ->where('blocks_classes', true)
            ->whereIn('holiday_date', $dateKeys)
            ->where(function ($query) use ($cycle) {
                $query->whereNull('professor_id')
                    ->orWhere('professor_id', $cycle->professor_id);
            })
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return array_values(array_diff($dateKeys, $holidayDates));
    }

    private function cycleBoundedDate($courseDate, $cycleDate, bool $isStart): ?Carbon
    {
        if (!$courseDate || !$cycleDate) {
            return null;
        }

        $course = Carbon::parse($courseDate)->startOfDay();
        $cycle = Carbon::parse($cycleDate)->startOfDay();

        if ($isStart) {
            return $course->greaterThan($cycle) ? $course : $cycle;
        }

        return $course->lessThan($cycle) ? $course : $cycle;
    }

    private function topicsFromText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/u', $text) ?: [];

        return $this->cleanTopics($lines);
    }

    private function topicsFromSpreadsheet(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray('', true, true, false);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'topics_file' => 'No se pudo leer el Excel de temas. Guarda el archivo como .xlsx, .xls o .csv e inténtalo nuevamente.',
            ]);
        }

        if (!$rows) {
            throw ValidationException::withMessages([
                'topics_file' => 'El archivo seleccionado no contiene temas.',
            ]);
        }

        $topicColumn = null;
        $header = array_map(
            fn ($value) => mb_strtolower(trim((string) $value)),
            array_values($rows[0] ?? [])
        );

        foreach ($header as $index => $value) {
            if (
                in_array($value, ['tema', 'temas', 'charla', 'título', 'titulo', 'topic'], true)
                || str_contains($value, 'tema')
                || str_contains($value, 'charla')
            ) {
                $topicColumn = $index;
                break;
            }
        }

        // A common fixed template starts with an item number and the topic in column B.
        if ($topicColumn === null && count($header) > 1) {
            $firstHeader = trim($header[0] ?? '');
            if (preg_match('/^(#|n[.º°]?|número|numero)$/u', $firstHeader)) {
                $topicColumn = 1;
            }
        }

        $values = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = array_values($row);
            if ($rowIndex === 0 && $topicColumn !== null) {
                continue;
            }

            $value = $topicColumn !== null
                ? ($cells[$topicColumn] ?? '')
                : ($cells[0] ?? '');

            $value = trim((string) $value);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        $topics = $this->cleanTopics($values);
        if (!$topics) {
            throw ValidationException::withMessages([
                'topics_file' => 'No se encontraron temas en la primera columna del Excel ni en una columna llamada Tema.',
            ]);
        }

        return $topics;
    }

    private function cleanTopics(array $values): array
    {
        $topics = [];
        $seen = [];

        foreach ($values as $value) {
            $title = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
            if ($title === '') {
                continue;
            }

            if (mb_strtolower($title) === 'tema' || mb_strtolower($title) === 'temas') {
                continue;
            }

            if (mb_strlen($title) > 255) {
                throw ValidationException::withMessages([
                    'topics' => 'Cada tema debe tener como máximo 255 caracteres. Revisa: '.mb_substr($title, 0, 60),
                ]);
            }

            $key = mb_strtolower($title);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $topics[] = $title;
        }

        return $topics;
    }
}
