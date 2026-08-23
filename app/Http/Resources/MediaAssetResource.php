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
            'url' => $this->url ?: $this->resolveUrl(),
            'altText' => $this->alt_text,
            'sortOrder' => $this->sort_order,
        ];
    }

    protected function resolveUrl(): ?string
    {
        if (! $this->disk || ! $this->object_key) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        if (method_exists($disk, 'url')) {
            return $disk->url($this->object_key);
        }

        return null;
    }
}