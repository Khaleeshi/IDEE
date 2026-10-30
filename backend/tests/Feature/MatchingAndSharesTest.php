<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\ProjectMatch;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\ProjectMatchNotice;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchingAndSharesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SectorSeeder::class);
    }

    private function mangoProject(array $attributes = []): Project
    {
        return Project::factory()->create([
            'sector_id' => Sector::firstWhere('slug', 'agro-transformation')->id,
            'region' => 'Ziguinchor',
            'title' => 'Mangue séchée solaire',
            'summary' => 'Séchage solaire de mangues de Casamance, vendues en sachets aux boutiques.',
            ...$attributes,
        ]);
    }

    public function test_similar_projects_are_suggested_only_between_owners_who_opted_in(): void
    {
        Notification::fake();
        $mine = $this->mangoProject();
        $other = $this->mangoProject(['title' => 'Séchoir à mangues de Bignona']);
        Sanctum::actingAs($mine->owner);

        // RG-12 : rien n'est proposé tant que les deux porteurs n'ont pas activé les rapprochements.
        $this->getJson("/api/projects/{$mine->id}/matches")->assertOk()->assertJsonCount(0, 'data.suggestions');
        $this->putJson("/api/projects/{$mine->id}/matching", ['open' => true])->assertOk()->assertJsonPath('data.open_to_matching', true);
        $this->getJson("/api/projects/{$mine->id}/matches")->assertJsonCount(0, 'data.suggestions');
        $this->postJson("/api/projects/{$mine->id}/matches", ['target_project_id' => $other->id])->assertUnprocessable();

        $other->forceFill(['open_to_matching' => true])->save();

        $this->getJson("/api/projects/{$mine->id}/matches")
            ->assertJsonCount(1, 'data.suggestions')
            ->assertJsonPath('data.suggestions.0.id', $other->id)
            ->assertJsonPath('data.suggestions.0.reasons.0', 'Même secteur : Agro-transformation')
            ->assertJsonMissingPath('data.suggestions.0.details');

        $matchId = $this->postJson("/api/projects/{$mine->id}/matches", ['target_project_id' => $other->id, 'message' => 'Achetons nos sachets ensemble.'])
            ->assertCreated()
            ->json('data.id');
        $this->postJson("/api/projects/{$mine->id}/matches", ['target_project_id' => $other->id])->assertUnprocessable();
        Notification::assertSentTo($other->owner, ProjectMatchNotice::class);

        // Seul le porteur du projet visé répond.
        $this->postJson("/api/project-matches/{$matchId}/respond", ['accepted' => true])->assertForbidden();

        Sanctum::actingAs($other->owner);
        $conversationId = $this->postJson("/api/project-matches/{$matchId}/respond", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'acceptee')
            ->json('data.contact_request_id');

        $conversation = ContactRequest::findOrFail($conversationId);
        $this->assertTrue($conversation->allowsMessages());
        $this->assertTrue($conversation->involves($mine->owner) && $conversation->involves($other->owner));
        Notification::assertSentTo($mine->owner, ProjectMatchNotice::class);

        // Une paire déjà rapprochée n'est plus suggérée.
        Sanctum::actingAs($mine->owner);
        $this->getJson("/api/projects/{$mine->id}/matches")->assertJsonCount(0, 'data.suggestions')->assertJsonCount(1, 'data.sent');
    }

    public function test_unrelated_projects_are_not_suggested(): void
    {
        $mine = $this->mangoProject(['open_to_matching' => true]);
        $this->mangoProject(['open_to_matching' => true, 'owner_id' => $mine->owner_id]);
        Project::factory()->create([
            'open_to_matching' => true,
            'sector_id' => Sector::firstWhere('slug', 'numerique')->id,
            'title' => 'Application de covoiturage',
            'summary' => 'Une application pour partager les trajets entre Thiès et Dakar.',
        ]);
        Sanctum::actingAs($mine->owner);

        $this->getJson("/api/projects/{$mine->id}/matches")->assertJsonCount(0, 'data.suggestions');
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$mine->id}/matches")->assertForbidden();
    }

    public function test_the_matching_page_lets_the_owner_opt_in_and_answer(): void
    {
        $mine = $this->mangoProject();
        $other = $this->mangoProject(['open_to_matching' => true, 'title' => 'Coopérative de mangues']);
        $this->actingAs($mine->owner);

        $this->get(route('projects.show', $mine))->assertSee('Projets proches du vôtre');
        $this->get(route('matches.index', $mine))->assertOk()->assertSee('Activer les rapprochements');
        $this->post(route('matches.toggle', $mine), ['open' => 1])->assertRedirect(route('matches.index', $mine));
        $this->get(route('matches.index', $mine))->assertSee('Coopérative de mangues')->assertSee('Proposer un rapprochement');
        $this->post(route('matches.store', $mine), ['target_project_id' => $other->id])->assertRedirect(route('matches.index', $mine));

        $this->actingAs($other->owner)->get(route('matches.index', $other))->assertSee('Propositions reçues')->assertSee('Mangue séchée solaire');
        $this->post(route('matches.respond', ProjectMatch::sole()), ['accepted' => 1])
            ->assertRedirect(route('contact-requests.show', ContactRequest::sole()));
        $this->actingAs(User::factory()->create())->get(route('matches.index', $other))->assertForbidden();
    }

    public function test_envisaged_shares_stay_within_100_percent_and_private(): void
    {
        $project = $this->mangoProject();
        Sanctum::actingAs($project->owner);

        $memberId = $this->postJson("/api/projects/{$project->id}/team", ['name' => 'Awa Diatta', 'role' => 'Production', 'share_percent' => 60])
            ->assertCreated()
            ->assertJsonPath('data.share_percent', 60)
            ->json('data.id');
        $this->postJson("/api/projects/{$project->id}/team", ['name' => 'Ibou Sané', 'role' => 'Ventes', 'share_percent' => 45])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('share_percent');
        $this->patchJson("/api/team-members/{$memberId}", ['share_percent' => 35])->assertOk()->assertJsonPath('data.share_percent', 35);
        $this->postJson("/api/projects/{$project->id}/team", ['name' => 'Ibou Sané', 'role' => 'Ventes', 'share_percent' => 45])->assertCreated();

        $this->getJson("/api/projects/{$project->id}")->assertJsonPath('data.team.0.share_percent', 35);
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('part envisagée 35 %');

        // Un visiteur voit l'équipe, pas les parts.
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$project->id}")->assertJsonMissingPath('data.team.0.share_percent');
        $this->actingAs(User::factory()->create())->get(route('projects.show', $project))
            ->assertSee('Awa Diatta')
            ->assertDontSee('part envisagée');
    }
}
