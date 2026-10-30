<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Centre de notifications : les 50 dernières, marquées comme lues à l'ouverture.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(50)->get();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', ['notifications' => $notifications]);
    }
}
