<?php
namespace App\Http\Controllers;
use App\Models\Holiday;
use App\Models\CourseModality;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Carbon\Carbon;
class HolidayController extends Controller {
 public function index(){ $id=auth()->user()->professor->id; $holidays=Holiday::where(fn($q)=>$q->whereNull('professor_id')->orWhere('professor_id',$id))->orderBy('holiday_date')->paginate(80); return view('holidays.index',compact('holidays')); }
 public function store(Request $request,CalendarService $calendar){ $data=$request->validate(['holiday_date'=>'required|date','name'=>'required|string|max:255','scope'=>'required|in:NATIONAL,REGIONAL,INSTITUTIONAL,SECTOR','blocks_classes'=>'required|boolean','source_url'=>'nullable|url|max:500','source_note'=>'nullable|string|max:2000']); $data['professor_id']=auth()->user()->professor->id; $data['verified']=false; $holiday=Holiday::create($data);
  $day=Carbon::parse($holiday->holiday_date)->dayOfWeekIso;
  $modalities=CourseModality::whereHas('assignment.group',fn($q)=>$q->where('professor_id',$data['professor_id']))
   ->whereDate('start_date','<=',$holiday->holiday_date)->whereDate('end_date','>=',$holiday->holiday_date)
   ->whereHas('schedules',fn($q)=>$q->where('day_of_week',$day)->where('block_type','CLASS'))
   ->with('assignment.group')->get();
  $recalculated=0;
  if($holiday->blocks_classes){ foreach($modalities as $modality){ $calendar->rebuild($modality); $recalculated++; } }
  $message='Excepción de calendario guardada.';
  if($recalculated){ $message.=" Se recalcularon automáticamente {$recalculated} modalidades afectadas y sus documentos anteriores quedaron marcados para regeneración."; }
  return back()->with('success',$message);
 }
}
