<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\GeneratedDocument;
use App\Models\TeachingGroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GroupDocumentService
{
    private const LABELS = [
        'alumnos' => 'Registro de alumnos',
        'malla' => 'Malla curricular',
        'plan' => 'Plan de sesión',
        'avance' => 'Control de avance',
        'charla' => 'Registro de charlas',
    ];

    public function generate(TeachingGroup $group, string $type, int $userId): GeneratedDocument
    {
        if (!array_key_exists($type, self::LABELS)) {
            throw ValidationException::withMessages([
                'document' => 'El documento solicitado no pertenece al paquete del grupo.',
            ]);
        }

        $group->loadMissing(['cycle', 'professor']);
        $modalities = $this->modalitiesFor($group);

        [$spreadsheet, $rows] = match ($type) {
            'alumnos' => [$this->studentRoster($group), $group->students()->count()],
            'malla' => $this->curriculumWorkbook($group),
            'plan' => [$this->sessionPlanWorkbook($group, $modalities), $modalities->sum(fn ($item) => $item->sessions()->whereNotIn('status', ['holiday', 'cancelled'])->count())],
            'avance' => [$this->progressWorkbook($group, $modalities), $modalities->sum(fn ($item) => $item->assignment->curriculum ? $item->assignment->curriculum->weeks()->count() : 0)],
            'charla' => [
                app(InstitutionalExcelService::class)->fiveMinuteTalkRegister($group),
                $group->fiveMinuteTalks()->whereNotNull('scheduled_date')->count(),
            ],
        };

        return $this->store(
            $spreadsheet,
            $group,
            $type,
            self::LABELS[$type],
            $userId,
            ['rows' => (int) $rows, 'modalities' => $modalities->count()]
        );
    }

    private function modalitiesFor(TeachingGroup $group)
    {
        return CourseModality::query()
            ->with([
                'assignment.course',
                'assignment.curriculum.weeks.task.operations',
                'assignment.curriculum.weeks.contentItems',
                'assignment.group.cycle',
                'schedules',
            ])
            ->where('active', true)
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id))
            ->orderBy('course_assignment_id')
            ->orderBy('modality')
            ->get();
    }

    private function studentRoster(TeachingGroup $group): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Alumnos');

        $students = $group->students()
            ->orderBy('students.surnames')
            ->orderBy('students.given_names')
            ->get();

        $headers = ['N.º', 'ID institucional', 'Usuario', 'Apellidos', 'Nombres'];
        $this->writeHeading(
            $sheet,
            'REGISTRO DE ALUMNOS',
            $this->groupSubtitle($group).' · Total: '.$students->count().' alumnos',
            $headers
        );

        foreach ($students as $index => $student) {
            $row = $index + 5;
            $values = [
                $index + 1,
                $student->institutional_id,
                $student->username,
                $student->surnames,
                $student->given_names,
            ];
            $this->writeRow($sheet, $row, $values);
        }

        $this->formatTable($sheet, count($headers), max(4, $students->count() + 4));
        return $spreadsheet;
    }

    private function curriculumWorkbook(TeachingGroup $group): array
    {
        $assignments = $group->assignments()
            ->with([
                'course',
                'curriculum.weeks.task.operations',
                'curriculum.weeks.contentItems',
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn ($assignment) => $assignment->curriculum !== null)
            ->values();

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'document' => 'Este grupo todavía no tiene una malla curricular vinculada a sus cursos.',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $rowCount = 0;

        foreach ($assignments as $index => $assignment) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($this->uniqueSheetTitle($spreadsheet, $assignment->course->name, $sheet));
            $curriculum = $assignment->curriculum;
            $headers = [
                'Semana',
                'Módulo / tarea',
                'Tema de programación',
                'Operaciones',
                'Conocimientos tecnológicos',
                'Conocimientos complementarios',
                'Actividades / ejercicios',
                'Materiales',
                'Evidencias',
                'Horas teoría',
                'Horas práctica',
                'Horas virtual',
                'Evaluación',
            ];

            $this->writeHeading(
                $sheet,
                'MALLA CURRICULAR · '.$assignment->course->name,
                implode(' · ', array_filter([
                    $assignment->course->code,
                    $curriculum->module_code,
                    $curriculum->module_name,
                    $curriculum->career,
                ])),
                $headers
            );

            foreach ($curriculum->weeks as $weekIndex => $week) {
                $task = $week->task;
                $items = $week->contentItems->where('approved', true);
                $operations = $task?->operations
                    ->map(fn ($operation) => trim(($operation->operation_code ? $operation->operation_code.': ' : '').$operation->description))
                    ->filter()
                    ->implode("\n");

                $contents = [
                    'TECHNOLOGICAL' => $items->where('category', 'TECHNOLOGICAL')->pluck('content')->implode("\n"),
                    'COMPLEMENTARY' => $items->where('category', 'COMPLEMENTARY')->pluck('content')->implode("\n"),
                    'EXERCISE' => $items->where('category', 'EXERCISE')->pluck('content')->implode("\n"),
                    'MATERIAL' => $items->where('category', 'MATERIAL')->pluck('content')->implode("\n"),
                    'EVIDENCE' => $items->where('category', 'EVIDENCE')->pluck('content')->implode("\n"),
                ];

                if (filled($week->notes)) {
                    $contents['EXERCISE'] = trim($contents['EXERCISE']."\n".$week->notes);
                }

                $this->writeRow($sheet, $weekIndex + 5, [
                    $week->week_number,
                    $task ? trim(($task->task_code ? $task->task_code.': ' : '').$task->title) : '',
                    $week->source_topic,
                    $operations,
                    $contents['TECHNOLOGICAL'],
                    $contents['COMPLEMENTARY'],
                    $contents['EXERCISE'],
                    $contents['MATERIAL'],
                    $contents['EVIDENCE'],
                    $week->hours_theory,
                    $week->hours_practice,
                    $week->hours_virtual,
                    $week->is_evaluation ? 'Sí' : 'No',
                ]);
                $rowCount++;
            }

            $this->formatTable($sheet, count($headers), max(4, $curriculum->weeks->count() + 4));
            foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I'] as $column) {
                $sheet->getColumnDimension($column)->setWidth(34);
            }
        }

        return [$spreadsheet, $rowCount];
    }

    private function sessionPlanWorkbook(TeachingGroup $group, $modalities): Spreadsheet
    {
        $ready = $modalities->filter(fn ($modality) =>
            $modality->assignment->curriculum
            && $modality->sessions()->whereNotIn('status', ['holiday', 'cancelled'])->exists()
        );

        if ($ready->isEmpty()) {
            throw ValidationException::withMessages([
                'document' => 'Para generar el Plan de sesión, configura los horarios, calcula el calendario y vincula la malla curricular de al menos un curso.',
            ]);
        }

        $spreadsheet = $this->groupCoverWorkbook($group, 'PLAN DE SESIÓN');
        $institutional = app(InstitutionalExcelService::class);

        foreach ($ready as $modality) {
            $source = $institutional->sessionPlan($modality);
            $prefix = $this->modalityLabel($modality);
            $this->appendWorkbook($spreadsheet, $source, $prefix);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    private function progressWorkbook(TeachingGroup $group, $modalities): Spreadsheet
    {
        $ready = $modalities->filter(fn ($modality) =>
            $modality->assignment->curriculum
            && $modality->assignment->curriculum->weeks()->exists()
        );

        if ($ready->isEmpty()) {
            throw ValidationException::withMessages([
                'document' => 'Para generar el Control de avance, vincula una malla curricular con semanas o temas a los cursos del grupo.',
            ]);
        }

        $spreadsheet = $this->groupCoverWorkbook($group, 'CONTROL DE AVANCE');
        $institutional = app(InstitutionalExcelService::class);

        foreach ($ready as $modality) {
            $source = $institutional->trainingRegister($modality);
            $prefix = $this->modalityLabel($modality);
            $this->appendWorkbook($spreadsheet, $source, $prefix);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    private function groupCoverWorkbook(TeachingGroup $group, string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $this->groupSubtitle($group));
        $sheet->setCellValue('A4', 'Grupo');
        $sheet->setCellValue('B4', $group->name);
        $sheet->setCellValue('A5', 'Ciclo');
        $sheet->setCellValue('B5', $group->cycle->academic_year.' · '.$group->cycle->cycle_code);
        $sheet->setCellValue('A6', 'Generado');
        $sheet->setCellValue('B6', now()->format('d/m/Y H:i'));
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '174B63']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '526B78']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDF4F7']],
        ]);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getSheetView()->setShowGridLines(false);
        return $spreadsheet;
    }

    private function appendWorkbook(Spreadsheet $target, Spreadsheet $source, string $prefix): void
    {
        $sheetMap = [];

        foreach ($source->getAllSheets() as $sheet) {
            $newTitle = $this->uniqueSheetTitle($target, $prefix.' '.$sheet->getTitle());
            $sheetMap[$sheet->getTitle()] = $newTitle;
        }

        foreach ($source->getAllSheets() as $sheet) {
            // Importa hojas de otro Spreadsheet con sus estilos; addSheet()
            // no copia correctamente los estilos entre libros distintos.
            $copy = clone $sheet;
            $copy->setTitle($sheetMap[$sheet->getTitle()]);
            $copy->setSheetState($sheet->getSheetState());

            foreach ($copy->getCellCollection() as $coordinate) {
                $cell = $copy->getCell($coordinate);
                $value = $cell->getValue();

                if (!is_string($value) || !str_starts_with($value, '=')) {
                    continue;
                }

                foreach ($sheetMap as $oldTitle => $newTitle) {
                    $value = str_replace(
                        ["'".$oldTitle."'!", $oldTitle.'!'],
                        ["'".$newTitle."'!", "'".$newTitle."'!"],
                        $value
                    );
                }

                $cell->setValue($value);
            }

            $target->addExternalSheet($copy);
        }

        $source->disconnectWorksheets();
    }

    private function modalityLabel(CourseModality $modality): string
    {
        return trim(implode(' ', array_filter([
            Str::limit($modality->assignment->course->code ?: $modality->assignment->course->name, 18, ''),
            $modality->modality,
            $modality->nrc ? 'NRC '.$modality->nrc : null,
        ])));
    }

    private function groupSubtitle(TeachingGroup $group): string
    {
        return implode(' · ', array_filter([
            $group->cycle->academic_year.'-'.$group->cycle->cycle_code,
            $group->career ?: $group->cycle->career,
            $group->semester ?: $group->cycle->semester,
            $group->name,
            $group->professor?->full_name,
        ]));
    }

    private function writeHeading(Worksheet $sheet, string $title, string $subtitle, array $headers): void
    {
        $lastColumn = $this->columnName(count($headers));
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A2:'.$lastColumn.'2');
        $sheet->setCellValue('A2', $subtitle);
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(34);
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '174B63']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:'.$lastColumn.'2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '526B78']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDF4F7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A4:'.$lastColumn.'4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '327C91']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getSheetView()->setShowGridLines(false);
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $cell = $this->columnName($index + 1).$row;
            if (is_string($value)) {
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($cell, $value);
            }
        }
    }

    private function formatTable(Worksheet $sheet, int $columnCount, int $lastRow): void
    {
        $lastColumn = $this->columnName($columnCount);
        if ($lastRow > 4) {
            $sheet->getStyle('A5:'.$lastColumn.$lastRow)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE6EB']]],
            ]);
        }
        $sheet->setAutoFilter('A4:'.$lastColumn.max(4, $lastRow));
        $sheet->freezePane('A5');
        foreach (range(1, $columnCount) as $columnIndex) {
            $sheet->getColumnDimension($this->columnName($columnIndex))->setAutoSize(true);
        }
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.35)->setBottom(0.35)->setLeft(0.25)->setRight(0.25);
    }

    private function uniqueSheetTitle(Spreadsheet $spreadsheet, string $title, ?Worksheet $current = null): string
    {
        $base = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/u', ' ', $title) ?: 'Hoja';
        $base = Str::limit(trim($base) ?: 'Hoja', 25, '');
        $candidate = $base;
        $counter = 2;

        while ($spreadsheet->getSheetByName($candidate) && $spreadsheet->getSheetByName($candidate) !== $current) {
            $suffix = ' '.$counter++;
            $candidate = Str::limit($base, 31 - mb_strlen($suffix), '').$suffix;
        }

        return $candidate;
    }

    private function sourceFingerprint(TeachingGroup $group, string $type): string
    {
        $group->loadMissing(['students', 'assignments.modalities.schedules', 'assignments.curriculum.weeks']);
        $payload = [
            'type' => $type,
            'today' => now()->toDateString(),
            'group_updated_at' => $group->updated_at?->toISOString(),
            'students' => $group->students->map(fn ($student) => [$student->id, $student->updated_at?->toISOString()])->all(),
            'assignments' => $group->assignments->map(fn ($assignment) => [
                $assignment->id,
                $assignment->curriculum_id,
                $assignment->updated_at?->toISOString(),
                $assignment->modalities->map(fn ($modality) => [
                    $modality->id,
                    $modality->start_date?->toDateString(),
                    $modality->end_date?->toDateString(),
                    $modality->updated_at?->toISOString(),
                    $modality->schedules->map(fn ($schedule) => [
                        $schedule->id,
                        $schedule->day_of_week,
                        $schedule->start_time,
                        $schedule->end_time,
                        $schedule->repeat_interval_weeks,
                        $schedule->updated_at?->toISOString(),
                    ])->all(),
                ])->all(),
                $assignment->curriculum?->weeks->map(fn ($week) => [$week->id, $week->source_topic, $week->updated_at?->toISOString()])->all(),
            ])->all(),
            'talks_updated_at' => $group->fiveMinuteTalks()->max('updated_at'),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    private function store(
        Spreadsheet $spreadsheet,
        TeachingGroup $group,
        string $type,
        string $label,
        int $userId,
        array $metadata
    ): GeneratedDocument {
        $version = ((int) GeneratedDocument::query()
            ->where('teaching_group_id', $group->id)
            ->where('document_type', $type)
            ->max('version')) + 1;

        $filename = Str::slug($label.' '.$group->name).'-grupo-'.$group->id.'-v'.$version.'-'.now()->format('Ymd-His').'.xlsx';
        $storedPath = 'generated-documents/'.$filename;
        $temporaryPath = tempnam(sys_get_temp_dir(), 'gestion-docente-group-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('No se pudo preparar el archivo Excel del grupo.');
        }

        try {
            (new Xlsx($spreadsheet))->save($temporaryPath);
            $contents = file_get_contents($temporaryPath);
            if ($contents === false || !Storage::disk('local')->put($storedPath, $contents)) {
                throw new \RuntimeException('No se pudo guardar el Excel generado.');
            }

            return GeneratedDocument::create([
                'course_modality_id' => null,
                'teaching_group_id' => $group->id,
                'cycle_id' => null,
                'document_type' => $type,
                'version' => $version,
                'filename' => $filename,
                'stored_path' => $storedPath,
                'generated_by' => $userId,
                'source_fingerprint' => $this->sourceFingerprint($group, $type),
                'generated_at' => now(),
                'metadata' => array_merge($metadata, [
                    'stale' => false,
                    'scope' => 'group',
                    'label' => $label,
                    'group' => $group->name,
                    'generated_at' => now()->toIso8601String(),
                ]),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }
        return $name;
    }
}
