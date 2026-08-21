<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HubResource;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\NewsPostResource;
use App\Http\Resources\PartnerResource;
use App\Models\Event;
use App\Models\Hub;
use App\Models\Program;
use App\Models\Story;
use App\Models\NewsPost;
use App\Models\Partner;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicContentController extends Controller
{
    public function hubs(): AnonymousResourceCollection
    {
        return HubResource::collection(
            Hub::query()->with('media')->where('is_active', true)->orderBy('name')->paginate(12),
        );
    }

    public function hub(string $slug): HubResource
    {
        return new HubResource(Hub::query()->with('media')->where('is_active', true)->where('slug', $slug)->firstOrFail());
    }

    public function programs(): AnonymousResourceCollection
    {
        $programs = Program::query()
            ->with(['hub', 'media'])
            ->where('status', 'published')
            ->when(request('category'), fn ($query, $category) => $query->where('category', $category))
            ->when(request('hub'), fn ($query, $hub) => $query->whereHas('hub', fn ($hubQuery) => $hubQuery->where('slug', $hub)))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return ProgramResource::collection($programs);
    }

    public function program(string $slug): ProgramResource
    {
        return new ProgramResource(Program::query()
            ->with(['hub', 'media'])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail());
    }

    public function stories(): AnonymousResourceCollection
    {
        return StoryResource::collection(Story::query()
            ->with(['hub', 'program', 'media'])
            ->where('status', 'published')
            ->when(request('hub'), fn ($query, $hub) => $query->whereHas('hub', fn ($hubQuery) => $hubQuery->where('slug', $hub)))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString());
    }

    public function story(string $slug): StoryResource
    {
        return new StoryResource(Story::query()
            ->with(['hub', 'program', 'media'])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail());
    }

    public function events(): AnonymousResourceCollection
    {
        return EventResource::collection(Event::query()
            ->with(['hub', 'program', 'media'])
            ->where('status', 'published')
            ->where('is_public', true)
            ->when(request('hub'), fn ($query, $hub) => $query->whereHas('hub', fn ($hubQuery) => $hubQuery->where('slug', $hub)))
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString());
    }

    public function event(string $slug): EventResource
    {
        return new EventResource(Event::query()
            ->with(['hub', 'program', 'media'])
            ->where('status', 'published')
            ->where('is_public', true)
            ->where('slug', $slug)
            ->firstOrFail());
    }

    public function partners(): AnonymousResourceCollection
    {
        return PartnerResource::collection(Partner::query()->with('media')->orderBy('name')->paginate(24));
    }

    public function news(): AnonymousResourceCollection
    {
        return NewsPostResource::collection(NewsPost::query()
            ->with('media')
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString());
    }

    public function newsPost(string $slug): NewsPostResource
    {
        return new NewsPostResource(NewsPost::query()
            ->with('media')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail());
    }
}