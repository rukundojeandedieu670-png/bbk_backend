<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\HubResource;
use App\Http\Resources\NewsPostResource;
use App\Http\Resources\PartnerResource;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\StoryResource;
use App\Models\Event;
use App\Models\Hub;
use App\Models\HomepageHero;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminContentController extends Controller
{
    public function index(Request $request, string $type): JsonResponse
    {
        $this->authorizeType($request, $type, 'view');
        $model = $this->model($type);

        $query = $model::query();
        if ($type === 'hero') $query->orderBy('side')->orderBy('sort_order')->orderBy('id');
        else $query->latest();

        $records = $query->paginate(25);

        if ($type === 'partners') {
            return response()->json(['data' => PartnerResource::collection($records)]);
        }

        return response()->json(['data' => $records]);
    }

    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $this->authorizeType($request, $type, 'view');
        $record = $this->model($type)::query()->findOrFail($id);

        return response()->json(['data' => $this->serializeRecord($type, $record)]);
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $this->authorizeType($request, $type, 'create');
        $data = $this->validated($request, $type);
        $model = $this->model($type);
        $record = $model::query()->create($data);

        return response()->json(['data' => $this->serializeRecord($type, $record->fresh()), 'message' => 'Content created.'], 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $this->authorizeType($request, $type, 'update');
        $record = $this->model($type)::query()->findOrFail($id);
        $data = $this->validated($request, $type, $record->id);
        $record->update($data);

        return response()->json(['data' => $this->serializeRecord($type, $record->fresh()), 'message' => 'Content updated.']);
    }

    public function destroy(Request $request, string $type, int $id): JsonResponse
    {
        $this->authorizeType($request, $type, 'delete');
        $record = $this->model($type)::query()->findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Content deleted.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorizeType($request, 'hero', 'update');
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct', 'exists:homepage_heroes,id']]);

        DB::transaction(function () use ($data): void {
            foreach ($data['ids'] as $index => $id) HomepageHero::query()->whereKey($id)->update(['sort_order' => $index]);
        });

        return response()->json(['message' => 'Hero order updated.']);
    }

    private function model(string $type): string
    {
        return match ($type) {
            'hubs' => Hub::class,
            'programs' => Program::class,
            'stories' => Story::class,
            'events' => Event::class,
            'partners' => Partner::class,
            'news' => NewsPost::class,
            'hero' => HomepageHero::class,
            default => abort(404),
        };
    }

    private function authorizeType(Request $request, string $type, string $action): void
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');
        $permission = match ($type) {
            'hubs' => 'manage-hubs',
            'partners' => 'manage-partners',
            'programs' => 'manage-programs',
            'events' => 'manage-events',
            'stories' => 'manage-stories',
            'news' => 'manage-news',
            'hero' => 'manage-system-settings',
            default => abort(404),
        };

        abort_unless($user->can($permission), 403);
    }

    private function validated(Request $request, string $type, ?int $ignoreId = null): array
    {
        $slugRule = 'unique:'.Str::plural($type).',slug'.($ignoreId ? ",{$ignoreId}" : '');

        return match ($type) {
            'hubs' => $this->mapHub($request->validate([
                'name' => ['required', 'string', 'max:160'], 'slug' => ['nullable', 'string', 'max:180', $slugRule],
                'district' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string'], 'coverImage' => ['nullable', 'url', 'max:2000'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'], 'isActive' => ['nullable', 'boolean'],
            ])),
            'programs' => $this->mapProgram($request->validate([
                'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:180', $slugRule], 'hubId' => ['nullable', 'exists:hubs,id'],
                'category' => ['required', 'in:sport,culture,entertainment,peace_building,storytelling'], 'summary' => ['nullable', 'string', 'max:1000'], 'body' => ['nullable', 'string'], 'coverImage' => ['nullable', 'url', 'max:2000'], 'isFeatured' => ['nullable', 'boolean'], 'status' => ['nullable', 'in:draft,pending_review,published,archived'],
            ])),
            'stories' => $this->mapStory($request->validate([
                'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:180', $slugRule], 'hubId' => ['nullable', 'exists:hubs,id'], 'programId' => ['nullable', 'exists:programs,id'], 'authorName' => ['required', 'string', 'max:160'], 'body' => ['required', 'string'], 'status' => ['nullable', 'in:draft,pending_review,published,archived'], 'publishedAt' => ['nullable', 'date'],
            ])),
            'events' => $this->mapEvent($request->validate([
                'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:180', $slugRule], 'hubId' => ['nullable', 'exists:hubs,id'], 'programId' => ['nullable', 'exists:programs,id'], 'eventType' => ['required', 'in:match,concert,screening,workshop,exhibition'], 'location' => ['required', 'string', 'max:255'], 'startsAt' => ['required', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'description' => ['nullable', 'string'], 'coverImage' => ['nullable', 'url', 'max:2000'], 'status' => ['nullable', 'in:draft,pending_review,published,archived'], 'isPublic' => ['nullable', 'boolean'],
            ])),
            'partners' => $this->mapPartner($request->validate([
                'name' => ['required', 'string', 'max:180'],
                'logo' => ['nullable', 'url', 'max:2000'],
                'logoUrl' => ['nullable', 'url', 'max:2000'],
                'logo_url' => ['nullable', 'url', 'max:2000'],
                'websiteUrl' => ['nullable', 'url', 'max:2000'],
                'website_url' => ['nullable', 'url', 'max:2000'],
                'partnerType' => ['nullable', 'in:funder,implementing_partner,local_partner'],
                'partner_type' => ['nullable', 'in:funder,implementing_partner,local_partner'],
                'description' => ['nullable', 'string'],
            ])),
            'news' => $this->mapNews($request->validate([
                'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:180', $slugRule], 'body' => ['required', 'string'], 'coverImage' => ['nullable', 'url', 'max:2000'], 'status' => ['nullable', 'in:draft,pending_review,published,archived'], 'publishedAt' => ['nullable', 'date'],
            ])),
            'hero' => $this->mapHero($request->validate([
                'eyebrow' => ['nullable', 'string', 'max:255'], 'title' => ['required', 'string', 'max:255'], 'body' => ['nullable', 'string'],
                'cta_label' => ['nullable', 'string', 'max:120'], 'cta_url' => ['nullable', 'string', 'max:2048'], 'image_url' => ['nullable', 'string', 'max:2048'],
                'location' => ['nullable', 'string', 'max:255'], 'side' => ['nullable', 'in:left,right'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean'],
            ])),
            default => abort(404),
        };
    }

    private function mapHub(array $data): array { return ['name' => $data['name'], 'slug' => $data['slug'] ?? Str::slug($data['name']), 'district' => $data['district'] ?? null, 'description' => $data['description'] ?? null, 'cover_image' => $data['coverImage'] ?? null, 'lat' => $data['latitude'] ?? null, 'lng' => $data['longitude'] ?? null, 'is_active' => $data['isActive'] ?? true]; }
    private function mapProgram(array $data): array { return ['title' => $data['title'], 'slug' => $data['slug'] ?? Str::slug($data['title']), 'hub_id' => $data['hubId'] ?? null, 'category' => $data['category'], 'summary' => $data['summary'] ?? null, 'body' => $data['body'] ?? null, 'cover_image' => $data['coverImage'] ?? null, 'is_featured' => $data['isFeatured'] ?? false, 'status' => $data['status'] ?? 'draft']; }
    private function mapStory(array $data): array { return ['title' => $data['title'], 'slug' => $data['slug'] ?? Str::slug($data['title']), 'hub_id' => $data['hubId'] ?? null, 'program_id' => $data['programId'] ?? null, 'author_name' => $data['authorName'], 'body' => $data['body'], 'status' => $data['status'] ?? 'draft', 'published_at' => $data['publishedAt'] ?? null]; }
    private function mapEvent(array $data): array { return ['title' => $data['title'], 'slug' => $data['slug'] ?? Str::slug($data['title']), 'hub_id' => $data['hubId'] ?? null, 'program_id' => $data['programId'] ?? null, 'event_type' => $data['eventType'], 'location' => $data['location'], 'starts_at' => $data['startsAt'], 'ends_at' => $data['endsAt'] ?? null, 'description' => $data['description'] ?? null, 'cover_image' => $data['coverImage'] ?? null, 'status' => $data['status'] ?? 'draft', 'is_public' => $data['isPublic'] ?? false]; }
    private function mapPartner(array $data): array
    {
        $partnerType = $data['partnerType'] ?? $data['partner_type'] ?? null;
        $websiteUrl = $data['websiteUrl'] ?? $data['website_url'] ?? null;
        $logo = $data['logo'] ?? $data['logoUrl'] ?? $data['logo_url'] ?? null;

        return [
            'name' => $data['name'],
            'logo' => $logo,
            'website_url' => $websiteUrl,
            'partner_type' => $partnerType ?? 'local_partner',
            'description' => $data['description'] ?? null,
        ];
    }
    private function mapNews(array $data): array { return ['title' => $data['title'], 'slug' => $data['slug'] ?? Str::slug($data['title']), 'body' => $data['body'], 'cover_image' => $data['coverImage'] ?? null, 'status' => $data['status'] ?? 'draft', 'published_at' => $data['publishedAt'] ?? null]; }
    private function mapHero(array $data): array { return ['eyebrow' => $data['eyebrow'] ?? null, 'title' => $data['title'], 'body' => $data['body'] ?? null, 'cta_label' => $data['cta_label'] ?? null, 'cta_url' => $data['cta_url'] ?? null, 'image_url' => $data['image_url'] ?? null, 'location' => $data['location'] ?? null, 'side' => $data['side'] ?? 'left', 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $data['is_active'] ?? true]; }

    private function serializeRecord(string $type, mixed $record): mixed
    {
        if ($type === 'partners') {
            return PartnerResource::make($record);
        }

        return $record;
    }
}