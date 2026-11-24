<?php

namespace App\Services;

use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use App\Models\CurriculumWeek;
use App\Models\GeneratedDocument;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionalExcelService
{
    public function sessionPlan(CourseModality $modality): Spreadsheet
    {
        $modality->loadMissing([
            'assignment.course',
            'assignment.group.cycle',
            'assignment.curriculum.weeks.task.operations',
            'assignment.curriculum.weeks.contentItems',
            'sessions.curriculumWeek.task.operations',
            'sessions.curriculumWeek.contentItems',
            'sessions',
        ]);

        $spreadsheet = new Spreadsheet();
        $sessions = $modality->sessions()->with([
            'curriculumWeek.task.operations',
            'curriculumWeek.contentItems',
        ])->orderBy('session_date')->orderBy('session_number')->get();

        if ($sessions->isEmpty()) {
            $sheet = $spreadsheet->getActiveSheet();
            $this->buildSessionSheet($sheet, $modality, null, null);
            return $spreadsheet;
        }

        foreach ($sessions as $index => $session) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $sheet->setTitle($this->safeSheetTitle(
                ($session->session_date?->format('d-m-Y') ?? 'Sesion').'-'.$session->session_number
            ));

            $this->buildSessionSheet($sheet, $modality, $session, $sessions);
        }

        return $spreadsheet;
    }

    public function trainingRegister(CourseModality $modality): Spreadsheet
    {
        $template = $modality->modality === 'TALL'
            ? 'system_files/3.PDSD-234_REGISTRO FORMACION PRÁCTICA.xlsx'
            : 'system_files/3.PDSD-234_REGISTRO FORMACION TEORICA.xlsx';

        $spreadsheet = $this->loadTemplate($template);
        $sheet = $this->prepareCourseSheet($spreadsheet, $modality);
        $this->updateCourseLookup($spreadsheet, $modality);

        $lastRow = $sheet->getHighestRow();
        $footerRow = $lastRow;
        for ($row = $lastRow; $row >= 10; $row--) {
            if ($sheet->getCell('R'.$row)->getValue() !== null
                && str_contains((string) $sheet->getCell('R'.$row)->getValue(), 'ACAD-P-22')) {
                $footerRow = $row;
                break;
            }
        }

        for ($row = 10; $row < $footerRow; $row++) {
            foreach (range('A', 'R') as $column) {
                $sheet->setCellValue($column.$row, null);
            }
        }

        $curriculum = $modality->assignment->curriculum;
        $weeks = $curriculum
            ? $curriculum->weeks()->with(['task.operations', 'contentItems'])->orderBy('week_number')->get()
            : collect();

        $sessions = $modality->sessions()->orderBy('session_date')->get();
        $output = [];

        foreach ($weeks as $week) {
            $task = $week->task;
            $output[] = [
                'week' => $week->week_number,
                'text' => $task
                    ? trim(($task->task_code ? $task->task_code.': ' : '').$task->title)
                    : ($week->is_evaluation ? 'EVALUACIÓN' : ''),
                'date' => $this->datesForWeek($sessions, (int) $week->week_number),
                'kind' => 'heading',
            ];

            foreach ($task?->operations ?? [] as $operation) {
                $output[] = [
                    'text' => '▪ '.trim(($operation->operation_code ? $operation->operation_code.': ' : '').$operation->description),
                    'kind' => 'operation',
                ];
            }

            foreach ($week->contentItems->where('approved', true) as $item) {
                $label = match ($item->category) {
                    'TECHNOLOGICAL' => 'Conocimiento',
                    'COMPLEMENTARY' => 'Conocimiento complementario',
                    'VIRTUAL' => 'Actividad virtual',
                    'EXERCISE' => 'Actividad',
                    'MATERIAL' => 'Material',
                    'EVIDENCE' => 'Evidencia',
                    default => 'Contenido',
                };

                $output[] = [
                    'text' => '▪ '.$label.': '.trim($item->content),
                    'kind' => 'content',
                ];
            }

            if (filled($week->notes)) {
                $output[] = ['text' => '▪ Nota: '.trim($week->notes), 'kind' => 'content'];
            }
        }

        if (!$output) {
            foreach ($sessions as $session) {
                $output[] = [
                    'week' => $session->curriculum_week_number,
                    'text' => 'HT: '.($session->topic ?: 'Sesión '.$session->session_number),
                    'date' => $session->session_date?->format('d/m/Y'),
                    'kind' => 'heading',
                ];
                foreach (preg_split('/\r\n|\r|\n/u', (string) $session->activities) ?: [] as $line) {
                    if (trim($line) !== '') {
                        $output[] = ['text' => '▪ '.trim($line), 'kind' => 'content'];
                    }
                }
            }
        }

        $dataRows = count($output);
        $availableRows = max(0, $footerRow - 10);
        if ($dataRows > $availableRows) {
            $extra = $dataRows - $availableRows;
            $sheet->insertNewRowBefore($footerRow, $extra);
            for ($row = $footerRow; $row < $footerRow + $extra; $row++) {
                $sheet->duplicateStyle($sheet->getStyle('A'.($footerRow - 1).':R'.($footerRow - 1)), 'A'.$row.':R'.$row);
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension($footerRow - 1)->getRowHeight());
            }
            $footerRow += $extra;
        }

        $row = 10;
        foreach ($output as $item) {
            if (($item['kind'] ?? '') === 'heading') {
                $sheet->setCellValue('A'.$row, $item['week'] ?? null);
                $sheet->getStyle('A'.$row)->getNumberFormat()->setFormatCode('0.0');
                $this->setText($sheet, 'B'.$row, $item['text'] ?? '');
                $this->setText($sheet, 'O'.$row, $item['date'] ?? '');
            } else {
                $this->setText($sheet, 'B'.$row, $item['text']);
            }
            $sheet->getStyle('B'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('O'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $row++;
        }

        $sheet->getSheetView()->setShowGridLines(false);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);

        return $spreadsheet;
    }

    public function evaluationRegister(CourseModality $modality): Spreadsheet
    {
        $template = $modality->modality === 'TALL'
            ? 'system_files/4.PMSD-222_FORMATO EVALUACIÓN DE LA FORMACIÓN PRÁCTICA.xlsx'
            : 'system_files/4.PMSD-222_FORMATO EVALUACIÓN DE LA FORMACIÓN TEÓRICA.xlsx';

        $spreadsheet = $this->loadTemplate($template);
        $sheet = $this->prepareCourseSheet($spreadsheet, $modality);
        $this->updateCourseLookup($spreadsheet, $modality);
        $this->setEvaluationMetadata($sheet, $modality);

        $students = $modality->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => mb_strtolower(
                ($enrollment->student?->surnames ?? '').' '.($enrollment->student?->given_names ?? '')
            ))
            ->values();

        $templateRows = 20;
        $firstRow = 27;
        $footerRow = 48;
        if ($students->count() > $templateRows) {
            $extra = $students->count() - $templateRows;
            $sheet->insertNewRowBefore($footerRow, $extra);
            for ($row = $footerRow; $row < $footerRow + $extra; $row++) {
                $sheet->duplicateStyle($sheet->getStyle('B46:V46'), 'B'.$row.':V'.$row);
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension(46)->getRowHeight());
            }
        }

        foreach ($students as $index => $enrollment) {
            $row = $firstRow + $index;
            foreach (range('B', 'V') as $column) {
                if (in_array($column, ['F', 'M'], true)) {
                    continue;
                }
                $sheet->setCellValue($column.$row, null);
            }

            $sheet->setCellValue('B'.$row, $index + 1);
            $this->setText($sheet, 'C'.$row, $enrollment->student?->institutional_id ?? '');
            $this->setText($sheet, 'D'.$row, $enrollment->student?->surnames ?? '');
            $this->setText($sheet, 'E'.$row, $enrollment->student?->given_names ?? '');
            $sheet->setCellValue('F'.$row, '=IFERROR(SUMPRODUCT(G'.$row.':L'.$row.',$G$26:$L$26)/SUM($G$26:$L$26),0)');
            $sheet->setCellValue('M'.$row, '=SUM(G'.$row.':L'.$row.')');
        }

        $this->appendSystemGrades($spreadsheet, $modality, $students);
        $sheet->getSheetView()->setShowGridLines(false);

        return $spreadsheet;
    }

    public function fiveMinuteTalkRegister(TeachingGroup $group): Spreadsheet
    {
        $cataloguePath = base_path('system_files/charla_5_minutos_catalogo.json');
        $catalogue = json_decode((string) file_get_contents($cataloguePath), true);
        $group->loadMissing(['cycle', 'professor']);
        $talks = $group->fiveMinuteTalks()->whereNotNull('scheduled_date')->orderBy('scheduled_date')->orderBy('position')->get();

        $programs = $group->assignments()
            ->with('course')
            ->get()
            ->pluck('course.career')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registro de Charlas 5 Min.');
        $this->buildTalkRegisterHeader($sheet, $group);

        $row = 12;
        foreach ($talks as $talk) {
            $this->setText($sheet, 'C'.$row, implode(', ', $programs) ?: ($group->professor->career ?? ''));
            $this->setText($sheet, 'D'.$row, $group->name);
            if ($talk->participant_count !== null) {
                $sheet->setCellValue('E'.$row, $talk->participant_count);
            }
            if ($talk->scheduled_date) {
                $sheet->setCellValue('F'.$row, ExcelDate::PHPToExcel($talk->scheduled_date->copy()->startOfDay()));
                $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $this->setText($sheet, 'G'.$row, $talk->topic);
            $observation = $talk->status === 'done' ? 'Realizado' : 'Pendiente';
            if (filled($talk->observations)) {
                $observation .= ' · '.$talk->observations;
            }
            $this->setText($sheet, 'H'.$row, $observation);
            $row++;
        }

        $sheet->getStyle('C12:H'.max(12, $row - 1))->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B7B7B7']]],
        ]);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(55);
        $sheet->getColumnDimension('H')->setWidth(34);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);

        $topicsSheet = $spreadsheet->createSheet();
        $topicsSheet->setTitle('TEMAS');
        $this->buildTalkTopicsSheet($topicsSheet, $catalogue['areas'] ?? []);

        return $spreadsheet;
    }

    private function buildSessionSheet(
        Worksheet $sheet,
        CourseModality $modality,
        $session,
        ?Collection $allSessions
    ): void {
        $course = $modality->assignment->course;
        $group = $modality->assignment->group;
        $cycle = $group->cycle;
        $curriculum = $modality->assignment->curriculum;
        $week = $session?->curriculumWeek;
        $task = $week?->task;
        $date = $session?->session_date;
        $isTall = $modality->modality === 'TALL';
        $title = $isTall ? 'PLAN DE SESIÓN - PRÁCTICA DE TALLER' : 'PLAN DE SESIÓN - CONOCIMIENTOS TECNOLÓGICOS';

        $sheet->setTitle($this->safeSheetTitle($date?->format('d-m-Y') ?? 'Plan de Sesion'));
        $sheet->getSheetView()->setShowGridLines(false);
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:J1');
        $sheet->mergeCells('K1:N1');
        $sheet->setCellValue('A1', 'SENATI');
        $sheet->setCellValue('C1', $title);

        $sheet->mergeCells('A4:G4'); $sheet->mergeCells('H4:N4');
        $sheet->mergeCells('A5:G5'); $sheet->mergeCells('H5:N5');
        $sheet->mergeCells('A6:G6'); $sheet->mergeCells('H6:N6');
        $sheet->mergeCells('A7:N7'); $sheet->mergeCells('A8:N8'); $sheet->mergeCells('A9:N9');

        $this->setText($sheet, 'A4', 'DIRECCIÓN ZONAL: '.($curriculum?->professional_school ?: ''));
        $this->setText($sheet, 'H4', 'CFP/UCP/Escuela: '.(SystemSetting::valueFor('school_label', 'ETI') ?? 'ETI'));
        $this->setText($sheet, 'A5', 'INSTRUCTOR / FACILITADOR: '.auth()->user()?->name);
        $this->setText($sheet, 'H5', 'FECHA: '.($date?->format('d/m/Y') ?? 'Por programar'));
        $this->setText($sheet, 'A6', 'CARRERA: '.($curriculum?->career ?: ($course->career ?? '')));
        $this->setText($sheet, 'H6', 'MÓDULO: '.($curriculum?->module_code ?: ($curriculum?->module_name ?? '')));
        $this->setText($sheet, 'A7', 'MATERIA / CURSO: '.$course->name.' ('.($course->code ?? '').' / '.$modality->modality.' / NRC '.($modality->nrc ?? '').')');

        $taskTitle = $task
            ? trim(($task->task_code ? $task->task_code.': ' : '').$task->title)
            : ($session?->topic ?: 'Sesión de aprendizaje');
        $this->setText($sheet, 'A8', ($isTall ? 'TAREA / PROYECTO: ' : 'TEMA: ').$taskTitle);
        $this->setText($sheet, 'A9', 'OBJETIVOS: '.($session?->objective ?: ($curriculum?->general_objective ?? '')));

        $sheet->mergeCells('A11:B11'); $sheet->mergeCells('C11:J11'); $sheet->mergeCells('K11:N11');
        $sheet->setCellValue('A11', 'TIEMPO MIN.');
        $sheet->setCellValue('C11', 'ACTIVIDADES (PASOS A SEGUIR)');
        $sheet->setCellValue('K11', 'PREVENCIÓN DE RECURSOS');

        $talk = $date
            ? $group->fiveMinuteTalks()->whereDate('scheduled_date', $date->toDateString())->first()
            : null;
        $motivation = "1) MOTIVACIÓN (Presentar los objetivos, charla de cinco minutos)\n";
        if ($talk) {
            $motivation .= "\nCharla asignada: ".$talk->topic;
        }

        $topics = [];
        foreach ($week?->contentItems ?? collect() as $item) {
            if ($item->approved && in_array($item->category, ['TECHNOLOGICAL', 'COMPLEMENTARY', 'VIRTUAL'], true)) {
                $topics[] = '• '.$item->content;
            }
        }
        $operationLines = [];
        foreach ($task?->operations ?? [] as $operation) {
            $operationLines[] = '• '.trim(($operation->operation_code ? $operation->operation_code.': ' : '').$operation->description);
        }

        $sessionActivities = trim((string) ($session?->activities ?? ''));
        $sessionEvidence = trim((string) ($session?->evidence ?? ''));

        $sheet->mergeCells('A12:B14'); $sheet->mergeCells('C12:J14'); $sheet->mergeCells('K12:N20');
        $sheet->setCellValue('A12', 20);
        $sheet->setCellValue('C12', $motivation."\n".($session?->topic ?? $taskTitle)."\n".implode("\n", array_slice($topics, 0, 6)));
        $sheet->setCellValue('K12', "1) MATERIALES\nSegún las actividades y contenidos de la semana.\n\n2) HERRAMIENTAS\nSegún la modalidad y el entorno de aprendizaje.\n\n3) MÁQUINAS Y EQUIPOS\nRecursos disponibles en el aula o taller.\n\n4) INSTRUMENTOS\nLista de cotejo, evidencias y retroalimentación.");

        $teacherMinutes = $isTall ? 60 : 90;
        $studentMinutes = $isTall ? 120 : 90;
        $teacherRow = 16;
        $studentRow = 18;
        $evaluationRow = 20;
        $sheet->mergeCells('A'.$teacherRow.':B'.($teacherRow + 1));
        $sheet->mergeCells('C'.$teacherRow.':J'.($teacherRow + 1));
        $sheet->setCellValue('A'.$teacherRow, $teacherMinutes);
        $teacherText = $isTall
            ? "2) DEMOSTRACIÓN Y EJECUCIÓN (INSTRUCTOR)\n".implode("\n", array_slice($operationLines, 0, 8))
            : "2) DESARROLLO DEL TEMA (INSTRUCTOR)\n".implode("\n", array_slice($topics, 0, 8));
        if ($teacherText === "2) DESARROLLO DEL TEMA (INSTRUCTOR)\n" || $teacherText === "2) DEMOSTRACIÓN Y EJECUCIÓN (INSTRUCTOR)\n") {
            $teacherText .= $sessionActivities;
        }
        $sheet->setCellValue('C'.$teacherRow, $teacherText);

        $sheet->mergeCells('A'.$studentRow.':B'.($studentRow + 1));
        $sheet->mergeCells('C'.$studentRow.':J'.($studentRow + 1));
        $sheet->setCellValue('A'.$studentRow, $studentMinutes);
        $studentText = $isTall
            ? "3) PRÁCTICA GUIADA DEL ESTUDIANTE\n".$sessionActivities
            : "3) ACTIVIDADES DE APLICACIÓN\n".$sessionActivities;
        $sheet->setCellValue('C'.$studentRow, $studentText);

        $sheet->mergeCells('A'.$evaluationRow.':B'.($evaluationRow + 2));
        $sheet->mergeCells('C'.$evaluationRow.':J'.($evaluationRow + 2));
        $sheet->setCellValue('A'.$evaluationRow, 40);
        $sheet->setCellValue('C'.$evaluationRow, "4) EVALUACIÓN Y REFORZAMIENTO\n".$sessionEvidence);

        $sheet->mergeCells('K21:N22');
        $sheet->setCellValue('K21', "CRITERIOS TRANSVERSALES\nSeguridad, orden, participación y uso responsable de recursos según las actividades programadas.");
        $sheet->mergeCells('A23:N23');
        $sheet->setCellValue('A23', 'FORMATO EDITABLE EN EXCEL | REVISIÓN Y FIRMA SEGÚN PROCEDIMIENTO INSTITUCIONAL');

        $sheet->getColumnDimension('A')->setWidth(9);
        $sheet->getColumnDimension('B')->setWidth(3);
        foreach (range('C', 'J') as $column) $sheet->getColumnDimension($column)->setWidth(13);
        foreach (range('K', 'N') as $column) $sheet->getColumnDimension($column)->setWidth(12);
        foreach ([1=>26,4=>24,5=>26,6=>24,7=>26,8=>30,9=>36,11=>24,12=>36,13=>36,14=>36,16=>58,18=>58,20=>72,21=>48,23=>24] as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }

        $navy = '174B63'; $teal = '327C91'; $pale = 'EAF2F5';
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold'=>true, 'size'=>15, 'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>$navy]],
            'alignment' => ['vertical'=>Alignment::VERTICAL_CENTER, 'horizontal'=>Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:N9')->applyFromArray([
            'font' => ['size'=>10, 'color'=>['rgb'=>'222222']],
            'alignment' => ['vertical'=>Alignment::VERTICAL_CENTER, 'wrapText'=>true],
        ]);
        $sheet->getStyle('A11:N11')->applyFromArray([
            'font' => ['bold'=>true, 'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>$teal]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER, 'wrapText'=>true],
        ]);
        $sheet->getStyle('A12:N23')->applyFromArray([
            'alignment' => ['vertical'=>Alignment::VERTICAL_TOP, 'wrapText'=>true],
            'borders' => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['rgb'=>'8C979B']]],
            'font' => ['size'=>9, 'color'=>['rgb'=>'222222']],
        ]);
        $sheet->getStyle('A12:B14')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($pale);
        $sheet->getStyle('A16:B21')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($pale);
        $sheet->getStyle('C23:N23')->getFont()->setItalic(true);
        $sheet->getPageMargins()->setTop(0.35)->setBottom(0.35)->setLeft(0.25)->setRight(0.25);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(1);
        $sheet->getPageSetup()->setPrintArea('A1:N23');
    }

    private function buildTalkRegisterHeader(Worksheet $sheet, TeachingGroup $group): void
    {
        $sheet->mergeCells('C2:H2');
        $sheet->mergeCells('C3:H3');
        $sheet->setCellValue('C2', 'REGISTRO DE EJECUCIÓN DE LA CHARLA DE SEGURIDAD, SALUD EN EL TRABAJO O MEDIO AMBIENTE');
        $sheet->setCellValue('C3', '(CHARLAS FORMATIVAS DE 5 MINUTOS)');
        $sheet->setCellValue('C5', 'Zonal:');
        $sheet->setCellValue('D5', SystemSetting::valueFor('zonal_name', 'Independencia'));
        $sheet->setCellValue('C6', 'C.F.P.:');
        $sheet->setCellValue('D6', SystemSetting::valueFor('school_label', 'ETI'));
        $sheet->setCellValue('C7', 'Instructor:');
        $this->setText($sheet, 'D7', auth()->user()?->name ?? '');
        $sheet->setCellValue('C8', 'Grupo:');
        $this->setText($sheet, 'D8', $group->name);
        foreach (['C','D','E','F','G','H'] as $index => $column) {
            $sheet->setCellValue($column.'10', [
                'Programa de Formación', 'Grupo', 'Nº de Participantes', 'Fecha', 'Tema', 'Observaciones'
            ][$index]);
        }
        $sheet->getStyle('C2:H3')->applyFromArray([
            'font'=>['bold'=>true, 'color'=>['rgb'=>'174B63'], 'size'=>12],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER, 'wrapText'=>true],
        ]);
        $sheet->getStyle('C10:H10')->applyFromArray([
            'font'=>['bold'=>true, 'color'=>['rgb'=>'FFFFFF']],
            'fill'=>['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>'327C91']],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER, 'wrapText'=>true],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['rgb'=>'777777']]],
        ]);
        $sheet->getStyle('C10:H100')->applyFromArray([
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['rgb'=>'BBBBBB']]],
            'alignment'=>['vertical'=>Alignment::VERTICAL_TOP, 'wrapText'=>true],
        ]);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(52);
        $sheet->getColumnDimension('H')->setWidth(32);
        $sheet->getRowDimension(2)->setRowHeight(36);
        $sheet->getRowDimension(3)->setRowHeight(22);
    }

    private function buildTalkTopicsSheet(Worksheet $sheet, array $areas): void
    {
        $sheet->mergeCells('B2:E2');
        $sheet->mergeCells('B3:E3');
        $sheet->setCellValue('B2', 'TEMAS DE LAS CHARLAS DE SEGURIDAD, SALUD EN EL TRABAJO Y MEDIO AMBIENTE');
        $sheet->setCellValue('B3', '(CHARLAS FORMATIVAS DE 5 MINUTOS)');

        $environment = $areas[0] ?? ['name' => 'SISTEMA DE GESTIÓN AMBIENTAL', 'topics' => []];
        $safety = $areas[1] ?? ['name' => 'SEGURIDAD Y SALUD EN EL TRABAJO', 'topics' => []];

        $sheet->setCellValue('B5', mb_strtoupper($environment['name'] ?? 'SISTEMA DE GESTIÓN AMBIENTAL'));
        $row = 6;
        foreach (($environment['topics'] ?? []) as $topic) {
            $this->setText($sheet, 'B'.$row, $topic);
            $row++;
        }

        $sheet->setCellValue('B37', mb_strtoupper($safety['name'] ?? 'SEGURIDAD Y SALUD EN EL TRABAJO'));
        $safetyTopics = array_values($safety['topics'] ?? []);
        $leftTopics = array_slice($safetyTopics, 0, 16);
        $rightTopics = array_slice($safetyTopics, 16);

        $row = 38;
        foreach ($leftTopics as $topic) {
            $this->setText($sheet, 'B'.$row, $topic);
            $row++;
        }

        $row = 6;
        foreach ($rightTopics as $topic) {
            $this->setText($sheet, 'E'.$row, $topic);
            $row++;
        }

        $sheet->getColumnDimension('B')->setWidth(65);
        $sheet->getColumnDimension('E')->setWidth(65);
        $sheet->getStyle('B2:E3')->applyFromArray([
            'font'=>['bold'=>true, 'color'=>['rgb'=>'174B63']],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER, 'wrapText'=>true],
        ]);
        $sheet->getStyle('B5:B53')->getAlignment()->setWrapText(true);
        $sheet->getStyle('E6:E53')->getAlignment()->setWrapText(true);
        $sheet->getSheetView()->setShowGridLines(false);
    }

    private function setEvaluationMetadata(Worksheet $sheet, CourseModality $modality): void
    {
        $curriculum = $modality->assignment->curriculum;
        $course = $modality->assignment->course;
        $group = $modality->assignment->group;
        $this->setText($sheet, 'D15', $curriculum?->career ?: ($course->career ?? ''));
        $this->setText($sheet, 'D17', $curriculum?->semester ?: ($modality->semester ?? ''));
        $this->setText($sheet, 'D19', $course->name);
        $this->setText($sheet, 'D21', $modality->block_code ?: ($modality->block_reference ?: $modality->modality));
        $this->setText($sheet, 'D23', $modality->nrc ?? '');
        $sheet->setCellValue('D24', $modality->total_hours);
        $this->setText($sheet, 'D25', auth()->user()?->name ?? '');
        $this->setText($sheet, 'D12', SystemSetting::valueFor('school_label', 'ETI'));
    }

    private function appendSystemGrades(Spreadsheet $spreadsheet, CourseModality $modality, Collection $students): void
    {
        $evaluations = $modality->evaluations()->with('grades')->orderBy('evaluation_date')->orderBy('id')->get();
        if ($evaluations->isEmpty()) {
            return;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Calificaciones del sistema');
        $headers = ['N.º','ID institucional','Apellidos','Nombres','Evaluación','Fecha','Peso (%)','Máximo','Nota','Origen','Observaciones'];
        $sheet->fromArray($headers, null, 'A1');
        $row = 2;
        foreach ($students as $index => $enrollment) {
            foreach ($evaluations as $evaluation) {
                $grade = $evaluation->grades->firstWhere('enrollment_id', $enrollment->id);
                $values = [
                    $index + 1,
                    $enrollment->student?->institutional_id,
                    $enrollment->student?->surnames,
                    $enrollment->student?->given_names,
                    $evaluation->title,
                    $evaluation->evaluation_date?->format('d/m/Y'),
                    $evaluation->weight,
                    $evaluation->maximum_score,
                    $grade?->score,
                    $grade?->origin,
                    $grade?->observations,
                ];
                foreach ($values as $columnIndex => $value) {
                    $cell = $this->columnName($columnIndex + 1).$row;
                    if (is_string($value)) $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                    else $sheet->setCellValue($cell, $value);
                }
                $row++;
            }
        }
        $lastColumn = $this->columnName(count($headers));
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'327C91']],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'wrapText'=>true],
        ]);
        $sheet->getStyle('A2:'.$lastColumn.max(2,$row-1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->setAutoFilter('A1:'.$lastColumn.max(1,$row-1));
        $sheet->freezePane('A2');
        foreach (range(1,count($headers)) as $index) $sheet->getColumnDimension($this->columnName($index))->setAutoSize(true);
    }

    private function prepareCourseSheet(Spreadsheet $spreadsheet, CourseModality $modality): Worksheet
    {
        $coursesSheet = $spreadsheet->getSheetByName('Cursos');
        $first = null;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if ($sheet->getTitle() !== 'Cursos') {
                $first = $sheet;
                break;
            }
        }
        if (!$first) {
            $first = $spreadsheet->createSheet();
        }
        foreach (array_reverse($spreadsheet->getAllSheets()) as $sheet) {
            if ($sheet !== $first && $sheet !== $coursesSheet) {
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sheet));
            }
        }
        $courseTitle = $this->safeSheetTitle($modality->assignment->course->name);
        $first->setTitle($courseTitle);
        if ($coursesSheet) {
            $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($first));
        }
        return $first;
    }

    private function updateCourseLookup(Spreadsheet $spreadsheet, CourseModality $modality): void
    {
        $sheet = $spreadsheet->getSheetByName('Cursos');
        if (!$sheet) return;

        $course = $modality->assignment->course;
        $group = $modality->assignment->group;
        $cycle = $group->cycle;
        $sheet->setCellValue('A2', $course->name);
        $sheet->setCellValue('B2', $course->code);
        $sheet->setCellValue('C2', $modality->modality === 'TEC' ? $modality->block_code : null);
        $sheet->setCellValue('D2', $modality->modality === 'TEC' ? (float) $modality->nrc : null);
        $sheet->setCellValue('E2', $modality->modality === 'TALL' ? $modality->block_code : null);
        $sheet->setCellValue('F2', $modality->modality === 'TALL' ? $modality->block_code : null);
        $sheet->setCellValue('G2', $modality->modality === 'TALL' ? (float) $modality->nrc : null);
        $sheet->setCellValue('H2', ($cycle->start_date?->format('d/m/Y') ?? '').' hasta '.($cycle->end_date?->format('d/m/Y') ?? ''));
        for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {
            foreach (range('A','H') as $column) $sheet->setCellValue($column.$row, null);
        }
    }

    private function loadTemplate(string $relativePath): Spreadsheet
    {
        $path = base_path($relativePath);
        if (!is_file($path)) {
            throw new \RuntimeException('No se encontró la plantilla institucional: '.$relativePath);
        }
        return IOFactory::load($path);
    }

    private function datesForWeek(Collection $sessions, int $weekNumber): string
    {
        return $sessions
            ->filter(fn ($session) => (int) $session->curriculum_week_number === $weekNumber)
            ->pluck('session_date')
            ->filter()
            ->map(fn ($date) => $date->format('d/m/Y'))
            ->unique()
            ->implode("\n");
    }

    private function safeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/u', ' ', $title) ?: 'Sesion';
        return Str::limit(trim($title) ?: 'Sesion', 31, '');
    }

    private function setText(Worksheet $sheet, string $cell, ?string $value): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValue($cell, null);
            return;
        }
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }
}
