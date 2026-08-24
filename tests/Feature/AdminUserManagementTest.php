<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_system_owner_can_manage_sanitized_staff_accounts(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('system-owner'));

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Content Admin', 'email' => 'content@example.com',
                'password' => 'long-enough-password', 'role' => 'admin',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'content@example.com')
            ->assertJsonPath('data.roles.0', 'admin')
            ->assertJsonMissingPath('data.password');

        $staff = User::query()->where('email', 'content@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('long-enough-password', $staff->password));

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/v1/admin/users/'.$staff->id, ['name' => 'Updated Admin', 'role' => 'publisher'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Admin')
            ->assertJsonPath('data.roles.0', 'publisher');

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonFragment(['email' => 'content@example.com']);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/v1/admin/users/'.$staff->id)
            ->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_any_staff_role_can_manage_staff_accounts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonFragment(['email' => $admin->email]);
    }

    public function test_staff_creation_accepts_any_staff_role_but_requires_a_long_password(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('system-owner'));

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Unsafe User', 'email' => 'unsafe@example.com',
                'password' => 'short', 'role' => 'system-owner',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Second Owner', 'email' => 'second-owner@example.com',
                'password' => 'long-enough-password', 'role' => 'system-owner',
            ])
            ->assertCreated()
            ->assertJsonPath('data.roles.0', 'system-owner');
    }

    public function test_any_staff_role_can_view_the_audit_log(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        AuditLog::create(['user_id' => $publisher->id, 'action' => 'test', 'subject_type' => User::class, 'subject_id' => $publisher->id, 'changes' => ['ok' => true]]);

        $this->actingAs($publisher, 'sanctum')
            ->getJson('/api/v1/admin/audit-log')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'test')
            ->assertJsonPath('data.0.user.email', $publisher->email);
    }
}