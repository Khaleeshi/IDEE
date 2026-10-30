<?php

namespace Tests\Feature;

use App\Enums\StepStatus;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\StepProgress;
use App\Models\User;
use App\Notifications\StepReminder;
use App\Services\Journeys\JourneyService;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(JourneySeeder::class);
    }

    private function journey(string $slug): Journey
    {
        return Journey::where('slug', $slug)->firstOrFail();
    }

    private function step(string $journey, string $slug): JourneyStep
    {
        return $this->journey($journey)->steps()->where('slug', $slug)->firstOrFail();
    }

    public function test_the_five_journeys_are_public_and_each_step_cites_its_source(): void
    {
        $this->getJson('/api/journeys')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.situation', "J'ai une idée")
            ->assertJsonPath('data.4.title', 'Exporter pas à pas');

        $export = $this->journey('exporter');

        $this->getJson("/api/journeys/{$export->id}")
            ->assertOk()
            ->assertJsonPath('data.steps.1.cost', '41 500 FCFA')
            ->assertJsonPath('data.steps.1.source.url', 'https://industriecommerce.gouv.sn/carte-import-export/')
            ->assertJsonPath('data.steps.1.source.checked_on', '2026-10-29')
            ->assertJsonMissingPath('data.steps.1.progress');
    }

    public function test_every_sourced_step_is_dated_and_uncertain_export_steps_need_an_expert(): void
    {
        $this->assertSame(0, JourneyStep::whereNotNull('source_url')->whereNull('checked_on')->count());
        $this->assertTrue($this->step('exporter', 'normes-du-produit')->needs_expert);
        $this->assertTrue($this->step('exporter', 'controle-phytosanitaire')->needs_expert);
        $this->assertSame('formaliser-simplement', $this->step('exporter', 'entreprise-formalisee')->relatedJourney->slug);
    }

    public function test_a_member_starts_a_journey_and_tracks_a_step(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $journey = $this->journey('formaliser-simplement');
        $step = $this->step('formaliser-simplement', 'carte-de-commercant');

        $this->postJson("/api/journeys/{$journey->id}/start")
            ->assertOk()
            ->assertJsonPath('data.steps.0.progress.status.value', 'a_faire');

        $this->assertSame(8, $user->stepProgress()->count());

        $this->patchJson("/api/journey-steps/{$step->id}/progress", [
            'status' => 'terminee',
            'note' => 'Carte reçue le 30 octobre',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.label', 'Terminée')
            ->assertJsonPath('data.note', 'Carte reçue le 30 octobre');

        $this->getJson('/api/me/journeys')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.steps_count', 8)
            ->assertJsonPath('data.0.done_steps_count', 1);
    }

    public function test_updating_a_step_starts_the_journey_and_validates_the_reminder(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $step = $this->step('exporter', 'code-ppm');

        $this->patchJson("/api/journey-steps/{$step->id}/progress", [
            'status' => 'en_attente',
            'remind_on' => now()->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('remind_on');

        $this->patchJson("/api/journey-steps/{$step->id}/progress", [
            'status' => 'en_attente',
            'remind_on' => now()->addWeek()->toDateString(),
        ])->assertOk()->assertJsonPath('data.remind_on', now()->addWeek()->toDateString());

        $this->assertSame(8, $user->stepProgress()->count());
    }

    public function test_a_member_leaves_a_journey(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $journey = $this->journey('valider-mon-idee');

        $this->postJson("/api/journeys/{$journey->id}/start")->assertOk();
        $this->deleteJson("/api/journeys/{$journey->id}/start")->assertNoContent();

        $this->assertSame(0, $user->stepProgress()->count());
        $this->getJson('/api/me/journeys')->assertJsonCount(0, 'data');
    }

    public function test_guests_cannot_track_their_progress(): void
    {
        $journey = $this->journey('exporter');

        $this->postJson("/api/journeys/{$journey->id}/start")->assertUnauthorized();
    }

    public function test_the_legal_form_questionnaire_orients_the_member(): void
    {
        $this->getJson('/api/legal-form')
            ->assertOk()
            ->assertJsonPath('advice', null)
            ->assertJsonPath('questions.associes.options.seul', 'Seul');

        $this->getJson('/api/legal-form?associes=plusieurs&protection=oui&taille=petite')
            ->assertOk()
            ->assertJsonPath('advice.main.name', 'SARL')
            ->assertJsonPath('advice.alternative.name', 'SAS')
            ->assertJsonPath('advice.main.journey_id', $this->journey('creer-ma-societe')->id);

        $this->getJson('/api/legal-form?associes=seul&protection=non&taille=petite')
            ->assertJsonPath('advice.main.name', "Statut de l'entreprenant");

        $this->getJson('/api/legal-form?associes=seul')->assertUnprocessable()->assertJsonValidationErrors(['protection', 'taille']);
    }

    public function test_reminders_are_sent_once_and_never_for_finished_steps(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(JourneyService::class);

        $service->updateStep($user, $this->step('exporter', 'code-ppm'), StepStatus::EnAttente, today()->toDateString());
        $service->updateStep($user, $this->step('exporter', 'carte-import-export'), StepStatus::Terminee, today()->toDateString());

        $this->artisan('idee:step-reminders')->expectsOutputToContain('1 rappel(s)')->assertSuccessful();
        $this->artisan('idee:step-reminders')->expectsOutputToContain('0 rappel(s)')->assertSuccessful();

        Notification::assertSentToTimes($user, StepReminder::class, 1);
        $this->assertSame(1, $service->dueRemindersCount($user));
    }

    public function test_stale_guides_are_reported_to_the_team(): void
    {
        $this->artisan('idee:stale-guides')->expectsOutputToContain('0 fiche(s) à revoir')->assertSuccessful();

        $this->travel(config('idee.guide_review_after_months') + 1)->months();

        $this->assertTrue($this->step('exporter', 'carte-import-export')->isStale());
        $this->assertFalse($this->step('valider-mon-idee', 'decrire-le-probleme')->isStale());
        $this->artisan('idee:stale-guides')->expectsOutputToContain('Obtenir la carte import-export')->assertSuccessful();
    }

    public function test_reloading_the_guides_keeps_members_progress(): void
    {
        $user = User::factory()->create();
        app(JourneyService::class)->start($user, $this->journey('creer-ma-societe'));
        $ids = StepProgress::pluck('id')->sort()->values();

        $this->seed(JourneySeeder::class);

        $this->assertEquals($ids, StepProgress::pluck('id')->sort()->values());
        $this->assertSame(5, Journey::count());
    }
}
