<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->json('five_minute_talk_days')->nullable()->after('status');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->after('teaching_group_id')
                ->constrained('cycles')->cascadeOnDelete();
            $table->index(['cycle_id', 'document_type', 'version'], 'gd_cycle_type_ver_idx');
        });

        Schema::create('five_minute_talk_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('professors')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['professor_id', 'position'], 'talk_topics_prof_pos_uq');
            $table->index(['professor_id', 'title'], 'talk_topics_prof_title_idx');
        });

        Schema::create('cycle_five_minute_talks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
            $table->string('topic', 255);
            $table->date('scheduled_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['cycle_id', 'position'], 'cycle_talks_pos_uq');
            $table->unique(['cycle_id', 'scheduled_date'], 'cycle_talks_date_uq');
            $table->index(['cycle_id', 'scheduled_date'], 'cycle_talks_cycle_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_five_minute_talks');
        Schema::dropIfExists('five_minute_talk_topics');

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
            $table->dropIndex('gd_cycle_type_ver_idx');
            $table->dropColumn('cycle_id');
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('five_minute_talk_days');
        });
    }
};
