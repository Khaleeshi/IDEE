<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\NotificationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(30);

        return response()->json([
            'data' => $notifications->getCollection()->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? '',
                'link' => NotificationLink::for($notification->data),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
