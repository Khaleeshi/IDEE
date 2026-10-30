<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function projectPayload(array $overrides = []): array
    {
        return [
            'title' => 'Marque de cosmétiques naturels',
            'summary' => 'Des soins pour la peau fabriqués à Thiès à partir de karité, vendus en ligne et en salon.',
            'details' => 'Coûts de production, fournisseurs identifiés, prix de vente visé.',
            'region' => 'Thiès',
            'stage' => 'validation',
            ...$overrides,
        ];
    }

    public function test_a_member_publishes_a_project_and_becomes_a_project_holder(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/projects', $this->projectPayload([
            'sector_id' => Sector::create(['slug' => 'cosmetique', 'name' => 'Cosmétique'])->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Marque de cosmétiques naturels')
            ->assertJsonPath('data.status.value', 'actif')
            ->assertJsonPath('data.is_owner', true);

        $project = Project::firstOrFail();
        $this->assertTrue($user->fresh()->hasRole(UserRole::Porteur));
        $this->assertCount(1, $project->versions);
        $this->assertSame(64, strlen($project->versions->first()->fingerprint));
        $this->assertGreaterThan(0, $project->readiness_score);
    }

    public function test_private_details_are_hidden_from_other_members(): void
    {
        $project = Project::factory()->create(['details' => 'Informations confidentielles']);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.details')
            ->assertJsonMissingPath('data.readiness_advice');

        Sanctum::actingAs($project->owner);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.details', 'Informations confidentielles')
            ->assertJsonStructure(['data' => ['readiness_advice']]);
    }

    public function test_the_member_profile_never_exposes_the_phone_number(): void
    {
        $project = Project::factory()->create();

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.owner.phone');
    }

    public function test_only_the_owner_can_update_a_project(): void
    {
        $project = Project::factory()->create();

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/projects/{$project->id}", ['title' => 'Autre titre'])->assertForbidden();

        Sanctum::actingAs($project->owner);
        $this->patchJson("/api/projects/{$project->id}", ['title' => 'Nouveau titre'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Nouveau titre');
    }

    public function test_changing_the_text_records_a_new_timestamped_version(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $id = $this->postJson('/api/projects', $this->projectPayload())->json('data.id');
        $this->patchJson("/api/projects/{$id}", ['summary' => 'Un nouveau résumé, plus précis, qui décrit les clients visés.']);
        $this->patchJson("/api/projects/{$id}", ['region' => 'Thiès']);

        $this->assertCount(2, Project::find($id)->versions);
    }

    public function test_dormant_projects_leave_the_search_results(): void
    {
        $active = Project::factory()->create(['title' => 'Projet actif']);
        $dormant = Project::factory()->create(['title' => 'Projet oublié', 'last_activity_at' => now()->subDays(91)]);

        $this->artisan('idee:dormant-projects')->assertSuccessful();

        $this->assertSame(ProjectStatus::EnSommeil, $dormant->fresh()->status);
        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_an_update_wakes_a_dormant_project_up(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::EnSommeil]);
        Sanctum::actingAs($project->owner);

        $this->patchJson("/api/projects/{$project->id}", ['stage' => 'prototype'])->assertOk();

        $this->assertSame(ProjectStatus::Actif, $project->fresh()->status);
    }

    public function test_projects_can_be_searched_by_keyword_and_region(): void
    {
        Project::factory()->create(['title' => 'Farine de mil enrichie', 'summary' => 'Céréales locales transformées.', 'region' => 'Kaolack']);
        Project::factory()->create(['title' => 'Application de livraison', 'summary' => 'Livraison de repas en ville.', 'region' => 'Dakar']);

        $this->getJson('/api/projects?q=mil')->assertJsonCount(1, 'data');
        $this->getJson('/api/projects?region=Dakar')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Application de livraison');
    }
}
