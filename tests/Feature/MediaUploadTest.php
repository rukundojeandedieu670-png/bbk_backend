<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
    }

    public function test_admin_can_upload_an_image_to_a_hub(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));
        $hub = Hub::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->post("/api/v1/admin/media/hubs/{$hub->id}", [
            'file' => UploadedFile::fake()->image('hub-cover.jpg'),
            'altText' => 'Community football session',
        ]);

        $response->assertCreated()->assertJsonPath('data.type', 'image');
        $asset = MediaAsset::firstOrFail();
        Storage::disk('s3')->assertExists($asset->object_key);
        $this->assertSame($hub->id, $asset->mediable_id);
    }

    public function test_publisher_cannot_upload_media(): void
    {
        $publisher = User::factory()->create();
        $publisher->assignRole(Role::findByName('publisher'));
        $hub = Hub::factory()->create();

        $this->actingAs($publisher, 'sanctum')
            ->post("/api/v1/admin/media/hubs/{$hub->id}", ['file' => UploadedFile::fake()->image('blocked.jpg')])
            ->assertForbidden();
    }

    public function test_admin_can_delete_an_attached_media_asset(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin'));
        $hub = Hub::factory()->create();
        $asset = $hub->media()->create([
            'type' => 'video',
            'disk' => 's3',
            'object_key' => 'media/hubs/video.mp4',
            'url' => 'https://cdn.example.test/video.mp4',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/media/hubs/{$hub->id}/{$asset->id}")
            ->assertOk();

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
    }
}