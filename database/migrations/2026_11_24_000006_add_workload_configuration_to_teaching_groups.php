<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->string('workload_type', 12)->nullable()->after('notes');
            $table->date('workload_start_date')->nullable()->after('workload_type');
            $table->date('workload_end_date')->nullable()->after('workload_start_date');
            $table->json('workload_days')->nullable()->after('workload_end_date');
            $table->time('workload_start_time')->nullable()->after('workload_days');
            $table->time('workload_end_time')->nullable()->after('workload_start_time');
            $table->decimal('supervisor_theory_hours', 8, 2)->nullable()->after('workload_end_time');
            $table->decimal('supervisor_lab_hours', 8, 2)->nullable()->after('supervisor_theory_hours');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->dropColumn([
                'workload_type',
                'workload_start_date',
                'workload_end_date',
                'workload_days',
                'workload_start_time',
                'workload_end_time',
                'supervisor_theory_hours',
                'supervisor_lab_hours',
            ]);
        });
    }
};
