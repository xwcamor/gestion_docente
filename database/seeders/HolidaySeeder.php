<?php
namespace Database\Seeders;
use App\Models\Holiday;
use App\Models\Institution;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
class HolidaySeeder extends Seeder {
 public function run(): void {
  $fixed=[
   ['01-01','Año Nuevo'],['05-01','Día del Trabajo'],['06-07','Batalla de Arica y Día de la Bandera'],
   ['06-29','San Pedro y San Pablo'],['07-23','Día de la Fuerza Aérea del Perú'],['07-28','Fiestas Patrias'],['07-29','Fiestas Patrias'],
   ['08-06','Batalla de Junín'],['08-30','Santa Rosa de Lima'],['10-08','Combate de Angamos'],['11-01','Día de Todos los Santos'],
   ['12-08','Inmaculada Concepción'],['12-09','Batalla de Ayacucho'],['12-25','Navidad del Señor'],
  ];
  $institutions = Institution::query()->where('status', 'active')->get(['id']);

  foreach ($institutions as $institution) {
   for($year=2027;$year<=2031;$year++){
    $dates=[]; foreach($fixed as [$md,$name]) $dates[]=[$year.'-'.$md,$name];
    $easter=$this->easterSunday($year); $dates[]=[(clone $easter)->subDays(3)->toDateString(),'Jueves Santo']; $dates[]=[(clone $easter)->subDays(2)->toDateString(),'Viernes Santo'];
    foreach($dates as [$date,$name]) Holiday::firstOrCreate(
     ['institution_id'=>$institution->id,'professor_id'=>null,'holiday_date'=>$date,'name'=>$name],
     ['scope'=>'NATIONAL','blocks_classes'=>true,'source_url'=>'https://www.gob.pe/feriados','source_note'=>'Proyección inicial de calendario nacional; validar cada año con fuente oficial.','verified'=>false]
    );
   }
  }
 }
 private function easterSunday(int $year): Carbon {
  $a=$year%19; $b=intdiv($year,100); $c=$year%100; $d=intdiv($b,4); $e=$b%4; $f=intdiv($b+8,25); $g=intdiv($b-$f+1,3);
  $h=(19*$a+$b-$d-$g+15)%30; $i=intdiv($c,4); $k=$c%4; $l=(32+2*$e+2*$i-$h-$k)%7; $m=intdiv($a+11*$h+22*$d,451);
  $month=intdiv($h+$l-7*$m+114,31); $day=(($h+$l-7*$m+114)%31)+1;
  return Carbon::create($year,$month,$day)->startOfDay();
 }
}
