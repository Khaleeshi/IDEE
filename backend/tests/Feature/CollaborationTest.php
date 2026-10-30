<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\ContactRequestStatus;
use App\Models\Activity;
use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CollaborationNotice;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CollaborationTest extends TestCase
{
    use RefreshDatabase;

    private const INTRO = 'Bonjour, je fabrique des séchoirs solaires à Thiès et votre projet de mangue séchée m\'intéresse. Pouvons-nous en parler cette semaine ?';

    private function conversation(bool $accepted = true): ContactRequest
    {
        $project = Project::factory()->create();
        $service = app(ContactRequestService::class);
        $request = $service->send(User::factory()->create(), $project->owner, self::INTRO, $project);

        if ($accepted) {
            $service->respond($request, $project->owner, ContactRequestStatus::Interesse);
        }

        return $request->fresh();
    }

    public function test_a_next_step_binds_only_once_the_other_person_accepts_it(): void
    {
        Notification::fake();
        $conversation = $this->conversation();
        $owner = $conversation->recipient;
        Sanctum::actingAs($conversation->sender);

        $agreementId = $this->postJson("/api/contact-requests/{$conversation->id}/agreements", [
            'objective' => 'Envoyer le devis du séchoir',
            'due_on' => now()->addWeek()->toDateString(),
            'owner_id' => $conversation->sender_id,
        ])->assertCreated()
            ->assertJsonPath('data.status.value', 'proposee')
            ->json('data.id');

        Notification::assertSentTo($owner, CollaborationNotice::class);
        $this->postJson("/api/agreements/{$agreementId}/respond", ['accepted' => true])->assertForbidden();

        Sanctum::actingAs($owner);
        $this->postJson("/api/agreements/{$agreementId}/respond", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'acceptee');

        // L'étape acceptée rejoint le tableau de tâches du projet, partagé avec le partenaire (CO-02).
        $task = $conversation->project->tasks()->sole();
        $this->assertSame('Envoyer le devis du séchoir', $task->title);
        $this->assertSame($conversation->sender_id, $task->assignee_id);

        Sanctum::actingAs($conversation->sender);
        $this->getJson("/api/projects/{$conversation->project_id}/tasks")
            ->assertOk()
            ->assertJsonPath('data.0.agreement_id', $agreementId)
            ->assertJsonCount(2, 'members');

        // Terminer la tâche réalise l'étape convenue.
        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'fait'])->assertOk();
        $this->getJson("/api/contact-requests/{$conversation->id}/collaboration")
            ->assertOk()
            ->assertJsonPath('data.agreements.0.status.value', 'realisee');
    }

    public function test_next_steps_need_an_open_conversation_and_a_responsible_from_it(): void
    {
        $pending = $this->conversation(accepted: false);
        Sanctum::actingAs($pending->sender);

        $this->postJson("/api/contact-requests/{$pending->id}/agreements", [
            'objective' => 'Envoyer le devis',
            'due_on' => now()->addDay()->toDateString(),
            'owner_id' => $pending->sender_id,
        ])->assertForbidden();

        $conversation = $this->conversation();
        Sanctum::actingAs($conversation->sender);

        $this->postJson("/api/contact-requests/{$conversation->id}/agreements", [
            'objective' => 'Envoyer le devis',
            'due_on' => now()->subDay()->toDateString(),
            'owner_id' => User::factory()->create()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('due_on');

        $this->postJson("/api/contact-requests/{$conversation->id}/agreements", [
            'objective' => 'Envoyer le devis',
            'due_on' => now()->addDay()->toDateString(),
            'owner_id' => User::factory()->create()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('owner_id');
    }

    public function test_the_task_board_is_reserved_to_the_owner_and_partners(): void
    {
        $conversation = $this->conversation();
        $project = $conversation->project;

        Sanctum::actingAs($conversation->sender);
        $this->getJson("/api/projects/{$project->id}/tasks")->assertForbidden();

        Sanctum::actingAs($project->owner);
        $taskId = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Demander trois devis d\'emballage',
            'due_on' => now()->addDays(3)->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Livrer à Thiès',
            'assignee_id' => $conversation->sender_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');

        $this->patchJson("/api/tasks/{$taskId}", ['status' => 'en_cours'])->assertOk()->assertJsonPath('data.status.label', 'En cours');

        $this->get(route('tasks.index', $project))
            ->assertOk()
            ->assertSee('Tableau de tâches')
            ->assertSee('Demander trois devis');

        $this->deleteJson("/api/tasks/{$taskId}")->assertNoContent();
    }

    public function test_meetings_are_confirmed_by_the_other_person_and_reminded_the_day_before(): void
    {
        Notification::fake();
        $conversation = $this->conversation();
        Sanctum::actingAs($conversation->recipient);

        $this->postJson("/api/contact-requests/{$conversation->id}/meetings", [
            'starts_at' => now()->addHours(20)->format('Y-m-d\TH:i'),
            'mode' => 'presentiel',
        ])->assertUnprocessable()->assertJsonValidationErrors('place');

        $meetingId = $this->postJson("/api/contact-requests/{$conversation->id}/meetings", [
            'starts_at' => now()->addHours(20)->format('Y-m-d\TH:i'),
            'mode' => 'visio',
            'place' => 'https://meet.example.org/idee',
            'agenda' => 'Volumes, prix, délais de livraison',
        ])->assertCreated()->assertJsonPath('data.mode.label', 'En visio')->json('data.id');

        Notification::assertSentTo($conversation->sender, CollaborationNotice::class);
        $this->postJson("/api/meetings/{$meetingId}/respond", ['accepted' => true])->assertForbidden();

        Sanctum::actingAs($conversation->sender);
        $this->postJson("/api/meetings/{$meetingId}/respond", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'confirmee');

        $this->artisan('idee:meeting-reminders')->expectsOutputToContain('1 rendez-vous rappelé(s).')->assertSuccessful();
        Notification::assertSentToTimes($conversation->recipient, CollaborationNotice::class, 2);
        Notification::assertSentToTimes($conversation->sender, CollaborationNotice::class, 2);

        // Le rappel ne part qu'une fois.
        $this->artisan('idee:meeting-reminders')->expectsOutputToContain('0 rendez-vous rappelé(s).');

        $this->postJson("/api/meetings/{$meetingId}/cancel")->assertOk()->assertJsonPath('data.status.value', 'annulee');
    }

    public function test_a_collaboration_is_published_only_after_the_partner_confirms_it(): void
    {
        Notification::fake();
        $conversation = $this->conversation();
        $project = $conversation->project;
        Sanctum::actingAs($project->owner);

        $collaborationId = $this->postJson("/api/contact-requests/{$conversation->id}/collaborations", [
            'summary' => 'Premier lot de 200 kg séché avec le nouveau séchoir',
        ])->assertCreated()->assertJsonPath('data.status.value', 'en_attente')->json('data.id');

        // Rien n'est publié tant que le partenaire n'a pas confirmé (FC-06).
        $this->getJson("/api/members/{$project->owner_id}/collaborations")->assertOk()->assertJsonCount(0, 'data');
        $this->assertFalse(Activity::where('type', ActivityType::Reussite)->exists());
        $this->postJson("/api/collaborations/{$collaborationId}/respond", ['accepted' => true])->assertForbidden();
        $this->postJson("/api/contact-requests/{$conversation->id}/collaborations", [
            'summary' => 'Une deuxième déclaration en même temps',
        ])->assertUnprocessable();

        Sanctum::actingAs($conversation->sender);
        $this->postJson("/api/collaborations/{$collaborationId}/respond", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'confirmee');

        $this->getJson("/api/members/{$conversation->sender_id}/collaborations")
            ->assertOk()
            ->assertJsonPath('data.0.summary', 'Premier lot de 200 kg séché avec le nouveau séchoir')
            ->assertJsonPath('data.0.project.id', $project->id)
            ->assertJsonMissingPath('data.0.partner.phone');

        $this->getJson('/api/feed')
            ->assertOk()
            ->assertJsonPath('data.0.type.value', 'reussite')
            ->assertJsonPath('data.0.collaboration.id', $collaborationId);

        // Le partenaire a désormais accès au tableau de tâches du projet.
        $this->getJson("/api/projects/{$project->id}/tasks")->assertOk();

        $this->get(route('members.show', $conversation->sender))->assertSee('Collaborations confirmées');
        $this->get(route('projects.show', $project))->assertSee('Réussites confirmées');
    }

    public function test_the_conversation_page_offers_the_collaboration_tools(): void
    {
        $conversation = $this->conversation();
        $this->actingAs($conversation->sender);

        $this->get(route('contact-requests.show', $conversation))
            ->assertOk()
            ->assertSee('Avancer ensemble')
            ->assertSee('Proposer une prochaine étape')
            ->assertSee('Proposer un rendez-vous');

        $this->post(route('agreements.store', $conversation), [
            'objective' => 'Visiter l\'atelier de séchage',
            'due_on' => now()->addDays(2)->toDateString(),
            'owner_id' => $conversation->recipient_id,
        ])->assertRedirect(route('contact-requests.show', $conversation).'#collaboration');

        $this->actingAs($conversation->recipient)
            ->get(route('contact-requests.show', $conversation))
            ->assertSee('Visiter l', false)
            ->assertSee('Accepter');
    }
}
