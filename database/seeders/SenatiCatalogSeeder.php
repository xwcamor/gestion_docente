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
use Illuminate\Support\Facades\DB;

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
                    'reported_careers_in_execution_2025' => 90,
                    'reported_professional_schools_2025' => 14,
                    'named_careers_extracted_from_2025_report' => 85,
                    'unresolved_career_names_from_2025_report' => 5,
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
            // Reaplica las etiquetas de origen y el estado histórico correcto sin crear duplicados.
            $unit = InstitutionUnit::updateOrCreate(
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

        // Vincula los componentes existentes de SENATI con su catálogo institucional.
        // Se conserva el código anterior en course_modalities.modality para compatibilidad.
        $typeIds = InstitutionInstructionType::where('institution_id', $institution->id)->pluck('id', 'code');
        foreach (['TEC', 'TAL'] as $componentCode) {
            if (isset($typeIds[$componentCode])) {
                DB::table('course_modalities')
                    ->where('modality', $componentCode)
                    ->whereNull('institution_instruction_type_id')
                    ->update(['institution_instruction_type_id' => $typeIds[$componentCode]]);
            }
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


        // Catálogo de Formación Profesional 2025.
        // Se cargan únicamente los 85 nombres legibles en la tabla oficial consultada.
        // El informe reporta 90 carreras en 14 escuelas; cinco nombres no se inventan.
        $trainingCareerGroups = [
            'ADMINISTRACION' => [
                'reported_count' => 12,
                'name' => 'Administración de Empresas',
                'careers' => [
                    'Administración de Empresas',
                    'Administración de Negocios Internacionales',
                    'Administración de Recursos Humanos',
                    'Administración Industrial',
                    'Administración Logística',
                    'Contabilidad',
                    'Ingeniería de Producción Industrial',
                    'Marketing y Gestión Comercial',
                    'Procesos de Producción Industrial',
                    'Seguridad Industrial y Prevención de Riesgos',
                    'Trade Marketing y Visual Merchandising',
                ],
            ],
            'AGROINDUSTRIA' => [
                'reported_count' => 1,
                'name' => 'Agroindustria',
                'careers' => ['Agroindustria'],
            ],
            'ARTES_GRAFICAS' => [
                'reported_count' => 3,
                'name' => 'Artes Gráficas',
                'careers' => ['Diseño Gráfico', 'Diseño y Producción de Animación Digital', 'Producción Gráfica'],
            ],
            'CONFECCIONES' => [
                'reported_count' => 4,
                'name' => 'Confecciones',
                'careers' => [
                    'Confeccionista de Prendas de Vestir',
                    'Diseño y Gestión de Moda',
                    'Mecatrónica en Industria de la Moda',
                    'Tecnología de Procesos de Producción de Prendas de Vestir',
                ],
            ],
            'ELECTROTECNIA' => [
                'reported_count' => 11,
                'name' => 'Electrotecnia',
                'careers' => [
                    'Climatización y Refrigeración en Edificaciones',
                    'Climatización y Refrigeración Industrial',
                    'Electricidad Industrial',
                    'Electrónica y Automatización Industrial',
                    'Electrotecnia Industrial y Energías Renovables',
                    'Ingeniería Mecatrónica',
                    'Instrumentación y Control de Procesos Industriales',
                    'Mecatrónica Industrial',
                    'Técnicas en Ingeniería Electrónica',
                ],
            ],
            'HOTELERIA_TURISMO_RESTAURANTES' => [
                'reported_count' => 3,
                'name' => 'Hotelería, Turismo y Restaurantes',
                'careers' => ['Administración Turística', 'Cocina Peruana e Internacional', 'Guía Oficial de Turismo'],
            ],
            'INDUSTRIA_ALIMENTARIA' => [
                'reported_count' => 7,
                'name' => 'Industria Alimentaria',
                'careers' => [
                    'Control de Calidad y Procesos en la Industria Alimentaria',
                    'Ingeniería de Industrias Alimentarias',
                    'Panadería y Pastelería',
                    'Procesamiento Industrial de Alimentos',
                    'Procesos de la Industria Alimentaria y Productos Hidrobiológicos',
                    'Técnicas en Ingeniería de Industrias Alimentarias',
                ],
            ],
            'TECNOLOGIAS_INFORMACION' => [
                'reported_count' => 16,
                'name' => 'Tecnologías de la Información',
                'careers' => [
                    'Administrador de Sistemas de Información',
                    'Computación e Informática',
                    'Desarrollo de Software',
                    'Diseño de Estructuras Metálicas y Modelamiento BIM',
                    'Diseño Gráfico Digital',
                    'Diseño y Desarrollo de Videojuegos y Realidad Aumentada',
                    'Informática y Desarrollo de Aplicaciones Web',
                    'Ingeniería de Ciberseguridad',
                    'Ingeniería de Ciencia de Datos e Inteligencia Artificial',
                    'Ingeniería de Cloud Computing y Data Center',
                    'Ingeniería de Software con Inteligencia Artificial',
                    'Ingeniería de Soporte de TI',
                    'Ingeniería de Tecnologías de la Información y Comunicación',
                    'Redes y Seguridad Informática',
                    'Soporte y Mantenimiento de Equipos de Computación',
                ],
            ],
            'JOYERIA_ORFEBRERIA_PLATERIA' => [
                'reported_count' => 1,
                'name' => 'Joyería, Orfebrería y Platería',
                'careers' => ['Diseño y Gestión de Joyería'],
            ],
            'MANTENIMIENTO' => [
                'reported_count' => 4,
                'name' => 'Mantenimiento',
                'careers' => [
                    'Ingeniería de Mantenimiento Industrial',
                    'Mantenimiento de Plantas Industriales',
                    'Mecánico de Mantenimiento',
                    'Técnicas en Ingeniería Mecánica de Mantenimiento',
                ],
            ],
            'MECANICA_AUTOMOTRIZ' => [
                'reported_count' => 12,
                'name' => 'Área de mecánica y automotores (nombre oficial de escuela pendiente)',
                'school_label_inferred' => true,
                'careers' => [
                    'Mantenimiento de Maquinaria Pesada',
                    'Mantenimiento de Maquinaria Pesada para Construcción',
                    'Mecánica de Vehículos y Equipos Menores',
                    'Mecánico Automotriz',
                    'Mecánico de Automotores Diesel',
                    'Mecánico de Mantenimiento de Maquinaria Pesada',
                    'Mecánico de Maquinaria Pesada',
                    'Mecánico de Motores Menores',
                    'Mecatrónica Automotriz',
                    'Mecatrónica de Buses y Camiones',
                    'Mecatrónica de Maquinaria Agrícola',
                    'Mecatrónica de Motores Diésel',
                ],
            ],
            'METALMECANICA' => [
                'reported_count' => 8,
                'name' => 'Metalmecánica',
                'careers' => [
                    'Diseño y Desarrollo de Máquinas',
                    'Estructuras y Construcciones Metálicas',
                    'Mecánica de Producción',
                    'Mecánico de Construcciones Metálicas',
                    'Mecatrónica de Fabricación Metalmecánica',
                    'Procesos de la Industria del Plástico',
                    'Procesos Tecnológicos de Soldadura',
                    'Soldador Universal',
                ],
            ],
            'TECNOLOGIAS_AMBIENTALES' => [
                'reported_count' => 2,
                'name' => 'Tecnologías Ambientales',
                'careers' => ['Química Industrial', 'Tecnologías Ambientales'],
            ],
            'TEXTIL' => [
                'reported_count' => 6,
                'name' => 'Textil',
                'careers' => [
                    'Analista de Calidad Textil',
                    'Diseño y Desarrollo Textil',
                    'Mecatrónica Textil',
                    'Procesos de Producción Textil',
                    'Química en Procesos Textiles',
                    'Tintorería y Acabados Textiles',
                ],
            ],
        ];

        $trainingUnitModels = [];
        foreach ($trainingCareerGroups as $unitCode => $group) {
            $trainingUnit = InstitutionUnit::firstOrCreate(
                ['institution_id' => $institution->id, 'code' => 'FORMACION_2025_' . $unitCode],
                [
                    'name' => $group['name'],
                    'unit_type' => 'professional_school_reference',
                    'source_label' => $group['name'],
                    'source_year' => 2025,
                    'verification_status' => !empty($group['school_label_inferred']) ? 'school_label_inferred' : 'reported_in_senati_2025_memory',
                    'is_active' => true,
                    'metadata' => [],
                ]
            );
            $trainingUnit->forceFill([
                'name' => $group['name'],
                'source_label' => $group['name'],
                'source_year' => 2025,
                'verification_status' => !empty($group['school_label_inferred']) ? 'school_label_inferred' : 'reported_in_senati_2025_memory',
                'metadata' => array_merge($trainingUnit->metadata ?? [], [
                    'source_url' => 'https://embed-rech-01.dialog.cm/senati1/docs/memoria_institucional_senati_2025',
                    'source_section' => 'Total de carreras en ejecución durante el año 2025',
                    'reported_school_count' => 14,
                    'reported_career_count' => $group['reported_count'],
                    'school_label_inferred' => !empty($group['school_label_inferred']),
                    'note' => !empty($group['school_label_inferred'])
                        ? 'Agrupación funcional según los nombres de carreras; el encabezado oficial de esta escuela no quedó legible en la extracción y requiere confirmación.'
                        : 'Nombre de escuela tomado de la tabla de la Memoria 2025; vigencia futura por confirmar.',
                ]),
            ])->save();
            $trainingUnitModels[$unitCode] = $trainingUnit;

            foreach ($group['careers'] as $careerName) {
                $program = AcademicProgram::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'institution_unit_id' => $trainingUnitModels[$unitCode]->id,
                        'name' => $careerName,
                    ],
                    [
                        'status' => 'reference_2025',
                        'verification_status' => 'reported_in_senati_2025_memory',
                        'source_url' => 'https://embed-rech-01.dialog.cm/senati1/docs/memoria_institucional_senati_2025',
                        'source_year' => 2025,
                        'metadata' => [
                            'source_section' => 'Total de carreras en ejecución durante el año 2025',
                            'duration_semesters' => null,
                            'duration_note' => 'La memoria 2025 consultada no detalla la duración individual de esta carrera.',
                        ],
                    ]
                );

                AcademicProgramVersion::firstOrCreate(
                    ['academic_program_id' => $program->id, 'version_code' => 'EJECUCION_2025'],
                    [
                        'duration_semesters' => null,
                        'status' => 'reference_2025',
                        'is_current' => false,
                        'source_url' => 'https://embed-rech-01.dialog.cm/senati1/docs/memoria_institucional_senati_2025',
                        'source_year' => 2025,
                        'metadata' => [
                            'duration_note' => 'Pendiente de verificar en la malla curricular de la carrera.',
                            'reported_duration_options_semesters' => [4, 5, 6, 8],
                        ],
                    ]
                );
            }
        }

        // El informe cierra con 90 carreras en ejecución en 14 escuelas.
        // La extracción consultada permitió recuperar 85 nombres. Los otros cinco
        // quedan contabilizados como pendientes, sin fabricar nombres ni códigos.
        $institution->forceFill([
            'settings' => array_merge($institution->settings ?? [], [
                '2025_career_catalog' => [
                    'reported_execution_careers' => 90,
                    'reported_schools' => 14,
                    'names_imported_from_readable_table' => 85,
                    'names_pending_source_validation' => 5,
                    'duration_options_semesters' => [4, 5, 6, 8],
                    'note' => 'La cantidad de semestres es específica de cada carrera y versión de malla. Cinco nombres no se inventaron porque la extracción legible de la tabla no los recuperó.',
                    'source_url' => 'https://embed-rech-01.dialog.cm/senati1/docs/memoria_institucional_senati_2025',
                ],
            ]),
        ])->save();

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
