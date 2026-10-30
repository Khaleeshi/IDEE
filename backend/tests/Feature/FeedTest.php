<?php

namespace Tests\Feature;

use App\Enums\NeedStatus;
use App\Enums\NeedType;
use App\Enums\ProjectStatus;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Services\Needs\NeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    private function publishProject(User $owner, string $title = 'Farine de mil enrichie'): Project
    {
        Sanctum::actingAs($owner);

        $this->postJson('/api/projects', [
            'title' => $title,
            'summary' => 'Farines de mil et de maïs enrichies pour les boutiques de quartier de Kaolack.',
            'stage' => 'lancement',
        ])->assertCreated();

        return Project::where('title', $title)->firstOrFail();
    }

    public function test_projects_needs_updates_and_successes_feed_the_network(): void
    {
        $owner = User::factory()->create();
        $project = $this->publishProject($owner);

        $this->postJson("/api/projects/{$project->id}/needs", [
            'type' => 'clients',
            'title' => 'Trouver des distributeurs à Dakar',
            'description' => 'Je cherche des grossistes pour écouler 2 tonnes de farine par mois.',
        ])->assertCreated();

        $this->postJson("/api/projects/{$project->id}/updates", ['body' => 'Premier lot de 500 kg vendu en dix jours.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Premier lot de 500 kg vendu en dix jours.');

        app(NeedService::class)->close(Need::firstOrFail(), NeedStatus::Comble, 'Deux grossistes trouvés à Tilène');

        $this->getJson('/api/feed')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.type.value', 'reussite')
            ->assertJsonPath('data.0.need.outcome', 'Deux grossistes trouvés à Tilène')
            ->assertJsonPath('data.1.update.body', 'Premier lot de 500 kg vendu en dix jours.')
            ->assertJsonPath('data.2.type.label', 'Nouvel appel à action')
            ->assertJsonPath('data.3.type.value', 'projet_publie')
            ->assertJsonMissingPath('data.3.actor.phone');

        $this->getJson("/api/projects/{$project->id}/updates")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_only_the_owner_publishes_updates(): void
    {
        $project = $this->publishProject(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/projects/{$project->id}/updates", ['body' => 'Une mise à jour usurpée.'])->assertForbidden();
    }

    public function test_dormant_projects_leave_the_feed(): void
    {
        $project = $this->publishProject(User::factory()->create());
        $project->forceFill(['status' => ProjectStatus::EnSommeil])->save();

        $this->getJson('/api/feed')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_projects_can_be_searched_by_amount(): void
    {
        $small = Project::factory()->create(['title' => 'Petit atelier']);
        Need::factory()->for($small)->create(['type' => NeedType::Financement, 'amount_fcfa' => 500_000]);
        $large = Project::factory()->create(['title' => 'Usine de transformation']);
        Need::factory()->for($large)->create(['type' => NeedType::Financement, 'amount_fcfa' => 25_000_000]);

        $this->getJson('/api/projects?amount_min=1000000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Usine de transformation');

        $this->getJson('/api/projects?amount_max=1000000')->assertJsonPath('data.0.title', 'Petit atelier');
    }
}
