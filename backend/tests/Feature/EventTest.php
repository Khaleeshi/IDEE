<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use App\Notifications\EventNotice;
use App\Services\Organisations\OrganisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedOrganiser(): User
    {
        $organiser = User::factory()->create();
        $organisation = app(OrganisationService::class)->create($organiser, [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès.',
        ]);
        $organisation->forceFill(['verification_status' => 'verifiee', 'verified_at' => now()])->save();

        return $organiser;
    }

    private function pitchSession(User $organiser, array $attributes = []): string
    {
        Sanctum::actingAs($organiser);

        return $this->postJson('/api/events', [
            'organisation_id' => Organisation::sole()->id,
            'title' => 'Pitch agro-transformation',
            'description' => 'Cinq projets présentent en sept minutes devant un jury de banques et de fonds.',
            'mode' => 'visio',
            'starts_at' => now()->addHours(20)->format('Y-m-d\TH:i'),
            'place' => 'https://meet.example.org/pitch',
            'pitch_slots' => 1,
            ...$attributes,
        ])->assertCreated()->json('data.id');
    }

    public function test_only_the_team_or_a_verified_organisation_organises_a_session(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/events', [])->assertForbidden();

        $organiser = $this->verifiedOrganiser();
        $id = $this->pitchSession($organiser);

        $this->getJson('/api/events')->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.place', null);
        $this->getJson("/api/events/{$id}")->assertJsonPath('data.place', 'https://meet.example.org/pitch');

        $this->actingAs($organiser)->get(route('events.create'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('events.create'))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('events.create'))->assertOk();
    }

    public function test_members_attend_or_apply_and_the_organiser_selects_within_the_slots(): void
    {
        Notification::fake();
        $organiser = $this->verifiedOrganiser();
        $id = $this->pitchSession($organiser);

        $listener = User::factory()->create();
        Sanctum::actingAs($listener);
        $this->getJson("/api/events/{$id}")->assertJsonPath('data.place', null);
        $this->postJson("/api/events/{$id}/registration")->assertCreated()->assertJsonPath('data.status.value', 'inscrit');
        $this->postJson("/api/events/{$id}/registration")->assertUnprocessable();
        $this->getJson("/api/events/{$id}")->assertJsonPath('data.place', 'https://meet.example.org/pitch');

        $first = Project::factory()->create();
        $second = Project::factory()->create();
        Sanctum::actingAs($first->owner);
        $this->postJson("/api/events/{$id}/registration", ['project_id' => $second->id])->assertUnprocessable();
        $firstId = $this->postJson("/api/events/{$id}/registration", ['project_id' => $first->id])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'candidat')
            ->json('data.id');
        $this->getJson("/api/events/{$id}")->assertJsonPath('data.place', null);
        Sanctum::actingAs($second->owner);
        $secondId = $this->postJson("/api/events/{$id}/registration", ['project_id' => $second->id])->json('data.id');

        Sanctum::actingAs($first->owner);
        $this->postJson("/api/event-registrations/{$firstId}/decide", ['accepted' => true])->assertForbidden();

        Sanctum::actingAs($organiser);
        $this->getJson("/api/events/{$id}/registrations")->assertOk()->assertJsonCount(3, 'data');
        $this->postJson("/api/event-registrations/{$firstId}/decide", ['accepted' => true])->assertOk()->assertJsonPath('data.status.value', 'retenu');
        $this->postJson("/api/event-registrations/{$secondId}/decide", ['accepted' => true])->assertUnprocessable();
        $this->postJson("/api/event-registrations/{$secondId}/decide", ['accepted' => false])->assertOk();
        Notification::assertSentTo([$first->owner, $second->owner], EventNotice::class);

        // Rappel la veille aux inscrits et aux projets retenus seulement.
        $this->artisan('idee:meeting-reminders')->expectsOutputToContain('2 rappel(s) de session de pitch');
        Notification::assertSentToTimes($listener, EventNotice::class, 1);
        Notification::assertSentToTimes($second->owner, EventNotice::class, 1);

        $this->actingAs($organiser)->get(route('events.show', $id))->assertSee('Candidatures')->assertSee($first->title);
    }

    public function test_cancelling_a_session_warns_the_registered_members(): void
    {
        Notification::fake();
        $organiser = $this->verifiedOrganiser();
        $id = $this->pitchSession($organiser, ['pitch_slots' => 0]);
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        $this->postJson("/api/events/{$id}/registration")->assertCreated();

        $this->actingAs($organiser)->post(route('events.cancel', $id))->assertRedirect(route('events.show', $id));

        Notification::assertSentTo($member, EventNotice::class, fn (EventNotice $notice) => str_contains($notice->text, 'annulée'));
        $this->assertTrue(Event::find($id)->isCancelled());
        $this->getJson('/api/events')->assertJsonCount(0, 'data');

        $this->actingAs($member)->post(route('events.register', $id))->assertSessionHasErrors('event');
    }

    public function test_the_sessions_pages_show_the_video_link_only_to_registered_members(): void
    {
        $organiser = $this->verifiedOrganiser();
        $id = $this->pitchSession($organiser);
        $visitor = User::factory()->create();

        $this->actingAs($visitor)->get(route('events.index'))->assertOk()->assertSee('Pitch agro-transformation');
        $this->get(route('events.show', $id))
            ->assertOk()
            ->assertSee('communiqué aux inscrits')
            ->assertDontSee('https://meet.example.org/pitch')
            ->assertSee('pas une offre de titres');

        $this->post(route('events.register', $id))->assertRedirect(route('events.show', $id));
        $this->get(route('events.show', $id))->assertSee('https://meet.example.org/pitch');
    }
}
