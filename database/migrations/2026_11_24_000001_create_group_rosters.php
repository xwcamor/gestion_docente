<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_group_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_group_id')->constrained('teaching_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teaching_group_id', 'student_id'], 'group_students_unique');
            $table->index('student_id', 'group_students_student_idx');
        });

        // Una importación de padrón pertenece al grupo. La matrícula y las notas
        // continúan siendo específicas de cada modalidad/curso.
        DB::statement('ALTER TABLE import_batches DROP FOREIGN KEY import_batches_modality_fk');
        DB::statement('ALTER TABLE import_batches MODIFY course_modality_id BIGINT UNSIGNED NULL');
        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('teaching_group_id')->nullable()->after('course_modality_id');
            $table->index('teaching_group_id', 'import_batches_group_idx');
        });
        DB::statement('ALTER TABLE import_batches ADD CONSTRAINT import_batches_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE import_batches ADD CONSTRAINT import_batches_group_fk FOREIGN KEY (teaching_group_id) REFERENCES teaching_groups(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Los lotes de padrón por grupo se eliminan al revertir esta migración.
        DB::table('import_batches')->whereNotNull('teaching_group_id')->delete();

        DB::statement('ALTER TABLE import_batches DROP FOREIGN KEY import_batches_group_fk');
        DB::statement('ALTER TABLE import_batches DROP FOREIGN KEY import_batches_modality_fk');

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropIndex('import_batches_group_idx');
            $table->dropColumn('teaching_group_id');
        });

        DB::statement('ALTER TABLE import_batches MODIFY course_modality_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE import_batches ADD CONSTRAINT import_batches_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE');

        Schema::dropIfExists('teaching_group_students');
    }
};
