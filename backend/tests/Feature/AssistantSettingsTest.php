<?php

namespace Tests\Feature;

use App\Enums\AssistantStatus;
use App\Enums\NeedType;
use App\Models\AssistantDocument;
use App\Models\IdeaClarification;
use App\Models\Need;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Services\Network\RecommendationService;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_erases_their_exchanges_with_the_assistant_but_keeps_their_documents(): void
    {
        $project = Project::factory()->create();
        $owner = $project->owner;
        IdeaClarification::forceCreate(['project_id' => $project->id, 'status' => AssistantStatus::Pret, 'questions' => ['Qui sont vos clients ?']]);
        AssistantDocument::forceCreate(['kind' => 'preparation_rdv', 'user_id' => $owner->id, 'version' => 1, 'status' => AssistantStatus::Pret, 'content' => ['objective' => 'Convenir d\'un essai']]);
        $deck = AssistantDocument::forceCreate(['kind' => 'presentation', 'user_id' => $owner->id, 'project_id' => $project->id, 'version' => 1, 'status' => AssistantStatus::Pret, 'content' => ['slides' => []]]);
        $other = IdeaClarification::forceCreate(['project_id' => Project::factory()->create()->id, 'status' => AssistantStatus::Pret, 'questions' => []]);

        $this->actingAs($owner)->get(route('profile.edit'))->assertSee('Effacer mes échanges avec l');
        $this->delete(route('assistant.forget'))->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'Vos échanges avec l\'assistant ont été effacés.');

        $this->assertSame(0, IdeaClarification::where('project_id', $project->id)->count());
        $this->assertSame([$deck->id], AssistantDocument::pluck('id')->all());
        $this->assertNotNull($other->fresh(), 'Les échanges des autres membres ne sont pas touchés.');

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/me/assistant')->assertOk()->assertJsonPath('data.deleted', 0);
    }

    public function test_a_member_turns_off_personalised_recommendations(): void
    {
        $this->seed(SectorSeeder::class);
        $member = User::factory()->create(['sectors' => ['agro-transformation']]);
        $project = Project::factory()->create(['sector_id' => Sector::where('slug', 'agro-transformation')->value('id')]);
        Need::factory()->for($project)->create(['type' => NeedType::Financement, 'title' => 'Financer une décortiqueuse']);
        $this->assertNotEmpty(app(RecommendationService::class)->forUser($member));

        $this->actingAs($member)->put(route('profile.update'), [
            'name' => $member->name,
            'weekly_digest' => '1',
        ])->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertFalse($member->wantsPersonalizedRecommendations());
        $this->assertTrue($member->contact_preferences['weekly_digest']);
        $this->assertEmpty(app(RecommendationService::class)->forUser($member));
        $this->get(route('home'))->assertDontSee('Pour vous aujourd');

        Sanctum::actingAs($member);
        $this->patchJson('/api/me', ['contact_preferences' => ['personalized_recommendations' => true]])->assertOk();
        $this->assertTrue($member->fresh()->wantsPersonalizedRecommendations());
    }
}
