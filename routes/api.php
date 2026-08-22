<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PublicContentController;
use App\Http\Controllers\Api\V1\InteractionController;
use App\Http\Controllers\Api\V1\AdminInboxController;
use App\Http\Controllers\Api\V1\AdminContentWorkflowController;
use App\Http\Controllers\Api\V1\AdminMediaController;
use App\Http\Controllers\Api\V1\AdminContentController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show']);

Route::get('run-setup', function () {
    Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--force' => true,
    ]);

    return response()->json([
        'message' => 'Database setup completed successfully.',
        'output' => Artisan::output(),
    ]);
});

Route::prefix('v1')->group(function (): void {
    Route::get('health', [HealthController::class, 'show']);
    Route::get('hubs', [PublicContentController::class, 'hubs']);
    Route::get('hubs/{slug}', [PublicContentController::class, 'hub']);
    Route::get('programs', [PublicContentController::class, 'programs']);
    Route::get('programs/{slug}', [PublicContentController::class, 'program']);
    Route::get('stories', [PublicContentController::class, 'stories']);
    Route::get('stories/{slug}', [PublicContentController::class, 'story']);
    Route::get('events', [PublicContentController::class, 'events']);
    Route::get('events/{slug}', [PublicContentController::class, 'event']);
    Route::get('partners', [PublicContentController::class, 'partners']);
    Route::get('news', [PublicContentController::class, 'news']);
    Route::get('news/{slug}', [PublicContentController::class, 'newsPost']);

    Route::prefix('interactions')->middleware('throttle:public-interactions')->group(function (): void {
        Route::post('volunteer', [InteractionController::class, 'volunteer']);
        Route::post('partnership', [InteractionController::class, 'partnership']);
        Route::post('newsletter', [InteractionController::class, 'newsletter']);
        Route::post('contact', [InteractionController::class, 'contact']);
    });

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware('auth:sanctum')->prefix('admin')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::prefix('content')->group(function (): void {
            Route::get('{type}', [AdminContentController::class, 'index']);
            Route::post('{type}', [AdminContentController::class, 'store']);
            Route::get('{type}/{id}', [AdminContentController::class, 'show']);
            Route::match(['put', 'patch'], '{type}/{id}', [AdminContentController::class, 'update']);
            Route::delete('{type}/{id}', [AdminContentController::class, 'destroy']);
        });
        Route::prefix('inbox')->middleware('permission:manage-inbox')->group(function (): void {
            Route::get('{type}', [AdminInboxController::class, 'index']);
            Route::patch('{type}/{id}', [AdminInboxController::class, 'updateStatus'])
                ->middleware('role:system-owner|admin');
        });
        Route::patch('content/{type}/{id}/status', [AdminContentWorkflowController::class, 'updateStatus']);
        Route::middleware('permission:manage-media')->group(function (): void {
            Route::post('media/{type}/{id}', [AdminMediaController::class, 'store']);
            Route::delete('media/{type}/{id}/{mediaId}', [AdminMediaController::class, 'destroy']);
        });
    });
});