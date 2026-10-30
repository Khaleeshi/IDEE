<?php

namespace Tests\Feature;

use App\Enums\NeedType;
use App\Enums\UserRole;
use App\Models\Need;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Services\Network\RecommendationService;
use App\Services\Projects\ProjectService;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_follows_a_project_a_person_and_a_sector(): void
    {
        $this->seed(SectorSeeder::class);
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/follows/projects/{$project->id}")->assertOk()->assertJson(['following' => true]);
        $this->postJson("/api/follows/members/{$project->owner_id}")->assertJson(['following' => true]);
        $this->postJson('/api/follows/sectors/agriculture')->assertJson(['following' => true]);
        $this->postJson("/api/follows/projects/{$project->id}")->assertJson(['following' => false]);

        $this->postJson("/api/follows/members/{$user->id}")->assertUnprocessable()->assertJsonValidationErrors('follow');
    }

    public function test_the_followed_feed_only_shows_what_the_member_follows(): void
    {
        $this->seed(SectorSeeder::class);
        $projects = app(ProjectService::class);
        $followedOwner = User::factory()->create();
        $followed = $projects->create($followedOwner, ['title' => 'Projet suivi', 'summary' => 'Un projet que le membre suit de près.', 'stage' => 'idee']);
        $inSector = $projects->create(User::factory()->create(), [
            'title' => 'Projet du secteur suivi',
            'summary' => 'Un projet du secteur agricole.',
            'stage' => 'idee',
            'sector_id' => Sector::where('slug', 'agriculture')->value('id'),
        ]);
        $projects->create(User::factory()->create(), ['title' => 'Projet ignoré', 'summary' => 'Un projet sans lien avec le membre.', 'stage' => 'idee']);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/follows/members/{$followedOwner->id}");
        $this->postJson('/api/follows/sectors/agriculture');

        $this->getJson('/api/me/feed')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => $followed->title])
            ->assertJsonFragment(['title' => $inSector->title])
            ->assertJsonMissing(['title' => 'Projet ignoré']);
    }

    public function test_a_member_signals_someone_they_know_for_a_need(): void
    {
        $need = Need::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/needs/{$need->id}/help-offers", [
            'kind' => 'mise_en_relation',
            'message' => 'Mon cousin est grossiste à Tilène, je peux vous le présenter.',
        ])->assertCreated()->assertJsonPath('data.kind.label', "Je connais quelqu'un");

        $this->postJson("/api/needs/{$need->id}/help-offers", [
            'message' => 'Je peux aussi vous aider moi-même à démarcher les boutiques.',
        ])->assertCreated()->assertJsonPath('data.kind.value', 'aide');

        $this->postJson("/api/needs/{$need->id}/help-offers", [
            'kind' => 'mise_en_relation',
            'message' => 'Je connais encore une autre personne intéressée.',
        ])->assertUnprocessable();
    }

    public function test_recommendations_match_the_profile_and_explain_why(): void
    {
        $this->seed(SectorSeeder::class);
        $investor = User::factory()->create([
            'region' => 'Thiès',
            'skills' => ['Comptabilité'],
            'sectors' => ['agro-transformation'],
        ]);
        $investor->syncRoles([UserRole::Investisseur]);

        $match = Project::factory()->create([
            'region' => 'Thiès',
            'sector_id' => Sector::where('slug', 'agro-transformation')->value('id'),
        ]);
        Need::factory()->for($match)->create(['type' => NeedType::Financement, 'title' => 'Financer une décortiqueuse']);
        Need::factory()->for($match)->create(['type' => NeedType::Competence, 'title' => 'Tenir la comptabilité du projet']);
        Need::factory()->for(Project::factory()->create(['region' => 'Ziguinchor']))->create(['type' => NeedType::Local, 'title' => 'Trouver un local']);
        Need::factory()->for(Project::factory()->for($investor, 'owner')->create(['region' => 'Thiès']))->create(['type' => NeedType::Financement]);

        $picks = app(RecommendationService::class)->compute($investor);

        $this->assertSame(['Financer une décortiqueuse', 'Tenir la comptabilité du projet'], $picks->pluck('need.title')->all());
        $this->assertSame([
            'Vous êtes investisseur et ce projet cherche un financement.',
            'Secteur de votre profil : Agro-transformation.',
            'Le projet est dans votre région : Thiès.',
        ], $picks[0]['reasons']);
        $this->assertContains('Il cherche « Comptabilité », une de vos compétences.', $picks[1]['reasons']);

        Sanctum::actingAs($investor);
        $this->getJson('/api/me/recommendations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.need.title', 'Financer une décortiqueuse')
            ->assertJsonPath('data.0.project.id', $match->id);
    }
}
