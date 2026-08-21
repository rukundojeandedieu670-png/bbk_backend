<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'status' => $this->status,
            'summary' => $this->summary,
            'body' => $this->body,
            'coverImage' => $this->cover_image,
            'isFeatured' => $this->is_featured,
            'hub' => new HubResource($this->whenLoaded('hub')),
            'media' => MediaAssetResource::collection($this->whenLoaded('media')),
        ];
    }
}