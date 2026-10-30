<?php

namespace Tests\Feature\Web;

use App\Enums\NeedType;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_follows_a_project_from_its_page(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('projects.show', $project))->assertSee('Suivre ce projet');
        $this->post(route('follows.project', $project))->assertSessionHas('status');
        $this->get(route('projects.show', $project))->assertSee('✓ Suivi');
        $this->get(route('home', ['vue' => 'suivis']))->assertOk()->assertSee('Suivis');
    }

    public function test_the_home_page_recommends_needs_with_their_reasons(): void
    {
        $user = User::factory()->create(['region' => 'Kaolack']);
        $project = Project::factory()->create(['region' => 'Kaolack']);
        Need::factory()->for($project)->create(['type' => NeedType::Clients, 'title' => 'Trouver des distributeurs']);

        $this->actingAs($user)->get(route('home'))
            ->assertSee("Pour vous aujourd'hui", false)
            ->assertSee('Trouver des distributeurs')
            ->assertSee('Le projet est dans votre région : Kaolack.');
    }

    public function test_a_member_signals_someone_they_know(): void
    {
        $need = Need::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('help-offers.store', $need), [
                'kind' => 'mise_en_relation',
                'message' => 'Ma voisine tient une épicerie et cherche ce produit.',
            ])
            ->assertSessionHas('status', "Merci ! Le porteur du projet sait que vous connaissez quelqu'un qui peut l'aider.");

        $this->actingAs($need->project->owner)->get(route('projects.show', $need->project))
            ->assertSee('Je connais quelqu&#039;un', false)
            ->assertSee('Ma voisine tient une épicerie');
    }
}
