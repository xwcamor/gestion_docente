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
        if (! Schema::hasTable('generated_documents')) {
            return;
        }

        if (! Schema::hasColumn('generated_documents', 'slug')) {
            Schema::table('generated_documents', function (Blueprint $table): void {
                $table->string('slug', 191)->nullable()->after('id');
            });
        }

        $seenSlugs = [];

        DB::table('generated_documents')
            ->select('id', 'document_type', 'version', 'filename', 'slug')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$seenSlugs): void {
                foreach ($rows as $row) {
                    $slug = trim((string) ($row->slug ?? ''));

                    if ($slug === '' || isset($seenSlugs[$slug])) {
                        $source = "documento-{$row->document_type}-{$row->version}-{$row->filename}";
                        $base = Str::limit(Str::slug($source), 165, '');
                        $slug = $base.'-'.$row->id;
                        $suffix = 1;

                        while (isset($seenSlugs[$slug])) {
                            $slug = $base.'-'.$row->id.'-'.$suffix;
                            $suffix++;
                        }

                        DB::table('generated_documents')
                            ->where('id', $row->id)
                            ->update(['slug' => $slug]);
                    }

                    $seenSlugs[$slug] = true;
                }
            });

        DB::statement('ALTER TABLE generated_documents MODIFY slug VARCHAR(191) NOT NULL');

        $indexes = DB::select(
            "SHOW INDEX FROM generated_documents WHERE Key_name = 'generated_documents_slug_unique'"
        );

        if ($indexes === []) {
            Schema::table('generated_documents', function (Blueprint $table): void {
                $table->unique('slug', 'generated_documents_slug_unique');
            });
        }
    }

    public function down(): void
    {
        // Keep slugs because GeneratedDocument uses them as route keys.
    }
};
