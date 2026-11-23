<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_modalities', function (Blueprint $table) {
            $table->string('block_code', 100)->nullable()->after('nrc');
            $table->string('block_reference', 120)->nullable()->after('block_code');
        });
    }

    public function down(): void
    {
        Schema::table('course_modalities', function (Blueprint $table) {
            $table->dropColumn(['block_code', 'block_reference']);
        });
    }
};
