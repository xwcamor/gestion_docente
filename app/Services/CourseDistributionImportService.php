<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CourseDistributionImportService
{
    public function parse(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = null;
        foreach ($spreadsheet->getWorksheetIterator() as $candidate) {
            if (mb_strtolower(trim($candidate->getTitle())) === 'cursos') {
                $sheet = $candidate;
                break;
            }
        }

        if (!$sheet) {
            throw ValidationException::withMessages([
                'file' => 'No encontré una pestaña llamada "Cursos" en el Excel. Verifica que estés subiendo uno de los formatos institucionales.',
            ]);
        }

        $headers = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[$this->normalize((string) $cell->getValue())] = $cell->getColumn();
            }
        }

        $required = ['curso', 'codigo', 'bloque tec', 'nrc tec', 'bloque taller', 'bloque', 'nrc talle', 'fecha'];
        $missing = array_values(array_filter($required, fn ($header) => !isset($headers[$header])));
        if ($missing) {
            throw ValidationException::withMessages([
                'file' => 'La pestaña Cursos no tiene todas las columnas esperadas: '.implode(', ', $missing).'.',
            ]);
        }

        $rows = [];
        $errors = [];
        $warnings = [];
        $maxRow = $sheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $maxRow; $rowNumber++) {
            $value = fn ($key) => trim((string) ($sheet->getCell($headers[$key].$rowNumber)->getFormattedValue() ?? ''));
            $name = $value('curso');
            $code = $value('codigo');
            $tecBlock = $value('bloque tec');
            $tecNrc = $value('nrc tec');
            $tallBlock = $value('bloque taller');
            $tallCode = $value('bloque');
            $tallNrc = $value('nrc talle');
            $dateText = $value('fecha');

            if ($name === '' && $code === '' && $tecBlock === '' && $tallBlock === '' && $tallCode === '') {
                continue;
            }
            if ($name === '') {
                $errors[] = "Fila {$rowNumber}: falta el nombre del curso.";
                continue;
            }

            $period = $this->parsePeriod($dateText);
            $identity = $code ?: ($tecBlock ?: ($tallCode ?: $tallBlock));
            if ($identity === '') {
                $errors[] = "Fila {$rowNumber}: falta CODIGO y también los bloques TEC/TALL para identificar el curso.";
                continue;
            }

            $modalities = [];
            if ($tecNrc !== '' || $tecBlock !== '') {
                $modalities[] = [
                    'type' => 'TEC', 'nrc' => $this->cleanNrc($tecNrc),
                    'block_code' => $tecBlock, 'start_date' => $period['start'],
                    'end_date' => $period['end'],
                ];
            }
            if ($tallNrc !== '' || $tallBlock !== '' || $tallCode !== '') {
                $modalities[] = [
                    'type' => 'TALL', 'nrc' => $this->cleanNrc($tallNrc),
                    'block_code' => $tallCode ?: $tallBlock, 'start_date' => $period['start'],
                    'end_date' => $period['end'],
                ];
            }
            if (!$modalities) {
                $errors[] = "Fila {$rowNumber}: no hay NRC ni bloque TEC/TALL.";
                continue;
            }
            if (!$period['start'] || !$period['end']) {
                $warnings[] = "Fila {$rowNumber} ({$name}): no tiene fechas completas; se importará sin periodo y deberás completarlo.";
            }
            foreach ($modalities as $modality) {
                if (!$modality['nrc']) {
                    $warnings[] = "Fila {$rowNumber} ({$name}): modalidad {$modality['type']} sin NRC.";
                }
            }

            $rows[] = [
                'source_row' => $rowNumber, 'name' => $name, 'code' => $identity,
                'source_code' => $code, 'modalities' => $modalities,
            ];
        }

        if (!$rows) {
            throw ValidationException::withMessages([
                'file' => 'La pestaña Cursos no contiene filas de cursos válidas. Revisa los encabezados y los datos.',
            ]);
        }

        $allDates = collect($rows)->flatMap(fn ($row) => collect($row['modalities'])->flatMap(fn ($modality) => array_filter([$modality['start_date'], $modality['end_date']])));
        $firstCode = collect($rows)->flatMap(fn ($row) => collect($row['modalities'])->pluck('block_code'))->first(fn ($value) => preg_match('/(20\d{2})(\d{2})-PIAD/i', (string) $value));
        $cycleMatch = [];
        if ($firstCode) {
            preg_match('/(20\d{2})(\d{2})-PIAD/i', $firstCode, $cycleMatch);
        }
        if (!$cycleMatch) {
            preg_match('/(20\d{2})(\d{2})-PIAD/i', implode(' ', array_column($rows, 'code')), $cycleMatch);
        }
        if (!$cycleMatch) {
            throw ValidationException::withMessages([
                'file' => 'No pude identificar el año y ciclo a partir de los códigos 202620-PIAD-... de la pestaña Cursos.',
            ]);
        }

        $dateValues = $allDates->values();
        return [
            'academic_year' => (int) $cycleMatch[1],
            'cycle_code' => $cycleMatch[2],
            'start_date' => $dateValues->isNotEmpty() ? min($dateValues->all()) : null,
            'end_date' => $dateValues->isNotEmpty() ? max($dateValues->all()) : null,
            'rows' => $rows, 'errors' => $errors, 'warnings' => array_values(array_unique($warnings)),
            'course_count' => count($rows),
            'modality_count' => collect($rows)->sum(fn ($row) => count($row['modalities'])),
            'source_sheet' => $sheet->getTitle(),
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
        return preg_replace('/\s+/', ' ', $value);
    }

    private function cleanNrc(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : preg_replace('/\.0$/', '', $value);
    }

    private function parsePeriod(string $value): array
    {
        if ($value === '') return ['start' => null, 'end' => null];
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}).*?(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/u', $value, $matches)) {
            return [
                'start' => $this->parseDate($matches[1]),
                'end' => $this->parseDate($matches[2]),
            ];
        }
        return ['start' => null, 'end' => null];
    }

    private function parseDate(string $value): ?string
    {
        foreach (['d/m/Y', 'd-m-Y'] as $format) {
            try { return Carbon::createFromFormat($format, $value)->toDateString(); }
            catch (\Throwable) {}
        }
        return null;
    }
}