<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'websiteUrl' => $this->website_url,
            'partnerType' => $this->partner_type,
            'description' => $this->description,
            'media' => MediaAssetResource::collection($this->whenLoaded('media')),
        ];
    }
}