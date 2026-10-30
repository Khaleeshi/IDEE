<?php

namespace App\Services\Notifications;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Notifications push du navigateur (NO-01), gratuites : elles s'affichent
 * sur le téléphone même quand IDEE n'est pas ouvert.
 */
class PushService
{
    public function isEnabled(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    /**
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}}  $subscription
     */
    public function subscribe(User $user, array $subscription): PushSubscription
    {
        $item = PushSubscription::firstOrNew(['endpoint_hash' => hash('sha256', $subscription['endpoint'])]);
        $item->fill([
            'endpoint' => $subscription['endpoint'],
            'public_key' => $subscription['keys']['p256dh'],
            'auth_token' => $subscription['keys']['auth'],
        ]);
        $item->user()->associate($user);
        $item->save();

        return $item;
    }

    public function unsubscribe(User $user, string $endpoint): void
    {
        PushSubscription::whereBelongsTo($user)->where('endpoint_hash', hash('sha256', $endpoint))->delete();
    }

    /**
     * Envoie une notification à tous les appareils du membre ; les abonnements expirés sont oubliés.
     */
    public function send(User $user, string $body, ?string $url = null): void
    {
        $subscriptions = PushSubscription::whereBelongsTo($user)->get();

        if ($subscriptions->isEmpty() || ! $this->isEnabled()) {
            return;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('services.webpush.subject'),
            'publicKey' => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ]]);
        $payload = (string) json_encode(['title' => 'IDEE', 'body' => $body, 'url' => $url ?? route('notifications.index')], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
            ]), $payload);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
            } elseif (! $report->isSuccess()) {
                Log::warning('Notification push non remise.', ['raison' => $report->getReason()]);
            }
        }
    }
}
