<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'district' => $this->district,
            'description' => $this->description,
            'coverImage' => $this->cover_image,
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'media' => MediaAssetResource::collection($this->whenLoaded('media')),
        ];
    }
}