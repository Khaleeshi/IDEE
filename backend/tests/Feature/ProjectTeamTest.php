<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Équipe du projet par fonction (EQ-01, EQ-02) et calendrier (EQ-03).
 */
class ProjectTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_idee_member_is_invited_to_a_function_and_joins_after_accepting(): void
    {
        Notification::fake();
        $project = Project::factory()->create(['title' => 'Savons au karité']);
        $cheffe = User::factory()->create(['name' => 'Aïssatou Diallo']);

        Sanctum::actingAs($project->owner);
        $id = $this->postJson("/api/projects/{$project->id}/team", ['identifier' => $cheffe->phone, 'role' => 'Chef de projet'])
            ->assertCreated()->assertJsonPath('data.invitation_status', 'invitee')->assertJsonPath('data.name', 'Aïssatou Diallo')->json('data.id');
        Notification::assertSentTo($cheffe, TeamInvitation::class);

        // Pas encore visible du public, pas encore d'accès aux tâches.
        Sanctum::actingAs(User::factory()->create());
        $this->assertSame([], $this->getJson("/api/projects/{$project->id}")->json('data.team'));
        $this->actingAs($cheffe)->get(route('tasks.index', $project))->assertForbidden();

        // Elle accepte : elle apparaît, et le tableau de tâches et le calendrier lui sont ouverts.
        $this->get(route('team.invitation', $id))->assertOk()->assertSee('Chef de projet');
        $this->post(route('team.invitation.answer', $id), ['accepted' => '1'])->assertRedirect();
        $this->get(route('tasks.index', $project))->assertOk();
        $this->get(route('projects.calendar', $project))->assertOk()->assertSee('Calendrier du projet');
        $this->get(route('projects.show', $project))->assertSee('Aïssatou Diallo');
        Notification::assertSentTo($project->owner, TeamInvitation::class);
    }

    public function test_invitations_are_checked(): void
    {
        $project = Project::factory()->create();
        $member = User::factory()->create();
        Sanctum::actingAs($project->owner);

        $this->postJson("/api/projects/{$project->id}/team", ['identifier' => '+221700000000', 'role' => 'Coach'])
            ->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->postJson("/api/projects/{$project->id}/team", ['identifier' => $project->owner->phone, 'role' => 'Coach'])
            ->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->postJson("/api/projects/{$project->id}/team", ['identifier' => $member->phone, 'role' => 'Coach'])->assertCreated();
        $this->postJson("/api/projects/{$project->id}/team", ['identifier' => $member->phone, 'role' => 'Mentor'])
            ->assertUnprocessable()->assertJsonValidationErrors('identifier');

        // Une personne sans compte s'ajoute toujours par son nom.
        $this->postJson("/api/projects/{$project->id}/team", ['name' => 'Moussa Fall', 'role' => 'Production'])->assertCreated()->assertJsonPath('data.invitation_status', null);

        // Seule la personne invitée répond.
        $invitation = ProjectMember::where('user_id', $member->id)->firstOrFail();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/team-members/{$invitation->id}/answer", ['accepted' => true])->assertNotFound();

        Sanctum::actingAs($member);
        $this->postJson("/api/team-members/{$invitation->id}/answer", ['accepted' => false])->assertOk();
        $this->assertNull($invitation->fresh());
    }

    public function test_the_calendar_gathers_dated_tasks_for_the_team(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs($project->owner);
        $this->postJson("/api/projects/{$project->id}/tasks", ['title' => 'Commander les emballages', 'due_on' => now()->addDays(5)->toDateString()])->assertCreated();
        $this->postJson("/api/projects/{$project->id}/tasks", ['title' => 'Sans date'])->assertCreated();

        $items = $this->getJson("/api/projects/{$project->id}/calendar")->assertOk()->json('data');
        $this->assertSame(['Commander les emballages'], array_column($items, 'title'));

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$project->id}/calendar")->assertForbidden();
    }
}
