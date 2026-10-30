<?php

namespace Tests\Feature\Web;

use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRequestPagesTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Bonjour, je travaille dans la distribution à Dakar et je peux vous présenter à trois épiceries '
        .'intéressées par vos produits. Pouvons-nous en parler cette semaine ?';

    private function sendRequest(User $sender, Project $project): ContactRequest
    {
        $this->actingAs($sender)->post(route('contact-requests.store'), [
            'recipient_id' => $project->owner_id,
            'project_id' => $project->id,
            'message' => self::MESSAGE,
        ]);

        return ContactRequest::latest()->firstOrFail();
    }

    public function test_a_member_contacts_a_project_owner(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();

        $this->actingAs($sender)->get(route('contact-requests.create', $project))
            ->assertOk()
            ->assertSee('Demandes envoyées cette semaine')
            ->assertSee('0 / 5');

        $request = $this->sendRequest($sender, $project);

        $this->get(route('contact-requests.show', $request))->assertOk()->assertSee('Envoyée');
        $this->get(route('contact-requests.index', ['boite' => 'envoyees']))->assertSee($project->owner->name);
    }

    public function test_a_too_short_message_is_refused_with_an_explanation(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('contact-requests.create', $project))
            ->post(route('contact-requests.store'), [
                'recipient_id' => $project->owner_id,
                'project_id' => $project->id,
                'message' => 'Intéressé.',
            ])
            ->assertRedirect(route('contact-requests.create', $project))
            ->assertSessionHasErrors('message');
    }

    public function test_the_owner_cannot_contact_themselves(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($project->owner)
            ->get(route('contact-requests.create', $project))
            ->assertRedirect(route('projects.show', $project));
    }

    public function test_the_recipient_sees_the_badge_reads_and_answers(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();
        $request = $this->sendRequest($sender, $project);

        $this->actingAs($project->owner)->get('/')->assertSee('Demandes');
        $this->get(route('contact-requests.index'))->assertSee($sender->name);

        $this->get(route('contact-requests.show', $request))->assertOk()->assertSee('Votre réponse');
        $this->assertSame('lue', $request->fresh()->status->value);

        $this->post(route('contact-requests.respond', $request), ['status' => 'interesse'])
            ->assertRedirect(route('contact-requests.show', $request));

        $this->get(route('contact-requests.show', $request))
            ->assertSee('Vous êtes tous les deux intéressés')
            ->assertSee(PhoneNumber::format($sender->phone))
            ->assertSee('wa.me/'.ltrim($sender->phone, '+'), escape: false);
    }

    public function test_phone_numbers_stay_hidden_until_both_parties_agree(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->create();
        $request = $this->sendRequest($sender, $project);

        $this->actingAs($project->owner)
            ->get(route('contact-requests.show', $request))
            ->assertDontSee(PhoneNumber::format($sender->phone))
            ->assertDontSee(ltrim($sender->phone, '+'));

        $this->actingAs($sender)
            ->get(route('contact-requests.show', $request))
            ->assertDontSee(PhoneNumber::format($project->owner->phone))
            ->assertDontSee(ltrim($project->owner->phone, '+'));
    }

    public function test_a_stranger_cannot_open_a_request(): void
    {
        $project = Project::factory()->create();
        $request = $this->sendRequest(User::factory()->create(), $project);

        $this->actingAs(User::factory()->create())
            ->get(route('contact-requests.show', $request))
            ->assertForbidden();
    }
}
