<?php
namespace App\Http\Controllers;
use App\Models\ClassSession;
use Illuminate\Http\Request;
class SessionController extends Controller {
 public function update(Request $request,ClassSession $session){
  abort_unless($session->modality->assignment->group->professor_id===auth()->user()->professor->id,404);
  $data=$request->validate(['topic'=>'nullable|string|max:255','objective'=>'nullable|string|max:2000','activities'=>'nullable|string|max:10000','evidence'=>'nullable|string|max:2000']);
  $session->update([...$data, 'content_origin' => 'manual']); return back()->with('success','Contenido de sesión guardado.');
 }
}
