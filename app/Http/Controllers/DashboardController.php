<?php
namespace App\Http\Controllers;
use App\Models\Cycle;
use App\Models\Holiday;
use App\Models\CourseModality;
use Illuminate\Support\Carbon;
class DashboardController extends Controller {
 public function index(){
  $prof=auth()->user()->professor;
  $cycles=$prof->cycles()->withCount('groups')->orderByDesc('academic_year')->orderByDesc('cycle_code')->get();
  $groups=$prof->groups()->with(['cycle','assignments.course','assignments.modalities'])->latest()->take(8)->get();
  $modalities=CourseModality::whereHas('assignment.group',fn($q)=>$q->where('professor_id',$prof->id))->with(['assignment.course','assignment.group'])->get();
  $upcoming=Holiday::whereBetween('holiday_date',[now()->toDateString(),now()->addDays(45)->toDateString()])->where('blocks_classes',true)->where(fn($q)=>$q->whereNull('professor_id')->orWhere('professor_id',$prof->id))->orderBy('holiday_date')->get();
  return view('dashboard',compact('cycles','groups','modalities','upcoming'));
 }
}
