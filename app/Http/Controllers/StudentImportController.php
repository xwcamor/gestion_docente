<?php
namespace App\Http\Controllers;
use App\Models\CourseModality;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class StudentImportController extends Controller {
 public function preview(Request $request,CourseModality $modality,StudentImportService $service){
  $this->owned($modality); $request->validate(['roster'=>'required|file|max:10240']);
  $file=$request->file('roster'); $stored=$file->store('imports/pending/'.$modality->id,'local');
  try { $preview=$service->preview(Storage::disk('local')->path($stored),$file->getClientOriginalExtension()); }
  catch(\Throwable $e){ Storage::disk('local')->delete($stored); throw $e; }
  $token=bin2hex(random_bytes(20)); session()->put('pending_roster_'.$token,['modality_id'=>$modality->id,'path'=>$stored,'filename'=>$file->getClientOriginalName(),'extension'=>$file->getClientOriginalExtension()]);
  return view('modalities.import-preview',compact('modality','preview','token'));
 }
 public function confirm(Request $request,CourseModality $modality,StudentImportService $service){
  $this->owned($modality); $request->validate(['token'=>'required|string']);
  $key='pending_roster_'.$request->input('token'); $pending=session()->pull($key);
  abort_unless($pending && (int)$pending['modality_id']===$modality->id,419,'La vista previa expiró. Vuelve a cargar el archivo.');
  $batch=$service->importStored($modality,$pending['path'],$pending['filename'],$pending['extension'],auth()->id());
  return redirect()->route('modalities.show',$modality)->with('success',"Importación completada: {$batch->created_count} alumnos nuevos, {$batch->updated_count} actualizados y {$batch->skipped_count} omitidos.");
 }
 private function owned(CourseModality $m): void { abort_unless($m->assignment->group->professor_id===auth()->user()->professor->id,404); }
}
