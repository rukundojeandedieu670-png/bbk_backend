<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'slug', 'district', 'description', 'cover_image', 'lat', 'lng', 'is_active'])]
class Hub extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['lat' => 'decimal:7', 'lng' => 'decimal:7', 'is_active' => 'boolean'];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable');
    }
}