<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Projects\ReadinessScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_presents_the_team_which_counts_in_the_readiness_score(): void
    {
        $project = Project::factory()->create();
        app(ReadinessScore::class)->refresh($project);
        $before = $project->fresh()->readiness_score;
        Sanctum::actingAs($project->owner);

        $memberId = $this->postJson("/api/projects/{$project->id}/team", ['name' => 'Ibou Ndiaye', 'role' => 'Production'])
            ->assertCreated()
            ->json('data.id');

        $this->assertSame($before + 10, $project->fresh()->readiness_score);
        $this->getJson("/api/projects/{$project->id}")->assertJsonPath('data.team.0.role', 'Production');
        $this->get(route('projects.show', $project))->assertSee('Ibou Ndiaye')->assertSee('porteur du projet');

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/team-members/{$memberId}")->assertForbidden();

        Sanctum::actingAs($project->owner);
        $this->deleteJson("/api/team-members/{$memberId}")->assertNoContent();
        $this->assertSame($before, $project->fresh()->readiness_score);
    }

    public function test_the_team_size_is_capped(): void
    {
        config(['idee.max_team_members' => 1]);
        $project = Project::factory()->create();

        $this->actingAs($project->owner)->post(route('projects.team.store', $project), ['name' => 'Awa', 'role' => 'Ventes']);
        $this->post(route('projects.team.store', $project), ['name' => 'Modou', 'role' => 'Livraison'])->assertSessionHasErrors('name');
    }
}
