<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\Event;
use App\Models\MediaAsset;
use App\Models\Program;
use App\Models\Story;
use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_hubs_include_active_hubs_only(): void
    {
        $active = Hub::factory()->create(['name' => 'Kiyovu', 'slug' => 'kiyovu']);
        Hub::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/hubs')
            ->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_programs_include_published_programs_and_filter_by_hub(): void
    {
        $hub = Hub::factory()->create(['slug' => 'huye']);
        $published = Program::factory()->create([
            'hub_id' => $hub->id,
            'slug' => 'girls-football',
            'category' => 'sport',
            'status' => 'published',
        ]);
        Program::factory()->create(['status' => 'draft', 'hub_id' => $hub->id]);

        $this->getJson('/api/v1/programs?hub=huye&category=sport')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_stories_exclude_drafts(): void
    {
        $published = Story::factory()->create(['slug' => 'a-story']);
        Story::factory()->create(['status' => 'draft']);

        $this->getJson('/api/v1/stories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_events_include_only_published_public_events(): void
    {
        $event = Event::factory()->create(['slug' => 'peace-match']);
        Event::factory()->create(['is_public' => false]);
        Event::factory()->create(['status' => 'draft']);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('data.0.id', $event->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_media_assets_attach_to_content_without_local_paths(): void
    {
        $program = Program::factory()->create();
        $media = $program->media()->create([
            'type' => 'image',
            'disk' => 's3',
            'object_key' => 'programs/football.jpg',
            'url' => 'https://cdn.example.test/programs/football.jpg',
            'alt_text' => 'Football session',
        ]);

        $this->assertInstanceOf(MediaAsset::class, $program->media()->first());
        $this->assertSame('programs/football.jpg', $media->object_key);
        $this->assertStringNotContainsString(DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR, $media->object_key);
    }
}