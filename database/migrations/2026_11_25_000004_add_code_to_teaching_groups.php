<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->string('code', 80)->nullable()->after('name');
            $table->unique(['cycle_id', 'code'], 'teaching_groups_cycle_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_groups', function (Blueprint $table) {
            $table->dropUnique('teaching_groups_cycle_code_unique');
            $table->dropColumn('code');
        });
    }
};
