<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'authorName' => $this->author_name,
            'body' => $this->body,
            'publishedAt' => $this->published_at?->toISOString(),
            'hub' => new HubResource($this->whenLoaded('hub')),
            'program' => new ProgramResource($this->whenLoaded('program')),
        ];
    }
}