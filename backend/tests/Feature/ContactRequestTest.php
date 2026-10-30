<?php

namespace Tests\Feature;

use App\Enums\ContactRequestStatus;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ContactRequestReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Bonjour, je suis investisseur dans la cosmétique et votre projet m\'intéresse. '
        .'Pouvez-vous me présenter vos coûts de production et vos premiers clients ?';

    private function send(User $sender, User $recipient, Project $project, string $message = self::MESSAGE)
    {
        Sanctum::actingAs($sender);

        return $this->postJson('/api/contact-requests', [
            'recipient_id' => $recipient->id,
            'project_id' => $project->id,
            'message' => $message,
        ]);
    }

    public function test_a_member_sends_a_contact_request_about_a_project(): void
    {
        $project = Project::factory()->create();
        $investor = User::factory()->identityVerified()->create();

        $this->send($investor, $project->owner, $project)
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'envoyee')
            ->assertJsonPath('data.project.id', $project->id);

        $request = ContactRequest::firstOrFail();
        $this->assertTrue($request->expires_at->between(now()->addDays(14)->subMinute(), now()->addDays(14)->addMinute()));
        $this->assertCount(1, $request->events);
    }

    public function test_a_request_must_target_a_project_or_a_need(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/contact-requests', [
            'recipient_id' => User::factory()->create()->id,
            'message' => self::MESSAGE,
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
    }

    public function test_a_request_targeting_a_need_is_linked_to_its_project(): void
    {
        $need = Need::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/contact-requests', [
            'recipient_id' => $need->project->owner_id,
            'need_id' => $need->id,
            'message' => self::MESSAGE,
        ])->assertCreated()
            ->assertJsonPath('data.project.id', $need->project_id)
            ->assertJsonPath('data.need.id', $need->id);
    }

    public function test_the_message_needs_at_least_one_hundred_characters(): void
    {
        $project = Project::factory()->create();

        $this->send(User::factory()->create(), $project->owner, $project, 'Bonjour, intéressé.')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_an_unverified_member_sends_at_most_five_requests_per_week(): void
    {
        $sender = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $project = Project::factory()->create();
            $this->send($sender, $project->owner, $project)->assertCreated();
        }

        $project = Project::factory()->create();
        $this->send($sender, $project->owner, $project)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quota');

        $this->travel(8)->days();
        $this->send($sender, $project->owner, $project)->assertCreated();
    }

    public function test_a_verified_member_has_a_larger_quota(): void
    {
        $sender = User::factory()->identityVerified()->create();

        foreach (range(1, 6) as $i) {
            $project = Project::factory()->create();
            $this->send($sender, $project->owner, $project)->assertCreated();
        }

        $this->assertSame(20, $sender->weeklyContactQuota());
    }

    public function test_a_pending_request_cannot_be_sent_twice(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();

        $this->send($sender, $project->owner, $project)->assertCreated();
        $this->send($sender, $project->owner, $project)->assertUnprocessable();
    }

    public function test_opening_the_request_marks_it_as_read(): void
    {
        $project = Project::factory()->create();
        $id = $this->send(User::factory()->create(), $project->owner, $project)->json('data.id');

        Sanctum::actingAs($project->owner);
        $this->getJson("/api/contact-requests/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'lue');
    }

    public function test_a_stranger_cannot_see_a_request(): void
    {
        $project = Project::factory()->create();
        $id = $this->send(User::factory()->create(), $project->owner, $project)->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/contact-requests/{$id}")->assertForbidden();
    }

    public function test_a_refusal_requires_a_reason(): void
    {
        $project = Project::factory()->create();
        $id = $this->send(User::factory()->create(), $project->owner, $project)->json('data.id');

        Sanctum::actingAs($project->owner);
        $this->postJson("/api/contact-requests/{$id}/respond", ['status' => 'refusee'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refusal_reason');

        $this->postJson("/api/contact-requests/{$id}/respond", [
            'status' => 'refusee',
            'refusal_reason' => 'Mon projet ne cherche pas encore d\'investisseur.',
        ])->assertOk()
            ->assertJsonPath('data.status.value', 'refusee')
            ->assertJsonPath('data.refusal_reason', 'Mon projet ne cherche pas encore d\'investisseur.');
    }

    public function test_only_the_recipient_can_answer(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();
        $id = $this->send($sender, $project->owner, $project)->json('data.id');

        $this->postJson("/api/contact-requests/{$id}/respond", ['status' => 'interesse'])->assertForbidden();
    }

    public function test_requests_without_answer_get_a_reminder_then_expire(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $id = $this->send(User::factory()->create(), $project->owner, $project)->json('data.id');

        $this->travel(7)->days();
        $this->artisan('idee:contact-requests')->assertSuccessful();
        Notification::assertSentTo($project->owner, ContactRequestReminder::class);
        $this->assertSame(ContactRequestStatus::Envoyee, ContactRequest::find($id)->status);

        $this->travel(7)->days();
        $this->artisan('idee:contact-requests')->assertSuccessful();
        $this->assertSame(ContactRequestStatus::Expiree, ContactRequest::find($id)->status);

        Sanctum::actingAs($project->owner);
        $this->postJson("/api/contact-requests/{$id}/respond", ['status' => 'interesse'])->assertUnprocessable();
    }

    public function test_the_reactivity_indicator_counts_answers_and_silences(): void
    {
        $project = Project::factory()->create();
        $owner = $project->owner;

        $answered = $this->send(User::factory()->create(), $owner, $project)->json('data.id');
        $this->send(User::factory()->create(), $owner, $project);

        $this->travel(3)->hours();
        Sanctum::actingAs($owner);
        $this->postJson("/api/contact-requests/{$answered}/respond", ['status' => 'interesse'])->assertOk();

        $this->travel(15)->days();
        $this->artisan('idee:contact-requests');

        $this->getJson("/api/members/{$owner->id}")
            ->assertOk()
            ->assertJsonPath('reactivity.response_rate', 50)
            ->assertJsonPath('reactivity.median_response_hours', 3)
            ->assertJsonPath('reactivity.requests_considered', 2);
    }

    public function test_members_list_their_received_and_sent_requests(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();
        $this->send($sender, $project->owner, $project);

        $this->getJson('/api/contact-requests?box=sent')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/contact-requests')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($project->owner);
        $this->getJson('/api/contact-requests')->assertOk()->assertJsonCount(1, 'data');
    }
}
