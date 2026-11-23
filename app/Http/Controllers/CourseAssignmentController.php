<?php
namespace App\Http\Controllers;
use App\Models\TeachingGroup;
use App\Models\Course;
use App\Models\CourseModality;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CourseAssignmentController extends Controller {
 public function store(Request $request,TeachingGroup $group,CalendarService $calendar){
  abort_unless($group->professor_id===auth()->user()->professor->id,404);
  $data=$request->validate([
   'course_name'=>'required|string|max:255','course_code'=>'nullable|string|max:80','career'=>'nullable|string|max:255','module'=>'nullable|string|max:50','assigned_at'=>'nullable|date','notes'=>'nullable|string|max:2000',
   'has_tec'=>'nullable|boolean','has_tall'=>'nullable|boolean',
   'tec_nrc'=>'nullable|string|max:30','tec_start_date'=>'nullable|date','tec_end_date'=>'nullable|date|after_or_equal:tec_start_date','tec_total_hours'=>'nullable|numeric|min:0','tec_days'=>'nullable|array','tec_days.*'=>'integer|min:1|max:7','tec_start_time'=>'nullable|date_format:H:i','tec_end_time'=>'nullable|date_format:H:i','tec_classroom'=>'nullable|string|max:100',
   'tall_nrc'=>'nullable|string|max:30','tall_start_date'=>'nullable|date','tall_end_date'=>'nullable|date|after_or_equal:tall_start_date','tall_total_hours'=>'nullable|numeric|min:0','tall_days'=>'nullable|array','tall_days.*'=>'integer|min:1|max:7','tall_start_time'=>'nullable|date_format:H:i','tall_end_time'=>'nullable|date_format:H:i','tall_classroom'=>'nullable|string|max:100',
   'tec_schedules'=>'nullable|array','tec_schedules.*.day_of_week'=>'nullable|integer|min:1|max:7','tec_schedules.*.start_time'=>'nullable|date_format:H:i','tec_schedules.*.end_time'=>'nullable|date_format:H:i','tec_schedules.*.classroom'=>'nullable|string|max:100','tec_schedules.*.block_type'=>'nullable|in:CLASS,ASYNC',
   'tall_schedules'=>'nullable|array','tall_schedules.*.day_of_week'=>'nullable|integer|min:1|max:7','tall_schedules.*.start_time'=>'nullable|date_format:H:i','tall_schedules.*.end_time'=>'nullable|date_format:H:i','tall_schedules.*.classroom'=>'nullable|string|max:100','tall_schedules.*.block_type'=>'nullable|in:CLASS,ASYNC',
  ]);
  if(!$request->boolean('has_tec')&&!$request->boolean('has_tall')) throw ValidationException::withMessages(['modalities'=>'Seleccione TEC, TALL o ambas modalidades.']);
  $professorId=$group->professor_id; $code=$data['course_code'] ?: Str::upper(Str::slug($data['course_name'],'-'));
  $course=Course::firstOrCreate(['professor_id'=>$professorId,'code'=>$code],['name'=>$data['course_name'],'career'=>$data['career']??null,'module'=>$data['module']??null]);
  $defaultCurriculum=$course->curricula()->where('is_active',true)->with('weeks')->first();
  $assignment=$group->assignments()->firstOrCreate(['course_id'=>$course->id],[
   'curriculum_id'=>$defaultCurriculum?->id,
   'assigned_at'=>$data['assigned_at']??now()->toDateString(),
   'notes'=>$data['notes']??null,
  ]);
  if (!$assignment->curriculum_id && $defaultCurriculum) $assignment->update(['curriculum_id'=>$defaultCurriculum->id]);
  foreach(['TEC'=>'tec','TALL'=>'tall'] as $kind=>$prefix){
   if(!$request->boolean('has_'.$prefix)) continue;
   $modality=CourseModality::updateOrCreate(['course_assignment_id'=>$assignment->id,'modality'=>$kind],[
    'nrc'=>$data[$prefix.'_nrc']??null,'start_date'=>$data[$prefix.'_start_date']??null,'end_date'=>$data[$prefix.'_end_date']??null,
    'total_hours'=>$data[$prefix.'_total_hours']??null,'curriculum_start_week'=>1,'active'=>true,'semester'=>$data['module']??null,
    'period_label'=>$group->cycle->academic_year.'-'.$group->cycle->cycle_code.'-'.$kind,
   ]);
   $modality->schedules()->delete();
   foreach(($data[$prefix.'_schedules']??[]) as $schedule){
    if(empty($schedule['day_of_week']) || empty($schedule['start_time']) || empty($schedule['end_time']) || $schedule['end_time'] <= $schedule['start_time']) continue;
    $modality->schedules()->create(['day_of_week'=>$schedule['day_of_week'],'start_time'=>$schedule['start_time'],'end_time'=>$schedule['end_time'],'classroom'=>$schedule['classroom']??null,'block_type'=>$schedule['block_type']??'CLASS']);
   }
   if(empty($data[$prefix.'_schedules']) && !empty($data[$prefix.'_days']) && isset($data[$prefix.'_start_time'],$data[$prefix.'_end_time'])) {
    foreach(array_unique($data[$prefix.'_days']) as $day) if($data[$prefix.'_end_time']>$data[$prefix.'_start_time']) $modality->schedules()->create(['day_of_week'=>$day,'start_time'=>$data[$prefix.'_start_time'],'end_time'=>$data[$prefix.'_end_time'],'classroom'=>$data[$prefix.'_classroom']??null,'block_type'=>'CLASS']);
   }
   if ($modality->start_date && $modality->end_date && $modality->schedules()->where('block_type','CLASS')->exists()) {
    $calendar->rebuild($modality);
   }
  }
  return redirect()->route('groups.show',$group)->with('success','Curso asignado. Se calcularon automáticamente las sesiones de las modalidades que ya tienen fechas y horarios. Cada modalidad conserva sus propios NRC, alumnos y documentos; puede agregar más cursos sin límite fijo.');
 }
}
