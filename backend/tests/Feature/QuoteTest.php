<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\QuoteNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private const NEED = 'Rédiger les statuts d\'une SARL à deux associés et préparer le dossier de création à l\'APIX.';

    private function expert(): User
    {
        $expert = User::factory()->identityVerified()->create(['expertise' => 'Droit des sociétés OHADA', 'rate' => 'Sur devis']);
        $expert->syncRoles([UserRole::Expert]);

        return $expert;
    }

    public function test_a_quote_goes_from_request_to_completed_service(): void
    {
        Notification::fake();
        $expert = $this->expert();
        $project = Project::factory()->create();
        $client = $project->owner;
        Sanctum::actingAs($client);

        $quoteId = $this->postJson("/api/members/{$expert->id}/quotes", ['description' => self::NEED, 'project_id' => $project->id, 'budget' => '100 000 FCFA'])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'demandee')
            ->json('data.id');
        Notification::assertSentTo($expert, QuoteNotice::class);

        // Le client n'envoie pas de devis à sa place.
        $this->postJson("/api/quotes/{$quoteId}/quote", ['quote_amount_fcfa' => 1, 'quote_delay' => '1 jour', 'quote_details' => 'x'])->assertForbidden();

        Sanctum::actingAs($expert);
        $this->postJson("/api/quotes/{$quoteId}/quote", ['quote_amount_fcfa' => 90000, 'quote_delay' => '10 jours', 'quote_details' => 'Statuts, PV de constitution, accompagnement au dépôt.'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'devis_envoye')
            ->assertJsonPath('data.quote.amount_fcfa', 90000);
        $this->postJson("/api/quotes/{$quoteId}/deliver")->assertUnprocessable();

        Sanctum::actingAs($client);
        $this->postJson("/api/quotes/{$quoteId}/answer", ['accepted' => true])->assertOk()->assertJsonPath('data.status.value', 'acceptee');

        Sanctum::actingAs($expert);
        $this->postJson("/api/quotes/{$quoteId}/deliver")->assertOk()->assertJsonPath('data.status.value', 'livree');

        Sanctum::actingAs($client);
        $this->postJson("/api/quotes/{$quoteId}/complete")->assertOk()->assertJsonPath('data.status.value', 'terminee');
        $this->getJson('/api/me/quotes')->assertJsonCount(1, 'data');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/quotes/{$quoteId}")->assertForbidden();
    }

    public function test_only_verified_experts_receive_quote_requests(): void
    {
        $unverified = User::factory()->create();
        $unverified->syncRoles([UserRole::Expert]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/members/{$unverified->id}/quotes", ['description' => self::NEED])->assertUnprocessable();
        $this->postJson("/api/members/{$this->expert()->id}/quotes", ['description' => self::NEED, 'project_id' => Project::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_the_quote_pages_follow_the_service(): void
    {
        $expert = $this->expert();
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('members.show', $expert))->assertSee('Demander un devis');
        $this->get(route('quotes.create', $expert))->assertOk()->assertSee('aucun argent ne transite');
        $this->post(route('quotes.store', $expert), ['description' => self::NEED]);
        $quote = QuoteRequest::sole();

        $this->actingAs($expert)->get(route('quotes.show', $quote))->assertSee('Envoyer mon devis');
        $this->post(route('quotes.send', $quote), ['quote_amount_fcfa' => 75000, 'quote_delay' => '7 jours', 'quote_details' => 'Statuts et dépôt.']);
        $this->actingAs($client)->get(route('quotes.show', $quote))->assertSee("75\u{00A0}000 FCFA")->assertSee('Accepter');
        $this->get(route('quotes.index'))->assertSee($expert->name);
    }
}
