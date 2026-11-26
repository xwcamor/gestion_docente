<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_modalities', 'institution_instruction_type_id')) {
            Schema::table('course_modalities', function (Blueprint $table) {
                $table->foreignId('institution_instruction_type_id')->nullable()->after('modality');
                $table->foreign('institution_instruction_type_id', 'course_modalities_instruction_type_fk')
                    ->references('id')->on('institution_instruction_types')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_modalities', 'institution_instruction_type_id')) {
            Schema::table('course_modalities', function (Blueprint $table) {
                $table->dropForeign('course_modalities_instruction_type_fk');
                $table->dropColumn('institution_instruction_type_id');
            });
        }
    }
};
