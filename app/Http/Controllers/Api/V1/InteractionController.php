<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function volunteer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'hubOfInterest' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $application = VolunteerApplication::create([
            ...$data,
            'hub_of_interest' => $data['hubOfInterest'] ?? null,
        ]);

        return response()->json(['message' => 'Volunteer application received.', 'id' => $application->id], 201);
    }

    public function partnership(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationName' => ['required', 'string', 'max:180'],
            'contactName' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $inquiry = PartnershipInquiry::create([
            'organization_name' => $data['organizationName'],
            'contact_name' => $data['contactName'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);

        return response()->json(['message' => 'Partnership inquiry received.', 'id' => $inquiry->id], 201);
    }

    public function newsletter(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['subscribed_at' => now()],
        );

        return response()->json([
            'message' => 'Newsletter subscription received.',
            'alreadySubscribed' => ! $subscriber->wasRecentlyCreated,
        ], $subscriber->wasRecentlyCreated ? 201 : 200);
    }

    public function contact(Request $request): JsonResponse
    {
        $message = ContactMessage::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:3000'],
        ]));

        return response()->json(['message' => 'Contact message received.', 'id' => $message->id], 201);
    }
}