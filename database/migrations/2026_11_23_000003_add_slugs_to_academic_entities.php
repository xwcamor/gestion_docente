<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'cycles' => ['professor_id', 'academic_year', 'cycle_code'],
        'teaching_groups' => ['cycle_id', 'name'],
        'courses' => ['professor_id', 'code', 'name'],
        'course_assignments' => ['teaching_group_id', 'course_id'],
        'course_modalities' => ['course_assignment_id', 'modality'],
    ];

    public function up(): void
    {
        foreach (array_keys($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('slug', 191)->nullable()->after('id');
            });
        }

        $this->backfillSlugs();

        foreach (array_keys($this->tables) as $tableName) {
            DB::statement("ALTER TABLE {$tableName} MODIFY slug VARCHAR(191) NOT NULL");

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unique('slug', "{$tableName}_slug_unique");
            });
        }
    }

    private function backfillSlugs(): void
    {
        DB::table('cycles')->select('id', 'academic_year', 'cycle_code')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $this->saveSlug('cycles', $row->id, "ciclo-{$row->academic_year}-{$row->cycle_code}");
            }
        });

        DB::table('teaching_groups')->select('id', 'cycle_id', 'name')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $this->saveSlug('teaching_groups', $row->id, 'grupo-'.$row->cycle_id.'-'.$row->name);
            }
        });

        DB::table('courses')->select('id', 'code', 'name')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $this->saveSlug('courses', $row->id, 'curso-'.($row->code ?: $row->name));
            }
        });

        DB::table('course_assignments')->select('id', 'teaching_group_id', 'course_id')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $this->saveSlug('course_assignments', $row->id, "asignacion-grupo-{$row->teaching_group_id}-curso-{$row->course_id}");
            }
        });

        DB::table('course_modalities')->select('id', 'course_assignment_id', 'modality')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $this->saveSlug('course_modalities', $row->id, "modalidad-{$row->modality}-asignacion-{$row->course_assignment_id}");
            }
        });
    }

    private function saveSlug(string $table, int $id, string $source): void
    {
        $slug = Str::limit(Str::slug($source), 165, '').'-'.$id;

        DB::table($table)->where('id', $id)->update(['slug' => $slug]);
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys($this->tables)) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropUnique("{$tableName}_slug_unique");
                $table->dropColumn('slug');
            });
        }
    }
};
