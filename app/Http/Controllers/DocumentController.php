<?php
namespace App\Http\Controllers;
use App\Models\CourseModality;
use App\Models\Cycle;
use App\Models\GeneratedDocument;
use App\Services\ExcelDocumentService;
use Illuminate\Support\Facades\Storage;
class DocumentController extends Controller {
 public function generate(CourseModality $modality,string $type,ExcelDocumentService $service){
  abort_unless($modality->assignment->group->professor_id===auth()->user()->professor->id,404);
  $document=$service->generateForModality($modality,$type,auth()->id());
  return Storage::disk('local')->download($document->stored_path,$document->filename);
 }
 public function distribution(Cycle $cycle,ExcelDocumentService $service){
  abort_unless($cycle->professor_id===auth()->user()->professor->id,404);
  $doc=$service->generateLoadDistribution($cycle,auth()->id());
  return Storage::disk('local')->download($doc->stored_path,$doc->filename);
 }
 public function download(GeneratedDocument $document){
  $owned=($document->course_modality_id && $document->modality->assignment->group->professor_id===auth()->user()->professor->id)
   || ($document->teaching_group_id && $document->group->professor_id===auth()->user()->professor->id);
  abort_unless($owned,404); return Storage::disk('local')->download($document->stored_path,$document->filename);
 }
}
