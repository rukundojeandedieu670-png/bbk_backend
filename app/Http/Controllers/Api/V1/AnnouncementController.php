<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Announcement::query()->latest()->get(),
        ]);
    }

    public function active(): JsonResponse
    {
        return response()->json([
            'data' => Announcement::query()->active()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAnnouncementManagement($request);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:info,promo,alert'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $announcement = Announcement::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $announcement, 'message' => 'Announcement created.'], 201);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        return response()->json(['data' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorizeAnnouncementManagement($request);

        $data = $request->validate([
            'message' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:info,promo,alert'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $announcement->update($data);

        return response()->json(['data' => $announcement->fresh(), 'message' => 'Announcement updated.']);
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorizeAnnouncementManagement($request);
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }

    private function authorizeAnnouncementManagement(Request $request): void
    {
        abort_unless($request->user()?->can('manage-system-settings') || $request->user()?->hasRole('system-owner'), 403);
    }
}
