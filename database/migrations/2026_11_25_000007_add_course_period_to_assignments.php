<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->date('course_start_date')->nullable()->after('assigned_at');
            $table->date('course_end_date')->nullable()->after('course_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropColumn(['course_start_date', 'course_end_date']);
        });
    }
};
