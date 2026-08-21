<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'hub_of_interest', 'message', 'status'])]
class VolunteerApplication extends Model
{
    protected function casts(): array
    {
        return ['status' => 'string'];
    }
}