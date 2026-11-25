<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->string('career', 255)->nullable();
            $table->string('semester', 60)->nullable();
        });

        // Los grupos existentes heredan una sola vez el contexto actual del ciclo.
        // Desde este momento cada grupo mantiene sus propios valores.
        DB::table('teaching_groups')
            ->orderBy('id')
            ->chunkById(200, function ($groups) {
                $cycles = DB::table('cycles')
                    ->whereIn('id', $groups->pluck('cycle_id')->filter()->unique())
                    ->get(['id', 'career', 'semester'])
                    ->keyBy('id');

                foreach ($groups as $group) {
                    $cycle = $cycles->get($group->cycle_id);

                    if (!$cycle) {
                        continue;
                    }

                    DB::table('teaching_groups')
                        ->where('id', $group->id)
                        ->update([
                            'career' => $cycle->career,
                            'semester' => $cycle->semester,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->dropColumn(['career', 'semester']);
        });
    }
};
