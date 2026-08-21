<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'coverImage' => $this->cover_image,
            'publishedAt' => $this->published_at?->toISOString(),
            'media' => MediaAssetResource::collection($this->whenLoaded('media')),
        ];
    }
}