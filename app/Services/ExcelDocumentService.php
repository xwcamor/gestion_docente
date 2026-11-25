<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelDocumentService
{
    private const DOCUMENTS = [
        'plan' => 'Plan de sesión',
        'registro' => 'Control de avance',
        'evaluacion' => 'Formato de evaluación',
    ];

    /**
     * A session plan and the training progress register depend on official
     * weekly topics from the linked curriculum. The task title is accepted as
     * a fallback for older curricula that predate the programming-sheet field.
     */
    public function hasCurriculumTopics(CourseModality $modality): bool
    {
        $modality->loadMissing([
            'assignment.curriculum.weeks.task',
        ]);

        $assignment = $modality->assignment;
        $curriculum = $assignment->curriculum;

        if (!$curriculum) {
            return false;
        }

        return $curriculum->weeks->contains(fn ($week) =>
            filled($week->source_topic) || filled($week->task?->title)
        );
    }

    public function generateForModality(CourseModality $modality, string $type, int $userId): GeneratedDocument
    {
        if (!$modality->active) {
            throw ValidationException::withMessages(['document' => 'Activa esta modalidad desde el árbol antes de generar documentos.']);
        }

        if (!array_key_exists($type, self::DOCUMENTS)) {
            throw ValidationException::withMessages([
                'document' => 'El tipo de Excel solicitado no está disponible. Las charlas de 5 minutos se generan desde el ciclo.',
            ]);
        }

        $modality->loadMissing([
            'assignment.course',
            'assignment.group.cycle',
            'schedules',
        ]);

        if (
            in_array($type, ['plan', 'registro'], true)
            && !$this->hasCurriculumTopics($modality)
        ) {
            throw ValidationException::withMessages([
                'document' => 'Para generar el Plan de sesión o el Control de avance, primero importa una malla curricular que contenga temas semanales.',
            ]);
        }

        $course = $modality->assignment->course;
        $group = $modality->assignment->group;
        $cycle = $group->cycle;
        $subtitle = implode(' · ', array_filter([
            $course->name,
            $course->code ? 'Código '.$course->code : null,
            $modality->modality,
            $modality->nrc ? 'NRC '.$modality->nrc : null,
            $group->name,
            $cycle->academic_year.'-'.$cycle->cycle_code,
        ]));

        $institutional = app(InstitutionalExcelService::class);
        $spreadsheet = match ($type) {
            'plan' => $institutional->sessionPlan($modality),
            'registro' => $institutional->trainingRegister($modality),
            'evaluacion' => $institutional->evaluationRegister($modality),
        };
        $rowCount = match ($type) {
            'plan' => $modality->sessions()->count(),
            'registro', 'evaluacion' => $modality->enrollments()->count(),
        };

        $document = $this->storeWorkbook($spreadsheet, [
            'course_modality_id' => $modality->id,
            'teaching_group_id' => null,
            'cycle_id' => null,
        ], $type, self::DOCUMENTS[$type], $userId, [
            'course' => $course->name,
            'modality' => $modality->modality,
            'nrc' => $modality->nrc,
            'rows' => $rowCount,
        ]);

        $spreadsheet->disconnectWorksheets();

        return $document;
    }

    public function generateLoadDistribution(Cycle $cycle, int $userId): GeneratedDocument
    {
        $cycle->loadMissing('groups');
        $modalities = CourseModality::query()
            ->with([
                'assignment.course',
                'assignment.group',
                'schedules' => fn ($query) => $query->where('block_type', 'CLASS'),
            ])
            ->where('active', true)
            ->whereHas('assignment.group', fn ($query) => $query->where('cycle_id', $cycle->id))
            ->get();

        $dayNames = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
        ];

        $rows = [];
        foreach ($modalities as $modality) {
            foreach ($modality->schedules as $schedule) {
                $rows[] = [
                    $dayNames[(int) $schedule->day_of_week] ?? 'Sin día',
                    substr((string) $schedule->start_time, 0, 5),
                    substr((string) $schedule->end_time, 0, 5),
                    $modality->assignment->group->name,
                    $modality->assignment->course->name,
                    $modality->assignment->course->code,
                    $modality->modality,
                    $modality->nrc,
                    ($schedule->instruction_type ?? 'THEORY') === 'LAB' ? 'Laboratorio' : 'Teoría',
                    $schedule->classroom,
                    $modality->start_date?->format('d/m/Y'),
                    $modality->end_date?->format('d/m/Y'),
                    round($modality->schedules->sum(fn ($item) => $item->duration_minutes) / 60, 2),
                ];
            }
        }

        usort($rows, function ($a, $b) {
            $dayOrder = ['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6, 'Domingo' => 7];
            return [
                $dayOrder[$a[0]] ?? 8,
                $a[1],
                $a[3],
                $a[4],
            ] <=> [
                $dayOrder[$b[0]] ?? 8,
                $b[1],
                $b[3],
                $b[4],
            ];
        });

        $spreadsheet = $this->makeWorkbook(
            'Distribución de carga laboral',
            $cycle->academic_year.' · Ciclo '.$cycle->cycle_code.' · Rango general '.$cycle->start_date?->format('d/m/Y').' al '.$cycle->end_date?->format('d/m/Y'),
            ['Día', 'Desde', 'Hasta', 'Grupo', 'Curso', 'Código', 'Modalidad', 'NRC', 'Tipo de enseñanza', 'Aula / ambiente', 'Inicio del curso', 'Fin del curso', 'Horas semanales calculadas'],
            $rows
        );

        $document = $this->storeWorkbook($spreadsheet, [
            'course_modality_id' => null,
            'teaching_group_id' => null,
            'cycle_id' => $cycle->id,
        ], 'carga_laboral', 'Distribución de carga laboral', $userId, [
            'cycle' => $cycle->academic_year.'-'.$cycle->cycle_code,
            'groups' => $cycle->groups()->count(),
            'modalities' => $modalities->count(),
            'rows' => count($rows),
        ]);

        $spreadsheet->disconnectWorksheets();

        return $document;
    }

    public function generateFiveMinuteTalks(TeachingGroup $group, int $userId): GeneratedDocument
    {
        $group->loadMissing('cycle');
        $talks = $group->fiveMinuteTalks()->whereNotNull('scheduled_date')->orderBy('scheduled_date')->orderBy('position')->get();
        $spreadsheet = app(InstitutionalExcelService::class)->fiveMinuteTalkRegister($group);

        $document = $this->storeWorkbook($spreadsheet, [
            'course_modality_id' => null,
            'teaching_group_id' => $group->id,
            'cycle_id' => null,
        ], 'charla', 'Charlas de 5 minutos '.$group->name, $userId, [
            'cycle' => $group->cycle->academic_year.'-'.$group->cycle->cycle_code,
            'group' => $group->name,
            'topics' => $talks->count(),
            'assigned' => $talks->whereNotNull('scheduled_date')->count(),
            'completed' => $talks->where('status', 'done')->count(),
            'pending' => $talks->where('status', '!=', 'done')->count(),
        ]);

        $spreadsheet->disconnectWorksheets();

        return $document;
    }

    private function sessionPlanRows(CourseModality $modality): array
    {
        $sessions = $modality->sessions()
            ->with('curriculumWeek')
            ->orderBy('session_date')
            ->orderBy('session_number')
            ->get();

        $rows = $sessions->map(fn ($session) => [
            $session->session_date?->format('d/m/Y'),
            $session->session_number,
            $session->curriculum_week_number,
            $session->duration_minutes,
            $session->topic,
            $session->objective,
            $session->activities,
            $session->evidence,
            $session->status,
        ])->all();

        return [
            ['Fecha', 'Sesión', 'Semana', 'Duración (min)', 'Tema', 'Objetivo', 'Actividades / materiales', 'Evidencias', 'Estado'],
            $rows,
        ];
    }

    private function trainingRegisterRows(CourseModality $modality): array
    {
        $enrollments = $modality->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => mb_strtolower(
                ($enrollment->student?->surnames ?? '').' '.($enrollment->student?->given_names ?? '')
            ))
            ->values();

        $rows = $enrollments->values()->map(fn ($enrollment, $index) => [
            $index + 1,
            $enrollment->student?->institutional_id,
            $enrollment->student?->surnames,
            $enrollment->student?->given_names,
            $enrollment->student?->username,
            $enrollment->status,
            '',
        ])->all();

        return [
            ['N.º', 'ID institucional', 'Apellidos', 'Nombres', 'Usuario', 'Estado de matrícula', 'Firma / observaciones'],
            $rows,
        ];
    }

    private function evaluationRows(CourseModality $modality): array
    {
        $enrollments = $modality->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => mb_strtolower(
                ($enrollment->student?->surnames ?? '').' '.($enrollment->student?->given_names ?? '')
            ))
            ->values();

        $evaluations = $modality->evaluations()->with('grades')->orderBy('id')->get();
        $headers = ['N.º', 'ID institucional', 'Apellidos', 'Nombres'];

        foreach ($evaluations as $evaluation) {
            $headers[] = $evaluation->title.' (máx. '.$evaluation->maximum_score.')';
        }
        $headers[] = 'Promedio simple';
        $headers[] = 'Estado';

        $rows = [];
        foreach ($enrollments as $index => $enrollment) {
            $scores = [];
            $numericScores = [];

            foreach ($evaluations as $evaluation) {
                $grade = $evaluation->grades->firstWhere('enrollment_id', $enrollment->id);
                $score = $grade?->score;
                $scores[] = $score;

                if ($score !== null) {
                    $numericScores[] = (float) $score;
                }
            }

            $average = $numericScores
                ? round(array_sum($numericScores) / count($numericScores), 2)
                : null;

            $rows[] = array_merge([
                $index + 1,
                $enrollment->student?->institutional_id,
                $enrollment->student?->surnames,
                $enrollment->student?->given_names,
            ], $scores, [
                $average,
                $enrollment->status,
            ]);
        }

        return [$headers, $rows];
    }

    private function makeWorkbook(string $title, string $subtitle, array $headers, array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit(Str::ascii($title), 31, ''));

        $columnCount = max(1, count($headers));
        $lastColumn = $this->columnName($columnCount);

        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A2:'.$lastColumn.'2');
        $sheet->setCellValue('A2', $subtitle);
        $sheet->fromArray($headers, null, 'A4');

        $lastRow = 4;
        foreach ($rows as $row) {
            $lastRow++;
            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).$lastRow;

                // Treat user/imported text as text, never as an Excel formula.
                if (is_string($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(32);

        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '174B63']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:'.$lastColumn.'2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '526B78']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDF4F7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A4:'.$lastColumn.'4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '327C91']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        if ($lastRow > 4) {
            $sheet->getStyle('A5:'.$lastColumn.$lastRow)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE6EB']],
                ],
            ]);

        }

        $sheet->setAutoFilter('A4:'.$lastColumn.max(4, $lastRow));
        $sheet->freezePane('A5');

        foreach (range(1, $columnCount) as $columnIndex) {
            $column = $this->columnName($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.35)->setBottom(0.35)->setLeft(0.25)->setRight(0.25);
        $sheet->getSheetView()->setShowGridLines(false);

        return $spreadsheet;
    }

    private function storeWorkbook(
        Spreadsheet $spreadsheet,
        array $scope,
        string $type,
        string $label,
        int $userId,
        array $metadata
    ): GeneratedDocument {
        $versionQuery = GeneratedDocument::query()->where('document_type', $type);

        if (!empty($scope['course_modality_id'])) {
            $versionQuery->where('course_modality_id', $scope['course_modality_id']);
        } elseif (!empty($scope['teaching_group_id'])) {
            $versionQuery->where('teaching_group_id', $scope['teaching_group_id']);
        } elseif (!empty($scope['cycle_id'])) {
            $versionQuery->where('cycle_id', $scope['cycle_id']);
        }

        $version = ((int) $versionQuery->max('version')) + 1;
        $scopeId = $scope['course_modality_id'] ?? $scope['teaching_group_id'] ?? $scope['cycle_id'] ?? 'general';
        $filename = Str::slug($label).'-'.$scopeId.'-v'.$version.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.xlsx';
        $storedPath = 'generated-documents/'.$filename;
        $temporaryPath = tempnam(sys_get_temp_dir(), 'gestion-docente-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('No se pudo preparar el archivo temporal de Excel.');
        }

        try {
            (new Xlsx($spreadsheet))->save($temporaryPath);
            $contents = file_get_contents($temporaryPath);
            if ($contents === false || !Storage::disk('local')->put($storedPath, $contents)) {
                throw new \RuntimeException('No se pudo guardar el Excel generado.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        try {
            return GeneratedDocument::create(array_merge($scope, [
                'document_type' => $type,
                'version' => $version,
                'filename' => $filename,
                'stored_path' => $storedPath,
                'generated_by' => $userId,
                'metadata' => array_merge($metadata, ['stale' => false, 'generated_at' => now()->toIso8601String()]),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }
    }

    private function columnName(int $columnNumber): string
    {
        $name = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $name;
    }
}
