<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Bonjour, je distribue des produits alimentaires à Dakar et votre farine m\'intéresse beaucoup. Pouvons-nous en parler cette semaine ?';

    public function test_a_new_contact_request_appears_in_the_notification_centre(): void
    {
        Http::fake();
        $project = Project::factory()->create();
        app(ContactRequestService::class)->send(User::factory()->create(['name' => 'Awa Diop']), $project->owner, self::MESSAGE, $project);

        $this->actingAs($project->owner)->get(route('home'))->assertSee('1 non lues');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Awa Diop vous a envoyé une demande de contact');
        $this->assertSame(0, $project->owner->unreadNotifications()->count());

        Http::assertNothingSent();
    }

    public function test_alerts_also_go_to_whatsapp_once_the_channel_is_enabled(): void
    {
        config([
            'idee.notifications.whatsapp' => true,
            'services.whatsapp.token' => 'jeton',
            'services.whatsapp.phone_number_id' => '123',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid']]])]);
        $project = Project::factory()->create();
        $optedOut = Project::factory()->for(User::factory()->state(['contact_preferences' => ['accepts_requests' => true, 'whatsapp_alerts' => false]]), 'owner')->create();

        $service = app(ContactRequestService::class);
        $service->send(User::factory()->create(), $project->owner, self::MESSAGE, $project);
        $service->send(User::factory()->create(), $optedOut->owner, self::MESSAGE, $optedOut);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'idee_alerte'
            && $request['to'] === ltrim($project->owner->phone, '+')
            && str_contains($request['template']['components'][0]['parameters'][0]['text'], 'demande de contact'));

        Sanctum::actingAs($project->owner);
        $this->getJson('/api/me/notifications')->assertJsonPath('unread', 1)->assertJsonPath('data.0.link', route('contact-requests.show', $project->owner->receivedContactRequests()->first()));
        $this->postJson('/api/me/notifications/read')->assertNoContent();
        $this->getJson('/api/me/notifications')->assertJsonPath('unread', 0);
    }

    public function test_a_critical_alert_falls_back_to_sms_when_whatsapp_fails(): void
    {
        config([
            'idee.notifications.whatsapp' => true,
            'idee.notifications.sms_fallback' => true,
            'services.whatsapp.token' => 'jeton',
            'services.whatsapp.phone_number_id' => '123',
            'services.orange_sms.client_id' => 'id',
            'services.orange_sms.client_secret' => 'secret',
            'services.orange_sms.sender' => 'tel:+2210000',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'indisponible']], 500),
            'api.orange.com/oauth/*' => Http::response(['access_token' => 'jeton-orange', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response([], 201),
        ]);
        $project = Project::factory()->for(User::factory()->state(['phone' => '+221771112233']), 'owner')->create();

        app(ContactRequestService::class)->send(User::factory()->create(), $project->owner, self::MESSAGE, $project);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'smsmessaging')
            && str_starts_with($request['outboundSMSMessageRequest']['outboundSMSTextMessage']['message'], 'IDEE : ')
            && $request['outboundSMSMessageRequest']['address'] === 'tel:+221771112233');
    }
}
