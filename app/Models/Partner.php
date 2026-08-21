<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'logo', 'website_url', 'partner_type', 'description'])]
class Partner extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;
}