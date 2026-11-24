<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modality_schedules', function (Blueprint $table) {
            $table->string('instruction_type', 20)->default('THEORY')->after('block_type');
        });
    }

    public function down(): void
    {
        Schema::table('modality_schedules', function (Blueprint $table) {
            $table->dropColumn('instruction_type');
        });
    }
};
