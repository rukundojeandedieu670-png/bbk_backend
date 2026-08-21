<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['organization_name', 'contact_name', 'email', 'message', 'status'])]
class PartnershipInquiry extends Model {}