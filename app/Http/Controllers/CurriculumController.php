<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Curriculum;
use App\Models\CurriculumContentItem;
use App\Services\CurriculumImportService;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CurriculumController extends Controller
{
    public function show(CourseAssignment $assignment)
    {
        $this->owned($assignment);
        $curricula = Curriculum::with(['tasks.operations','weeks.task','weeks.contentItems'])
            ->where('course_id', $assignment->course_id)
            ->where('professor_id', auth()->user()->professor->id)
            ->orderByDesc('version')->get();
        $assignment->load(['modalities.schedules', 'modalities.sessions']);
        $activeCurriculum = $curricula->firstWhere('is_active', true);
        $suggestions = $activeCurriculum
            ? $activeCurriculum->weeks->flatMap->contentItems->where('source_kind', 'AI_SUGGESTION')->values()
            : collect();
        $suggestionStats = [
            'total' => $suggestions->count(),
            'approved' => $suggestions->where('approved', true)->count(),
            'tec_total' => $suggestions->filter(fn ($item) => in_array(data_get($item->metadata, 'target_modality', 'BOTH'), ['TEC', 'BOTH'], true))->count(),
            'tec_approved' => $suggestions->filter(fn ($item) => in_array(data_get($item->metadata, 'target_modality', 'BOTH'), ['TEC', 'BOTH'], true) && $item->approved)->count(),
            'tall_total' => $suggestions->filter(fn ($item) => in_array(data_get($item->metadata, 'target_modality', 'BOTH'), ['TALL', 'BOTH'], true))->count(),
            'tall_approved' => $suggestions->filter(fn ($item) => in_array(data_get($item->metadata, 'target_modality', 'BOTH'), ['TALL', 'BOTH'], true) && $item->approved)->count(),
            'pending' => $suggestions->where('approved', false)->count(),
        ];
        $hasTecModality = $assignment->modalities->contains('modality', 'TEC');
        $hasTallModality = $assignment->modalities->contains('modality', 'TALL');
        $modalityStats = $assignment->modalities->mapWithKeys(function ($modality) {
            return [$modality->modality => [
                'sessions' => $modality->sessions->where('status', 'scheduled')->count(),
                'configured' => $modality->start_date && $modality->end_date && $modality->schedules->where('block_type', 'CLASS')->isNotEmpty(),
            ]];
        });
        $totalHoursTheory = $activeCurriculum?->weeks->sum(fn ($week) => (float) ($week->hours_theory ?? 0)) ?? 0;
        $totalHoursPractice = $activeCurriculum?->weeks->sum(fn ($week) => (float) ($week->hours_practice ?? 0)) ?? 0;
        $workflow = [
            'has_curriculum' => (bool) $activeCurriculum,
            'has_suggestions' => $suggestions->isNotEmpty(),
            'all_approved' => (bool) $activeCurriculum && ($suggestions->isEmpty() || $suggestions->every(fn ($item) => $item->approved)),
            'has_tec_modality' => $hasTecModality,
            'has_tall_modality' => $hasTallModality,
            'tec_configured' => !$hasTecModality || (bool) data_get($modalityStats, 'TEC.configured', false),
            'tall_configured' => !$hasTallModality || (bool) data_get($modalityStats, 'TALL.configured', false),
            'has_tec_sessions' => !$hasTecModality || (int) data_get($modalityStats, 'TEC.sessions', 0) > 0,
            'has_tall_sessions' => !$hasTallModality || (int) data_get($modalityStats, 'TALL.sessions', 0) > 0,
        ];
        $completedSteps = (int) $workflow['has_curriculum']
            + (int) $workflow['has_suggestions']
            + (int) ($workflow['tec_configured'] && $workflow['tall_configured'])
            + (int) ($workflow['has_tec_sessions'] && $workflow['has_tall_sessions'])
            + (int) $workflow['all_approved'];
        $progress = (int) round(($completedSteps / 5) * 100);
        return view('curricula.index', compact('assignment','curricula','activeCurriculum','suggestionStats','modalityStats','workflow','progress','totalHoursTheory','totalHoursPractice'));
    }

    public function extractGet(CourseAssignment $assignment)
    {
        $this->owned($assignment);

        return redirect()
            ->route('curricula.show', $assignment)
            ->with('success', 'Para analizar la malla, selecciona el PDF y pulsa «Analizar PDF y preparar prompt».');
    }

    public function extract(Request $request, CourseAssignment $assignment, CurriculumImportService $service)
    {
        $this->owned($assignment);
        $request->validate(['curriculum_pdf' => 'required|file|mimes:pdf|max:20480']);
        $file = $request->file('curriculum_pdf');
        $relative = $file->store('curricula/sources/'.$assignment->group->professor_id, 'local');
        try {
            $extracted = $service->extractPdf(Storage::disk('local')->path($relative));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($relative);
            if ($exception instanceof ValidationException) throw $exception;
            report($exception);
            throw ValidationException::withMessages(['curriculum_pdf' => 'No se pudo leer este PDF. Verifica que no esté protegido y vuelve a intentarlo.']);
        }
        $token = bin2hex(random_bytes(20));
        $textPath = 'curricula/pending/'.$token.'.txt';
        Storage::disk('local')->put($textPath, $extracted['text']);
        $promptGeneratedAt = now();
        $promptVersion = $service->promptVersion();
        $prompt = $service->buildPrompt($extracted['text'], $assignment, $promptGeneratedAt->toIso8601String());
        $promptSha256 = hash('sha256', $prompt);
        session()->put('curriculum_pdf_'.$token, [
            'assignment_id' => $assignment->id,
            'path' => $relative,
            'text_path' => $textPath,
            'filename' => $file->getClientOriginalName(),
            'sha256' => hash_file('sha256', Storage::disk('local')->path($relative)),
            'prompt_generated_at' => $promptGeneratedAt->toIso8601String(),
            'prompt_version' => $promptVersion,
            'prompt_sha256' => $promptSha256,
        ]);
        return redirect()
            ->route('curricula.prompt', ['assignment' => $assignment, 'token' => $token])
            ->with('success', 'PDF leído correctamente. Tu prompt está listo para usar en ChatGPT.');
    }

    public function showPrompt(CourseAssignment $assignment, string $token, CurriculumImportService $service)
    {
        $this->owned($assignment);

        $draft = session()->get('curriculum_pdf_'.$token);
        abort_unless(
            $draft && (int) ($draft['assignment_id'] ?? 0) === $assignment->id,
            419,
            'La sesión de análisis expiró. Vuelve a cargar el PDF para generar un nuevo prompt.'
        );

        $textPath = $draft['text_path'] ?? null;
        abort_unless(
            $textPath && Storage::disk('local')->exists($textPath),
            419,
            'El texto extraído ya no está disponible. Vuelve a cargar el PDF.'
        );

        $text = Storage::disk('local')->get($textPath);
        $promptGeneratedAt = Carbon::parse($draft['prompt_generated_at'] ?? now());
        $promptVersion = $draft['prompt_version'] ?? $service->promptVersion();
        $prompt = $service->buildPrompt($text, $assignment, $promptGeneratedAt->toIso8601String());

        return view('curricula.prompt', compact(
            'assignment',
            'prompt',
            'token',
            'promptGeneratedAt',
            'promptVersion'
        ));
    }

    public function preview(Request $request, CourseAssignment $assignment, CurriculumImportService $service)
    {
        $this->owned($assignment);
        $request->validate([
            'json_file' => 'nullable|file|mimes:json,txt|max:5120',
            'json_content' => 'nullable|string',
            'source_token' => 'nullable|string|max:80',
        ]);
        $raw = $request->input('json_content');
        if ($request->hasFile('json_file')) $raw = file_get_contents($request->file('json_file')->getRealPath());
        if (!is_string($raw) || trim($raw) === '') throw ValidationException::withMessages(['json_content' => 'Pega el JSON generado por la IA o selecciona el archivo .json.']);
        $raw = trim($raw);
        if (preg_match('/^\x60\x60\x60(?:json)?\s*(.*?)\s*\x60\x60\x60$/is', $raw, $matches)) $raw = trim($matches[1]);
        try { $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw ValidationException::withMessages(['json_content' => 'El contenido no es JSON válido: '.$exception->getMessage()]); }
        if (!is_array($payload)) throw ValidationException::withMessages(['json_content' => 'El JSON debe contener un objeto.']);
        $payload = $service->validatePayload($payload);
        $source = null; $sourceToken = $request->input('source_token');
        if ($sourceToken) {
            $draft = session()->get('curriculum_pdf_'.$sourceToken);
            if ($draft && (int) $draft['assignment_id'] === $assignment->id) {
                $source = $draft; $processing = $payload['processing'] ?? [];
                if ((int) ($payload['schema_version'] ?? 1) >= 2
                    && (($processing['prompt_version'] ?? null) !== ($draft['prompt_version'] ?? null)
                    || ($processing['prompt_generated_at'] ?? null) !== ($draft['prompt_generated_at'] ?? null))) {
                    throw ValidationException::withMessages(['json_content' => 'El JSON no corresponde a la versión/fecha del prompt. Conserva exactamente processing.prompt_version y processing.prompt_generated_at.']);
                }
            }
        }
        $token = bin2hex(random_bytes(20)); $jsonPath = 'curricula/pending/'.$token.'.json';
        Storage::disk('local')->put($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $promptInfo = $source ? [
            'prompt_version' => $source['prompt_version'] ?? null,
            'prompt_generated_at' => $source['prompt_generated_at'] ?? null,
            'prompt_sha256' => $source['prompt_sha256'] ?? null,
        ] : [
            'prompt_version' => $payload['processing']['prompt_version'] ?? null,
            'prompt_generated_at' => $payload['processing']['prompt_generated_at'] ?? null,
            'prompt_sha256' => null,
        ];
        session()->put('curriculum_json_'.$token, ['assignment_id'=>$assignment->id,'path'=>$jsonPath,'source'=>$source,'source_token'=>$sourceToken,'prompt_info'=>$promptInfo]);
        return view('curricula.preview', compact('assignment','payload','token','promptInfo'));
    }

    public function confirm(Request $request, CourseAssignment $assignment, CurriculumImportService $service, CalendarService $calendar)
    {
        $this->owned($assignment); $request->validate(['token' => 'required|string|max:80']);
        $key = 'curriculum_json_'.$request->input('token'); $draft = session()->pull($key);
        abort_unless($draft && (int) $draft['assignment_id'] === $assignment->id, 419, 'La vista previa expiró. Vuelve a importar el JSON.');
        $path = $draft['path']; abort_unless(Storage::disk('local')->exists($path), 404);
        $payload = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        $source = $draft['source'] ?? [];
        $sourceInfo = [
            'filename' => $source['filename'] ?? 'Importación JSON de malla curricular',
            'path' => $source['path'] ?? null, 'sha256' => $source['sha256'] ?? null,
            'text' => !empty($source['text_path']) && Storage::disk('local')->exists($source['text_path']) ? Storage::disk('local')->get($source['text_path']) : null,
            'prompt_version' => $draft['prompt_info']['prompt_version'] ?? ($source['prompt_version'] ?? null),
            'prompt_generated_at' => $draft['prompt_info']['prompt_generated_at'] ?? ($source['prompt_generated_at'] ?? null),
            'prompt_sha256' => $draft['prompt_info']['prompt_sha256'] ?? ($source['prompt_sha256'] ?? null),
        ];
        $curriculum = $service->importPayload($assignment, $payload, $sourceInfo, auth()->id());
        $this->rebuildCourseCalendars($assignment, $calendar);
        Storage::disk('local')->delete($path);
        if (!empty($source['text_path'])) Storage::disk('local')->delete($source['text_path']);
        return redirect()->route('curricula.show',$assignment)->with('success', 'Malla curricular importada. Se guardaron '. $curriculum->weeks->count() .' bloques; las propuestas TEC/TALL quedan pendientes de aprobación.');
    }

    public function approveSuggestions(Request $request, Curriculum $curriculum, CalendarService $calendar)
    {
        $this->ownedCurriculum($curriculum);
        $data = $request->validate([
            'approved_items' => 'nullable|array',
            'approved_items.*' => 'integer',
            'reviewed_items' => 'nullable|array',
            'reviewed_items.*' => 'integer',
        ]);
        $allowed = $curriculum->weeks()->with('contentItems')->get()->flatMap->contentItems->where('source_kind','AI_SUGGESTION')->pluck('id')->map(fn($id)=>(int)$id)->all();
        $selected = array_map('intval', $data['approved_items'] ?? []);
        $reviewed = array_map('intval', $data['reviewed_items'] ?? $allowed);
        abort_if(array_diff($selected, $allowed) || array_diff($reviewed, $allowed) || array_diff($selected, $reviewed), 422, 'La lista contiene sugerencias de otra malla.');
        foreach ($reviewed as $id) CurriculumContentItem::whereKey($id)->update(['approved'=>in_array($id,$selected,true)]);
        $assignment = \App\Models\CourseAssignment::where('curriculum_id',$curriculum->id)->whereHas('group',fn($q)=>$q->where('professor_id',auth()->user()->professor->id))->first();
        if ($assignment) $this->rebuildCourseCalendars($assignment,$calendar);
        return back()->with('success','Se actualizaron las sugerencias y se recalcularon los calendarios asociados.');
    }

    public function downloadExample(CourseAssignment $assignment)
    {
        $this->owned($assignment); $path=base_path('documentacion/mallas/PIAD-224_curriculum.json'); abort_unless(is_file($path),404);
        return response()->download($path,'PIAD-224_curriculum_ejemplo.json');
    }

    private function rebuildCourseCalendars(CourseAssignment $assignment, CalendarService $calendar): void
    {
        $assignment->refresh(); $query=\App\Models\CourseAssignment::query()->whereHas('group',fn($builder)=>$builder->where('professor_id',auth()->user()->professor->id));
        if ($assignment->curriculum_id) $query->where('curriculum_id',$assignment->curriculum_id); else $query->whereKey($assignment->id);
        foreach ($query->with('modalities.schedules')->get() as $courseAssignment) foreach ($courseAssignment->modalities as $modality) {
            if ($modality->start_date && $modality->end_date && $modality->schedules->where('block_type','CLASS')->isNotEmpty()) $calendar->rebuild($modality);
        }
    }

    private function owned(CourseAssignment $assignment): void
    {
        abort_unless($assignment->group()->where('professor_id',auth()->user()->professor->id)->exists(),404);
        abort_unless($assignment->course->professor_id===auth()->user()->professor->id,404);
    }
    private function ownedCurriculum(Curriculum $curriculum): void
    {
        abort_unless($curriculum->professor_id===auth()->user()->professor->id,404);
    }
}
