<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->string('career', 255)->nullable()->after('cycle_code');
            $table->string('semester', 60)->nullable()->after('career');
        });

        Schema::table('curriculum_weeks', function (Blueprint $table) {
            $table->text('source_topic')->nullable()->after('source_week_number');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_weeks', function (Blueprint $table) {
            $table->dropColumn('source_topic');
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn(['career', 'semester']);
        });
    }
};
