<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role')->index();
        });

        Schema::table('cycle_five_minute_talks', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('scheduled_date');
            $table->unsignedSmallInteger('participant_count')->nullable()->after('status');
            $table->text('observations')->nullable()->after('participant_count');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('professor_id')->nullable()->constrained('professors')->nullOnDelete();
            $table->string('module', 80)->index();
            $table->string('event', 40)->index();
            $table->string('auditable_type', 191)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();
            $table->index(['professor_id', 'created_at'], 'audit_prof_created_idx');
            $table->index(['auditable_type', 'auditable_id'], 'audit_subject_idx');
            $table->index(['module', 'created_at'], 'audit_module_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');

        Schema::table('cycle_five_minute_talks', function (Blueprint $table) {
            $table->dropColumn(['status', 'participant_count', 'observations']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
