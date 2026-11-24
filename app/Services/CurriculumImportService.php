<?php

namespace App\Services;

use App\Models\CourseAssignment;
use App\Models\CourseModality;
use App\Models\Curriculum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class CurriculumImportService
{
    public function extractPdf(string $path): array
    {
        $config = new Config();
        $config->setDecodeMemoryLimit(100 * 1024 * 1024);
        $parser = new Parser([], $config);
        $document = $parser->parseFile($path);
        $pages = [];
        foreach ($document->getPages() as $index => $page) {
            $text = trim((string) $page->getText());
            $pages[] = ['page' => $index + 1, 'text' => $text];
        }
        $fullText = implode("\n\n========== PÁGINA =========\n\n", array_map(
            fn (array $page) => 'PÁGINA '.$page['page']."\n".$page['text'],
            $pages
        ));
        $textExtractionWarning = mb_strlen(trim($fullText)) < 500;

        return [
            'pages' => $pages,
            'text' => $fullText,
            'sections' => $this->detectKeySections($pages),
            'text_extraction_warning' => $textExtractionWarning,
        ];
    }

    /**
     * Detect the two institutional tables by their headings without discarding
     * any page. The complete PDF text remains available even when detection fails.
     */
    private function detectKeySections(array $pages): array
    {
        $definitions = [
            'cuadro_programa' => [
                'label' => 'Cuadro Programa',
                'pattern' => '/\bCUADRO\s+(?:DE\s+)?PROGRAMA(?:CI[OÓ]N)?\b/iu',
            ],
            'hoja_programacion' => [
                'label' => 'Hoja de Programación',
                'pattern' => '/\bHOJA\s+(?:DE\s+)?PROGRAMACI[ÓO]N\b|\bPROGRAMACI[ÓO]N\s+SEMANAL\b/iu',
            ],
        ];

        // A table may continue on the next pages without repeating its title.
        // Keep all pages from one detected heading up to the next detected table.
        $starts = [];
        foreach ($pages as $index => $page) {
            foreach ($definitions as $key => $definition) {
                if (!isset($starts[$key]) && preg_match($definition['pattern'], (string) ($page['text'] ?? '')) === 1) {
                    $starts[$key] = $index;
                }
            }
        }

        $orderedStarts = $starts;
        asort($orderedStarts);
        $sections = [];

        foreach ($definitions as $key => $definition) {
            $start = $starts[$key] ?? null;
            $matchedPages = [];

            if ($start !== null) {
                $end = count($pages);
                foreach ($orderedStarts as $otherKey => $otherStart) {
                    if ($otherKey !== $key && $otherStart > $start) {
                        $end = min($end, $otherStart);
                    }
                }

                $matchedPages = array_slice($pages, $start, $end - $start);
            }

            $sections[$key] = [
                'label' => $definition['label'],
                'detected' => $start !== null,
                'pages' => array_values(array_map(fn (array $page) => (int) $page['page'], $matchedPages)),
                'text' => implode("\n\n", array_map(
                    fn (array $page) => 'PÁGINA '.$page['page']."\n".$page['text'],
                    $matchedPages
                )),
            ];
        }

        return $sections;
    }

    public const PROMPT_VERSION = 'GD-MALLA-3.0';

    public function promptVersion(): string
    {
        return self::PROMPT_VERSION;
    }

    public function buildPrompt(
        string $sourceText,
        CourseAssignment $assignment,
        ?string $generatedAt = null,
        array $sections = []
    ): string
    {
        $assignment->loadMissing(['course', 'group.cycle']);
        $course = $assignment->course;
        $cycle = $assignment->group?->cycle;
        $career = $cycle?->career ?: ($course->career ?: 'No registrada');
        $semester = $cycle?->semester ?: 'No registrado';
        $generatedAt = $generatedAt ?: now()->toIso8601String();
        $sectionSource = '';
        foreach ([
            'cuadro_programa' => 'CUADRO PROGRAMA',
            'hoja_programacion' => 'HOJA DE PROGRAMACIÓN',
        ] as $key => $label) {
            $section = $sections[$key] ?? [];
            $pages = $section['pages'] ?? [];
            $sectionSource .= "\n\n========== {$label} =========="."\n";
            $sectionSource .= !empty($section['detected'])
                ? 'Encabezado detectado en página(s): '.implode(', ', $pages)."\n".($section['text'] ?? '')
                : 'El extractor de texto no reconoció este encabezado. Localiza la tabla manualmente en el PDF adjunto; no concluyas que no existe.\n';
        }
        $schema = <<<'JSON'
{
  "schema_version": 3,
  "processing": {
    "prompt_version": "GD-MALLA-3.0",
    "prompt_generated_at": "2026-11-23T10:00:00-05:00"
  },
  "metadata": {
    "catalog_code": "",
    "module_code": "",
    "module_name": "",
    "course_name": "",
    "career": "",
    "professional_school": "",
    "occupational_module": "",
    "semester": "",
    "general_objective": ""
  },
  "operation_matrix": [
    {
      "task_code": "HT-01",
      "task_title": "",
      "operations": [
        {"code": "HO-01", "description": "", "type": "NEW"}
      ]
    }
  ],
  "weeks": [
    {
      "week_number": 1,
      "source_week_number": 13,
      "programming_topic": "Tema exacto de la Hoja de Programación para esta semana",
      "hours": {"T": 5, "P": 10, "V": 7},
      "task_code": "HT-01",
      "task_title": "",
      "is_evaluation": false,
      "source_pages": [3],
      "technological_knowledge": [],
      "complementary_knowledge": [],
      "virtual_activities": [],
      "tec_plan": {
        "topic": "",
        "objective": "",
        "activities": [],
        "exercises": [],
        "materials": [],
        "evidence": []
      },
      "tall_plan": {
        "topic": "",
        "objective": "",
        "activities": [],
        "exercises": [],
        "materials": [],
        "evidence": []
      },
      "notes": ""
    }
  ]
}
JSON;

        return "ROL: Actúa como especialista en planificación curricular técnico-profesional, diseño de sesiones TEC/TALL y estructuración de datos académicos.\n\n"
            ."TAREA: analiza completamente el PDF de malla curricular que adjunto a este chat y el texto extraído que incluyo abajo. Revisa todas sus páginas, tablas, imágenes, matrices, leyendas, encabezados y continuaciones. Devuelve UN ÚNICO objeto JSON válido, sin Markdown, sin bloque de código y sin texto fuera del JSON. El sistema lo importará en una aplicación PHP/MySQL y lo utilizará para planificar y generar Excel.\n\n"
            ."CURSO DESTINO EN EL SISTEMA: {$course->name}. Código interno: ".($course->code ?: 'no indicado').". Este dato es contexto de asignación, no reemplaza ni corrige lo que diga la fuente oficial.\n\n"
            ."CONTEXTO DEL CICLO REGISTRADO: carrera = {$career}; semestre = {$semester}. Usa estos datos para metadata.career y metadata.semester solo si la fuente PDF no indica otros valores. Si hay discrepancia, conserva el dato de la malla y explica la diferencia en notes.\n\n"
            ."IMPORTANTE: UNA MISMA MALLA CURRICULAR SIRVE PARA LAS DOS MODALIDADES. Extrae una sola vez la información oficial común (HT, HO, conocimientos y horas) y prepara dos propuestas pedagógicas separadas por cada bloque: TEC (conocimientos tecnológicos/aula) y TALL (práctica de taller/laboratorio). No generes dos mallas independientes ni dupliques el contenido oficial. Las propuestas TEC/TALL son complementos didácticos de IA y deben mantenerse separadas y claramente identificadas.\n\n"
            ."REGLAS DE EXTRACCIÓN DE LAS DOS TABLAS INSTITUCIONALES:\n"
            ."A. CUADRO PROGRAMA: es la fuente para códigos y títulos HT, operaciones HO, relación HT→HO, conocimientos y horas T/P/V. Conserva códigos y asocia cada HO a su HT correcto. Respeta NEW/REPEATED; si no se distingue, usa UNSPECIFIED y explica la duda.\n"
            ."B. HOJA DE PROGRAMACIÓN: es la fuente para la distribución semanal y los temas de cada semana. Extrae el tema/título semanal exactamente como aparece y colócalo en programming_topic. No lo reemplaces por el título HT, por una propuesta de IA ni por un tema inventado.\n"
            ."C. Cruza ambas tablas por semana, código y título. El Cuadro Programa aporta la estructura HT/HO; la Hoja de Programación aporta el tema semanal que debe aparecer en los Excel. Si difieren, conserva ambos datos y explícalo en notes.\n"
            ."1. El PDF adjunto es la fuente oficial. Revisa visualmente ambas tablas en el documento original aunque el texto extraído no las detecte. Conserva nombres, códigos, terminología, organización y detalle. No completes vacíos con conocimiento externo.\n"
            ."2. Extrae tareas HT y operaciones HO desde el Cuadro Programa y su relación exacta HT→HO.\n"
            ."3. Separa los contenidos oficiales en technological_knowledge, complementary_knowledge y virtual_activities. Los contenidos de cada semana deben corresponder a la Hoja de Programación; no confundas formación virtual con una clase presencial.\n"
            ."4. Conserva las horas T (tecnología), P (práctica) y V (formación virtual) por bloque. Si no están visibles, devuelve null. Nunca inventes ni deduzcas horas.\n"
            ."5. NUMERACIÓN DE SEMANAS: no uses el número impreso de la malla como número de semana del curso en el sistema. El sistema calculará las semanas reales con las fechas de inicio/fin y los días de clase. En week_number asigna una secuencia interna 1, 2, 3... según el orden en que aparecen los bloques de aprendizaje; en source_week_number conserva el número original impreso en la malla, o null si no aparece. Para el ejemplo de fuente 13,14,15,16,17, devuelve week_number 1,2,3,4,5 y source_week_number 13,14,15,16,17. No uses fechas de calendario.\n"
            ."6. Si la semana/bloque solo señala EVALUACIÓN, marca is_evaluation=true, conserva horas null cuando no estén impresas y no inventes criterios institucionales.\n"
            ."7. Si un contenido continúa en otra página, intégralo sin perder detalle y coloca todas las páginas en source_pages. Si algo es ilegible o ambiguo, no lo corrijas por intuición; explica el problema en notes.\n"
            ."8. programming_topic debe conservar el tema oficial exacto de la Hoja de Programación para cada semana. Si no se puede identificar, déjalo vacío y explica el motivo en notes.\n"
            ."9. No inventes fechas de inicio/fin, NRC, horarios, alumnos, notas, ponderaciones ni reglas institucionales.\n\n"
            ."PLANIFICACIÓN DIDÁCTICA PARA CADA MODALIDAD (PROPUESTA DE IA, NO CONTENIDO OFICIAL):\n"
            ."- tec_plan: orienta la sesión TEC a explicar, analizar, comparar, interpretar, modelar, resolver ejercicios guiados y verificar conocimientos. Incluye topic, objective y listas concretas de activities, exercises, materials y evidence.\n"
            ."- tall_plan: orienta la sesión TALL a ejecutar operaciones HO, practicar procedimientos, configurar/probar en laboratorio, resolver casos y evidenciar el producto. Incluye topic, objective y listas concretas de activities, exercises, materials y evidence.\n"
            ."- Deriva las propuestas de HT/HO y de los conocimientos del PDF. No inventes equipamiento especializado no disponible; indica alternativas cuando corresponda. Si una actividad no aplica a una modalidad, devuelve campos vacíos. No copies literalmente el mismo plan en TEC y TALL.\n"
            ."- Estas propuestas deben aparecer únicamente dentro de tec_plan/tall_plan y se importarán como sugerencias pendientes de aprobación del profesor.\n\n"
            ."REGLA SOBRE LOS MATERIALES EXCEL: una vez importada la malla, el sistema genera archivos separados por curso, grupo y modalidad. TEC usa sus documentos de conocimientos tecnológicos/teoría; TALL usa sus documentos de práctica/taller. No mezcles estudiantes, NRC ni evaluaciones entre modalidades.\n\n"
            ."ESQUEMA JSON OBLIGATORIO (mantén todos los nombres de clave):\n{$schema}\n\n"
            ."VALORES PERMITIDOS operation_matrix[].operations[].type: NEW, REPEATED, UNSPECIFIED.\n\n"
            ."MAPA DE DETECCIÓN DE LAS DOS TABLAS PRIORITARIAS:\n{$sectionSource}\n"
            ."TEXTO EXTRAÍDO DEL PDF, SEPARADO POR PÁGINA:\n================ INICIO DE FUENTE ================\n"
            .$sourceText
            ."\n================ FIN DE FUENTE ================\n\n"
            ."METADATOS QUE DEBES REPETIR EXACTAMENTE EN processing: prompt_version = ".self::PROMPT_VERSION."; prompt_generated_at = {$generatedAt}. No cambies estos valores.\n\n"
            ."VERIFICACIÓN FINAL: JSON parseable; schema_version=3; weeks en secuencia 1..N sin duplicados; source_week_number conserva la numeración impresa o null; programming_topic contiene el tema exacto de la Hoja de Programación o queda vacío con explicación; horas numéricas o null; tareas HT relacionadas con las HO correctas; contenidos semanales fieles a la fuente; tec_plan y tall_plan presentes en cada bloque; sin comentarios ni comas finales. Devuelve solo JSON.";
    }

    public function validatePayload(array $payload): array
    {
        $schemaVersion = (int) ($payload['schema_version'] ?? 0);
        if (!in_array($schemaVersion, [1, 2, 3], true)) {
            throw ValidationException::withMessages(['json_content' => 'La versión del esquema JSON no es compatible (se admite schema_version 1, 2 o 3).']);
        }

        $metadata = $payload['metadata'] ?? null;
        $weeks = $payload['weeks'] ?? null;
        $matrix = $payload['operation_matrix'] ?? [];
        if (!is_array($metadata) || !is_array($weeks) || !is_array($matrix) || count($weeks) < 1) {
            throw ValidationException::withMessages(['json_content' => 'El JSON debe contener metadata, operation_matrix y weeks.']);
        }

        foreach (['catalog_code','module_code','module_name','course_name','career','professional_school','occupational_module','semester','general_objective'] as $key) {
            if (isset($metadata[$key]) && !is_string($metadata[$key])) {
                throw ValidationException::withMessages(['json_content' => "El campo metadata.{$key} debe ser texto."]);
            }
        }

        if ($schemaVersion >= 2) {
            $processing = $payload['processing'] ?? [];
            if (!is_array($processing)) {
                throw ValidationException::withMessages(['json_content' => 'processing debe ser un objeto con la versión y fecha del prompt.']);
            }
            foreach (['prompt_version', 'prompt_generated_at'] as $key) {
                if (empty($processing[$key]) || !is_string($processing[$key])) {
                    throw ValidationException::withMessages(['json_content' => "Falta processing.{$key}. Usa el prompt completo generado por Gestión Docente y conserva sus metadatos."]);
                }
            }
        }

        $taskCodes = [];
        foreach ($matrix as $task) {
            if (!is_array($task) || empty($task['task_code']) || empty($task['task_title']) || !is_array($task['operations'] ?? null)) {
                throw ValidationException::withMessages(['json_content' => 'Cada tarea de operation_matrix necesita task_code, task_title y operations.']);
            }
            $taskCode = trim((string) $task['task_code']);
            if (isset($taskCodes[$taskCode])) {
                throw ValidationException::withMessages(['json_content' => 'El código de tarea '.$taskCode.' está duplicado en operation_matrix.']);
            }
            $taskCodes[$taskCode] = true;
            $operationCodes = [];
            foreach ($task['operations'] as $operation) {
                if (!is_array($operation) || empty($operation['code']) || empty($operation['description'])) {
                    throw ValidationException::withMessages(['json_content' => "Una operación de {$taskCode} no tiene code o description."]);
                }
                $operationCode = trim((string) $operation['code']);
                if (isset($operationCodes[$operationCode])) {
                    throw ValidationException::withMessages(['json_content' => "La operación {$operationCode} está duplicada en {$taskCode}."]);
                }
                $operationCodes[$operationCode] = true;
            }
        }

        $seenWeeks = [];
        foreach ($weeks as $index => &$week) {
            if (!is_array($week)) {
                throw ValidationException::withMessages(['json_content' => 'Cada elemento de weeks debe ser un objeto.']);
            }
            if ($schemaVersion === 1) {
                $legacySourceNumber = filter_var($week['week_number'] ?? null, FILTER_VALIDATE_INT);
                if (!$legacySourceNumber || $legacySourceNumber < 1 || $legacySourceNumber > 60) {
                    throw ValidationException::withMessages(['json_content' => 'La semana de origen debe estar entre 1 y 60. Error en elemento '.($index + 1).'.']);
                }
                $number = $index + 1;
                $sourceWeekNumber = $legacySourceNumber;
            } else {
                $number = filter_var($week['week_number'] ?? null, FILTER_VALIDATE_INT);
                if (!$number || $number < 1 || $number > 60) {
                    throw ValidationException::withMessages(['json_content' => 'week_number debe ser la secuencia interna del curso, iniciando en 1. Error en elemento '.($index + 1).'.']);
                }
                $rawSourceNumber = $week['source_week_number'] ?? null;
                $sourceWeekNumber = $rawSourceNumber === null || $rawSourceNumber === ''
                    ? null
                    : filter_var($rawSourceNumber, FILTER_VALIDATE_INT);
                if ($rawSourceNumber !== null && $rawSourceNumber !== '' && (!$sourceWeekNumber || $sourceWeekNumber < 1 || $sourceWeekNumber > 60)) {
                    throw ValidationException::withMessages(['json_content' => 'source_week_number debe ser un entero entre 1 y 60 o null.']);
                }
            }
            if (isset($seenWeeks[$number])) {
                throw ValidationException::withMessages(['json_content' => 'La secuencia de semanas del curso está duplicada: '.$number.'.']);
            }
            if ($number !== $index + 1) {
                throw ValidationException::withMessages(['json_content' => 'week_number debe ser consecutivo desde 1. Se esperaba '.($index + 1).' y se recibió '.$number.'.']);
            }
            $seenWeeks[$number] = true;
            $week['week_number'] = $number;
            $week['source_week_number'] = $sourceWeekNumber;
            if (isset($week['programming_topic']) && !is_string($week['programming_topic'])) {
                throw ValidationException::withMessages(['json_content' => 'programming_topic de la semana '.$number.' debe ser texto.']);
            }
            $week['programming_topic'] = trim((string) ($week['programming_topic'] ?? ''));

            $hours = $week['hours'] ?? [];
            if (!is_array($hours)) $hours = [];
            foreach (['T','P','V'] as $hourKey) {
                $value = $hours[$hourKey] ?? null;
                if ($value !== null && (!is_numeric($value) || (float) $value < 0 || (float) $value > 200)) {
                    throw ValidationException::withMessages(['json_content' => "Valor inválido para hours.{$hourKey} en bloque {$number}."]);
                }
            }
            $week['hours'] = ['T' => $hours['T'] ?? null, 'P' => $hours['P'] ?? null, 'V' => $hours['V'] ?? null];

            foreach (['technological_knowledge','complementary_knowledge','virtual_activities','proposed_exercises','proposed_materials','proposed_evidence'] as $key) {
                if (!isset($week[$key])) $week[$key] = [];
                if (!is_array($week[$key])) throw ValidationException::withMessages(["json_content" => "La clave weeks.{$number}.{$key} debe ser una lista."]);
                foreach ($week[$key] as $item) {
                    if (!is_string($item) || trim($item) === '') throw ValidationException::withMessages(['json_content' => "La lista {$key} del bloque {$number} contiene un valor vacío o inválido."]);
                }
            }

            foreach (['tec_plan', 'tall_plan'] as $planKey) {
                if (!isset($week[$planKey])) $week[$planKey] = [];
                if (!is_array($week[$planKey])) throw ValidationException::withMessages(["json_content" => "weeks.{$number}.{$planKey} debe ser un objeto."]);
                foreach (['topic', 'objective'] as $textKey) {
                    if (isset($week[$planKey][$textKey]) && !is_string($week[$planKey][$textKey])) {
                        throw ValidationException::withMessages(["json_content" => "{$planKey}.{$textKey} del bloque {$number} debe ser texto."]);
                    }
                    $week[$planKey][$textKey] = trim((string) ($week[$planKey][$textKey] ?? ''));
                }
                foreach (['activities','exercises','materials','evidence'] as $listKey) {
                    if (!isset($week[$planKey][$listKey])) $week[$planKey][$listKey] = [];
                    if (!is_array($week[$planKey][$listKey])) {
                        throw ValidationException::withMessages(["json_content" => "{$planKey}.{$listKey} del bloque {$number} debe ser una lista."]);
                    }
                    foreach ($week[$planKey][$listKey] as $item) {
                        if (!is_string($item) || trim($item) === '') {
                            throw ValidationException::withMessages(["json_content" => "{$planKey}.{$listKey} del bloque {$number} contiene un texto vacío o inválido."]);
                        }
                    }
                }
            }

            $week['is_evaluation'] = (bool) ($week['is_evaluation'] ?? false);
            $week['source_pages'] = is_array($week['source_pages'] ?? null) ? $week['source_pages'] : [];
            $week['notes'] = is_string($week['notes'] ?? null) ? $week['notes'] : '';
        }
        unset($week);

        foreach ($weeks as &$week) {
            $taskCode = $week['task_code'] ?? null;
            if (is_string($taskCode)) {
                $taskCode = trim($taskCode);
                $week['task_code'] = $taskCode === '' ? null : $taskCode;
                if ($taskCode === '') $taskCode = null;
            }
            if ($taskCode !== null && (!is_string($taskCode) || !isset($taskCodes[$taskCode]))) {
                throw ValidationException::withMessages(['json_content' => 'El bloque '.$week['week_number'].' referencia una tarea task_code que no existe en operation_matrix.']);
            }
            if (isset($week['task_title']) && !is_string($week['task_title'])) {
                throw ValidationException::withMessages(['json_content' => 'El título de tarea del bloque '.$week['week_number'].' debe ser texto.']);
            }
        }
        unset($week);

        $payload['schema_version'] = $schemaVersion;
        $payload['weeks'] = array_values($weeks);
        return $payload;
    }

    public function importPayload(CourseAssignment $assignment, array $payload, array $source = [], int $userId): Curriculum
    {
        $payload = $this->validatePayload($payload);
        $meta = $payload['metadata'];
        return DB::transaction(function () use ($assignment, $payload, $meta, $source, $userId) {
            $course = $assignment->course;
            $professorId = $assignment->group->professor_id;
            $version = ((int) Curriculum::where('course_id', $course->id)->max('version')) + 1;
            Curriculum::where('course_id', $course->id)->update(['is_active' => false]);
            $curriculum = Curriculum::create([
                'professor_id' => $professorId,
                'course_id' => $course->id,
                'catalog_code' => $meta['catalog_code'] ?? null,
                'module_code' => $meta['module_code'] ?? null,
                'module_name' => $meta['module_name'] ?? null,
                'course_name' => $meta['course_name'] ?? $course->name,
                'career' => $meta['career'] ?? ($assignment->group->cycle->career ?? $course->career),
                'professional_school' => $meta['professional_school'] ?? null,
                'occupational_module' => $meta['occupational_module'] ?? null,
                'semester' => $meta['semester'] ?? ($assignment->group->cycle->semester ?? null),
                'general_objective' => $meta['general_objective'] ?? null,
                'source_filename' => $source['filename'] ?? 'Importación JSON de malla curricular',
                'source_path' => $source['path'] ?? null,
                'source_sha256' => $source['sha256'] ?? null,
                'extracted_text' => $source['text'] ?? null,
                'source_metadata' => [
                    'schema_version' => (int) ($payload['schema_version'] ?? 1),
                    'prompt_version' => $source['prompt_version'] ?? ($payload['processing']['prompt_version'] ?? null),
                    'prompt_generated_at' => $source['prompt_generated_at'] ?? ($payload['processing']['prompt_generated_at'] ?? null),
                    'prompt_sha256' => $source['prompt_sha256'] ?? null,
                    'pdf_sections' => collect($source['sections'] ?? [])->map(fn ($section) => [
                        'label' => $section['label'] ?? null,
                        'detected' => (bool) ($section['detected'] ?? false),
                        'pages' => array_values($section['pages'] ?? []),
                    ])->all(),
                    'json_imported_at' => now()->toIso8601String(),
                    'imported_at' => now()->toIso8601String(),
                ],
                'version' => $version,
                'is_active' => true,
                'status' => 'imported',
                'imported_by' => $userId,
            ]);

            $tasks = [];
            foreach (($payload['operation_matrix'] ?? []) as $position => $taskData) {
                $task = $curriculum->tasks()->create([
                    'task_code' => trim($taskData['task_code']),
                    'title' => trim($taskData['task_title']),
                    'position' => $position + 1,
                ]);
                $tasks[$task->task_code] = $task;
                foreach (($taskData['operations'] ?? []) as $operationPosition => $operationData) {
                    $task->operations()->create([
                        'operation_code' => trim($operationData['code']),
                        'description' => trim($operationData['description']),
                        'operation_type' => in_array(($operationData['type'] ?? ''), ['NEW','REPEATED','UNSPECIFIED'], true) ? $operationData['type'] : 'UNSPECIFIED',
                        'position' => $operationPosition + 1,
                    ]);
                }
            }

            foreach ($payload['weeks'] as $weekData) {
                $task = isset($weekData['task_code']) ? ($tasks[$weekData['task_code']] ?? null) : null;
                $week = $curriculum->weeks()->create([
                    'curriculum_task_id' => $task?->id,
                    'week_number' => $weekData['week_number'],
                    'source_week_number' => $weekData['source_week_number'] ?? null,
                    'source_topic' => $weekData['programming_topic'] ?: null,
                    'hours_theory' => $weekData['hours']['T'],
                    'hours_practice' => $weekData['hours']['P'],
                    'hours_virtual' => $weekData['hours']['V'],
                    'is_evaluation' => $weekData['is_evaluation'],
                    'source_pages' => $weekData['source_pages'],
                    'notes' => $weekData['notes'] ?: null,
                ]);
                $items = [
                    'TECHNOLOGICAL' => ['items' => $weekData['technological_knowledge'], 'kind' => 'SOURCE', 'approved' => true, 'target' => null],
                    'COMPLEMENTARY' => ['items' => $weekData['complementary_knowledge'], 'kind' => 'SOURCE', 'approved' => true, 'target' => null],
                    'VIRTUAL' => ['items' => $weekData['virtual_activities'], 'kind' => 'SOURCE', 'approved' => true, 'target' => null],
                    'EXERCISE' => ['items' => $weekData['proposed_exercises'], 'kind' => 'AI_SUGGESTION', 'approved' => false, 'target' => 'BOTH'],
                    'MATERIAL' => ['items' => $weekData['proposed_materials'], 'kind' => 'AI_SUGGESTION', 'approved' => false, 'target' => 'BOTH'],
                    'EVIDENCE' => ['items' => $weekData['proposed_evidence'], 'kind' => 'AI_SUGGESTION', 'approved' => false, 'target' => 'BOTH'],
                ];
                foreach ($items as $category => $config) {
                    foreach ($config['items'] as $position => $content) {
                        $week->contentItems()->create([
                            'category' => $category,
                            'content' => trim($content),
                            'source_kind' => $config['kind'],
                            'approved' => $config['approved'],
                            'position' => $position + 1,
                            'metadata' => $config['target'] ? ['target_modality' => $config['target'], 'plan_component' => $category] : null,
                        ]);
                    }
                }
                foreach (['TEC' => 'tec_plan', 'TALL' => 'tall_plan'] as $targetModality => $planKey) {
                    $plan = $weekData[$planKey] ?? [];
                    $components = [
                        'topic' => $targetModality.'_TOPIC', 'objective' => $targetModality.'_OBJECTIVE',
                        'activities' => $targetModality.'_ACTIVITY', 'exercises' => $targetModality.'_EXERCISE',
                        'materials' => $targetModality.'_MATERIAL', 'evidence' => $targetModality.'_EVIDENCE',
                    ];
                    foreach ($components as $field => $category) {
                        $values = $plan[$field] ?? [];
                        if (is_string($values)) $values = trim($values) === '' ? [] : [$values];
                        foreach ($values as $position => $content) {
                            $week->contentItems()->create([
                                'category' => $category, 'content' => trim((string) $content),
                                'source_kind' => 'AI_SUGGESTION', 'approved' => false, 'position' => $position + 1,
                                'metadata' => ['target_modality' => $targetModality, 'plan_component' => $field],
                            ]);
                        }
                    }
                }
            }

            $assignment->update(['curriculum_id' => $curriculum->id]);
            CourseModality::query()->where('course_assignment_id', $assignment->id)->update(['curriculum_start_week' => 1]);
            return $curriculum->load(['tasks.operations','weeks.task','weeks.contentItems']);
        });
    }
}
