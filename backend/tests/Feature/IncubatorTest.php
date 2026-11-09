<?php

namespace Tests\Feature;

use App\Enums\OrganisationRole;
use App\Models\Cohort;
use App\Models\CohortProject;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use App\Services\Organisations\OrganisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Espace incubateur : postes internes (IN-01), coachs affectés aux projets
 * incubés (IN-02), tableau de suivi exportable (IN-03).
 */
class IncubatorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organisation $incubator;

    private Cohort $cohort;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->admin = User::factory()->create();
        $this->incubator = app(OrganisationService::class)->create($this->admin, [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès.',
        ]);
        $this->incubator->forceFill(['verification_status' => 'verifiee', 'verified_at' => now()])->save();
        $this->cohort = Cohort::forceCreate(['organisation_id' => $this->incubator->id, 'name' => 'Cohorte 2027']);
    }

    private function staff(OrganisationRole $role): User
    {
        $user = User::factory()->create();
        app(OrganisationService::class)->addMember($this->incubator, $this->admin, $user->phone, $role);

        return $user;
    }

    private function incubate(string $title): CohortProject
    {
        return CohortProject::forceCreate(['cohort_id' => $this->cohort->id, 'project_id' => Project::factory()->create(['title' => $title])->id]);
    }

    public function test_a_programme_manager_runs_cohorts_without_managing_the_page(): void
    {
        $manager = $this->staff(OrganisationRole::Gestionnaire);
        $this->incubate('Savons au karité');

        $this->actingAs($manager)->get(route('organisations.show', $this->incubator))->assertOk()->assertSee('Suivre les cohortes');
        $this->get(route('cohorts.index', $this->incubator))->assertOk()->assertSee('Cohorte 2027');
        $this->get(route('cohorts.show', $this->cohort))->assertOk()->assertSee('Savons au karité');

        // La page elle-même reste à l'administrateur.
        $this->get(route('organisations.manage', $this->incubator))->assertForbidden();

        // Un simple membre ne voit pas les cohortes.
        $this->actingAs($this->staff(OrganisationRole::Membre))->get(route('cohorts.show', $this->cohort))->assertForbidden();
    }

    public function test_a_coach_follows_only_the_projects_entrusted_to_them(): void
    {
        $coach = $this->staff(OrganisationRole::Coach);
        $mine = $this->incubate('Savons au karité');
        $other = $this->incubate('Jus de bissap');

        $this->actingAs($this->admin)->post(route('cohorts.coach', $mine), ['coach_id' => $coach->id])->assertRedirect();
        $this->assertSame($coach->id, $mine->fresh()->coach_id);

        // Un membre extérieur ne peut pas être désigné coach.
        $this->post(route('cohorts.coach', $other), ['coach_id' => User::factory()->create()->id])->assertSessionHasErrors('coach_id');

        $this->actingAs($coach)->get(route('accompaniments.index'))->assertOk()->assertSee('Projets incubés que je suis')->assertSee('Savons au karité');
        $this->get(route('cohorts.show', $this->cohort))->assertOk()->assertSee('Savons au karité')->assertDontSee('Jus de bissap')->assertDontSee('Télécharger le tableau');

        $this->post(route('cohorts.checkin', $mine), ['status' => 'bonne_voie', 'note' => 'Premier client signé.'])->assertRedirect();
        $this->post(route('cohorts.checkin', $other), ['status' => 'bonne_voie', 'note' => 'Pas le mien.'])->assertForbidden();
        $this->get(route('cohorts.export', $this->cohort))->assertForbidden();
    }

    public function test_the_follow_up_table_downloads_for_a_spreadsheet(): void
    {
        $coach = $this->staff(OrganisationRole::Coach);
        $member = $this->incubate('Savons au karité');
        $member->forceFill(['coach_id' => $coach->id])->save();

        $response = $this->actingAs($this->admin)->get(route('cohorts.export', $this->cohort))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Projet;Porteur;Région', $csv);
        $this->assertStringContainsString('Savons au karité', $csv);
        $this->assertStringContainsString($coach->name, $csv);
    }
}
