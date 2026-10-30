<?php

namespace Tests\Feature;

use App\Enums\NeedType;
use App\Models\Need;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundraisingNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_seeking_funds_carries_the_notice_and_is_not_indexed(): void
    {
        $project = Project::factory()->create();
        Need::factory()->for($project)->create(['type' => NeedType::Financement, 'amount_fcfa' => 2500000, 'counterpart' => '10 % des parts']);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('Ceci n\'est pas une offre au public');

        $this->getJson("/api/projects/{$project->id}")->assertOk()->assertJsonPath('data.open_needs.0.notice', NeedType::FUNDRAISING_NOTICE);
    }

    public function test_other_projects_stay_visible_to_search_engines(): void
    {
        $project = Project::factory()->create();
        Need::factory()->for($project)->create(['type' => NeedType::Competence]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertDontSee('Ceci n\'est pas une offre au public');
    }
}
