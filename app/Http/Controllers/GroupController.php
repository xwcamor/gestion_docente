<?php
namespace App\Http\Controllers;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
class GroupController extends Controller {
 public function store(Request $request,Cycle $cycle){
  abort_unless($cycle->professor_id===auth()->user()->professor->id,404);
  $data=$request->validate(['name'=>'required|string|max:100','assigned_at'=>'nullable|date','notes'=>'nullable|string|max:2000']);
  $group=$cycle->groups()->create(['professor_id'=>$cycle->professor_id,...$data]);
  return redirect()->route('groups.show',$group)->with('success','Grupo creado. Puede agregar todos los cursos que le asignen.');
 }
 public function show(TeachingGroup $group){
  $this->owned($group); $group->load(['cycle','assignments.course','assignments.modalities.schedules']);
  return view('groups.show',compact('group'));
 }
 private function owned(TeachingGroup $group): void { abort_unless($group->professor_id===auth()->user()->professor->id,404); }
}
