<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('catalog_code', 40)->nullable();
            $table->string('module_code', 80)->nullable();
            $table->string('module_name')->nullable();
            $table->string('course_name')->nullable();
            $table->string('career')->nullable();
            $table->string('professional_school')->nullable();
            $table->string('occupational_module')->nullable();
            $table->string('semester', 30)->nullable();
            $table->longText('general_objective')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_path')->nullable();
            $table->string('source_sha256', 64)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->json('source_metadata')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('status', 24)->default('imported');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['professor_id', 'course_id', 'is_active']);
        });

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->nullable()->after('course_id')->constrained('curricula')->nullOnDelete();
        });

        Schema::create('curriculum_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->string('task_code', 30);
            $table->string('title');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['curriculum_id', 'task_code']);
        });

        Schema::create('curriculum_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_task_id')->constrained()->cascadeOnDelete();
            $table->string('operation_code', 30);
            $table->text('description');
            $table->string('operation_type', 24)->default('UNSPECIFIED');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['curriculum_task_id', 'operation_code']);
        });

        Schema::create('curriculum_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_task_id')->nullable()->constrained('curriculum_tasks')->nullOnDelete();
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('source_week_number')->nullable();
            $table->decimal('hours_theory', 7, 2)->nullable();
            $table->decimal('hours_practice', 7, 2)->nullable();
            $table->decimal('hours_virtual', 7, 2)->nullable();
            $table->boolean('is_evaluation')->default(false);
            $table->json('source_pages')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_id', 'week_number']);
            $table->index(['curriculum_id', 'week_number']);
        });

        Schema::create('curriculum_content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_week_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->longText('content');
            $table->string('source_kind', 24)->default('SOURCE');
            $table->boolean('approved')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Explicit short name: MySQL limits identifiers to 64 characters.
            $table->index(
                ['curriculum_week_id', 'category', 'approved'],
                'cci_week_category_approved_idx'
            );
        });

        Schema::table('course_modalities', function (Blueprint $table) {
            $table->unsignedSmallInteger('curriculum_start_week')->default(1)->after('total_hours');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('curriculum_week_id')->nullable()->after('course_modality_id')->constrained('curriculum_weeks')->nullOnDelete();
            $table->unsignedSmallInteger('curriculum_week_number')->nullable()->after('curriculum_week_id');
            $table->string('content_origin', 20)->default('manual')->after('curriculum_week_number');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curriculum_week_id');
            $table->dropColumn(['curriculum_week_number', 'content_origin']);
        });
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curriculum_id');
        });
        Schema::table('course_modalities', function (Blueprint $table) {
            $table->dropColumn('curriculum_start_week');
        });
        Schema::dropIfExists('curriculum_content_items');
        Schema::dropIfExists('curriculum_weeks');
        Schema::dropIfExists('curriculum_operations');
        Schema::dropIfExists('curriculum_tasks');
        Schema::dropIfExists('curricula');
    }
};
