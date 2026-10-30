<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Project;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\ContactRequestReceived;
use App\Notifications\NewMessage;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Minishlink\WebPush\VAPID;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SUBSCRIPTION = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abonnement-de-test',
        'keys' => ['p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM', 'auth' => 'tBHItJI5svbpez7KI4CCXg'],
    ];

    private function enablePush(): void
    {
        $keys = VAPID::createVapidKeys();
        config(['services.webpush.public_key' => $keys['publicKey'], 'services.webpush.private_key' => $keys['privateKey']]);
    }

    public function test_a_member_subscribes_this_phone_from_the_notification_page(): void
    {
        $this->enablePush();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))->assertSee('Recevoir les notifications sur ce téléphone');
        $this->postJson(route('push.subscribe'), self::SUBSCRIPTION)->assertNoContent();
        $this->postJson(route('push.subscribe'), self::SUBSCRIPTION)->assertNoContent();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        $this->postJson(route('push.subscribe'), ['endpoint' => 'http://exemple.sn/push', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])
            ->assertUnprocessable();

        $this->deleteJson(route('push.unsubscribe'), ['endpoint' => self::SUBSCRIPTION['endpoint']])->assertNoContent();
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_alerts_and_messages_go_to_subscribed_phones(): void
    {
        $this->enablePush();
        Notification::fake();
        config(['idee.notifications.whatsapp' => true]);
        $project = Project::factory()->create();
        $this->actingAs($project->owner)->postJson(route('push.subscribe'), self::SUBSCRIPTION);

        app(ContactRequestService::class)->send(User::factory()->create(), $project->owner, str_repeat('Bonjour, votre projet m\'intéresse beaucoup. ', 4), $project);

        Notification::assertSentTo($project->owner, ContactRequestReceived::class, fn ($notification, array $channels) => $channels === ['database', WebPushChannel::class, WhatsAppChannel::class]);
        $this->assertSame(['database', WebPushChannel::class], (new NewMessage(new Message))->via($project->owner));
    }

    public function test_nothing_is_offered_until_the_keys_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))->assertDontSee('Recevoir les notifications sur ce téléphone');
        $this->postJson(route('push.subscribe'), self::SUBSCRIPTION)->assertNotFound();
    }
}
