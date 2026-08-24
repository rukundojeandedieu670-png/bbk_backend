<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['eyebrow', 'title', 'body', 'cta_label', 'cta_url', 'image_url', 'location', 'side', 'sort_order', 'is_active'])]
class HomepageHero extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
