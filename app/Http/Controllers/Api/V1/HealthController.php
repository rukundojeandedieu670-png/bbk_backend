<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthController
{
    public function show(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}