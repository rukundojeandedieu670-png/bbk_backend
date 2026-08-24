<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Hub;
use App\Models\HomepageHero;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Story;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        HomepageHero::updateOrCreate(
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
            ],
        );

        $kiyovu = Hub::updateOrCreate(
            ['slug' => 'kiyovu'],
            ['name' => 'Kiyovu', 'district' => 'Kigali', 'description' => 'A Kigali hub where sport and culture create space for connection.', 'is_active' => true],
        );
        $huye = Hub::updateOrCreate(
            ['slug' => 'huye'],
            ['name' => 'Huye', 'district' => 'Southern Province', 'description' => 'A Southern Province hub for participation, creativity and shared possibility.', 'is_active' => true],
        );

        $football = Program::updateOrCreate(
            ['slug' => 'football-for-peace'],
            ['title' => 'Football for Peace', 'hub_id' => $kiyovu->id, 'category' => 'sport', 'summary' => 'Weekly football sessions that turn competition into connection.', 'body' => 'Young people from different backgrounds train, play and reflect together through football.', 'status' => 'published', 'is_featured' => true],
        );
        Program::updateOrCreate(
            ['slug' => 'culture-in-motion'],
            ['title' => 'Culture in Motion', 'hub_id' => $huye->id, 'category' => 'culture', 'summary' => 'Creative exchange through music, performance and local storytelling.', 'body' => 'Culture in Motion brings people together through creative practice and shared celebration.', 'status' => 'published', 'is_featured' => true],
        );
        Program::updateOrCreate(
            ['slug' => 'girls-lead-the-field'],
            ['title' => 'Girls Lead the Field', 'hub_id' => null, 'category' => 'peace_building', 'summary' => 'Girls and women shaping confident, inclusive spaces through sport.', 'body' => 'A national thread supporting girls and women to participate, lead and be heard.', 'status' => 'published', 'is_featured' => true],
        );

        Partner::updateOrCreate(
            ['name' => 'International Alert Rwanda'],
            ['partner_type' => 'implementing_partner', 'description' => 'Peace-building partner supporting community trust and inclusion.'],
        );
        Partner::updateOrCreate(
            ['name' => 'Adidas Foundation'],
            ['partner_type' => 'funder', 'description' => 'Supporting sport for development through the Moving for Change initiative.'],
        );

        $story = Story::updateOrCreate(
            ['slug' => 'a-place-to-be-heard'],
            ['title' => 'A Place to Be Heard', 'hub_id' => $kiyovu->id, 'program_id' => $football->id, 'author_name' => 'Aline', 'body' => 'When I joined the sessions, I found more than a team. I found a place where my voice mattered and where I could help others feel welcome.', 'status' => 'published', 'published_at' => now()],
        );

        Event::updateOrCreate(
            ['slug' => 'kiyovu-community-match'],
            ['title' => 'Kiyovu Community Match', 'hub_id' => $kiyovu->id, 'program_id' => $football->id, 'event_type' => 'match', 'location' => 'Kiyovu, Kigali', 'starts_at' => now()->addDays(14), 'ends_at' => now()->addDays(14)->addHours(3), 'description' => 'A community football match followed by a conversation on belonging.', 'status' => 'published', 'is_public' => true],
        );

        NewsPost::updateOrCreate(
            ['slug' => 'bbk-expands-to-huye'],
            ['title' => 'BBK Expands to Huye', 'body' => 'Our second anchor hub brings the movement to the Southern Province through sport, culture and peace-building.', 'status' => 'published', 'published_at' => now()],
        );

        $story->touch();
    }
}