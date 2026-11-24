<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_sessions')) {
            return;
        }

        if (! Schema::hasColumn('class_sessions', 'slug')) {
            Schema::table('class_sessions', function (Blueprint $table): void {
                $table->string('slug', 191)->nullable()->after('id');
            });
        }

        $seenSlugs = [];

        DB::table('class_sessions')
            ->select('id', 'course_modality_id', 'session_date', 'session_number', 'slug')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$seenSlugs): void {
                foreach ($rows as $row) {
                    $slug = trim((string) ($row->slug ?? ''));

                    if ($slug === '' || isset($seenSlugs[$slug])) {
                        $date = $row->session_date ?: 'sin-fecha';
                        $source = "sesion-modalidad-{$row->course_modality_id}-{$date}-{$row->session_number}";
                        $base = Str::limit(Str::slug($source), 165, '');
                        $slug = $base.'-'.$row->id;
                        $suffix = 1;

                        while (isset($seenSlugs[$slug])) {
                            $slug = $base.'-'.$row->id.'-'.$suffix;
                            $suffix++;
                        }

                        DB::table('class_sessions')
                            ->where('id', $row->id)
                            ->update(['slug' => $slug]);
                    }

                    $seenSlugs[$slug] = true;
                }
            });

        DB::statement('ALTER TABLE class_sessions MODIFY slug VARCHAR(191) NOT NULL');

        $indexes = DB::select(
            "SHOW INDEX FROM class_sessions WHERE Key_name = 'class_sessions_slug_unique'"
        );

        if ($indexes === []) {
            Schema::table('class_sessions', function (Blueprint $table): void {
                $table->unique('slug', 'class_sessions_slug_unique');
            });
        }
    }

    public function down(): void
    {
        // Keep slugs because ClassSession uses them as route keys.
    }
};
