<?php

namespace App\Services;

use Illuminate\Support\Str;

class CourseCodeParser
{
    private const PATTERN = '/(?<![A-Z0-9])(?:(?<period>20\d{4})-)?(?<program>[A-Z][A-Z0-9]{1,9})-(?<course>[A-Z0-9]{1,12})-(?<modality>TEC|TALL|TAL)(?:[-_\s]*NRC[_\s-]*(?<nrc>\d{3,12}))?(?![A-Z0-9])/i';

    /**
     * Accepts one full code or multiple modality codes for the same course,
     * separated by semicolons, new lines or other punctuation.
     */
    public function parse(string $sourceCode): array
    {
        $sourceCode = trim($sourceCode);

        if ($sourceCode === '') {
            return [
                'source_code' => '',
                'base_code' => '',
                'period' => null,
                'program' => null,
                'course_number' => null,
                'has_structured_code' => false,
                'detected_modalities' => [],
                'nrc_by_modality' => [],
                'matches' => [],
                'errors' => [],
            ];
        }

        preg_match_all(self::PATTERN, $sourceCode, $rawMatches, PREG_SET_ORDER);

        $matches = [];
        $baseCodes = [];
        $periods = [];
        $programs = [];
        $courseNumbers = [];
        $modalityNrcs = ['TEC' => [], 'TALL' => []];

        foreach ($rawMatches as $match) {
            $period = trim((string) ($match['period'] ?? ''));
            $program = Str::upper(trim((string) ($match['program'] ?? '')));
            $courseNumber = Str::upper(trim((string) ($match['course'] ?? '')));
            $modality = Str::upper(trim((string) ($match['modality'] ?? '')));
            $modality = $modality === 'TAL' ? 'TALL' : $modality;
            $nrc = trim((string) ($match['nrc'] ?? ''));

            $baseParts = array_filter([$period, $program, $courseNumber], fn ($part) => $part !== '');
            $baseCode = implode('-', $baseParts);

            $baseCodes[$baseCode] = true;
            if ($period !== '') {
                $periods[$period] = true;
            }
            $programs[$program] = true;
            $courseNumbers[$courseNumber] = true;

            if ($nrc !== '') {
                $modalityNrcs[$modality][$nrc] = true;
            }

            $matches[] = [
                'base_code' => $baseCode,
                'modality' => $modality,
                'nrc' => $nrc !== '' ? $nrc : null,
                'source_fragment' => $match[0],
            ];
        }

        $errors = [];

        if (count($baseCodes) > 1) {
            $errors[] = 'El campo código contiene identificadores de cursos diferentes. Ingresa juntos solo los códigos TEC/TAL del mismo curso.';
        }

        foreach ($modalityNrcs as $modality => $nrcs) {
            if (count($nrcs) > 1) {
                $errors[] = "Se encontraron varios NRC distintos para {$modality}. Ingresa un solo NRC por modalidad.";
            }
        }

        $detectedModalities = array_values(array_unique(array_column($matches, 'modality')));
        $nrcByModality = [];

        foreach ($modalityNrcs as $modality => $nrcs) {
            if (count($nrcs) === 1) {
                $nrcByModality[$modality] = array_key_first($nrcs);
            }
        }

        $firstMatch = $matches[0] ?? null;
        $baseCode = $firstMatch['base_code'] ?? Str::upper($sourceCode);

        return [
            'source_code' => $sourceCode,
            'base_code' => $baseCode,
            'period' => count($periods) === 1 ? array_key_first($periods) : null,
            'program' => count($programs) === 1 ? array_key_first($programs) : null,
            'course_number' => count($courseNumbers) === 1 ? array_key_first($courseNumbers) : null,
            'has_structured_code' => $matches !== [],
            'detected_modalities' => $detectedModalities,
            'nrc_by_modality' => $nrcByModality,
            'matches' => $matches,
            'errors' => $errors,
        ];
    }
}
