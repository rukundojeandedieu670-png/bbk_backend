<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SETTINGS = [
        'impact_people_impacted' => '1k+',
        'impact_youth_trained' => '30+',
        'impact_satisfaction_rate' => '98%',
    ];

    public function up(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', array_keys(self::SETTINGS))->delete();
    }
};