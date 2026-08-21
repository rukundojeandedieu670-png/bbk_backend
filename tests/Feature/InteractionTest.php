<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Program;
use App\Models\AuditLog;
use App\Models\VolunteerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_volunteer_application_requires_a_valid_email(): void
    {
        $this->postJson('/api/v1/interactions/volunteer', [
            'name' => 'Aline',
            'email' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_valid_contact_message_is_created(): void
    {
        $this->postJson('/api/v1/interactions/contact', [
            'name' => 'Aline',
            'email' => 'aline@example.test',
            'subject' => 'Partnership',
            'message' => 'Please contact me.',
        ])->assertCreated()->assertJsonStructure(['message', 'id']);
    }

    public function test_newsletter_signup_is_duplicate_safe(): void
    {
        $payload = ['email' => 'NEWS@example.test'];

        $this->postJson('/api/v1/interactions/newsletter', $payload)->assertCreated();
        $this->postJson('/api/v1/interactions/newsletter', $payload)
            ->assertOk()
            ->assertJsonPath('alreadySubscribed', true);

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame('news@example.test', NewsletterSubscriber::first()->email);
    }

    public function test_publisher_can_read_but_not_update_inbox_records(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        $application = VolunteerApplication::create([
            'name' => 'Aline',
            'email' => 'aline@example.test',
            'status' => 'new',
        ]);

        $this->actingAs($publisher, 'sanctum')
            ->getJson('/api/v1/admin/inbox/volunteers')
            ->assertOk();

        $this->actingAs($publisher, 'sanctum')
            ->patchJson("/api/v1/admin/inbox/volunteers/{$application->id}", ['status' => 'reviewed'])
            ->assertForbidden();
    }

    public function test_admin_can_request_review_but_publisher_must_publish(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        $program = Program::factory()->create(['status' => 'draft']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$program->id}/status", ['status' => 'pending_review'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$program->id}/status", ['status' => 'published'])
            ->assertForbidden();

        $this->actingAs($publisher, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$program->id}/status", ['status' => 'published'])
            ->assertOk();

        $this->assertSame(2, AuditLog::count());
    }

    public function test_publisher_cannot_skip_pending_review(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        $program = Program::factory()->create(['status' => 'draft']);

        $this->actingAs($publisher, 'sanctum')
            ->patchJson("/api/v1/admin/content/programs/{$program->id}/status", ['status' => 'published'])
            ->assertUnprocessable();
    }

    public function test_profile_exposes_effective_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('roles.0', 'admin')
            ->assertJsonFragment(['manage-programs'])
            ->assertJsonFragment(['manage-inbox'])
            ->assertJsonFragment(['review-content']);
    }
}