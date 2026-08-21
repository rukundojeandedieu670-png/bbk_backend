<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'eventType' => $this->event_type,
            'location' => $this->location,
            'startsAt' => $this->starts_at?->toISOString(),
            'endsAt' => $this->ends_at?->toISOString(),
            'description' => $this->description,
            'coverImage' => $this->cover_image,
            'hub' => new HubResource($this->whenLoaded('hub')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'media' => MediaAssetResource::collection($this->whenLoaded('media')),
        ];
    }
}