<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['type', 'disk', 'object_key', 'url', 'alt_text', 'sort_order'])]
class MediaAsset extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}