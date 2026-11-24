<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Support\\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('curricula', 'slug')) {
            Schema::table('curricula', function (Blueprint $table): void {
                $table->string('slug', 191)->nullable()->after('id');
            });
        }

        // Preserve existing slugs. Only fill empty values or repair duplicates.
        $seenSlugs = [];

        DB::table('curricula')
            ->select('id', 'course_id', 'version', 'slug')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$seenSlugs): void {
                foreach ($rows as $row) {
                    $slug = trim((string) ($row->slug ?? ''));

                    if ($slug === '' || isset($seenSlugs[$slug])) {
                        $source = "malla-curso-{$row->course_id}-version-{$row->version}";
                        $base = Str::limit(Str::slug($source), 165, '');
                        $slug = $base.'-'.$row->id;
                        $suffix = 1;

                        while (isset($seenSlugs[$slug])) {
                            $slug = $base.'-'.$row->id.'-'.$suffix;
                            $suffix++;
                        }

                        DB::table('curricula')
                            ->where('id', $row->id)
                            ->update(['slug' => $slug]);
                    }

                    $seenSlugs[$slug] = true;
                }
            });

        // Match the non-null slug column expected by HasSlugRouteKey.
        DB::statement('ALTER TABLE `curricula` MODIFY `slug` VARCHAR(191) NOT NULL');

        $indexes = DB::select(
            'SHOW INDEX FROM `curricula` WHERE Key_name = ?',
            ['curricula_slug_unique']
        );

        if ($indexes === []) {
            Schema::table('curricula', function (Blueprint $table): void {
                $table->unique('slug', 'curricula_slug_unique');
            });
        }
    }

    public function down(): void
    {
        // Keep slug values and the compatibility column when rolling back.
        // Existing curriculum URLs may already depend on these slugs.
    }
};
