<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url ?: Storage::disk($this->disk)->url($this->object_key),
            'altText' => $this->alt_text,
            'sortOrder' => $this->sort_order,
        ];
    }
}