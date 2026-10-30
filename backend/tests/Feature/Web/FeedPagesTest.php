<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_shows_the_network_activity(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('projects.store'), [
            'title' => 'Atelier de couture solidaire',
            'summary' => 'Un atelier qui forme des jeunes femmes à la couture et vend des tenues sur commande.',
            'stage' => 'lancement',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nouveau projet')
            ->assertSee('Atelier de couture solidaire');
    }

    public function test_the_owner_keeps_a_progress_journal_and_shares_the_project(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('projects.store'), [
            'title' => 'Atelier de couture solidaire',
            'summary' => 'Un atelier qui forme des jeunes femmes à la couture et vend des tenues sur commande.',
            'stage' => 'lancement',
        ]);
        $project = $owner->projects()->firstOrFail();

        $this->post(route('projects.updates.store', $project), ['body' => 'Dix apprenties formées ce mois-ci.'])
            ->assertRedirect(route('projects.show', $project).'#journal');

        $this->get(route('projects.show', $project))
            ->assertSee('Journal de progression')
            ->assertSee('Dix apprenties formées ce mois-ci.')
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('<meta property="og:description" content="Un atelier qui forme', false);

        $this->get(route('home'))->assertSee('Mise à jour')->assertSee('Dix apprenties formées ce mois-ci.');
    }
}
