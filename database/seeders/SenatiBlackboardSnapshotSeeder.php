<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\InstitutionAcademicPeriod;
use App\Models\InstitutionCourse;
use App\Models\InstitutionCourseOffering;
use App\Models\InstitutionInstructionType;
use App\Models\InstitutionPeriodType;
use App\Models\InstitutionUnit;
use Illuminate\Database\Seeder;

class SenatiBlackboardSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::where('code', 'SENATI')->firstOrFail();
        $periodType = InstitutionPeriodType::where('institution_id', $institution->id)
            ->where('code', 'BLACKBOARD_PERIOD')->firstOrFail();

        $units = InstitutionUnit::where('institution_id', $institution->id)->get()->keyBy('code');
        $instructionTypes = InstitutionInstructionType::where('institution_id', $institution->id)->get()->keyBy('code');

        $periods = [
            '202510' => ['year' => 2025, 'unit' => 'FORMACION_PROFESIONAL', 'capture' => 'image(20261126-070246).png'],
            '202520' => ['year' => 2025, 'unit' => 'FORMACION_PROFESIONAL', 'capture' => 'image(20261126-070358).png'],
            '202610' => ['year' => 2026, 'unit' => 'FORMACION_PROFESIONAL', 'capture' => 'image(20261126-070427).png'],
            '202620' => ['year' => 2026, 'unit' => 'FORMACION_PROFESIONAL', 'capture' => 'image(20261126-070608).png'],
            '202601' => ['year' => 2026, 'unit' => 'ETI', 'capture' => 'image(20261126-070739).png'],
        ];

        $periodModels = [];
        foreach ($periods as $code => $data) {
            $periodModels[$code] = InstitutionAcademicPeriod::firstOrCreate(
                ['institution_period_type_id' => $periodType->id, 'code' => $code],
                [
                    'label' => $code,
                    'academic_year' => $data['year'],
                    'sequence_number' => null,
                    'starts_on' => null,
                    'ends_on' => null,
                    'status' => 'reference',
                    'source_label' => $units[$data['unit']]->source_label,
                    'metadata' => [
                        'source' => 'Blackboard screenshots supplied in the conversation',
                        'capture' => $data['capture'],
                        'period_semester_equivalence' => 'not_inferred',
                    ],
                ]
            );
        }

        $teacher = 'CARLOS ALBERTO MORALES LARRAÑAGA';
        $courses = [
            // 202510 — Formación Profesional.
            ['202510', 'PDSD-331', 'DATABASE DESIGN AND PROGRAMMING WITH SQL (ORACLE)', [['TEC','13010','Finalizado'], ['TAL','13011','Finalizado']], $teacher],
            ['202510', 'PDSD-332', 'DESARROLLO DE SOFTWARE I', [['TEC','13012','Finalizado'], ['TAL','13013','Finalizado']], $teacher],
            ['202510', 'PDSD-329', 'FUNDAMENTOS DE PROGRAMACION WEB', [['TEC','12995','Finalizado'], ['TAL','12999','Finalizado']], $teacher],
            ['202510', 'PDSD-330', 'JAVA FOUNDATIONS (ORACLE)', [['TEC','13000','Finalizado'], ['TAL','13009','Finalizado']], $teacher],
            ['202510', 'PDSD-333', 'TALLER DE PROGRAMACION', [['TEC','13014','Finalizado'], ['TAL','12996','Finalizado']], $teacher],

            // 202520 — Formación Profesional.
            ['202520', 'PDSD-236', 'CPA / CLA PROGRAMMING ESSENTIALS (CISCO)', [['TEC','35784','Finalizado'], ['TAL','35785','Finalizado']], $teacher],
            ['202520', 'PDSD-237', 'DATABASES FOUNDATIONS (ORACLE)', [['TEC','35786','Finalizado'], ['TAL','7954','Finalizado']], $teacher],
            ['202520', 'PDSD-233', 'FUNDAMENTOS DE PROGRAMACION', [['TEC','7953','Finalizado'], ['TAL','7957','Finalizado']], $teacher],
            ['202520', 'PDSD-235', 'JAVA FUNDAMENTALS (ORACLE)', [['TEC','35783','Finalizado'], ['TAL','35782','Finalizado']], $teacher],
            ['202520', 'PIAD-220', 'RED HAT SYSTEM ADMINISTRATION I (LINUX RED HAT)', [['TEC','7792','Finalizado'], ['TAL','8431','Finalizado']], 'MÚLTIPLES PROFESORES'],
            ['202520', 'PIAD-234', 'TECNOLOGIAS WEB', [['TEC','35781','Finalizado'], ['TAL','35780','Finalizado']], $teacher],

            // 202610 — Formación Profesional.
            ['202610', 'PIAD-216', 'ALGORITMIA PARA EL DESARROLLO DE PROGRAMAS', [['TEC','4525','Finalizado'], ['TAL','4536','Finalizado']], $teacher],
            ['202610', 'PIAD-219', 'DATABASE FOUNDATIONS (ORACLE)', [['TEC','4595','Finalizado'], ['TAL','4584','Finalizado']], $teacher],
            ['202610', 'PIAD-218', 'FUNDAMENTOS DE PROGRAMACION WEB', [['TEC','4573','Finalizado'], ['TAL','4561','Finalizado']], $teacher],
            ['202610', 'PIAD-217', 'JAVA FOUNDATIONS (ORACLE)', [['TEC','4547','Finalizado'], ['TAL','4558','Finalizado']], $teacher],
            ['202610', 'PIAD-220', 'RED HAT SYSTEM ADMINISTRATION I (LINUX RED HAT)', [['TEC','4617','Finalizado'], ['TAL','4606','Finalizado']], $teacher],

            // 202620 — los NRC dobles TEC se conservan como secciones distintas.
            ['202620', 'PIAD-221', 'ALGORITMOS Y PROGRAMACIÓN PARA DESARROLLO DE SOFTWARE', [['TEC','11516','Abierto'], ['TEC','11202','Abierto'], ['TAL','11517','Finalizado']], $teacher],
            ['202620', 'PIAD-223', 'DATABASE FOUNDATIONS (ORACLE)', [['TEC','11539','Abierto'], ['TEC','11521','Abierto'], ['TAL','11522','Finalizado']], $teacher],
            ['202620', 'PIAD-222', 'FRONTEND DEVELOPER WEB', [['TEC','11519','Abierto'], ['TEC','11518','Abierto'], ['TAL','11520','Finalizado']], $teacher],
            ['202620', 'PIAD-224', 'SISTEMAS OPERATIVOS Y COMUNICACIÓN DE DATOS', [['TEC','11560','Abierto'], ['TEC','11523','Abierto'], ['TAL','11524',null]], $teacher],

            // 202601 — ETI. Se conserva el rótulo de Blackboard sin inferir su jerarquía.
            ['202601', 'PCCU-1008', 'CONCEPTOS DE GRANDES MODELOS LINGÜÍSTICOS (LLM)', [['TAL','4526','Finalizado']], $teacher],
            ['202601', 'PCCU-1010', 'CONCEPTOS DE LA IA GENERATIVA', [['TAL','4528','Finalizado']], $teacher],
            ['202601', 'PCCU-1009', 'ÉTICA DE LA IA', [['TAL','4527','Finalizado']], $teacher],
        ];

        foreach ($courses as [$periodCode, $courseCode, $courseName, $sections, $instructor]) {
            $period = $periodModels[$periodCode];
            $unit = $units[$periods[$periodCode]['unit']];

            $groupPattern = $periodCode === '202620' && $courseCode === 'PIAD-223'
                ? ['observed' => 'two_groups_one_with_tec_and_tal_one_with_tec_only', 'group_count' => 2, 'pairing_confirmed' => false]
                : null;

            $course = InstitutionCourse::firstOrCreate(
                ['institution_id' => $institution->id, 'external_code' => $courseCode],
                [
                    'institution_unit_id' => $unit->id,
                    'name' => $courseName,
                    'catalog_status' => 'reference',
                    'verification_status' => 'captured_from_blackboard',
                    'metadata' => [
                        'catalog_code_origin' => 'Blackboard course identifier',
                        'name_is_snapshot' => true,
                        'observed_group_pattern' => $groupPattern,
                    ],
                ]
            );

            foreach ($sections as [$instructionCode, $nrc, $statusLabel]) {
                $instructionType = $instructionTypes[$instructionCode] ?? null;
                $status = match ($statusLabel) {
                    'Abierto' => 'open',
                    'Finalizado' => 'finished',
                    default => 'unknown',
                };
                $identifier = sprintf('%s-%s-%s-NRC_%s', $periodCode, $courseCode, $instructionCode, $nrc);

                InstitutionCourseOffering::firstOrCreate(
                    ['institution_academic_period_id' => $period->id, 'nrc' => $nrc],
                    [
                        'institution_id' => $institution->id,
                        'institution_unit_id' => $unit->id,
                        'institution_course_id' => $course->id,
                        'institution_instruction_type_id' => $instructionType?->id,
                        'teaching_group_id' => null,
                        'external_identifier' => $identifier,
                        'course_code_snapshot' => $courseCode,
                        'course_name_snapshot' => $courseName,
                        'source_status' => $status,
                        'source_status_label' => $statusLabel,
                        'instructor_label' => $instructor,
                        'group_mapping_status' => 'pending_review',
                        'source_capture' => $periods[$periodCode]['capture'],
                        'source_metadata' => [
                            'identifier_from_blackboard' => $identifier,
                            'group_mapping' => 'Not inferred from screenshot. NRC remains unassigned to a teaching group until confirmed.',
                        ],
                    ]
                );
            }
        }
    }
}
