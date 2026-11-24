<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_five_minute_talks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_group_id')->constrained('teaching_groups')->cascadeOnDelete();
            $table->string('topic', 255);
            $table->date('scheduled_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('participant_count')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['teaching_group_id', 'position'], 'group_talks_pos_uq');
            $table->unique(['teaching_group_id', 'scheduled_date'], 'group_talks_date_uq');
            $table->index(['teaching_group_id', 'scheduled_date'], 'group_talks_group_date_idx');
        });

        // Preserve the old cycle-wide topic list as pending copies for each group.
        // Legacy cycle execution records remain untouched in cycle_five_minute_talks.
        foreach (DB::table('cycles')->orderBy('id')->get(['id']) as $cycle) {
            $topics = DB::table('cycle_five_minute_talks')
                ->where('cycle_id', $cycle->id)
                ->orderBy('position')
                ->get(['topic', 'position']);

            if ($topics->isEmpty()) {
                continue;
            }

            $groupIds = DB::table('teaching_groups')
                ->where('cycle_id', $cycle->id)
                ->orderBy('id')
                ->pluck('id');

            foreach ($groupIds as $groupId) {
                $now = now();
                $rows = [];
                foreach ($topics as $index => $topic) {
                    $rows[] = [
                        'teaching_group_id' => $groupId,
                        'topic' => $topic->topic,
                        'scheduled_date' => null,
                        'status' => 'pending',
                        'participant_count' => null,
                        'observations' => null,
                        'position' => (int) $topic->position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 400) as $chunk) {
                    DB::table('group_five_minute_talks')->insert($chunk);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_five_minute_talks');
    }
};
