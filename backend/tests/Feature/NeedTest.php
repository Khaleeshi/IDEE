<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NeedTest extends TestCase
{
    use RefreshDatabase;

    private function needPayload(array $overrides = []): array
    {
        return [
            'type' => 'financement',
            'title' => 'Financer le premier lot',
            'description' => 'Achat de matières premières et d\'emballages pour produire 1 000 pots.',
            'amount_fcfa' => 5_000_000,
            ...$overrides,
        ];
    }

    public function test_the_owner_publishes_a_call_to_action(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs($project->owner);

        $this->postJson("/api/projects/{$project->id}/needs", $this->needPayload())
            ->assertCreated()
            ->assertJsonPath('data.type.label', 'Financement')
            ->assertJsonPath('data.status.value', 'ouvert')
            ->assertJsonPath('data.amount_fcfa', 5_000_000);
    }

    public function test_another_member_cannot_publish_a_need_on_someone_elses_project(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/projects/{$project->id}/needs", $this->needPayload())->assertForbidden();
    }

    public function test_a_project_has_at_most_ten_open_needs(): void
    {
        $project = Project::factory()->create();
        Need::factory()->count(10)->for($project)->create();
        Sanctum::actingAs($project->owner);

        $this->postJson("/api/projects/{$project->id}/needs", $this->needPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('need');
    }

    public function test_a_closed_need_frees_a_slot(): void
    {
        $project = Project::factory()->create();
        $needs = Need::factory()->count(10)->for($project)->create();
        Sanctum::actingAs($project->owner);

        $this->postJson("/api/needs/{$needs->first()->id}/close", ['status' => 'comble', 'outcome' => 'Associé trouvé'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'comble');

        $this->postJson("/api/projects/{$project->id}/needs", $this->needPayload())->assertCreated();
    }

    public function test_a_member_offers_help_once_and_the_owner_accepts(): void
    {
        $need = Need::factory()->create();
        $helper = User::factory()->create();

        Sanctum::actingAs($helper);
        $offerId = $this->postJson("/api/needs/{$need->id}/help-offers", [
            'message' => 'Je suis développeuse et je peux créer votre boutique en ligne.',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/needs/{$need->id}/help-offers", [
            'message' => 'Je propose à nouveau mon aide pour ce besoin.',
        ])->assertUnprocessable();

        $this->postJson("/api/help-offers/{$offerId}/respond", ['accepted' => true])->assertForbidden();

        Sanctum::actingAs($need->project->owner);
        $this->getJson("/api/needs/{$need->id}/help-offers")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/help-offers/{$offerId}/respond", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'acceptee');
    }

    public function test_the_owner_cannot_offer_help_on_their_own_need(): void
    {
        $need = Need::factory()->create();
        Sanctum::actingAs($need->project->owner);

        $this->postJson("/api/needs/{$need->id}/help-offers", [
            'message' => 'Je propose mon aide pour mon propre besoin.',
        ])->assertUnprocessable();
    }
}
