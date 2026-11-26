<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->string('legal_name', 255)->nullable();
            $table->char('country_code', 2)->default('PE');
            $table->string('timezone', 64)->default('America/Lima');
            $table->string('status', 24)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('institution_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->default('teacher');
            $table->string('status', 24)->default('active');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'user_id'], 'inst_membership_user_uq');
            $table->index(['user_id', 'status'], 'inst_membership_user_status_idx');
        });

        Schema::create('institution_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('institution_units')->nullOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('name', 255);
            $table->string('unit_type', 40)->default('academic_unit');
            $table->string('source_label', 255)->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->string('verification_status', 32)->default('unverified');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'code'], 'inst_units_code_uq');
            $table->index(['institution_id', 'name'], 'inst_units_name_idx');
        });

        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('institution_unit_id')->nullable()->constrained('institution_units')->nullOnDelete();
            $table->string('code', 80)->nullable();
            $table->string('name', 255);
            $table->string('qualification_level', 80)->nullable();
            $table->string('status', 32)->default('active');
            $table->string('verification_status', 32)->default('unverified');
            $table->string('source_url', 500)->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'name'], 'academic_programs_name_idx');
            $table->index(['institution_unit_id', 'status'], 'academic_programs_unit_status_idx');
        });

        Schema::create('academic_program_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->string('version_code', 80);
            $table->unsignedTinyInteger('duration_semesters')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 32)->default('current');
            $table->boolean('is_current')->default(false);
            $table->string('source_url', 500)->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['academic_program_id', 'version_code'], 'academic_program_version_uq');
            $table->index(['academic_program_id', 'is_current'], 'academic_program_current_idx');
        });

        Schema::create('institution_period_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name', 120);
            $table->string('period_unit', 32)->default('period');
            $table->string('code_pattern', 80)->nullable();
            $table->unsignedTinyInteger('default_duration_weeks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'code'], 'inst_period_types_code_uq');
        });

        Schema::create('institution_academic_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_period_type_id')->constrained('institution_period_types')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('label', 160);
            $table->unsignedSmallInteger('academic_year');
            $table->unsignedTinyInteger('sequence_number')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 24)->default('reference');
            $table->string('source_label', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['institution_period_type_id', 'code'], 'inst_period_code_uq');
            $table->index(['academic_year', 'status'], 'inst_period_year_status_idx');
        });

        Schema::create('institution_instruction_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'code'], 'inst_instruction_type_uq');
        });

        Schema::create('institution_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'feature_key'], 'inst_feature_key_uq');
        });

        Schema::create('institution_document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('institution_instruction_type_id')->nullable()
                ->constrained('institution_instruction_types')->nullOnDelete();
            $table->string('document_type', 80);
            $table->string('name', 180);
            $table->string('version', 40)->default('1');
            $table->string('disk', 40)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->json('mapping_config')->nullable();
            $table->string('status', 24)->default('pending_upload');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'document_type', 'status'], 'inst_templates_type_status_idx');
        });

        Schema::create('institution_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('institution_unit_id')->nullable()->constrained('institution_units')->nullOnDelete();
            $table->foreignId('academic_program_id')->nullable()->constrained('academic_programs')->nullOnDelete();
            $table->string('external_code', 100);
            $table->string('name', 255);
            $table->string('catalog_status', 24)->default('active');
            $table->string('verification_status', 32)->default('captured_from_blackboard');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'external_code'], 'inst_courses_external_code_uq');
            $table->index(['institution_id', 'name'], 'inst_courses_name_idx');
        });

        Schema::create('institution_course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('institution_academic_period_id')->constrained('institution_academic_periods')->cascadeOnDelete();
            $table->foreignId('institution_unit_id')->nullable()->constrained('institution_units')->nullOnDelete();
            $table->foreignId('institution_course_id')->constrained('institution_courses')->cascadeOnDelete();
            $table->foreignId('institution_instruction_type_id')->nullable()
                ->constrained('institution_instruction_types')->nullOnDelete();
            $table->foreignId('teaching_group_id')->nullable()
                ->constrained('teaching_groups')->nullOnDelete();
            $table->string('nrc', 40);
            $table->string('external_identifier', 180);
            $table->string('course_code_snapshot', 100);
            $table->string('course_name_snapshot', 255);
            $table->string('source_status', 24)->default('unknown');
            $table->string('source_status_label', 40)->nullable();
            $table->string('instructor_label', 255)->nullable();
            $table->string('group_mapping_status', 32)->default('pending_review');
            $table->string('source_capture', 255)->nullable();
            $table->json('source_metadata')->nullable();
            $table->timestamps();
            $table->unique(['institution_academic_period_id', 'nrc'], 'inst_offering_period_nrc_uq');
            $table->unique(['institution_id', 'external_identifier'], 'inst_offering_external_id_uq');
            $table->index(['institution_course_id', 'institution_academic_period_id'], 'inst_offering_course_period_idx');
            $table->index(['institution_unit_id', 'source_status'], 'inst_offering_unit_status_idx');
            $table->index(['teaching_group_id', 'group_mapping_status'], 'inst_offering_group_map_idx');
        });

        Schema::create('employment_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employment_type', 32);
            $table->string('job_title', 180)->nullable();
            $table->decimal('contracted_weekly_hours', 6, 2)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 24)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'user_id', 'status'], 'employment_inst_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_relationships');
        Schema::dropIfExists('institution_course_offerings');
        Schema::dropIfExists('institution_courses');
        Schema::dropIfExists('institution_document_templates');
        Schema::dropIfExists('institution_features');
        Schema::dropIfExists('institution_instruction_types');
        Schema::dropIfExists('institution_academic_periods');
        Schema::dropIfExists('institution_period_types');
        Schema::dropIfExists('academic_program_versions');
        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('institution_units');
        Schema::dropIfExists('institution_memberships');
        Schema::dropIfExists('institutions');
    }
};
