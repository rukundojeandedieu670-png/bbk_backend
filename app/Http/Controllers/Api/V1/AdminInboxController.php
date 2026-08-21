<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInboxController extends Controller
{
    public function index(string $type): JsonResponse
    {
        $model = $this->modelFor($type);

        return response()->json([
            'data' => $model::query()->latest()->paginate(25),
        ]);
    }

    public function updateStatus(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate(['status' => ['required', 'string', 'max:40']]);
        $record = $this->modelFor($type)::query()->findOrFail($id);
        $record->update(['status' => $request->string('status')->toString()]);

        return response()->json(['message' => 'Inbox status updated.', 'data' => $record->fresh()]);
    }

    private function modelFor(string $type): string
    {
        return match ($type) {
            'volunteers' => VolunteerApplication::class,
            'partnerships' => PartnershipInquiry::class,
            'newsletter' => NewsletterSubscriber::class,
            'contacts' => ContactMessage::class,
            default => abort(404),
        };
    }
}