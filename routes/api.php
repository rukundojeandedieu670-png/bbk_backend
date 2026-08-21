<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PublicContentController;
use App\Http\Controllers\Api\V1\InteractionController;
use App\Http\Controllers\Api\V1\AdminInboxController;
use App\Http\Controllers\Api\V1\AdminContentWorkflowController;
use App\Http\Controllers\Api\V1\AdminMediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', fn () => response()->json(['status' => 'ok']));
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