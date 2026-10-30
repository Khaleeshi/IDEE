<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\User;
use App\Notifications\OrganisationNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function incubator(User $admin): string
    {
        Sanctum::actingAs($admin);

        return $this->postJson('/api/organisations', [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès pendant six mois.',
            'region' => 'Thiès',
        ])->assertCreated()
            ->assertJsonPath('data.verification.value', 'non_verifiee')
            ->assertJsonPath('data.members.0.role.value', 'administrateur')
            ->json('data.id');
    }

    public function test_an_organisation_page_is_managed_by_several_members(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $colleague = User::factory()->create(['phone' => '+221771112233']);
        $organisationId = $this->incubator($admin);

        $this->postJson("/api/organisations/{$organisationId}/members", ['identifier' => '77 999 99 99', 'role' => 'membre'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identifier');
        $this->postJson("/api/organisations/{$organisationId}/members", ['identifier' => '77 111 22 33', 'role' => 'administrateur'])
            ->assertCreated()
            ->assertJsonCount(2, 'data.members');
        Notification::assertSentTo($colleague, OrganisationNotice::class);

        // Le collègue gère la page à son tour ; la dernière personne qui gère ne peut pas partir.
        Sanctum::actingAs($colleague);
        $this->patchJson("/api/organisations/{$organisationId}", ['region' => 'Thiès et Diourbel'])->assertOk()->assertJsonPath('data.region', 'Thiès et Diourbel');
        $adminMembership = Organisation::find($organisationId)->members()->where('user_id', $admin->id)->sole();
        $this->deleteJson("/api/organisation-members/{$adminMembership->id}")->assertNoContent();
        $ownMembership = Organisation::find($organisationId)->members()->sole();
        $this->deleteJson("/api/organisation-members/{$ownMembership->id}")->assertUnprocessable();

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/organisations/{$organisationId}", ['region' => 'Dakar'])->assertForbidden();

        $this->get(route('organisations.show', $organisationId))->assertOk()->assertSee('Incubateur de Thiès');
        $this->get(route('members.show', $colleague))->assertSee('Membre de');
    }

    public function test_verification_checks_rccm_and_ninea_and_erases_the_document(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $organisationId = $this->incubator($admin);
        $document = UploadedFile::fake()->create('rccm.pdf', 200, 'application/pdf');

        $this->postJson("/api/organisations/{$organisationId}/verification", ['rccm' => '12345', 'ninea' => 'abc', 'document' => $document])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rccm', 'ninea']);

        $this->postJson("/api/organisations/{$organisationId}/verification", ['rccm' => 'SN-THS-2023-B-1234', 'ninea' => '009876543 2V2', 'document' => $document])
            ->assertOk()
            ->assertJsonPath('data.verification.value', 'en_attente')
            ->assertJsonPath('data.rccm', null)
            ->assertJsonMissingPath('data.verification_document_path');

        $organisation = Organisation::find($organisationId);
        Storage::disk('local')->assertExists($organisation->verification_document_path);

        // Programmes réservés aux organisations vérifiées (OR-01).
        $this->postJson("/api/organisations/{$organisationId}/programmes", ['title' => 'Cohorte 2027', 'kind' => 'accompagnement', 'summary' => 'Six mois d\'accompagnement.'])
            ->assertUnprocessable();

        $reviewer = User::factory()->create(['is_admin' => true]);
        $this->actingAs($reviewer)->get(route('admin.organisations'))->assertOk()->assertSee('SN-THS-2023-B-1234');
        $this->get(route('admin.organisations.document', $organisation))->assertOk();
        $this->post(route('admin.organisations.decide', $organisation), ['approved' => 1])->assertRedirect(route('admin.organisations'));

        $organisation->refresh();
        $this->assertTrue($organisation->isVerified());
        $this->assertNull($organisation->verification_document_path);
        Storage::disk('local')->assertDirectoryEmpty("verifications/organisations/{$organisationId}");
        Notification::assertSentTo($admin, OrganisationNotice::class);
        $this->get(route('organisations.show', $organisation))->assertSee('Vérifiée');

        $this->actingAs(User::factory()->create())->get(route('admin.organisations'))->assertForbidden();
    }

    public function test_a_verified_organisation_publishes_its_programmes_in_the_directory(): void
    {
        $admin = User::factory()->create();
        $organisationId = $this->incubator($admin);
        Organisation::find($organisationId)->forceFill(['verification_status' => 'verifiee', 'verified_at' => now()])->save();

        $programmeId = $this->postJson("/api/organisations/{$organisationId}/programmes", [
            'title' => 'Cohorte 2027 de l\'incubateur',
            'kind' => 'accompagnement',
            'summary' => 'Six mois d\'accompagnement, de formation et de mise en relation.',
            'stages' => ['idee', 'validation'],
            'deadline' => today()->addMonth()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.organisation', 'Incubateur de Thiès')
            ->assertJsonPath('data.organisation_id', $organisationId)
            ->json('data.id');

        $this->getJson('/api/opportunities')->assertJsonFragment(['id' => $programmeId]);
        $this->get(route('opportunities.show', $programmeId))->assertSee('Incubateur de Thiès')->assertSee(route('organisations.show', $organisationId), false);

        $this->putJson("/api/programmes/{$programmeId}", ['title' => 'Cohorte 2027', 'kind' => 'accompagnement', 'summary' => 'Six mois.'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Cohorte 2027');

        // Une autre organisation ne touche pas à ce programme, ni à ceux de l'équipe IDEE.
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/programmes/{$programmeId}")->assertForbidden();
        $teamOpportunity = Opportunity::create(['slug' => 'equipe', 'title' => 'Fiche IDEE', 'organisation' => 'DER/FJ', 'kind' => 'pret', 'summary' => 'Fiche de l\'équipe.']);
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/programmes/{$teamOpportunity->id}")->assertNotFound();
        $this->deleteJson("/api/programmes/{$programmeId}")->assertNoContent();
    }

    public function test_the_web_pages_create_and_manage_an_organisation(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);

        $this->get(route('organisations.create'))->assertOk()->assertSee('Créer une page d');
        $this->post(route('organisations.store'), [
            'name' => 'GIE des transformatrices de Mbour',
            'kind' => 'association',
            'description' => 'Groupement de femmes qui transforment le poisson et les céréales locales à Mbour.',
        ]);
        $organisation = Organisation::sole();
        $this->get(route('organisations.manage', $organisation))->assertOk()->assertSee('Demander la vérification')->assertDontSee('Publier un programme');
        $this->get(route('organisations.index'))->assertSee('GIE des transformatrices de Mbour');

        $this->actingAs(User::factory()->create())->get(route('organisations.manage', $organisation))->assertForbidden();
    }

    public function test_an_organisation_page_can_be_reported_and_its_presentation_removed(): void
    {
        $admin = User::factory()->create();
        $organisationId = $this->incubator($admin);
        Sanctum::actingAs(User::factory()->create());

        $reportId = $this->postJson('/api/reports', ['target_type' => 'organisation', 'target_id' => $organisationId, 'reason' => 'arnaque'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.reports.decide', $reportId), ['decision' => 'retrait', 'note' => 'Faux incubateur qui demande des frais de dossier.']);

        $this->assertSame('Présentation retirée par la modération.', Organisation::find($organisationId)->description);
    }
}
