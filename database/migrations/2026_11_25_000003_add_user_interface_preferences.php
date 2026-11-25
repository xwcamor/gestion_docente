<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('sidebar_visible')->default(true)->after('is_active');
            $table->string('accent_color', 7)->nullable()->after('sidebar_visible');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sidebar_visible', 'accent_color']);
        });
    }
};
