<?php

namespace Tests\Feature;

use App\Models\GroupPost;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SectorSeeder::class);
    }

    public function test_groups_exist_for_every_sector_and_region_with_their_projects(): void
    {
        $sector = Sector::firstWhere('slug', 'peche-et-aquaculture');
        Project::factory()->create(['sector_id' => $sector->id, 'region' => 'Thiès', 'title' => 'Poisson fumé de Kayar']);

        $this->getJson('/api/groups')
            ->assertOk()
            ->assertJsonCount(Sector::count(), 'data.secteur')
            ->assertJsonCount(14, 'data.region');

        $this->getJson('/api/groups/secteur/peche-et-aquaculture')
            ->assertOk()
            ->assertJsonPath('data.name', 'Pêche et aquaculture')
            ->assertJsonPath('data.projects.0.title', 'Poisson fumé de Kayar');
        $this->getJson('/api/groups/region/thies')->assertOk()->assertJsonPath('data.name', 'Thiès')->assertJsonCount(1, 'data.projects');
        $this->getJson('/api/groups/region/paris')->assertNotFound();
        $this->getJson('/api/groups/ville/dakar')->assertNotFound();

        $this->get(route('groups.index'))->assertOk()->assertSee('Pêche et aquaculture')->assertSee('Ziguinchor');
        $this->get(route('groups.show', ['region', 'thies']))->assertOk()->assertSee('Poisson fumé de Kayar');
    }

    public function test_members_discuss_in_a_group_within_a_daily_limit(): void
    {
        config(['idee.groups.posts_per_day' => 2]);
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $postId = $this->postJson('/api/groups/region/thies/posts', ['body' => 'Qui connaît un fournisseur d\'emballages à Thiès ?'])
            ->assertCreated()
            ->assertJsonPath('data.author.name', $member->name)
            ->json('data.id');
        $this->postJson('/api/groups/region/thies/posts', ['body' => 'Et un transporteur frigorifique ?'])->assertCreated();
        $this->postJson('/api/groups/region/thies/posts', ['body' => 'Encore une question.'])->assertUnprocessable();

        $this->getJson('/api/groups/region/thies')->assertJsonCount(2, 'data.posts');

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/group-posts/{$postId}")->assertForbidden();

        $this->actingAs($member)->get(route('groups.show', ['region', 'thies']))->assertSee('fournisseur d', false)->assertSee('Supprimer');
        $this->delete(route('groups.posts.destroy', $postId))->assertRedirect(route('groups.show', ['region', 'thies']).'#discussion');
        $this->assertNull(GroupPost::find($postId));
    }

    public function test_a_reported_group_message_can_be_removed_by_moderation(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);
        $postId = $this->postJson('/api/groups/secteur/commerce/posts', ['body' => 'Envoyez 10 000 FCFA pour recevoir la liste des acheteurs.'])->json('data.id');

        $reader = User::factory()->create();
        Sanctum::actingAs($reader);
        $reportId = $this->postJson('/api/reports', ['target_type' => 'publication', 'target_id' => $postId, 'reason' => 'arnaque'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.reports.decide', $reportId), ['decision' => 'retrait', 'note' => 'Demande d\'argent.']);

        $this->getJson('/api/groups/secteur/commerce')->assertJsonCount(0, 'data.posts');
        $this->assertNotNull(GroupPost::find($postId)->removed_at);
    }
}
