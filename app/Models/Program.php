<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['title', 'slug', 'hub_id', 'category', 'summary', 'body', 'cover_image', 'is_featured', 'status'])]
class Program extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['hub_id' => 'integer', 'is_featured' => 'boolean'];
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable');
    }
}