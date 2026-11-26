<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $institution = DB::table('institutions')->where('code', 'SENATI')->first();

        if (! $institution) {
            return;
        }

        $settings = json_decode((string) ($institution->settings ?? '{}'), true);
        $settings = is_array($settings) ? $settings : [];
        $settings['catalog_profile'] = 'senati_reference';

        DB::table('institutions')
            ->where('id', $institution->id)
            ->update(['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    public function down(): void
    {
        $institution = DB::table('institutions')->where('code', 'SENATI')->first();

        if (! $institution) {
            return;
        }

        $settings = json_decode((string) ($institution->settings ?? '{}'), true);
        $settings = is_array($settings) ? $settings : [];
        unset($settings['catalog_profile']);

        DB::table('institutions')
            ->where('id', $institution->id)
            ->update(['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }
};
