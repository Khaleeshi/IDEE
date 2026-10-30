<?php

namespace Tests\Feature;

use App\Enums\ContactRequestStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Messaging\MessageService;
use App\Services\Moderation\ScamDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScamAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_for_money_codes_or_bank_details_are_spotted(): void
    {
        $detector = app(ScamDetector::class);

        $this->assertTrue($detector->isSuspicious('Pour étudier votre dossier, il faut payer les FRAIS DE DOSSIER : 25 000 FCFA.'));
        $this->assertTrue($detector->isSuspicious('Faites un transfert sur mon numéro Wave avant lundi.'));
        $this->assertTrue($detector->isSuspicious('Donne-moi le code reçu par SMS pour valider.'));
        $this->assertTrue($detector->isSuspicious('Envoyez votre RIB et la date d’expiration de votre carte.'));
        $this->assertFalse($detector->isSuspicious('Je vous envoie le devis demain, rendez-vous à la tribune du marché de Thiès.'));
        $this->assertFalse($detector->isSuspicious(null));
    }

    public function test_the_recipient_is_warned_in_the_conversation(): void
    {
        $project = Project::factory()->create();
        $investor = User::factory()->create();
        $requests = app(ContactRequestService::class);
        $conversation = $requests->send($investor, $project->owner, str_repeat('Notre fonds finance les projets agricoles et le vôtre nous intéresse. ', 2), $project);
        $requests->respond($conversation, $project->owner, ContactRequestStatus::Interesse);
        $message = app(MessageService::class)->send($conversation->fresh(), $investor, 'Pour débloquer le financement, réglez les frais de dossier par Orange Money.');

        $this->actingAs($project->owner)->get(route('contact-requests.show', $conversation))->assertSee('Prudence : ce message parle d');
        $this->actingAs($investor)->get(route('contact-requests.show', $conversation))->assertDontSee('Prudence : ce message parle d');

        Sanctum::actingAs($project->owner);
        $this->getJson("/api/contact-requests/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.id', $message->id)
            ->assertJsonPath('data.0.scam_alert', true);
    }
}
