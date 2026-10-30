<?php

namespace Tests\Feature;

use App\Enums\ContactRequestStatus;
use App\Enums\OrganisationRole;
use App\Models\ContactRequest;
use App\Models\OrganisationMember;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Documents\DocumentService;
use App\Services\Messaging\MessageService;
use App\Services\Organisations\OrganisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Bonjour, je fabrique des séchoirs solaires et votre projet de mangue séchée m\'intéresse beaucoup pour un essai.';

    /**
     * Un porteur et un partenaire qui échangent déjà dans une conversation ouverte.
     *
     * @return array{0: Project, 1: User, 2: ContactRequest}
     */
    private function conversation(): array
    {
        $project = Project::factory()->create(['title' => 'Mangue séchée de Casamance', 'details' => 'Marge de 45 %']);
        $partner = User::factory()->create(['name' => 'Awa Diop', 'email' => 'awa@example.org']);
        $requests = app(ContactRequestService::class);
        $conversation = $requests->send($partner, $project->owner, self::MESSAGE, $project);
        $requests->respond($conversation, $project->owner, ContactRequestStatus::Interesse);
        app(MessageService::class)->send($conversation->fresh(), $project->owner, 'Venez visiter l\'atelier mardi.');

        return [$project, $partner, $conversation->fresh()];
    }

    public function test_a_member_downloads_all_their_data_without_the_contacts_of_others(): void
    {
        [$project, $partner] = $this->conversation();
        $owner = $project->owner;

        $response = $this->actingAs($owner)->get(route('account.export'))->assertOk();
        $this->assertStringContainsString('attachment; filename="idee-mes-donnees-', $response->headers->get('Content-Disposition'));

        $data = $response->json();
        $this->assertSame($owner->phone, $data['profil']['telephone']);
        $this->assertSame('Mangue séchée de Casamance', $data['projets'][0]['title']);
        $this->assertSame('Marge de 45 %', $data['projets'][0]['details']);
        $this->assertSame('Awa Diop', $data['demandes_de_contact'][0]['interlocuteur']);
        $this->assertSame('moi', $data['demandes_de_contact'][0]['messages'][0]['de']);
        $this->assertStringNotContainsString($partner->phone, $response->getContent());
        $this->assertStringNotContainsString('awa@example.org', $response->getContent());

        Sanctum::actingAs($owner);
        $this->getJson('/api/me/export')->assertOk()->assertJsonPath('data.projets.0.title', 'Mangue séchée de Casamance');
    }

    public function test_deleting_the_account_erases_the_profile_and_projects_but_keeps_shared_conversations(): void
    {
        Storage::fake('local');
        [$project, $partner, $conversation] = $this->conversation();
        $owner = $project->owner;
        $phone = $owner->phone;
        $document = app(DocumentService::class)->upload($project, $owner, 'Comptes 2026', UploadedFile::fake()->create('comptes.pdf', 20, 'application/pdf'));
        Storage::disk('local')->assertExists($document->path);
        $waiting = app(ContactRequestService::class)->send(User::factory()->create(), $owner, self::MESSAGE, $project);
        $payment = Payment::forceCreate(['user_id' => $owner->id, 'offer' => 'pro', 'quantity' => 1, 'amount_fcfa' => 5000, 'method' => 'orange_money', 'status' => 'payee', 'receipt_number' => 'REC-2026-000001', 'paid_at' => now()]);

        $this->actingAs($owner)->get(route('account.delete'))->assertOk()->assertSee('Vos 1 projet(s)');
        $this->delete(route('account.destroy'), ['confirmation' => 'oui'])->assertSessionHasErrors('confirmation');
        $this->assertNotSoftDeleted($owner);

        $this->delete(route('account.destroy'), ['confirmation' => 'supprimer'])->assertRedirect(route('home'));
        $this->assertGuest();

        $owner = User::withTrashed()->find($owner->id);
        $this->assertSoftDeleted($owner);
        $this->assertSame('Membre supprimé', $owner->name);
        $this->assertNull($owner->phone);
        $this->assertSame(0, User::withTrashed()->where('phone', $phone)->count(), 'Le numéro peut servir à créer un nouveau compte.');
        $this->assertSame(0, Project::withTrashed()->whereKey($project->id)->count());
        Storage::disk('local')->assertMissing($document->path);
        $this->assertSame(ContactRequestStatus::Expiree, $waiting->fresh()->status);
        $this->assertNotNull($payment->fresh(), 'Les reçus restent pour la comptabilité.');

        // Le partenaire garde la conversation, mais ne peut plus écrire.
        $this->actingAs($partner)
            ->get(route('contact-requests.show', $conversation))
            ->assertOk()
            ->assertSee('Membre supprimé')
            ->assertSee('Venez visiter l');
        $this->post(route('messages.store', $conversation), ['body' => 'Toujours d\'accord pour mardi ?'])->assertSessionHasErrors('body');
        $this->get(route('members.show', $owner->id))->assertStatus(410)->assertSee('Ce membre a supprimé son compte');
    }

    public function test_the_mobile_app_deletes_the_account_and_revokes_its_access(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->deleteJson('/api/me')->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->deleteJson('/api/me', ['confirmation' => 'SUPPRIMER'])->assertNoContent();

        $this->assertSoftDeleted($member);
    }

    public function test_an_organisation_keeps_someone_to_manage_its_page(): void
    {
        $manager = User::factory()->create();
        $colleague = User::factory()->create();
        $organisation = app(OrganisationService::class)->create($manager, [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès.',
        ]);
        OrganisationMember::forceCreate(['organisation_id' => $organisation->id, 'user_id' => $colleague->id, 'role' => 'membre']);

        Sanctum::actingAs($manager);
        $this->deleteJson('/api/me', ['confirmation' => 'SUPPRIMER'])->assertNoContent();

        $this->assertSame([$colleague->id], $organisation->members()->pluck('user_id')->all());
        $this->assertSame(OrganisationRole::Administrateur, $organisation->members()->sole()->role);
    }
}
