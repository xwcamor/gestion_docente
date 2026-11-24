<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tables whose models use HasSlugRouteKey.
     * The source fields match each model's slugSource() method.
     */
    private array $slugTables = [
        'cycles' => ['professor_id', 'academic_year', 'cycle_code'],
        'teaching_groups' => ['cycle_id', 'name'],
        'courses' => ['professor_id', 'code', 'name'],
        'course_assignments' => ['teaching_group_id', 'course_id'],
        'course_modalities' => ['course_assignment_id', 'modality'],
        'curricula' => ['course_id', 'version'],
        'class_sessions' => ['course_modality_id', 'session_date', 'session_number'],
        'evaluations' => ['course_modality_id', 'title'],
        'generated_documents' => ['document_type', 'version', 'filename'],
    ];

    public function up(): void
    {
        foreach ($this->slugTables as $table => $fields) {
            if (! Schema::hasColumn($table, 'slug')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('slug', 191)->nullable()->after('id');
                });
            }

            $this->backfillTable($table, $fields);

            DB::statement("ALTER TABLE {$table} MODIFY slug VARCHAR(191) NOT NULL");

            $indexName = "{$table}_slug_unique";
            $indexes = DB::select(
                "SHOW INDEX FROM {$table} WHERE Key_name = ?",
                [$indexName]
            );

            if ($indexes === []) {
                Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
                    $blueprint->unique('slug', $indexName);
                });
            }
        }
    }

    private function backfillTable(string $table, array $fields): void
    {
        $seenSlugs = [];

        DB::table($table)
            ->select(array_values(array_unique(array_merge(['id', 'slug'], $fields))))
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, &$seenSlugs): void {
                foreach ($rows as $row) {
                    $slug = trim((string) ($row->slug ?? ''));

                    if ($slug === '' || isset($seenSlugs[$slug])) {
                        $base = Str::limit(Str::slug($this->slugSource($table, $row)), 165, '');
                        $slug = $base.'-'.$row->id;
                        $suffix = 1;

                        while (isset($seenSlugs[$slug])) {
                            $slug = $base.'-'.$row->id.'-'.$suffix;
                            $suffix++;
                        }

                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['slug' => $slug]);
                    }

                    $seenSlugs[$slug] = true;
                }
            });
    }

    private function slugSource(string $table, object $row): string
    {
        return match ($table) {
            'cycles' => "ciclo-{$row->academic_year}-{$row->cycle_code}",
            'teaching_groups' => "grupo-{$row->cycle_id}-{$row->name}",
            'courses' => 'curso-'.($row->code ?: $row->name),
            'course_assignments' => "asignacion-grupo-{$row->teaching_group_id}-curso-{$row->course_id}",
            'course_modalities' => "modalidad-{$row->modality}-asignacion-{$row->course_assignment_id}",
            'curricula' => "malla-curso-{$row->course_id}-version-{$row->version}",
            'class_sessions' => "sesion-modalidad-{$row->course_modality_id}-{$row->session_date}-{$row->session_number}",
            'evaluations' => "evaluacion-modalidad-{$row->course_modality_id}-{$row->title}",
            'generated_documents' => "documento-{$row->document_type}-{$row->version}-{$row->filename}",
            default => "{$table}-{$row->id}",
        };
    }

    public function down(): void
    {
        // Intentionally retain slug columns and values.
        // Routes and existing bookmarks may already depend on them.
    }
};
