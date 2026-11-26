<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\AcademicProgramVersion;
use App\Models\Institution;
use App\Models\InstitutionFeature;
use App\Models\InstitutionInstructionType;
use App\Models\InstitutionPeriodType;
use App\Models\InstitutionUnit;
use Illuminate\Database\Seeder;

class SenatiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::firstOrCreate(
            ['code' => 'SENATI'],
            [
                'slug' => 'senati',
                'name' => 'SENATI',
                'legal_name' => 'Servicio Nacional de Adiestramiento en Trabajo Industrial',
                'country_code' => 'PE',
                'timezone' => 'America/Lima',
                'status' => 'active',
                'settings' => [
                    'catalog_completeness' => 'partial_reference',
                    'career_duration_options_semesters' => [4, 5, 6, 8],
                    'semester_duration_weeks' => 17,
                    'reported_professional_careers_2025' => 91,
                    'reported_professional_schools_2025' => 14,
                    'notes' => [
                        'La cantidad de semestres se define por carrera y versión de malla, no por tenant.',
                        'Los códigos 202510, 202520, 202610, 202620 y 202601 se conservan como periodos de Blackboard; no equivalen al semestre de avance curricular.',
                        'El catálogo general de carreras requiere una carga oficial vigente completa; no se infieren carreras a partir de los prefijos PDSD, PIAD o PCCU.',
                    ],
                ],
            ]
        );

        $units = [];
        $unitDefinitions = [
            [
                'key' => 'FORMACION_PROFESIONAL',
                'name' => 'Formación Profesional',
                'unit_type' => 'blackboard_context',
                'source_label' => 'Formación Profesional',
                'verification_status' => 'observed_in_blackboard',
                'source_year' => 2026,
                'is_active' => true,
                'metadata' => ['meaning' => 'Rótulo de agrupación observado en Blackboard; no clasificado como carrera.'],
            ],
            [
                'key' => 'ETI',
                'name' => 'ETI',
                'unit_type' => 'blackboard_context',
                'source_label' => 'ETI',
                'verification_status' => 'source_label_unclassified',
                'source_year' => 2026,
                'is_active' => true,
                'metadata' => ['meaning' => 'Se conserva exactamente el rótulo visto en Blackboard. No se infiere si representa una escuela, sede o unidad vigente.'],
            ],
            [
                'key' => 'EEST',
                'name' => 'Escuela de Educación Superior Tecnológica',
                'unit_type' => 'school',
                'source_label' => 'Escuela de Educación Superior Tecnológica',
                'verification_status' => 'verified_official_2026',
                'source_year' => 2026,
                'is_active' => true,
                'metadata' => ['source_url' => 'https://escuelasuperior.senati.edu.pe/'],
            ],
            [
                'key' => 'ETIA_HISTORICAL_2022',
                'name' => 'Escuela de Tecnología de Información y Administración (ETIA) — referencia 2022',
                'unit_type' => 'historical_school_reference',
                'source_label' => 'ETIA',
                'verification_status' => 'historical_reference',
                'source_year' => 2022,
                'is_active' => false,
                'metadata' => ['source_url' => 'https://www.senati.edu.pe/sites/default/files/archivos/2023/publicaciones/07/memoria_2022c.pdf'],
            ],
        ];

        foreach ($unitDefinitions as $data) {
            $unit = InstitutionUnit::firstOrCreate(
                ['institution_id' => $institution->id, 'code' => $data['key']],
                [
                    'name' => $data['name'],
                    'unit_type' => $data['unit_type'],
                    'source_label' => $data['source_label'],
                    'verification_status' => $data['verification_status'],
                    'source_year' => $data['source_year'],
                    'is_active' => $data['is_active'],
                    'metadata' => $data['metadata'],
                ]
            );
            $units[$data['key']] = $unit;
        }

        InstitutionPeriodType::firstOrCreate(
            ['institution_id' => $institution->id, 'code' => 'BLACKBOARD_PERIOD'],
            [
                'name' => 'Periodo académico de Blackboard',
                'period_unit' => 'institution_period',
                'code_pattern' => 'YYYYNN',
                'default_duration_weeks' => null,
                'is_active' => true,
                'metadata' => [
                    'note' => 'Conserva el código institucional exactamente como aparece en Blackboard. Su duración y equivalencia no se deducen del código.',
                ],
            ]
        );

        InstitutionPeriodType::firstOrCreate(
            ['institution_id' => $institution->id, 'code' => 'STUDY_SEMESTER'],
            [
                'name' => 'Semestre de avance de malla',
                'period_unit' => 'semester',
                'code_pattern' => '1..N',
                'default_duration_weeks' => 17,
                'is_active' => true,
                'metadata' => [
                    'note' => 'La duración de cada carrera se fija en la versión de su malla; las opciones de referencia SENATI son 4, 5, 6 y 8 semestres.',
                    'source_url' => 'https://www.senati.edu.pe/sites/default/files/escritorio/2023/reglamento_interno_del_estudiante_v02_17112022.pdf',
                ],
            ]
        );

        foreach ([
            ['TEC', 'Técnica', 'Componente identificado en los códigos de sección de Blackboard.', 10],
            ['TAL', 'Taller', 'Componente identificado en los códigos de sección de Blackboard.', 20],
        ] as [$code, $name, $description, $order]) {
            InstitutionInstructionType::firstOrCreate(
                ['institution_id' => $institution->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'sort_order' => $order,
                    'settings' => ['source' => 'SENATI Blackboard evidence', 'configurable' => true],
                ]
            );
        }

        $features = [
            [
                'feature_key' => 'five_minute_talks',
                'enabled' => true,
                'settings' => [
                    'scope' => 'institution_configurable',
                    'default_frequency' => null,
                    'template_required' => true,
                    'note' => 'Habilitada para SENATI; la frecuencia y el formato deben configurarse con la pauta institucional.',
                ],
            ],
            [
                'feature_key' => 'institution_document_templates',
                'enabled' => true,
                'settings' => [
                    'templates_uploaded' => false,
                    'note' => 'Pendiente cargar los Excel oficiales TEC/TAL y definir el mapa de celdas.',
                ],
            ],
            [
                'feature_key' => 'blackboard_style_scheduling',
                'enabled' => true,
                'settings' => [
                    'supports_start_date' => true,
                    'supports_end_date' => true,
                    'supports_start_time' => true,
                    'supports_end_time' => true,
                    'supports_multiple_weekdays' => true,
                    'supports_weekly_repeat_interval' => true,
                    'actual_schedules_imported' => false,
                    'reference_event_only' => [
                        'alias' => '202601-PCCU-1008-TAL-NRC_4526',
                        'first_date' => '2026-11-26',
                        'start_time' => '14:00',
                        'end_time' => '15:00',
                        'frequency' => 'WEEKLY',
                        'interval_weeks' => 1,
                        'weekdays_iso' => [4],
                        'repeat_until_date' => '2026-11-27',
                        'all_day' => false,
                        'note' => 'Ejemplo de evento visto en Blackboard; no es un horario lectivo confirmado.',
                    ],
                ],
            ],
        ];

        foreach ($features as $feature) {
            InstitutionFeature::firstOrCreate(
                ['institution_id' => $institution->id, 'feature_key' => $feature['feature_key']],
                ['enabled' => $feature['enabled'], 'settings' => $feature['settings']]
            );
        }

        $currentPrograms = [
            'Ingeniería de Mantenimiento Industrial',
            'Ingeniería de Tecnologías de la Información y Comunicación',
            'Ingeniería de Ciencia de Datos e Inteligencia Artificial',
            'Ingeniería de Industrias Alimentarias',
            'Ingeniería de Producción Industrial',
            'Ingeniería Mecatrónica',
            'Ingeniería de Sistemas de Información',
        ];

        foreach ($currentPrograms as $name) {
            $program = AcademicProgram::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'institution_unit_id' => $units['EEST']->id,
                    'name' => $name,
                ],
                [
                    'qualification_level' => 'Escuela Superior Tecnológica',
                    'status' => 'active',
                    'verification_status' => 'verified_official_2026',
                    'source_url' => 'https://escuelasuperior.senati.edu.pe/',
                    'source_year' => 2026,
                    'metadata' => ['duration_note' => 'La página oficial informa 8 semestres (4 años) para las carreras de esta escuela.'],
                ]
            );

            AcademicProgramVersion::firstOrCreate(
                ['academic_program_id' => $program->id, 'version_code' => 'OFERTA_OFICIAL_2026'],
                [
                    'duration_semesters' => 8,
                    'status' => 'current',
                    'is_current' => true,
                    'source_url' => 'https://escuelasuperior.senati.edu.pe/',
                    'source_year' => 2026,
                    'metadata' => ['duration_evidence' => '8 semestres (4 años), según la oferta publicada por la Escuela Superior Tecnológica.'],
                ]
            );
        }

        $historicalPrograms = [
            'Administración Industrial',
            'Seguridad Industrial y Prevención de Riesgos',
            'Administración Logística',
            'Administración de Empresas',
            'Procesos de Producción Industrial',
            'Administración de la Producción',
            'Marketing y Gestión Comercial',
            'Administración de Negocios Internacionales',
            'Informática y Desarrollo de Aplicaciones Web',
            'Diseño Gráfico Digital',
            'Diseño de Estructuras Metálicas y de Construcción',
            'Redes y Seguridad Informática',
            'Diseño y Desarrollo de Videojuegos y Realidad Aumentada',
            'Administración de Base de Datos',
            'Seguridad de la Información',
            'Internet de las Cosas y Machine Learning',
            'Ingeniería de Software con Inteligencia Artificial',
            'Ingeniería de Soporte de TI',
            'Ingeniería de Ciberseguridad',
            'Ingeniería de Cloud Computing y Data Center',
            'Ingeniería de Ciencia de Datos e Inteligencia Artificial',
            'Ingeniería de Tecnologías de la Información y Comunicación',
        ];

        foreach ($historicalPrograms as $name) {
            $program = AcademicProgram::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'institution_unit_id' => $units['ETIA_HISTORICAL_2022']->id,
                    'name' => $name,
                ],
                [
                    'status' => 'historical_reference',
                    'verification_status' => 'historical_2022',
                    'source_url' => 'https://www.senati.edu.pe/sites/default/files/archivos/2023/publicaciones/07/memoria_2022c.pdf',
                    'source_year' => 2022,
                    'metadata' => ['note' => 'Nombre recuperado de la memoria institucional 2022. No se ofrece como carrera vigente confirmada.'],
                ]
            );

            AcademicProgramVersion::firstOrCreate(
                ['academic_program_id' => $program->id, 'version_code' => 'REFERENCIA_HISTORICA_2022'],
                [
                    'duration_semesters' => null,
                    'status' => 'historical_reference',
                    'is_current' => false,
                    'source_url' => 'https://www.senati.edu.pe/sites/default/files/archivos/2023/publicaciones/07/memoria_2022c.pdf',
                    'source_year' => 2022,
                    'metadata' => ['note' => 'La fuente consultada no acredita la duración individual de esta carrera.'],
                ]
            );
        }
    }
}
