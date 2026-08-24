<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageHeroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eyebrow' => $this->eyebrow,
            'title' => $this->title,
            'body' => $this->body,
            'ctaLabel' => $this->cta_label,
            'ctaUrl' => $this->cta_url,
            'imageUrl' => $this->image_url,
            'location' => $this->location,
            'side' => $this->side,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
        ];
    }
}
