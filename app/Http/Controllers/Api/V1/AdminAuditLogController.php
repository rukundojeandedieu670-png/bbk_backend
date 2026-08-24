<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-audit-log'), 403);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->paginate(50);

        return response()->json([
            'data' => $logs->getCollection()->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'subjectType' => $log->subject_type,
                'subjectId' => $log->subject_id,
                'changes' => $log->changes,
                'createdAt' => $log->created_at?->toIso8601String(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ]),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }
}