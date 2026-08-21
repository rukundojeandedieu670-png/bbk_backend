<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'logo', 'website_url', 'partner_type', 'description'])]
class Partner extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    public function media(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable');
    }
}