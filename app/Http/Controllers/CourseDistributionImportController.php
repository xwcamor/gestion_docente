<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Cycle;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Services\CourseDistributionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CourseDistributionImportController extends Controller
{
    public function create()
    {
        return view('distribution.create');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // La primera fila de esta hoja debe contener exactamente los encabezados
        // que CourseDistributionImportService::parse() reconoce.
        $courses = $spreadsheet->getActiveSheet();
        $courses->setTitle('Cursos');

        $headers = [
            'CURSO',
            'CODIGO',
            'BLOQUE TEC',
            'NRC TEC',
            'BLOQUE TALLER',
            'BLOQUE',
            'NRC TALLER',
            'FECHA',
        ];

        foreach ($headers as $index => $header) {
            $courses->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $courses->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '385548']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '294437']],
            ],
        ]);
        $courses->getRowDimension(1)->setRowHeight(32);

        foreach (['A' => 38, 'B' => 25, 'C' => 20, 'D' => 16, 'E' => 20, 'F' => 18, 'G' => 16, 'H' => 32] as $column => $width) {
            $courses->getColumnDimension($column)->setWidth($width);
        }

        // Prepara filas vacías para completar sin incluir cursos ficticios.
        $courses->getStyle('A2:H101')->getNumberFormat()->setFormatCode('@');
        $courses->getStyle('A2:H101')->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE5DF']],
            ],
        ]);
        $courses->freezePane('A2');
        $courses->setAutoFilter('A1:H101');
        $courses->getTabColor()->setRGB('385548');

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Instrucciones');
        $guide->mergeCells('A1:D1');
        $guide->setCellValue('A1', 'PLANTILLA PARA IMPORTAR CARGA LABORAL');
        $guide->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '385548']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $guide->getRowDimension(1)->setRowHeight(30);
        $guide->mergeCells('A2:D2');
        $guide->setCellValue('A2', 'Completa una fila por curso en la hoja Cursos. No escribas títulos encima de los encabezados ni cambies el nombre de la hoja.');
        $guide->getStyle('A2:D2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $guide->getRowDimension(2)->setRowHeight(34);

        $guideHeaders = ['COLUMNA', 'OBLIGATORIO', 'QUÉ DEBES COLOCAR', 'EJEMPLO (NO IMPORTAR)'];
        foreach ($guideHeaders as $index => $header) {
            $guide->setCellValueByColumnAndRow($index + 1, 4, $header);
        }
        $guide->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '60796B']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $instructions = [
            ['CURSO', 'Sí', 'Nombre oficial del curso.', 'Sistemas Operativos y Comunicación de Datos'],
            ['CODIGO', 'Sí', 'Código institucional que incluya año, ciclo y PIAD. Este dato permite reconocer el periodo.', '202620-PIAD-224'],
            ['BLOQUE TEC', 'Si tiene teoría', 'Código o identificador del bloque de teoría. Si se completa, se crea la modalidad TEC.', 'TEC-01'],
            ['NRC TEC', 'Si tiene teoría', 'NRC asignado a la oferta de teoría. Escríbelo como texto para conservar ceros iniciales.', '0011523'],
            ['BLOQUE TALLER', 'Si tiene taller', 'Completa este campo para indicar que el curso tiene modalidad de taller/práctica.', 'Sí'],
            ['BLOQUE', 'Si tiene taller', 'Código del bloque TALL. Si está vacío, se utiliza el valor de BLOQUE TALLER.', 'TALL-01'],
            ['NRC TALLER', 'Si tiene taller', 'NRC asignado a la oferta de taller/práctica. Se admite también el encabezado antiguo NRC TALLE.', '0011524'],
            ['FECHA', 'Sí', 'Rango común de inicio y fin para las modalidades activas del curso, en formato día/mes/año.', '01/08/2026 al 15/12/2026'],
        ];
        foreach ($instructions as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $guide->setCellValueByColumnAndRow($columnIndex + 1, 5 + $rowIndex, $value);
            }
        }

        $guide->getStyle('A5:D12')->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E1E8E2']]],
        ]);
        foreach (range(5, 12) as $row) {
            $guide->getRowDimension($row)->setRowHeight(42);
        }
        foreach (['A' => 22, 'B' => 18, 'C' => 72, 'D' => 48] as $column => $width) {
            $guide->getColumnDimension($column)->setWidth($width);
        }

        $guide->mergeCells('A14:D14');
        $guide->setCellValue('A14', 'REGLAS IMPORTANTES');
        $guide->getStyle('A14:D14')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '385548']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF1EC']],
        ]);
        $rules = [
            '1. En la hoja Cursos coloca solamente datos reales. Los ejemplos de esta hoja son orientativos y no se importan.',
            '2. Si el curso tiene TEC y TALL, completa ambas parejas de NRC y bloques. Si solo tiene una modalidad, deja vacíos los campos de la otra.',
            '3. La columna FECHA contiene un solo rango y el importador lo aplica a las modalidades activas de ese curso.',
            '4. Esta versión importa cursos, códigos, NRC, bloques y fechas. Los días, horas y ambientes del horario todavía se configuran después en cada modalidad.',
            '5. No elimines ni renombres las columnas de la hoja Cursos. Guarda el archivo como .xlsx antes de subirlo.',
        ];
        foreach ($rules as $index => $rule) {
            $row = 15 + $index;
            $guide->mergeCells("A{$row}:D{$row}");
            $guide->setCellValue("A{$row}", $rule);
            $guide->getStyle("A{$row}:D{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $guide->getRowDimension($row)->setRowHeight($index === 3 ? 32 : 24);
        }
        $guide->freezePane('A5');
        $guide->getTabColor()->setRGB('789184');

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'plantilla_carga_laboral.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    public function preview(Request $request, CourseDistributionImportService $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:15360',
        ], [
            'file.mimes' => 'Sube un Excel .xlsx o .xls.',
            'file.max' => 'El Excel no debe superar los 15 MB.',
        ]);

        $data = $importer->parse($request->file('file'));
        if (!$data['start_date'] || !$data['end_date']) {
            throw ValidationException::withMessages([
                'file' => 'El Excel no contiene ninguna fecha completa en la columna FECHA. Completa al menos un rango en la pestaña Cursos y vuelve a subirlo.',
            ]);
        }

        session(['distribution_import.preview' => $data]);
        return view('distribution.preview', ['data' => $data]);
    }

    public function confirm(Request $request)
    {
        $data = session('distribution_import.preview');
        abort_unless(is_array($data) && !empty($data['rows']), 419, 'La vista previa expiró. Vuelve a subir el Excel.');

        $validated = $request->validate([
            'group_name' => 'required|string|max:100',
        ]);

        $professor = auth()->user()->professor;
        $cycle = DB::transaction(function () use ($data, $validated, $professor) {
            $cycle = Cycle::firstOrCreate(
                ['professor_id' => $professor->id, 'academic_year' => $data['academic_year'], 'cycle_code' => $data['cycle_code']],
                ['start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'status' => 'active']
            );
            if ($cycle->start_date->gt($data['start_date'])) $cycle->update(['start_date' => $data['start_date']]);
            if ($cycle->end_date->lt($data['end_date'])) $cycle->update(['end_date' => $data['end_date']]);

            // Mantiene sincronizada la creación de grupos con la regla de no dejar
            // un ciclo sin grupos durante una eliminación concurrente.
            Cycle::query()->whereKey($cycle->id)->lockForUpdate()->firstOrFail();

            $group = TeachingGroup::firstOrCreate(
                ['cycle_id' => $cycle->id, 'professor_id' => $professor->id, 'name' => $validated['group_name']],
                ['assigned_at' => now()->toDateString(), 'notes' => 'Creado mediante importación de la pestaña Cursos.']
            );

            foreach ($data['rows'] as $row) {
                $course = Course::firstOrCreate(
                    ['professor_id' => $professor->id, 'code' => $row['code']],
                    ['name' => $row['name']]
                );
                if ($course->name !== $row['name']) $course->update(['name' => $row['name']]);

                $assignment = $group->assignments()->firstOrCreate(
                    ['course_id' => $course->id],
                    ['assigned_at' => now()->toDateString()]
                );

                foreach ($row['modalities'] as $item) {
                    $modality = CourseModality::firstOrNew([
                        'course_assignment_id' => $assignment->id,
                        'modality' => $item['type'],
                    ]);
                    $modality->fill([
                        'nrc' => $item['nrc'] ?: $modality->nrc,
                        'start_date' => $item['start_date'] ?: $modality->start_date?->toDateString(),
                        'end_date' => $item['end_date'] ?: $modality->end_date?->toDateString(),
                        'curriculum_start_week' => $modality->curriculum_start_week ?: 1,
                        'active' => true,
                        'period_label' => $data['academic_year'].'-'.$data['cycle_code'].'-'.$item['type'],
                    ]);
                    $modality->save();
                    // La hoja Cursos no contiene días ni horas: no se crean horarios ficticios.
                }
            }

            return $cycle;
        });

        session()->forget('distribution_import.preview');
        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Importación confirmada: ciclo, grupo, cursos, NRC y periodos registrados. Los horarios quedaron pendientes porque la pestaña Cursos no contiene días ni horas.');
    }
}