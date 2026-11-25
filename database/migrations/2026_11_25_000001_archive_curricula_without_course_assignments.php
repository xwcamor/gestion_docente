<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('curricula') || !Schema::hasTable('course_assignments')) {
            return;
        }

        if (!Schema::hasColumn('curricula', 'is_active')
            || !Schema::hasColumn('curricula', 'course_id')
            || !Schema::hasColumn('course_assignments', 'course_id')) {
            return;
        }

        // Conserva versiones, semanas, contenidos y PDF. Solo archiva las mallas
        // activas cuyo curso ya no está asignado a ningún grupo.
        DB::table('curricula')
            ->where('is_active', true)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('course_assignments')
                    ->whereColumn('course_assignments.course_id', 'curricula.course_id');
            })
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intencionalmente no reactivar registros: no se puede reconstruir con
        // seguridad cuáles se archivaron por esta limpieza y cuáles ya eran históricos.
    }
};
