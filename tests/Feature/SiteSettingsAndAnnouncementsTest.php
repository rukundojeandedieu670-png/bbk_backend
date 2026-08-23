<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteSettingsAndAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_system_owner_can_save_site_settings_and_public_api_exposes_them(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('system-owner'));

        $this->actingAs($owner, 'sanctum')
            ->putJson('/api/v1/admin/site-settings', [
                'site_name' => 'Bridging Borders Kigali',
                'background_color' => '#F7EFE4',
                'accent_color' => '#E8571F',
                'secondary_accent_color' => '#F2A93B',
                'font_pairing' => 'Barlow Condensed + Inter',
                'social_whatsapp_number' => '+250788123456',
                'social_linkedin_url' => 'https://linkedin.com/company/bbk',
                'social_tiktok_url' => 'https://tiktok.com/@bbk',
                'developer_credit_name' => 'Rukundo Studio',
                'developer_credit_url' => 'https://example.com',
            ])
            ->assertOk();

        $this->getJson('/api/v1/site-settings')
            ->assertOk()
            ->assertJsonPath('data.site_name', 'Bridging Borders Kigali')
            ->assertJsonPath('data.background_color', '#F7EFE4')
            ->assertJsonPath('data.social_whatsapp_number', '+250788123456');
    }

    public function test_active_announcements_are_exposed_publicly_and_filter_by_time(): void
    {
        $user = User::factory()->create();

        Announcement::create([
            'message' => 'Current training camp',
            'type' => 'promo',
            'link_url' => 'https://example.com/register',
            'link_label' => 'Learn more',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        Announcement::create([
            'message' => 'Past event',
            'type' => 'alert',
            'is_active' => true,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        Announcement::create([
            'message' => 'Inactive item',
            'type' => 'info',
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        $this->getJson('/api/v1/announcements/active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Current training camp');
    }
}
