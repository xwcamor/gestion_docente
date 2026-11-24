<?php
namespace App\Services;

use App\Models\CourseModality;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\TeachingGroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    public function preview(string $path, string $extension): array
    {
        [$rows, $format] = $this->readRows($path, $extension);
        if (count($rows) < 2) throw ValidationException::withMessages(['roster' => 'El archivo no contiene filas de alumnos.']);
        $headers = array_map(fn ($v) => $this->normalize((string) $v), array_shift($rows));
        $map = $this->mapHeaders($headers);
        if (!isset($map['surnames']) || !isset($map['given_names']) || (!isset($map['institutional_id']) && !isset($map['username'])))
            throw ValidationException::withMessages(['roster' => 'No se reconocen los campos obligatorios del archivo original del campus.']);
        $sample=[];
        foreach (array_slice($rows,0,8) as $row) {
            $get=fn($key)=>isset($map[$key])?trim((string)($row[$map[$key]]??'')):'';
            $sample[]=['id'=>$get('institutional_id'),'surnames'=>$get('surnames'),'names'=>$get('given_names'),'username'=>$get('username'),'grade'=>$get('platform_grade')];
        }
        return ['format'=>$format,'total'=>count($rows),'headers'=>$headers,'sample'=>$sample,'has_grade'=>isset($map['platform_grade'])];
    }

    public function import(CourseModality $modality, UploadedFile $file, int $userId): ImportBatch
    {
        $professorId = $modality->assignment->group->professor_id;
        $stored = $file->store('imports/'.$professorId, 'local');
        return $this->importStored($modality, $stored, $file->getClientOriginalName(), $file->getClientOriginalExtension(), $userId);
    }

    public function importStored(CourseModality $modality, string $stored, string $originalFilename, string $extension, int $userId): ImportBatch
    {
        $professorId = $modality->assignment->group->professor_id;
        $path = Storage::disk('local')->path($stored);
        [$rows, $format] = $this->readRows($path, $extension);
        if (count($rows) < 2) throw ValidationException::withMessages(['roster' => 'El archivo no contiene filas de alumnos.']);
        $headers = array_map(fn ($v) => $this->normalize((string) $v), array_shift($rows));
        $map = $this->mapHeaders($headers);
        foreach (['surnames','given_names'] as $required) {
            if (!isset($map[$required])) throw ValidationException::withMessages(['roster' => 'No se reconoce la columna obligatoria de apellidos o nombre.']);
        }
        if (!isset($map['institutional_id']) && !isset($map['username'])) {
            throw ValidationException::withMessages(['roster' => 'No se reconoce ID de estudiante ni Nombre de usuario para identificar alumnos.']);
        }
        $batch = ImportBatch::create([
            'course_modality_id' => $modality->id, 'user_id' => $userId,
            'original_filename' => $originalFilename, 'stored_path' => $stored,
            'detected_format' => $format, 'total_rows' => count($rows),
        ]);
        $created = 0; $updated = 0; $skipped = 0; $errors = []; $seen = [];
        DB::transaction(function () use ($rows, $map, $professorId, $modality, $batch, &$created, &$updated, &$skipped, &$errors, &$seen) {
            foreach ($rows as $index => $row) {
                $value = fn ($key) => isset($map[$key]) ? trim((string) ($row[$map[$key]] ?? '')) : '';
                $id = $value('institutional_id'); $username = $value('username');
                $surnames = $value('surnames'); $names = $value('given_names');
                if ($surnames === '' && $names === '' && $id === '' && $username === '') { $skipped++; continue; }
                if ($surnames === '' || $names === '' || ($id === '' && $username === '')) {
                    $skipped++; $errors[] = 'Fila '.($index+2).': falta nombre/apellido o identificador.'; continue;
                }
                $identity = $id !== '' ? 'ID:'.$id : 'USER:'.$username;
                if (isset($seen[$identity])) { $skipped++; $errors[] = 'Fila '.($index+2).': identificador repetido dentro del archivo.'; continue; }
                $seen[$identity] = true;
                $query = Student::where('professor_id',$professorId);
                if ($id !== '') $query->where('institutional_id',$id); else $query->where('username',$username);
                $student = $query->first();
                $attributes = [
                    'professor_id'=>$professorId, 'institutional_id'=>$id ?: ($student?->institutional_id),
                    'username'=>$username ?: ($student?->username), 'surnames'=>$surnames, 'given_names'=>$names,
                    'last_access_source'=>$value('last_access') ?: null, 'availability_source'=>$value('availability') ?: null,
                ];
                if ($student) { $student->update($attributes); $updated++; }
                else { $student = Student::create($attributes); $created++; }
                $enrollment = Enrollment::firstOrNew(['course_modality_id'=>$modality->id,'student_id'=>$student->id]);
                $enrollment->import_batch_id = $batch->id;
                $grade = $value('platform_grade');
                if ($grade !== '' && is_numeric(str_replace(',','.',$grade))) $enrollment->platform_grade = (float) str_replace(',','.',$grade);
                $enrollment->platform_last_access = $value('last_access') ?: null;
                $enrollment->platform_availability = $value('availability') ?: null;
                $enrollment->status = 'active'; $enrollment->save();
            }
        });
        $batch->update(['created_count'=>$created,'updated_count'=>$updated,'skipped_count'=>$skipped,'errors'=>$errors]);
        return $batch->fresh();
    }

    /**
     * Importa una sola lista de alumnos en el grupo y crea matrículas vacías
     * para los cursos actuales. Las notas y datos de actividad no se copian
     * entre cursos: pertenecen a cada matrícula individual.
     */
    public function importGroupStored(TeachingGroup $group, string $stored, string $originalFilename, string $extension, int $userId): ImportBatch
    {
        $path = Storage::disk('local')->path($stored);
        [$rows, $format] = $this->readRows($path, $extension);

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['roster' => 'El archivo no contiene filas de alumnos.']);
        }

        $headers = array_map(fn ($value) => $this->normalize((string) $value), array_shift($rows));
        $map = $this->mapHeaders($headers);

        foreach (['surnames', 'given_names'] as $required) {
            if (!isset($map[$required])) {
                throw ValidationException::withMessages(['roster' => 'No se reconoce la columna obligatoria de apellidos o nombre.']);
            }
        }

        if (!isset($map['institutional_id']) && !isset($map['username'])) {
            throw ValidationException::withMessages(['roster' => 'No se reconoce ID de estudiante ni Nombre de usuario para identificar alumnos.']);
        }

        $batch = ImportBatch::create([
            'course_modality_id' => null,
            'teaching_group_id' => $group->id,
            'user_id' => $userId,
            'original_filename' => $originalFilename,
            'stored_path' => $stored,
            'detected_format' => $format,
            'total_rows' => count($rows),
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $seen = [];

        DB::transaction(function () use ($rows, $map, $group, &$created, &$updated, &$skipped, &$errors, &$seen) {
            foreach ($rows as $index => $row) {
                $value = fn ($key) => isset($map[$key]) ? trim((string) ($row[$map[$key]] ?? '')) : '';
                $id = $value('institutional_id');
                $username = $value('username');
                $surnames = $value('surnames');
                $names = $value('given_names');

                if ($surnames === '' && $names === '' && $id === '' && $username === '') {
                    $skipped++;
                    continue;
                }

                if ($surnames === '' || $names === '' || ($id === '' && $username === '')) {
                    $skipped++;
                    $errors[] = 'Fila '.($index + 2).': falta nombre/apellido o identificador.';
                    continue;
                }

                $identity = $id !== '' ? 'ID:'.$id : 'USER:'.$username;
                if (isset($seen[$identity])) {
                    $skipped++;
                    $errors[] = 'Fila '.($index + 2).': identificador repetido dentro del archivo.';
                    continue;
                }
                $seen[$identity] = true;

                $query = Student::where('professor_id', $group->professor_id);
                if ($id !== '') {
                    $query->where('institutional_id', $id);
                } else {
                    $query->where('username', $username);
                }

                $student = $query->first();
                $attributes = [
                    'professor_id' => $group->professor_id,
                    'institutional_id' => $id ?: ($student?->institutional_id),
                    'username' => $username ?: ($student?->username),
                    'surnames' => $surnames,
                    'given_names' => $names,
                    'last_access_source' => $value('last_access') ?: $student?->last_access_source,
                    'availability_source' => $value('availability') ?: $student?->availability_source,
                ];

                if ($student) {
                    $student->update($attributes);
                    $updated++;
                } else {
                    $student = Student::create($attributes);
                    $created++;
                }

                $group->students()->syncWithoutDetaching([$student->id]);
            }

            $modalityIds = $group->assignments()
                ->with('modalities:id,course_assignment_id')
                ->get()
                ->flatMap(fn ($assignment) => $assignment->modalities)
                ->pluck('id')
                ->all();

            $studentIds = $group->students()->pluck('students.id')->all();
            $timestamp = now();
            foreach (array_chunk($studentIds, 500) as $studentChunk) {
                $enrollmentRows = [];
                foreach ($modalityIds as $modalityId) {
                    foreach ($studentChunk as $studentId) {
                        $enrollmentRows[] = [
                            'course_modality_id' => $modalityId,
                            'student_id' => $studentId,
                            'status' => 'active',
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }
                }
                if ($enrollmentRows) {
                    DB::table('enrollments')->insertOrIgnore($enrollmentRows);
                }
            }
        });

        $batch->update([
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'errors' => $errors,
        ]);

        return $batch->fresh();
    }

    /**
     * When a course is added after the group roster, enrol its existing students
     * without overwriting course-specific grades or platform data.
     */
    public function syncGroupRosterToModality(CourseModality $modality): int
    {
        $group = $modality->assignment()->with('group')->firstOrFail()->group;
        $studentIds = $group->students()->pluck('students.id')->all();
        $created = 0;
        $timestamp = now();

        foreach (array_chunk($studentIds, 500) as $studentChunk) {
            $rows = array_map(fn ($studentId) => [
                'course_modality_id' => $modality->id,
                'student_id' => $studentId,
                'status' => 'active',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $studentChunk);

            if ($rows) {
                $created += DB::table('enrollments')->insertOrIgnore($rows);
            }
        }

        return $created;
    }

    private function readRows(string $path, string $extension): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) throw ValidationException::withMessages(['roster'=>'No se pudo leer el archivo.']);
        $sample = substr($raw, 0, 4096);
        $looksTabText = str_contains($sample, "\0") || str_starts_with($sample, "\xFF\xFE") || str_starts_with($sample, "\xFE\xFF");
        if ($looksTabText) {
            $encoding = str_starts_with($raw,"\xFE\xFF") ? 'UTF-16BE' : 'UTF-16LE';
            $text = mb_convert_encoding($raw, 'UTF-8', $encoding);
            $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
            $stream = fopen('php://temp','r+'); fwrite($stream,$text); rewind($stream);
            $rows=[]; while (($row=fgetcsv($stream,0,"\t",'"','\\')) !== false) $rows[]=$row; fclose($stream);
            return [$rows,'TSV UTF-16 (exportación del campus)'];
        }
        $type = IOFactory::identify($path);
        $spreadsheet = IOFactory::load($path);
        return [$spreadsheet->getActiveSheet()->toArray(null,true,true,false),$type];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        return preg_replace('/\s+/u',' ',$value);
    }

    private function mapHeaders(array $headers): array
    {
        $map=[];
        foreach ($headers as $i=>$header) {
            if (str_contains($header,'apellido')) $map['surnames']=$i;
            elseif ($header === 'nombre' || $header === 'nombres') $map['given_names']=$i;
            elseif (str_contains($header,'nombre de usuario')) $map['username']=$i;
            elseif (str_contains($header,'id de estudiante')) $map['institutional_id']=$i;
            elseif (str_contains($header,'ultimo acceso')) $map['last_access']=$i;
            elseif (str_contains($header,'disponibilidad')) $map['availability']=$i;
            elseif (str_starts_with($header,'calificacion general')) $map['platform_grade']=$i;
        }
        return $map;
    }
}
