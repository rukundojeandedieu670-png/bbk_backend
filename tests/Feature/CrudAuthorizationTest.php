<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_create_update_and_delete_a_program(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));
        $payload = ['title' => 'Community Athletics', 'category' => 'sport', 'summary' => 'Track sessions'];

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/content/programs', $payload)
            ->assertCreated()->json('data');
        $this->assertSame('community-athletics', $created['slug']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$created['id']}", ['title' => 'Community Athletics Updated', 'category' => 'sport'])
            ->assertOk()->assertJsonPath('data.title', 'Community Athletics Updated');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/content/programs/{$created['id']}")
            ->assertOk();
    }

    public function test_publisher_can_update_but_cannot_create_or_delete_content(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        $program = Program::factory()->create(['status' => 'pending_review']);

        $this->actingAs($publisher, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$program->id}", ['title' => 'Publisher Edit', 'category' => 'sport'])
            ->assertOk();
        $this->actingAs($publisher, 'sanctum')
            ->postJson('/api/v1/admin/content/programs', ['title' => 'Blocked', 'category' => 'sport'])
            ->assertForbidden();
        $this->actingAs($publisher, 'sanctum')
            ->deleteJson("/api/v1/admin/content/programs/{$program->id}")
            ->assertForbidden();
    }

    public function test_only_owner_can_delete_a_hub(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('system-owner'));
        $hub = Hub::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/content/hubs/{$hub->id}")
            ->assertForbidden();
        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/admin/content/hubs/{$hub->id}")
            ->assertOk();
    }
}