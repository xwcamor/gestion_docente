<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('modality_schedules', 'repeat_interval_weeks')) {
            Schema::table('modality_schedules', function (Blueprint $table): void {
                $table->unsignedTinyInteger('repeat_interval_weeks')
                    ->default(1)
                    ->after('instruction_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modality_schedules', 'repeat_interval_weeks')) {
            Schema::table('modality_schedules', function (Blueprint $table): void {
                $table->dropColumn('repeat_interval_weeks');
            });
        }
    }
};
