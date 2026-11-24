<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\TeachingGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRegistrationService
{
    public function __construct(
        private readonly CourseCodeParser $parser,
    ) {}

    public function register(
        TeachingGroup $group,
        string $courseName,
        string $sourceCode,
        string $component,
        StudentImportService $students,
        array $modalityDates = [],
    ): array {
        $courseName = trim($courseName);
        $parsed = $this->parser->parse($sourceCode);

        if (!$parsed['has_structured_code'] && mb_strlen($parsed['base_code']) > 80) {
            throw ValidationException::withMessages([
                'course_code' => 'El código no tiene un formato institucional reconocible y supera los 80 caracteres.',
            ]);
        }

        if ($parsed['errors']) {
            throw ValidationException::withMessages([
                'course_code' => $parsed['errors'],
            ]);
        }

        $requestedTypes = match ($component) {
            'TEC' => ['TEC'],
            'TALL' => ['TALL'],
            'BOTH' => ['TEC', 'TALL'],
            default => [],
        };

        if (!$requestedTypes) {
            throw ValidationException::withMessages([
                'component' => 'Selecciona Teoría, Taller o Ambos.',
            ]);
        }

        if ($courseName === '') {
            throw ValidationException::withMessages([
                'course_name' => 'Escribe el nombre del curso.',
            ]);
        }

        $group->loadMissing('cycle');

        $course = $this->findExistingCourse($group->professor_id, $parsed);

        if ($course && mb_strtolower(trim($course->name)) !== mb_strtolower($courseName)) {
            throw ValidationException::withMessages([
                'course_code' => "El código {$parsed['base_code']} ya pertenece al curso «{$course->name}». Verifica el nombre.",
            ]);
        }

        $sameName = Course::query()
            ->where('professor_id', $group->professor_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($courseName)])
            ->first();

        if ($sameName && (!$course || (int) $sameName->id !== (int) $course->id)) {
            throw ValidationException::withMessages([
                'course_code' => "Ya existe «{$courseName}» con el código {$sameName->code}. Reutiliza ese curso para evitar duplicados.",
            ]);
        }

        return DB::transaction(function () use ($group, $courseName, $parsed, $requestedTypes, $course, $students, $modalityDates) {
            $courseRecord = $course ?: Course::firstOrCreate(
                [
                    'professor_id' => $group->professor_id,
                    'code' => $parsed['base_code'],
                ],
                ['name' => $courseName]
            );

            $defaultCurriculum = $courseRecord->curricula()
                ->where('is_active', true)
                ->first();

            $assignment = CourseAssignment::query()
                ->where('teaching_group_id', $group->id)
                ->where('course_id', $courseRecord->id)
                ->with('modalities')
                ->first();

            if (!$assignment) {
                $assignment = $group->assignments()->create([
                    'course_id' => $courseRecord->id,
                    'curriculum_id' => $defaultCurriculum?->id,
                    'assigned_at' => now()->toDateString(),
                ]);
                $assignment->setRelation('modalities', collect());
            }

            $existingTypes = $assignment->modalities->pluck('modality')->all();
            $typesToCreate = array_values(array_diff($requestedTypes, $existingTypes));

            if (!$typesToCreate) {
                throw ValidationException::withMessages([
                    'course_code' => 'Este curso ya tiene las modalidades seleccionadas en el grupo. No se modificó ningún NRC ni horario existente.',
                ]);
            }

            $createdTypes = [];

            foreach ($typesToCreate as $type) {
                $modality = $assignment->modalities()->create([
                    'modality' => $type,
                    'nrc' => $parsed['nrc_by_modality'][$type] ?? null,
                    'start_date' => $modalityDates[$type]['start_date'] ?? null,
                    'end_date' => $modalityDates[$type]['end_date'] ?? null,
                    'curriculum_start_week' => 1,
                    'active' => true,
                    'semester' => null,
                    'period_label' => $group->cycle->academic_year.'-'.$group->cycle->cycle_code.'-'.$type,
                ]);

                $students->syncGroupRosterToModality($modality);
                $createdTypes[] = $type;
            }

            return [
                'course' => $courseRecord,
                'assignment' => $assignment->fresh('modalities'),
                'created_modalities' => $createdTypes,
                'missing_nrc' => array_values(array_filter(
                    $typesToCreate,
                    fn ($type) => blank($parsed['nrc_by_modality'][$type] ?? null)
                )),
                'parsed' => $parsed,
            ];
        });
    }

    private function findExistingCourse(int $professorId, array $parsed): ?Course
    {
        $query = Course::query()->where('professor_id', $professorId);

        $course = (clone $query)->where('code', $parsed['base_code'])->first();
        if ($course || !$parsed['has_structured_code']) {
            return $course;
        }

        $legacyCandidates = (clone $query)
            ->where('code', 'like', $parsed['base_code'].'-%')
            ->get();

        return $legacyCandidates->first(function (Course $candidate) use ($parsed) {
            $legacy = $this->parser->parse($candidate->code);

            return !$legacy['errors'] && $legacy['base_code'] === $parsed['base_code'];
        });
    }
}
