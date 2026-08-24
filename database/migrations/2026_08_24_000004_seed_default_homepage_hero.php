<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('homepage_heroes')->updateOrInsert(
            ['title' => 'Different lives. One shared field.'],
            [
                'eyebrow' => 'A national movement rooted in Rwanda',
                'body' => 'Bridging Borders Kigali uses sport, culture and entertainment to rebuild trust between communities, migrants and refugees.',
                'cta_label' => 'Discover the movement',
                'cta_url' => '#approach',
                'location' => 'Kiyovu / Huye',
                'side' => 'left',
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('homepage_heroes')->where('title', 'Different lives. One shared field.')->delete();
    }
};
