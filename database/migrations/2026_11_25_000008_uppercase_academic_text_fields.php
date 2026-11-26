<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'cycles' => ['cycle_code', 'career'],
            'teaching_groups' => ['name', 'code', 'career'],
            'courses' => ['name', 'code', 'career'],
            'course_modalities' => ['nrc', 'block_code', 'block_reference'],
            'modality_schedules' => ['classroom', 'block_type', 'instruction_type'],
            'students' => ['surnames', 'given_names'],
        ];

        foreach ($columns as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($fields as $field) {
                if (!Schema::hasColumn($table, $field)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($field)
                    ->where($field, '<>', '')
                    ->update([$field => DB::raw("UPPER(`{$field}`)")]);
            }
        }
    }

    public function down(): void
    {
        // La conversión de texto a mayúsculas no puede revertirse sin perder información.
    }
};
