<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use App\Models\GeneratedDocument;
use App\Services\ExcelDocumentService;
use App\Services\FiveMinuteTalkService;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function generate(
        CourseModality $modality,
        string $type,
        ExcelDocumentService $service,
        FiveMinuteTalkService $talkService
    ) {
        abort_unless(
            $modality->assignment->group->professor_id === auth()->user()->professor->id,
            404
        );

        if ($type === 'plan') {
            $talkService->ensureScheduled($modality->assignment->group);
        }

        $document = $service->generateForModality($modality, $type, auth()->id());

        return Storage::disk('local')->download($document->stored_path, $document->filename);
    }

    public function distribution(Cycle $cycle, ExcelDocumentService $service)
    {
        abort_unless($cycle->professor_id === auth()->user()->professor->id, 404);

        $document = $service->generateLoadDistribution($cycle, auth()->id());

        return Storage::disk('local')->download($document->stored_path, $document->filename);
    }

    public function groupFiveMinuteTalks(TeachingGroup $group, ExcelDocumentService $service)
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
        abort_unless($group->fiveMinuteTalks()->exists(), 404, 'Primero abre el grupo para distribuir sus charlas institucionales.');

        $document = $service->generateFiveMinuteTalks($group, auth()->id());

        return Storage::disk('local')->download($document->stored_path, $document->filename);
    }

    public function download(GeneratedDocument $document)
    {
        $owned =
            ($document->course_modality_id && $document->modality->assignment->group->professor_id === auth()->user()->professor->id)
            || ($document->teaching_group_id && $document->group->professor_id === auth()->user()->professor->id)
            || ($document->cycle_id && $document->cycle->professor_id === auth()->user()->professor->id);

        abort_unless($owned, 404);
        abort_unless(Storage::disk('local')->exists($document->stored_path), 404, 'El archivo ya no existe. Genera nuevamente el documento.');

        return Storage::disk('local')->download($document->stored_path, $document->filename);
    }
}
