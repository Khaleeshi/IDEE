<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\ApplicationReminder;
use Database\Seeders\OpportunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OpportunitySeeder::class);
    }

    public function test_the_directory_lists_open_opportunities_with_their_sources(): void
    {
        Opportunity::where('slug', 'teranga-capital')->update(['deadline' => now()->subDay()]);

        $this->getJson('/api/opportunities')
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonMissing(['title' => 'Fonds d\'investissement Teranga Capital']);

        $this->getJson('/api/opportunities?type=garantie')->assertJsonCount(2, 'data')->assertJsonPath('data.0.organisation', 'FONGIP');
        $this->getJson('/api/opportunities?q=jeune')->assertJsonFragment(['title' => 'Crédit Jeune Entrepreneur']);

        $this->get(route('opportunities.index'))->assertOk()->assertSee('Garantie individuelle de crédit');
        $this->get(route('opportunities.show', Opportunity::where('slug', 'der-fj-financement')->first()))
            ->assertOk()
            ->assertSee('Taux de 5 % (TEG)')
            ->assertSee('consultée le 29 octobre 2026');
    }

    public function test_a_member_tracks_an_application_and_gets_a_reminder(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $opportunity = Opportunity::where('slug', 'fongip-garantie-individuelle')->firstOrFail();
        Sanctum::actingAs($user);

        $this->putJson("/api/opportunities/{$opportunity->id}/application", [
            'status' => 'deposee',
            'note' => 'Dossier remis à ma banque',
            'remind_on' => today()->toDateString(),
        ])->assertOk()->assertJsonPath('data.status.label', 'Déposée');

        $this->getJson('/api/me/applications')->assertJsonPath('data.0.opportunity.title', 'Garantie individuelle de crédit');

        $this->artisan('idee:step-reminders')->expectsOutputToContain('1 rappel(s) de candidature')->assertSuccessful();
        Notification::assertSentTo($user, ApplicationReminder::class);

        $this->deleteJson("/api/opportunities/{$opportunity->id}/application")->assertNoContent();
        $this->getJson('/api/me/applications')->assertJsonCount(0, 'data');
    }

    public function test_the_team_adds_an_opportunity_that_a_new_release_keeps(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.opportunities.store'), [
                'title' => 'Concours national de l\'innovation',
                'organisation' => 'Ministère de l\'Économie',
                'kind' => 'concours',
                'summary' => 'Prix pour les jeunes entreprises innovantes.',
                'deadline' => now()->addMonth()->toDateString(),
                'is_published' => 1,
            ])->assertRedirect(route('admin.opportunities'));

        $fongip = Opportunity::where('slug', 'fongip-garantie-individuelle')->firstOrFail();
        $this->put(route('admin.opportunities.update', $fongip), [
            'title' => $fongip->title,
            'organisation' => $fongip->organisation,
            'kind' => 'garantie',
            'summary' => $fongip->summary,
            'amount' => 'Jusqu\'à 600 millions FCFA',
            'is_published' => 1,
        ]);

        $this->seed(OpportunitySeeder::class);

        $this->assertSame('Jusqu\'à 600 millions FCFA', $fongip->fresh()->amount);
        $this->getJson('/api/opportunities?type=concours')->assertJsonPath('data.0.title', 'Concours national de l\'innovation');
    }
}
