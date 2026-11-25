<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_five_minute_talks', function (Blueprint $table) {
            $table->string('status_source', 12)->default('AUTO')->after('status');
        });

        // Las charlas que ya fueron marcadas como realizadas son decisiones
        // del docente y se conservan como registros manuales.
        DB::table('group_five_minute_talks')
            ->where('status', 'done')
            ->update(['status_source' => 'MANUAL']);

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->char('source_fingerprint', 64)->nullable()->after('metadata');
            $table->timestamp('generated_at')->nullable()->after('source_fingerprint');
            $table->unique(
                ['teaching_group_id', 'document_type', 'version'],
                'gd_group_type_version_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropUnique('gd_group_type_version_uq');
            $table->dropColumn(['source_fingerprint', 'generated_at']);
        });

        Schema::table('group_five_minute_talks', function (Blueprint $table) {
            $table->dropColumn('status_source');
        });
    }
};
