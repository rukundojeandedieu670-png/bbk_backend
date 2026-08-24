<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\HomepageHero;
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
                'hero_background_image' => 'https://example.com/images/community-field.jpg',
                'theme_primary_color' => '#1B2A57',
                'theme_accent_color' => '#E8571F',
                'theme_secondary_accent_color' => '#F2A93B',
                'font_pairing' => 'athletic',
                'social_whatsapp_number' => '+250788123456',
                'social_linkedin_url' => 'https://linkedin.com/company/bbk',
                'social_tiktok_url' => 'https://tiktok.com/@bbk',
                'developer_credit_name' => 'Rukundo Studio',
                'developer_credit_url' => 'https://example.com',
                'impact_people_impacted' => '2k+',
                'impact_youth_trained' => '45+',
                'impact_satisfaction_rate' => '99%',
            ])
            ->assertOk();

        $this->getJson('/api/v1/site-settings')
            ->assertOk()
            ->assertJsonPath('data.site_name', 'Bridging Borders Kigali')
            ->assertJsonPath('data.hero_background_image', 'https://example.com/images/community-field.jpg')
            ->assertJsonPath('data.theme_primary_color', '#1B2A57')
            ->assertJsonPath('data.social_whatsapp_number', '+250788123456')
            ->assertJsonPath('data.impact_people_impacted', '2k+')
            ->assertJsonPath('data.impact_youth_trained', '45+')
            ->assertJsonPath('data.impact_satisfaction_rate', '99%');
    }

    public function test_users_without_system_settings_permission_cannot_update_impact_settings(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));

        $this->actingAs($publisher, 'sanctum')
            ->putJson('/api/v1/admin/site-settings', [
                'impact_people_impacted' => '2k+',
            ])
            ->assertForbidden();
    }

    public function test_system_owner_can_manage_and_reorder_homepage_heroes(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('system-owner'));
        $first = HomepageHero::create(['title' => 'First']);
        $second = HomepageHero::create(['title' => 'Second']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/admin/content/hero/reorder', ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->getJson('/api/v1/homepage-hero')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Second');
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
