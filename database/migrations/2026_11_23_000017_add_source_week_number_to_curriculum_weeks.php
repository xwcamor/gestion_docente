<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('curriculum_weeks', 'source_week_number')) {
            Schema::table('curriculum_weeks', function (Blueprint $table) {
                $table->unsignedSmallInteger('source_week_number')->nullable()->after('week_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('curriculum_weeks', 'source_week_number')) {
            Schema::table('curriculum_weeks', function (Blueprint $table) {
                $table->dropColumn('source_week_number');
            });
        }
    }
};
