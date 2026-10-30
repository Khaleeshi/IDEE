<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Notifications\PushService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Abonnement d'un téléphone aux notifications push (NO-01), envoyé par le navigateur.
 */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly PushService $push) {}

    public function store(Request $request): Response
    {
        abort_unless($this->push->isEnabled(), Response::HTTP_NOT_FOUND);

        $subscription = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $this->push->subscribe($request->user(), $subscription);

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $this->push->unsubscribe($request->user(), (string) $request->validate(['endpoint' => ['required', 'string']])['endpoint']);

        return response()->noContent();
    }
}
