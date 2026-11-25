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

        // Acepta el encabezado corregido y conserva compatibilidad con archivos anteriores.
        if (!isset($headers['nrc talle'])) {
            foreach (['nrc taller', 'nrc tall'] as $alias) {
                if (isset($headers[$alias])) {
                    $headers['nrc talle'] = $headers[$alias];
                    break;
                }
            }
        }

        $required = ['curso', 'codigo', 'bloque tec', 'nrc tec', 'bloque taller', 'bloque', 'nrc talle'];
        $missing = array_values(array_filter($required, fn ($header) => !isset($headers[$header])));
        if ($missing) {
            throw ValidationException::withMessages([
                'file' => 'La pestaña Cursos no tiene todas las columnas esperadas: '.implode(', ', $missing).'.',
            ]);
        }

        $hasCommonDates = isset($headers['fecha']);
        $hasSeparateDates = isset($headers['fecha tec']) && isset($headers['fecha taller']);
        if (!$hasCommonDates && !$hasSeparateDates) {
            throw ValidationException::withMessages([
                'file' => 'La pestaña Cursos debe incluir FECHA (formato anterior) o ambas columnas FECHA TEC y FECHA TALLER.',
            ]);
        }

        $rows = [];
        $errors = [];
        $warnings = [];
        $maxRow = $sheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $maxRow; $rowNumber++) {
            $value = fn ($key) => isset($headers[$key])
                ? trim((string) ($sheet->getCell($headers[$key].$rowNumber)->getFormattedValue() ?? ''))
                : '';
            $name = $value('curso');
            $code = $value('codigo');
            $tecBlock = $value('bloque tec');
            $tecNrc = $value('nrc tec');
            $tallBlock = $value('bloque taller');
            $tallCode = $value('bloque');
            $tallNrc = $value('nrc talle');
            $commonDateText = $value('fecha');
            $tecDateText = $value('fecha tec');
            $tallDateText = $value('fecha taller');
            $commonPeriod = $this->parsePeriod($commonDateText);
            $tecPeriod = $tecDateText !== '' ? $this->parsePeriod($tecDateText) : $commonPeriod;
            $tallPeriod = $tallDateText !== '' ? $this->parsePeriod($tallDateText) : $commonPeriod;

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
                    'block_code' => $tecBlock, 'start_date' => $tecPeriod['start'],
                    'end_date' => $tecPeriod['end'],
                ];
            }
            if ($tallNrc !== '' || $tallBlock !== '' || $tallCode !== '') {
                $modalities[] = [
                    'type' => 'TALL', 'nrc' => $this->cleanNrc($tallNrc),
                    'block_code' => $tallCode ?: $tallBlock, 'start_date' => $tallPeriod['start'],
                    'end_date' => $tallPeriod['end'],
                ];
            }
            if (!$modalities) {
                $errors[] = "Fila {$rowNumber}: no hay NRC ni bloque TEC/TALL.";
                continue;
            }
            foreach ($modalities as $modality) {
                $modalityDateText = $modality['type'] === 'TEC'
                    ? ($tecDateText ?: $commonDateText)
                    : ($tallDateText ?: $commonDateText);

                if ($modalityDateText !== '' && (!$modality['start_date'] || !$modality['end_date'])) {
                    $errors[] = "Fila {$rowNumber} ({$name}): la fecha de {$modality['type']} no tiene el formato DD/MM/AAAA al DD/MM/AAAA.";
                } elseif (
                    $modality['start_date']
                    && $modality['end_date']
                    && $modality['end_date'] < $modality['start_date']
                ) {
                    $errors[] = "Fila {$rowNumber} ({$name}): la fecha final de {$modality['type']} es anterior a la fecha inicial.";
                }
                if (!$modality['start_date'] || !$modality['end_date']) {
                    $warnings[] = "Fila {$rowNumber} ({$name}): modalidad {$modality['type']} sin rango de fechas completo; deberás completarlo.";
                }
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

        $scheduleCount = $this->parseSchedules($spreadsheet, $rows, $warnings, $errors);

        $completeDateValues = collect($rows)
            ->flatMap(fn ($row) => collect($row['modalities']))
            ->filter(fn ($modality) => filled($modality['start_date']) && filled($modality['end_date']))
            ->flatMap(fn ($modality) => [$modality['start_date'], $modality['end_date']])
            ->values();
        // CODIGO identifica el periodo académico. BLOQUE TEC/TALL puede conservar un periodo interno distinto.
        $sourceCodes = collect($rows)->pluck('source_code')->filter()->all();
        $cycleMatch = [];
        preg_match('/(20\d{2})(\d{2})-PIAD/i', implode(' ', $sourceCodes), $cycleMatch);
        if (!$cycleMatch) {
            $blockCodes = collect($rows)->flatMap(fn ($row) => collect($row['modalities'])->pluck('block_code'))->filter()->all();
            preg_match('/(20\d{2})(\d{2})-PIAD/i', implode(' ', $blockCodes), $cycleMatch);
        }
        if (!$cycleMatch) {
            throw ValidationException::withMessages([
                'file' => 'No pude identificar el año y ciclo a partir de los códigos 202620-PIAD-... de la pestaña Cursos.',
            ]);
        }

        $hasCompletePeriod = $completeDateValues->isNotEmpty();

        return [
            'academic_year' => (int) $cycleMatch[1],
            'cycle_code' => $cycleMatch[2],
            'start_date' => $hasCompletePeriod ? min($completeDateValues->all()) : null,
            'end_date' => $hasCompletePeriod ? max($completeDateValues->all()) : null,
            'has_complete_period' => $hasCompletePeriod,
            'rows' => $rows, 'errors' => $errors, 'warnings' => array_values(array_unique($warnings)),
            'course_count' => count($rows),
            'modality_count' => collect($rows)->sum(fn ($row) => count($row['modalities'])),
            'schedule_count' => $scheduleCount,
            'source_sheet' => $sheet->getTitle(),
        ];
    }

    private function parseSchedules($spreadsheet, array &$rows, array &$warnings, array &$errors): int
    {
        $sheet = null;
        foreach ($spreadsheet->getWorksheetIterator() as $candidate) {
            if (mb_strtolower(trim($candidate->getTitle())) === 'horarios') {
                $sheet = $candidate;
                break;
            }
        }

        foreach ($rows as &$course) {
            foreach ($course['modalities'] as &$modality) {
                $modality['schedules'] = [];
            }
            unset($modality);
        }
        unset($course);

        if (!$sheet) {
            $warnings[] = 'No se encontró la pestaña Horarios. Los días, horas y ambientes deberán configurarse después en cada modalidad.';
            return 0;
        }

        $headers = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[$this->normalize((string) $cell->getValue())] = $cell->getColumn();
            }
        }

        $required = ['codigo', 'modalidad', 'nrc', 'dia', 'desde', 'hasta', 'tipo', 'ambiente'];
        $missing = array_values(array_filter($required, fn ($header) => !isset($headers[$header])));
        if ($missing) {
            $warnings[] = 'La pestaña Horarios existe, pero no tiene las columnas requeridas: '.implode(', ', $missing).'. No se importaron horarios.';
            return 0;
        }

        $courseIndexes = [];
        foreach ($rows as $courseIndex => $course) {
            $key = $this->normalize((string) ($course['source_code'] ?: $course['code']));
            $courseIndexes[$key] = $courseIndex;
        }

        $dayMap = [
            'lunes' => 1, 'lun' => 1, 'martes' => 2, 'mar' => 2,
            'miercoles' => 3, 'mie' => 3, 'miércoles' => 3,
            'jueves' => 4, 'jue' => 4, 'viernes' => 5, 'vie' => 5,
            'sabado' => 6, 'sab' => 6, 'domingo' => 7, 'dom' => 7,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
        ];

        $count = 0;
        $maxRow = $sheet->getHighestDataRow();
        for ($rowNumber = 2; $rowNumber <= $maxRow; $rowNumber++) {
            $value = fn ($key) => isset($headers[$key])
                ? trim((string) ($sheet->getCell($headers[$key].$rowNumber)->getFormattedValue() ?? ''))
                : '';
            $code = $value('codigo');
            $modalityName = strtoupper($this->normalize($value('modalidad')));
            $nrc = $this->cleanNrc($value('nrc') );
            $dayText = $this->normalize($value('dia'));
            $startTime = $this->parseTime($value('desde'));
            $endTime = $this->parseTime($value('hasta'));
            $instructionText = $this->normalize($value('tipo'));
            $classroom = $value('ambiente');
            $intervalText = $value('intervalo semanas');
            $repeatIntervalWeeks = $intervalText === '' ? 1 : (preg_match('/^\\d+$/', $intervalText) ? (int) $intervalText : 0);

            if ($code === '' && $modalityName === '' && $dayText === '' && $value('desde') === '' && $value('hasta') === '') {
                continue;
            }

            $courseKey = $this->normalize($code);
            if (!isset($courseIndexes[$courseKey])) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: el CODIGO no coincide con ningún curso de la hoja Cursos.";
                continue;
            }

            $courseIndex = $courseIndexes[$courseKey];
            $modalityType = match ($modalityName) {
                'TEC', 'TEORIA' => 'TEC',
                'TALL', 'TALLER', 'PRACTICA', 'PRACTICA DE TALLER' => 'TALL',
                default => null,
            };
            if (!$modalityType) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: MODALIDAD debe ser TEC o TALL.";
                continue;
            }

            $modalityIndex = null;
            foreach ($rows[$courseIndex]['modalities'] as $index => $candidate) {
                if ($candidate['type'] === $modalityType) {
                    $modalityIndex = $index;
                    break;
                }
            }
            if ($modalityIndex === null) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: el curso {$code} no tiene modalidad {$modalityType} en la hoja Cursos.";
                continue;
            }

            $expectedNrc = $rows[$courseIndex]['modalities'][$modalityIndex]['nrc'];
            if ($nrc && $expectedNrc && $nrc !== $expectedNrc) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: el NRC {$nrc} no coincide con el NRC {$expectedNrc} del curso {$code} ({$modalityType}).";
                continue;
            }

            if (!isset($dayMap[$dayText])) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: DIA debe ser un día de la semana (LUNES a DOMINGO).";
                continue;
            }
            if (!$startTime || !$endTime || $endTime <= $startTime) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: revisa DESDE y HASTA; usa horas HH:MM y una hora final posterior.";
                continue;
            }
            if ((int) substr($startTime, 3, 2) % 15 !== 0 || (int) substr($endTime, 3, 2) % 15 !== 0) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: las horas deben estar en intervalos de 15 minutos.";
                continue;
            }
            if ($repeatIntervalWeeks < 1 || $repeatIntervalWeeks > 52) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: INTERVALO SEMANAS debe ser un número entre 1 y 52.";
                continue;
            }

            $instructionType = match ($instructionText) {
                'TEORIA', 'THEORY' => 'THEORY',
                'TALLER', 'PRACTICA', 'PRACTICA DE TALLER', 'LAB', 'LABORATORIO' => 'LAB',
                default => null,
            };
            if (!$instructionType) {
                $errors[] = "Hoja Horarios, fila {$rowNumber}: TIPO debe ser TEORIA o TALLER/PRACTICA.";
                continue;
            }

            $rows[$courseIndex]['modalities'][$modalityIndex]['schedules'][] = [
                'day_of_week' => $dayMap[$dayText],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'classroom' => $classroom !== '' ? $classroom : null,
                'instruction_type' => $instructionType,
                'block_type' => 'CLASS',
                'repeat_interval_weeks' => $repeatIntervalWeeks,
                'nrc' => $expectedNrc ?: $nrc,
            ];
            $count++;
        }

        foreach ($rows as $course) {
            foreach ($course['modalities'] as $modality) {
                $grouped = collect($modality['schedules'] ?? [])->groupBy('day_of_week');
                foreach ($grouped as $day => $blocks) {
                    $ordered = $blocks->sortBy('start_time')->values();
                    for ($index = 1; $index < $ordered->count(); $index++) {
                        if ($ordered[$index]['start_time'] < $ordered[$index - 1]['end_time']) {
                            $errors[] = "Horarios del curso {$course['name']} ({$modality['type']}): hay bloques que se cruzan el mismo día.";
                            break;
                        }
                    }
                }
            }
        }

        if ($count === 0) {
            $warnings[] = 'La pestaña Horarios no contiene bloques válidos. Los horarios deberán configurarse en cada modalidad.';
        }

        return $count;
    }

    private function parseTime(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            if ($hour <= 23 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return null;
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