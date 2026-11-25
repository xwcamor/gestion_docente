<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Services\CalendarService;
use App\Services\CourseCodeParser;
use App\Services\CourseRegistrationService;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuickCourseEntryController extends Controller
{
    public function create(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load('cycle');

        $rows = [[]];
        $editingAssignment = null;

        return view('assignments.quick', compact('group', 'rows', 'editingAssignment'));
    }

    public function edit(TeachingGroup $group, CourseAssignment $assignment)
    {
        $this->authorizeGroup($group);
        abort_unless((int) $assignment->teaching_group_id === (int) $group->id, 404);

        $group->load('cycle');
        $assignment->load(['course', 'modalities.schedules']);

        $rows = [[
            'course_name' => $assignment->course->name,
            'tec_code' => $this->courseAlias($assignment->course->code, 'TEC', $assignment->course->name, $assignment->modalities->firstWhere('modality', 'TEC')),
            'tec_block_code' => $assignment->modalities->firstWhere('modality', 'TEC')?->block_code,
            'tec_start_date' => $assignment->modalities->firstWhere('modality', 'TEC')?->start_date?->toDateString(),
            'tec_end_date' => $assignment->modalities->firstWhere('modality', 'TEC')?->end_date?->toDateString(),
            'tec_schedules' => $this->groupScheduleRows($assignment->modalities->firstWhere('modality', 'TEC')),
            'tall_code' => $this->courseAlias($assignment->course->code, 'TALL', $assignment->course->name, $assignment->modalities->firstWhere('modality', 'TALL')),
            'tall_block_code' => $assignment->modalities->firstWhere('modality', 'TALL')?->block_code,
            'tall_start_date' => $assignment->modalities->firstWhere('modality', 'TALL')?->start_date?->toDateString(),
            'tall_end_date' => $assignment->modalities->firstWhere('modality', 'TALL')?->end_date?->toDateString(),
            'tall_schedules' => $this->groupScheduleRows($assignment->modalities->firstWhere('modality', 'TALL')),
        ]];

        $editingAssignment = $assignment;

        return view('assignments.quick', compact('group', 'rows', 'editingAssignment'));
    }

    public function store(
        Request $request,
        TeachingGroup $group,
        CourseCodeParser $parser,
        CourseRegistrationService $registration,
        StudentImportService $students,
        CalendarService $calendar
    ) {
        $this->authorizeGroup($group);

        $rows = $this->validateRows($request, $parser, 60);
        $created = 0;
        $createdModalities = 0;
        $scheduleBlocks = 0;
        $calendarModalityIds = [];
        $missingNrc = [];
        $calendarWarnings = [];

        DB::transaction(function () use (
            $rows,
            $group,
            $registration,
            $students,
            &$created,
            &$createdModalities,
            &$scheduleBlocks,
            &$calendarModalityIds,
            &$missingNrc
        ) {
            foreach ($rows as $index => $row) {
                $codes = array_values(array_filter([
                    trim((string) ($row['tec_code'] ?? '')),
                    trim((string) ($row['tall_code'] ?? '')),
                ], fn ($code) => $code !== ''));

                $component = match (count($codes)) {
                    2 => 'BOTH',
                    default => filled($row['tec_code'] ?? null) ? 'TEC' : 'TALL',
                };

                $modalityDates = [
                    'TEC' => [
                        'start_date' => $row['tec_start_date'] ?? null,
                        'end_date' => $row['tec_end_date'] ?? null,
                    ],
                    'TALL' => [
                        'start_date' => $row['tall_start_date'] ?? null,
                        'end_date' => $row['tall_end_date'] ?? null,
                    ],
                ];

                $modalitySchedules = [
                    'TEC' => $this->cleanScheduleBlocks($row['tec_schedules'] ?? []),
                    'TALL' => $this->cleanScheduleBlocks($row['tall_schedules'] ?? []),
                ];

                $blockCodes = [
                    'TEC' => trim((string) ($row['tec_block_code'] ?? '')) ?: null,
                    'TALL' => trim((string) ($row['tall_block_code'] ?? '')) ?: null,
                ];

                try {
                    $result = $registration->register(
                        $group,
                        $row['course_name'],
                        implode('; ', $codes),
                        $component,
                        $students,
                        $modalityDates,
                        $modalitySchedules,
                        $blockCodes
                    );
                } catch (ValidationException $exception) {
                    $errors = [];

                    foreach ($exception->errors() as $field => $messages) {
                        $target = $field === 'course_name' ? 'course_name' : 'row_error';
                        $errors["rows.{$index}.{$target}"] = $messages;
                    }

                    throw ValidationException::withMessages($errors);
                }

                $created++;
                $createdModalities += count($result['created_modalities']);
                $scheduleBlocks += (int) ($result['schedule_count'] ?? 0);
                foreach ($result['assignment']->modalities->whereIn('modality', $result['created_modalities']) as $createdModality) {
                    if (
                        $createdModality->start_date
                        && $createdModality->end_date
                        && $createdModality->schedules->where('block_type', 'CLASS')->isNotEmpty()
                    ) {
                        $calendarModalityIds[] = $createdModality->id;
                    }
                }

                foreach ($result['missing_nrc'] as $type) {
                    $missingNrc[] = $row['course_name'].' ('.$type.')';
                }
            }
        });

        foreach (array_unique($calendarModalityIds) as $modalityId) {
            try {
                $calendar->rebuild(CourseModality::findOrFail($modalityId));
            } catch (\Throwable $exception) {
                report($exception);
                $calendarWarnings[] = 'Uno de los horarios se guardó, pero no se pudo generar su calendario. Ábrelo y pulsa Recalcular calendario.';
            }
        }

        $message = "{$created} cursos registrados y {$createdModalities} modalidades creadas.";
        if ($scheduleBlocks > 0) {
            $message .= " Se configuraron {$scheduleBlocks} bloques semanales y se intentó generar el calendario.";
        } else {
            $message .= ' Puedes completar el horario de cada modalidad cuando lo necesites.';
        }
        if ($missingNrc) {
            $message .= ' NRC pendiente: '.implode(', ', array_unique($missingNrc)).'.';
        }

        $redirect = redirect()->route('groups.show', $group)->with($missingNrc ? 'warning' : 'success', $message);
        if ($calendarWarnings) {
            $redirect->with('warning', implode(' ', array_unique($calendarWarnings)));
        }

        return $redirect;
    }

    public function update(
        Request $request,
        TeachingGroup $group,
        CourseAssignment $assignment,
        CourseCodeParser $parser,
        StudentImportService $students,
        CalendarService $calendar
    ) {
        $this->authorizeGroup($group);
        abort_unless((int) $assignment->teaching_group_id === (int) $group->id, 404);

        $rows = $this->validateRows($request, $parser, 1);
        abort_unless($rows->count() === 1, 422, 'La edición debe contener un solo curso.');

        $row = $rows->first();
        $assignment->load(['course', 'modalities.schedules']);

        $parsedByType = [];
        foreach (['TEC' => 'tec', 'TALL' => 'tall'] as $type => $prefix) {
            $sourceCode = trim((string) ($row[$prefix.'_code'] ?? ''));
            if ($sourceCode === '') {
                continue;
            }

            $parsed = $parser->parse($sourceCode);
            if ($parsed['errors'] || $parsed['detected_modalities'] !== [$type]) {
                throw ValidationException::withMessages([
                    "rows.0.{$prefix}_code" => $parsed['errors'] ?: ['El alias debe corresponder a la modalidad '.$type.'.'],
                ]);
            }

            if (mb_strtolower($parsed['base_code']) !== mb_strtolower((string) $assignment->course->code)) {
                throw ValidationException::withMessages([
                    "rows.0.{$prefix}_code" => 'El código base identifica el curso en otros grupos y no se puede cambiar desde esta edición. Modifica solo el NRC y los datos de la modalidad.',
                ]);
            }

            $parsedByType[$type] = $parsed;
        }

        if (!$parsedByType) {
            throw ValidationException::withMessages([
                'rows.0.tec_code' => 'Conserva al menos un alias institucional para identificar el curso.',
            ]);
        }

        $calendarModalityIds = [];
        $updatedTypes = [];
        $createdTypes = [];
        $scheduleBlocksUpdated = 0;

        DB::transaction(function () use (
            $assignment,
            $row,
            $parsedByType,
            $students,
            &$calendarModalityIds,
            &$updatedTypes,
            &$createdTypes,
            &$scheduleBlocksUpdated
        ) {
            $assignment->course->update(['name' => trim($row['course_name'])]);

            foreach (['TEC' => 'tec', 'TALL' => 'tall'] as $type => $prefix) {
                if (!isset($parsedByType[$type])) {
                    continue;
                }

                $modality = $assignment->modalities()->firstOrNew(['modality' => $type]);
                $isNew = !$modality->exists;
                $oldNrc = $modality->nrc;

                $modality->fill([
                    'nrc' => $parsedByType[$type]['nrc_by_modality'][$type] ?? $oldNrc,
                    'block_code' => filled($row[$prefix.'_block_code'] ?? null)
                        ? trim($row[$prefix.'_block_code'])
                        : $modality->block_code,
                    'start_date' => $row[$prefix.'_start_date'] ?: null,
                    'end_date' => $row[$prefix.'_end_date'] ?: null,
                    'curriculum_start_week' => $modality->curriculum_start_week ?: 1,
                    'active' => true,
                ]);

                if ($isNew) {
                    $modality->period_label = $assignment->group->cycle->academic_year.'-'.$assignment->group->cycle->cycle_code.'-'.$type;
                }

                $modality->save();

                if ($isNew) {
                    $students->syncGroupRosterToModality($modality);
                    $createdTypes[] = $type;
                } else {
                    $updatedTypes[] = $type;
                }

                $blocks = $this->cleanScheduleBlocks($row[$prefix.'_schedules'] ?? []);
                $clearSchedules = (bool) ($row[$prefix.'_clear_schedules'] ?? false);

                if ($blocks || $clearSchedules) {
                    $modality->schedules()->delete();

                    $weeklyMinutes = 0;
                    foreach ($blocks as $block) {
                        $interval = max(1, (int) ($block['repeat_interval_weeks'] ?? 1));
                        $duration = max(0, (int) ((strtotime($block['end_time']) - strtotime($block['start_time'])) / 60));
                        $days = array_unique(array_map('intval', $block['days']));
                        $weeklyMinutes += ($duration * count($days)) / $interval;

                        foreach ($days as $day) {
                            $modality->schedules()->create([
                                'day_of_week' => $day,
                                'start_time' => $block['start_time'],
                                'end_time' => $block['end_time'],
                                'classroom' => $block['classroom'] ?: null,
                                'block_type' => 'CLASS',
                                'instruction_type' => $block['instruction_type'],
                                'repeat_interval_weeks' => $interval,
                            ]);
                            $scheduleBlocksUpdated++;
                        }
                    }

                    $modality->update(['total_hours' => round($weeklyMinutes, 2)]);
                }

                if (
                    $modality->start_date
                    && $modality->end_date
                    && $modality->schedules()->where('block_type', 'CLASS')->exists()
                ) {
                    $calendarModalityIds[] = $modality->id;
                }
            }
        });

        $calendarWarnings = [];
        $calendarUpdated = 0;
        foreach (array_unique($calendarModalityIds) as $modalityId) {
            try {
                $calendar->rebuild(CourseModality::findOrFail($modalityId));
                $calendarUpdated++;
            } catch (\Throwable $exception) {
                report($exception);
                $calendarWarnings[] = 'Los datos se guardaron, pero uno de los calendarios no pudo recalcularse. Revisa las fechas y bloques de esa modalidad.';
            }
        }

        $message = 'Curso actualizado.';
        if ($createdTypes) {
            $message .= ' Modalidades agregadas: '.implode(', ', $createdTypes).'.';
        }
        if ($updatedTypes) {
            $message .= ' Modalidades actualizadas: '.implode(', ', $updatedTypes).'.';
        }
        if ($scheduleBlocksUpdated > 0) {
            $message .= " Se guardaron {$scheduleBlocksUpdated} bloques semanales.";
        }
        if ($calendarUpdated > 0) {
            $message .= " Se recalcularon {$calendarUpdated} calendarios.";
        }

        $redirect = redirect()->route('groups.show', $group)->with('success', $message);
        if ($calendarWarnings) {
            $redirect->with('warning', implode(' ', array_unique($calendarWarnings)));
        }

        return $redirect;
    }

    private function validateRows(Request $request, CourseCodeParser $parser, int $maxRows)
    {
        $rows = (array) $request->input('rows', []);
        $aliasNames = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (['tec_code' => 'TEC', 'tall_code' => 'TALL'] as $field => $type) {
                $alias = $parser->splitAlias((string) ($row[$field] ?? ''));
                $row[$field] = $alias['source_code'];
                if ($alias['course_name'] !== '') {
                    $aliasNames[$index][] = $alias['course_name'];
                }
            }

            $explicitName = trim((string) ($row['course_name'] ?? ''));
            $detectedNames = array_values(array_unique($aliasNames[$index] ?? []));
            if ($explicitName === '' && count($detectedNames) === 1) {
                $row['course_name'] = $detectedNames[0];
            }
            $row['_alias_names'] = $detectedNames;
            $rows[$index] = $row;
        }

        $request->merge(['rows' => $rows]);

        $validator = Validator::make($request->all(), [
            'rows' => ['required', 'array', 'min:1', 'max:'.$maxRows],
            'rows.*' => ['array'],
            'rows.*.course_name' => ['nullable', 'string', 'max:255'],
            'rows.*.tec_code' => ['nullable', 'string', 'max:200'],
            'rows.*.tec_block_code' => ['nullable', 'string', 'max:100'],
            'rows.*.tec_clear_schedules' => ['nullable', 'boolean'],
            'rows.*.tall_clear_schedules' => ['nullable', 'boolean'],
            'rows.*.tec_start_date' => ['nullable', 'date'],
            'rows.*.tec_end_date' => ['nullable', 'date'],
            'rows.*.tall_code' => ['nullable', 'string', 'max:200'],
            'rows.*.tall_block_code' => ['nullable', 'string', 'max:100'],
            'rows.*.tall_start_date' => ['nullable', 'date'],
            'rows.*.tall_end_date' => ['nullable', 'date'],
            'rows.*.tec_schedules' => ['nullable', 'array', 'max:20'],
            'rows.*.tall_schedules' => ['nullable', 'array', 'max:20'],
            'rows.*.tec_schedules.*.days' => ['nullable', 'array'],
            'rows.*.tec_schedules.*.days.*' => ['integer', 'between:1,7'],
            'rows.*.tall_schedules.*.days' => ['nullable', 'array'],
            'rows.*.tall_schedules.*.days.*' => ['integer', 'between:1,7'],
            'rows.*.tec_schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'rows.*.tec_schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'rows.*.tall_schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'rows.*.tall_schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'rows.*.tec_schedules.*.repeat_interval_weeks' => ['nullable', 'integer', 'between:1,52'],
            'rows.*.tall_schedules.*.repeat_interval_weeks' => ['nullable', 'integer', 'between:1,52'],
            'rows.*.tec_schedules.*.instruction_type' => ['nullable', 'in:THEORY,LAB'],
            'rows.*.tall_schedules.*.instruction_type' => ['nullable', 'in:THEORY,LAB'],
            'rows.*.tec_schedules.*.classroom' => ['nullable', 'string', 'max:100'],
            'rows.*.tall_schedules.*.classroom' => ['nullable', 'string', 'max:100'],
        ]);

        $validator->after(function ($validator) use ($request, $parser) {
            $hasAtLeastOneCourse = false;
            $seenCourseCodes = [];

            foreach ((array) $request->input('rows', []) as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $courseName = trim((string) ($row['course_name'] ?? ''));
                $codes = [
                    'TEC' => trim((string) ($row['tec_code'] ?? '')),
                    'TALL' => trim((string) ($row['tall_code'] ?? '')),
                ];
                $hasAnyCode = $codes['TEC'] !== '' || $codes['TALL'] !== '';
                $hasAnySchedule = !empty($row['tec_schedules'] ?? []) || !empty($row['tall_schedules'] ?? []);
                $hasAnyExtra = filled($row['tec_block_code'] ?? null) || filled($row['tall_block_code'] ?? null);
                $hasAnyDate = filled($row['tec_start_date'] ?? null)
                    || filled($row['tec_end_date'] ?? null)
                    || filled($row['tall_start_date'] ?? null)
                    || filled($row['tall_end_date'] ?? null);

                if ($courseName === '' && !$hasAnyCode && !$hasAnyDate && !$hasAnyExtra && !$hasAnySchedule) {
                    continue;
                }

                $hasAtLeastOneCourse = true;

                if ($courseName === '') {
                    $validator->errors()->add("rows.{$index}.course_name", 'Pega el alias completo o escribe el nombre del curso.');
                }

                $aliasNames = $row['_alias_names'] ?? [];
                if (count($aliasNames) > 1) {
                    $validator->errors()->add("rows.{$index}.row_error", 'Los alias TEC y TALL deben tener el mismo nombre después de los dos puntos.');
                }

                if (!$hasAnyCode) {
                    $message = ($hasAnyDate || $hasAnyExtra || $hasAnySchedule)
                        ? 'Pega el alias institucional antes de completar fechas, bloques u horarios.'
                        : 'Ingresa al menos un alias institucional TEC o TALL.';
                    $validator->errors()->add("rows.{$index}.tec_code", $message);
                    continue;
                }

                $parsedByType = [];

                foreach ($codes as $type => $code) {
                    $prefix = $type === 'TEC' ? 'tec' : 'tall';
                    $startDate = $row[$prefix.'_start_date'] ?? null;
                    $endDate = $row[$prefix.'_end_date'] ?? null;
                    $blockCode = $row[$prefix.'_block_code'] ?? null;
                    $scheduleInput = $row[$prefix.'_schedules'] ?? [];

                    if ($code === '') {
                        if (filled($startDate) || filled($endDate) || filled($blockCode) || !empty($scheduleInput)) {
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_code",
                                "Completa el alias {$type} antes de indicar fechas, bloque u horario."
                            );
                        }

                        continue;
                    }

                    $parsed = $parser->parse($code);

                    if ($parsed['errors']) {
                        foreach ($parsed['errors'] as $message) {
                            $validator->errors()->add("rows.{$index}.{$prefix}_code", $message);
                        }
                        continue;
                    }

                    if ($parsed['has_structured_code']) {
                        if (count($parsed['matches']) !== 1 || $parsed['detected_modalities'] !== [$type]) {
                            $expectedLabel = $type === 'TEC' ? 'un alias TEC' : 'un alias TAL/TALL';
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_code",
                                "Este campo debe contener solamente {$expectedLabel} y su NRC."
                            );
                            continue;
                        }
                    } elseif ($codes['TEC'] !== '' && $codes['TALL'] !== '') {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_code",
                            'Cuando registres ambos componentes, utiliza alias institucionales completos para comprobar que pertenecen al mismo curso.'
                        );
                        continue;
                    }

                    $parsedByType[$type] = $parsed;

                    $hasStartDate = filled($startDate);
                    $hasEndDate = filled($endDate);

                    if ($hasStartDate !== $hasEndDate) {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_end_date",
                            "Completa Desde y Hasta de {$type}, o deja ambas fechas vacías."
                        );
                    } elseif ($hasStartDate && $hasEndDate && $endDate < $startDate) {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_end_date",
                            "La fecha Hasta de {$type} no puede ser anterior a Desde."
                        );
                    }

                    $blocksByDay = [];
                    foreach ($scheduleInput as $scheduleIndex => $schedule) {
                        if (!is_array($schedule)) {
                            continue;
                        }

                        $days = array_values(array_unique(array_map('intval', $schedule['days'] ?? [])));
                        $startTime = $schedule['start_time'] ?? null;
                        $endTime = $schedule['end_time'] ?? null;
                        $classroom = $schedule['classroom'] ?? null;
                        $hasValues = $days !== [] || filled($startTime) || filled($endTime) || filled($classroom);

                        if (!$hasValues) {
                            continue;
                        }

                        if (!$days || !$startTime || !$endTime) {
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_schedules.{$scheduleIndex}",
                                'Selecciona días e indica hora inicial y final para cada bloque semanal.'
                            );
                            continue;
                        }

                        if ($endTime <= $startTime) {
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_schedules.{$scheduleIndex}.end_time",
                                'La hora final debe ser posterior a la inicial.'
                            );
                            continue;
                        }

                        if (
                            (int) substr($startTime, 3, 2) % 15 !== 0
                            || (int) substr($endTime, 3, 2) % 15 !== 0
                        ) {
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_schedules.{$scheduleIndex}.start_time",
                                'Usa intervalos de 15 minutos (por ejemplo, 09:00, 09:15 o 09:30).'
                            );
                            continue;
                        }

                        foreach ($days as $day) {
                            $blocksByDay[$day][] = [
                                'start' => $startTime,
                                'end' => $endTime,
                                'interval' => max(1, (int) ($schedule['repeat_interval_weeks'] ?? 1)),
                                'index' => $scheduleIndex,
                            ];
                        }
                    }

                    foreach ($blocksByDay as $day => $blocks) {
                        usort($blocks, fn ($a, $b) => $a['start'] <=> $b['start']);
                        for ($blockIndex = 1; $blockIndex < count($blocks); $blockIndex++) {
                            $previous = $blocks[$blockIndex - 1];
                            $current = $blocks[$blockIndex];
                            if ($current['start'] < $previous['end']) {
                                $validator->errors()->add(
                                    "rows.{$index}.{$prefix}_schedules.{$current['index']}.start_time",
                                    'Hay bloques que se cruzan el mismo día. Revisa los horarios de '.$type.'.'
                                );
                            }
                        }
                    }
                }

                if (isset($parsedByType['TEC'], $parsedByType['TALL'])) {
                    if ($parsedByType['TEC']['base_code'] !== $parsedByType['TALL']['base_code']) {
                        $validator->errors()->add(
                            "rows.{$index}.tall_code",
                            'Los alias TEC y TAL no corresponden al mismo periodo, programa y número de curso.'
                        );
                    }
                }

                $baseCode = $parsedByType['TEC']['base_code']
                    ?? $parsedByType['TALL']['base_code']
                    ?? null;

                if ($baseCode !== null) {
                    $normalizedCode = mb_strtolower($baseCode);

                    if (isset($seenCourseCodes[$normalizedCode])) {
                        $validator->errors()->add(
                            "rows.{$index}.course_name",
                            'Este curso ya aparece en otra fila. Coloca sus alias TEC y TAL en la misma fila.'
                        );
                    }

                    $seenCourseCodes[$normalizedCode] = true;
                }
            }

            if (!$hasAtLeastOneCourse) {
                $validator->errors()->add('rows', 'Pega el alias y registra al menos un curso.');
            }
        });

        $data = $validator->validate();

        return collect($data['rows'])
            ->filter(fn ($row) => filled($row['course_name'] ?? null)
                || filled($row['tec_code'] ?? null)
                || filled($row['tall_code'] ?? null))
            ->values();
    }

    private function cleanScheduleBlocks(array $schedules): array
    {
        return collect($schedules)
            ->filter(fn ($schedule) => is_array($schedule)
                && (!empty($schedule['days'] ?? [])
                    || filled($schedule['start_time'] ?? null)
                    || filled($schedule['end_time'] ?? null)
                    || filled($schedule['classroom'] ?? null)))
            ->map(fn ($schedule) => [
                'days' => array_values(array_unique(array_map('intval', $schedule['days'] ?? []))),
                'start_time' => $schedule['start_time'] ?? '',
                'end_time' => $schedule['end_time'] ?? '',
                'repeat_interval_weeks' => max(1, (int) ($schedule['repeat_interval_weeks'] ?? 1)),
                'instruction_type' => $schedule['instruction_type'] ?? 'THEORY',
                'classroom' => trim((string) ($schedule['classroom'] ?? '')),
            ])
            ->values()
            ->all();
    }

    private function courseAlias(?string $baseCode, string $type, string $courseName, ?CourseModality $modality): string
    {
        $alias = trim((string) $baseCode);
        $alias .= '-'.$type;

        if (filled($modality?->nrc)) {
            $alias .= '-NRC_'.$modality->nrc;
        }

        return trim($alias).' : '.$courseName;
    }

    private function groupScheduleRows(?CourseModality $modality): array
    {
        if (!$modality) {
            return [];
        }

        return $modality->schedules
            ->where('block_type', 'CLASS')
            ->groupBy(fn ($schedule) => implode('|', [
                $schedule->start_time,
                $schedule->end_time,
                $schedule->classroom,
                $schedule->instruction_type,
                $schedule->repeat_interval_weeks ?? 1,
            ]))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'days' => $group->pluck('day_of_week')->map(fn ($day) => (int) $day)->unique()->values()->all(),
                    'start_time' => substr((string) $first->start_time, 0, 5),
                    'end_time' => substr((string) $first->end_time, 0, 5),
                    'repeat_interval_weeks' => (int) ($first->repeat_interval_weeks ?? 1),
                    'instruction_type' => $first->instruction_type ?? 'THEORY',
                    'classroom' => $first->classroom,
                ];
            })
            ->values()
            ->all();
    }

    private function courseAliasForNewInput(array $row, string $type, CourseCodeParser $parser): array
    {
        $prefix = $type === 'TEC' ? 'tec' : 'tall';
        return $parser->splitAlias((string) ($row[$prefix.'_code'] ?? ''));
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
