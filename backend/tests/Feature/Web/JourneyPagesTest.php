<?php

namespace Tests\Feature\Web;

use App\Enums\StepStatus;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\Project;
use App\Models\User;
use App\Services\Journeys\JourneyService;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyPagesTest extends TestCase
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

    public function test_visitors_choose_their_situation_and_read_a_journey(): void
    {
        $this->get(route('journeys.index'))
            ->assertOk()
            ->assertSee('Je vends déjà')
            ->assertSee('Je veux exporter')
            ->assertSee('Quelle forme juridique choisir ?');

        $this->get(route('journeys.show', $this->journey('exporter')))
            ->assertOk()
            ->assertSee('Obtenir la carte import-export')
            ->assertSee('41 500 FCFA')
            ->assertSee('consultée le 29 octobre 2026')
            ->assertSee('À valider avec un expert')
            ->assertSee('Connectez-vous')
            ->assertDontSee('Où en êtes-vous ?');
    }

    public function test_a_member_starts_a_journey_and_marks_a_step_done(): void
    {
        $user = User::factory()->create();
        $journey = $this->journey('formaliser-simplement');
        $step = $this->step('formaliser-simplement', 'reunir-les-pieces');

        $this->actingAs($user)->post(route('journeys.start', $journey))
            ->assertRedirect(route('journeys.show', $journey));

        $this->put(route('journey-steps.update', $step), ['status' => 'terminee', 'note' => 'Casier demandé au tribunal'])
            ->assertRedirect(route('journeys.show', $journey).'#etape-'.$step->id);

        $this->get(route('journeys.show', $journey))
            ->assertSee('1 étape terminée sur 8')
            ->assertSee('Casier demandé au tribunal');

        $this->get(route('journeys.index'))->assertSee('Mes démarches')->assertSee('1/8');
    }

    public function test_a_due_reminder_appears_in_the_journeys_page(): void
    {
        $user = User::factory()->create();
        app(JourneyService::class)->updateStep($user, $this->step('exporter', 'code-ppm'), StepStatus::EnAttente, today()->toDateString());

        $this->actingAs($user)->get(route('journeys.index'))
            ->assertSee('Rappels')
            ->assertSee('Obtenir le code douane (code PPM)');
    }

    public function test_a_member_leaves_a_journey(): void
    {
        $user = User::factory()->create();
        $journey = $this->journey('valider-mon-idee');
        app(JourneyService::class)->start($user, $journey);

        $this->actingAs($user)->delete(route('journeys.leave', $journey))->assertRedirect(route('journeys.index'));

        $this->assertSame(0, $user->stepProgress()->count());
    }

    public function test_the_questionnaire_suggests_a_legal_form(): void
    {
        $this->get(route('journeys.legal-form'))
            ->assertOk()
            ->assertSee('Vous lancez-vous seul ou avec des associés ?')
            ->assertDontSee('Piste conseillée');

        $this->get(route('journeys.legal-form', ['associes' => 'seul', 'protection' => 'oui', 'taille' => 'grande']))
            ->assertOk()
            ->assertSee('Piste conseillée')
            ->assertSee('SASU (SAS à associé unique)')
            ->assertSee('Suivre le parcours « Créer et s&#039;organiser »', false);
    }

    public function test_a_step_leads_to_a_prefilled_call_to_action_on_the_members_project(): void
    {
        $project = Project::factory()->create();
        $step = $this->step('creer-ma-societe', 'passer-chez-le-notaire');

        $this->actingAs($project->owner)->get(route('journeys.show', $this->journey('creer-ma-societe')))
            ->assertSee(route('projects.show', [$project, 'etape' => $step->id]), false);

        $this->get(route('projects.show', [$project, 'etape' => $step->id]))
            ->assertOk()
            ->assertSee('Aide pour : Passer chez le notaire');

        $this->actingAs(User::factory()->create())
            ->get(route('projects.show', [$project, 'etape' => $step->id]))
            ->assertDontSee('Aide pour : Passer chez le notaire');
    }
}
