<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SiteSetting::all()->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])->all(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage-system-settings') || $request->user()?->hasRole('system-owner'), 403);

        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'secondary_accent_color' => ['nullable', 'string', 'max:20'],
            'font_pairing' => ['nullable', 'string', 'max:255'],
            'social_facebook_url' => ['nullable', 'url', 'max:255'],
            'social_twitter_url' => ['nullable', 'url', 'max:255'],
            'social_whatsapp_number' => ['nullable', 'string', 'max:30'],
            'social_instagram_url' => ['nullable', 'url', 'max:255'],
            'social_youtube_url' => ['nullable', 'url', 'max:255'],
            'social_linkedin_url' => ['nullable', 'url', 'max:255'],
            'social_tiktok_url' => ['nullable', 'url', 'max:255'],
            'developer_credit_name' => ['nullable', 'string', 'max:255'],
            'developer_credit_url' => ['nullable', 'url', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        return response()->json([
            'message' => 'Site settings updated.',
            'data' => SiteSetting::all()->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])->all(),
        ]);
    }
}
