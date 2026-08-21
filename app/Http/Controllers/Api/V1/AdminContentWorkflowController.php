<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Program;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentWorkflowController extends Controller
{
    public function updateStatus(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,pending_review,published,archived'],
        ]);
        $content = $this->modelFor($type)::query()->findOrFail($id);
        $permission = $this->permissionFor($type);
        $user = $request->user();

        abort_unless(
            $user->can('publish-content') || $user->can($permission),
            403,
        );

        if (in_array($data['status'], ['published', 'archived'], true)) {
            abort_unless($user->can('publish-content'), 403);
        }

        $before = ['status' => $content->status];
        $content->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $data['status'] === 'published' ? 'publish' : 'status_changed',
            'subject_type' => $content->getMorphClass(),
            'subject_id' => $content->id,
            'changes' => ['before' => $before, 'after' => ['status' => $content->status]],
        ]);

        return response()->json(['data' => $content->fresh(), 'message' => 'Content status updated.']);
    }

    private function modelFor(string $type): string
    {
        return match ($type) {
            'programs' => Program::class,
            'stories' => Story::class,
            'events' => Event::class,
            'news' => NewsPost::class,
            default => abort(404),
        };
    }

    private function permissionFor(string $type): string
    {
        return match ($type) {
            'programs' => 'manage-programs',
            'stories' => 'manage-stories',
            'events' => 'manage-events',
            'news' => 'manage-news',
            default => abort(404),
        };
    }
}