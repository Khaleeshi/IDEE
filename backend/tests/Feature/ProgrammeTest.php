<?php

namespace Tests\Feature;

use App\Enums\AssistantStatus;
use App\Models\BusinessPlan;
use App\Models\Cohort;
use App\Models\Organisation;
use App\Models\ProgrammeApplication;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProgrammeNotice;
use App\Services\Organisations\OrganisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgrammeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Organisation $organisation;

    private string $programmeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create();
        $this->organisation = app(OrganisationService::class)->create($this->manager, [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès.',
        ]);
        $this->organisation->forceFill(['verification_status' => 'verifiee', 'verified_at' => now()])->save();

        Sanctum::actingAs($this->manager);
        $this->programmeId = $this->postJson("/api/organisations/{$this->organisation->id}/programmes", [
            'title' => 'Cohorte 2027',
            'kind' => 'accompagnement',
            'summary' => 'Six mois d\'accompagnement pour les projets de transformation.',
            'accepts_applications' => true,
            'application_questions' => "Combien de personnes travaillent sur le projet ?\n\nQuel est votre chiffre d'affaires mensuel ?",
        ])->assertCreated()->json('data.id');
    }

    private function answers(): array
    {
        return [
            'avancement' => 'Nous vendons 200 pots par mois à Thiès.',
            'besoins' => 'Un séchoir solaire et un comptable.',
            'motivation' => 'Structurer la production et trouver des clients.',
            'q1' => 'Trois personnes à temps plein.',
            'q2' => 'Environ 400 000 FCFA par mois.',
        ];
    }

    public function test_a_member_applies_with_their_idee_dossier_and_receives_the_decision(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        BusinessPlan::forceCreate(['project_id' => $project->id, 'version' => 1, 'status' => AssistantStatus::Pret, 'sections' => [['key' => 'resume', 'title' => 'Résumé', 'content' => 'Savons au karité.']]]);

        $this->getJson("/api/opportunities/{$this->programmeId}/questions")->assertOk()->assertJsonCount(5, 'data.questions');

        Sanctum::actingAs($project->owner);
        $this->postJson("/api/opportunities/{$this->programmeId}/applications", ['project_id' => $project->id, 'answers' => ['avancement' => 'Trop court']])
            ->assertUnprocessable();
        $applicationId = $this->postJson("/api/opportunities/{$this->programmeId}/applications", [
            'project_id' => $project->id,
            'answers' => $this->answers(),
            'share_business_plan' => true,
        ])->assertCreated()->assertJsonPath('data.business_plan_attached', true)->json('data.id');
        $this->postJson("/api/opportunities/{$this->programmeId}/applications", ['project_id' => $project->id, 'answers' => $this->answers()])->assertUnprocessable();
        Notification::assertSentTo($this->manager, ProgrammeNotice::class);

        // Un autre membre ne voit ni ne décide rien.
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/programmes/{$this->programmeId}/applications")->assertForbidden();
        $this->postJson("/api/programme-applications/{$applicationId}/decide", ['status' => 'retenue'])->assertForbidden();

        Sanctum::actingAs($this->manager);
        $this->getJson("/api/programmes/{$this->programmeId}/applications")
            ->assertOk()
            ->assertJsonPath('data.0.answers.3.answer', 'Trois personnes à temps plein.')
            ->assertJsonPath('data.0.business_plan.0.content', 'Savons au karité.');
        $this->postJson("/api/programme-applications/{$applicationId}/decide", ['status' => 'retenue', 'feedback' => 'Bienvenue dans la cohorte !'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'retenue');
        Notification::assertSentTo($project->owner, ProgrammeNotice::class);

        Sanctum::actingAs($project->owner);
        $this->getJson('/api/me/programme-applications')->assertJsonPath('data.0.feedback', 'Bienvenue dans la cohorte !');
        $this->actingAs($project->owner)->get(route('applications.show', $applicationId))->assertOk()->assertSee('Bienvenue dans la cohorte');
    }

    public function test_the_programme_page_offers_the_application_form(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($project->owner)
            ->get(route('opportunities.show', $this->programmeId))
            ->assertSee('Candidater avec mon dossier IDEE')
            ->assertSee('Combien de personnes travaillent sur le projet ?');

        $this->post(route('programmes.apply', $this->programmeId), ['project_id' => $project->id, 'answers' => $this->answers()]);
        $application = ProgrammeApplication::sole();
        $this->assertNull($application->business_plan);

        $this->get(route('opportunities.show', $this->programmeId))->assertSee('Voir ma candidature');
        $this->actingAs($this->manager)->get(route('programmes.applications', $this->programmeId))->assertOk()->assertSee($project->title);
    }

    public function test_retained_projects_are_followed_in_a_cohort(): void
    {
        $retained = Project::factory()->create(['title' => 'Savons au karité']);
        $pending = Project::factory()->create();
        Sanctum::actingAs($retained->owner);
        $retainedId = $this->postJson("/api/opportunities/{$this->programmeId}/applications", ['project_id' => $retained->id, 'answers' => $this->answers()])->json('data.id');
        Sanctum::actingAs($pending->owner);
        $pendingId = $this->postJson("/api/opportunities/{$this->programmeId}/applications", ['project_id' => $pending->id, 'answers' => $this->answers()])->json('data.id');

        $this->actingAs($this->manager);
        $this->post(route('applications.decide', $retainedId), ['status' => 'retenue']);
        $this->post(route('cohorts.store', $this->organisation), ['name' => 'Cohorte 2027', 'opportunity_id' => $this->programmeId]);
        $cohort = Cohort::sole();

        $this->post(route('cohorts.add', $cohort), ['application_id' => $pendingId])->assertSessionHasErrors('application');
        $this->post(route('cohorts.add', $cohort), ['application_id' => $retainedId])->assertRedirect(route('cohorts.show', $cohort));

        $member = $cohort->members()->sole();
        $this->post(route('cohorts.checkin', $member), ['status' => 'a_risque', 'note' => 'Le fournisseur de beurre de karité a doublé ses prix.']);

        $this->get(route('cohorts.show', $cohort))->assertOk()->assertSee('Savons au karité')->assertSee('À surveiller')->assertSee('doublé ses prix');
        Sanctum::actingAs($this->manager);
        $this->getJson("/api/cohorts/{$cohort->id}")->assertOk()->assertJsonPath('data.0.latest_checkin.status.value', 'a_risque');

        $this->actingAs(User::factory()->create())->get(route('cohorts.show', $cohort))->assertForbidden();
    }
}
