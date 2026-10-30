<?php

namespace Tests\Feature\Web;

use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_browse_and_search_the_projects(): void
    {
        $project = Project::factory()->create(['title' => 'Farine de mil enrichie', 'summary' => 'Céréales locales transformées.']);
        Need::factory()->for($project)->create();
        Project::factory()->create(['title' => 'Application de livraison', 'summary' => 'Livraison de repas en ville.']);

        $this->get('/')->assertOk()
            ->assertSee('Farine de mil enrichie')
            ->assertSee('Application de livraison')
            ->assertSee('Cherche : associé');

        $this->get('/?q=mil')->assertOk()
            ->assertSee('Farine de mil enrichie')
            ->assertDontSee('Application de livraison');
    }

    public function test_the_project_page_hides_private_details_from_visitors(): void
    {
        $project = Project::factory()->create(['details' => 'Fournisseur secret à Kédougou']);

        $this->get(route('projects.show', $project))->assertOk()
            ->assertSee($project->title)
            ->assertDontSee('Fournisseur secret à Kédougou')
            ->assertSee('Se connecter pour contacter');

        $this->actingAs($project->owner)
            ->get(route('projects.show', $project))->assertOk()
            ->assertSee('Fournisseur secret à Kédougou')
            ->assertSee('Score de préparation');
    }

    public function test_a_member_publishes_a_project_from_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('projects.create'))->assertOk()->assertSee('Publier un projet');

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'title' => 'Atelier de couture solidaire',
            'summary' => 'Un atelier qui forme des jeunes femmes à la couture et vend des tenues sur commande.',
            'region' => 'Dakar',
            'stage' => 'lancement',
        ]);

        $project = Project::firstOrFail();
        $response->assertRedirect(route('projects.show', $project));
        $this->get(route('projects.mine'))->assertSee('Atelier de couture solidaire');
    }

    public function test_guests_are_sent_to_the_login_page_to_publish(): void
    {
        $this->get(route('projects.create'))->assertRedirect(route('login'));
    }

    public function test_the_owner_publishes_and_closes_a_need(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($project->owner)
            ->from(route('projects.show', $project))
            ->post(route('needs.store', $project), [
                'type' => 'materiel',
                'title' => 'Deux machines à coudre',
                'description' => 'Deux machines à coudre industrielles, à acheter ou à louer.',
            ])->assertRedirect(route('projects.show', $project));

        $need = $project->needs()->firstOrFail();

        $this->post(route('needs.close', $need), ['status' => 'comble', 'outcome' => 'Machines louées'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($need->fresh()->isOpen());
    }

    public function test_another_member_offers_help_and_the_owner_accepts(): void
    {
        $need = Need::factory()->create();
        $helper = User::factory()->create(['name' => 'Fatou Sow']);

        $this->actingAs($helper)
            ->post(route('help-offers.store', $need), ['message' => 'Je peux vous prêter une machine pendant trois mois.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($need->project->owner)
            ->get(route('projects.show', $need->project))
            ->assertSee('Fatou Sow')
            ->assertSee('Je peux vous prêter une machine');

        $offer = $need->helpOffers()->firstOrFail();
        $this->post(route('help-offers.respond', $offer), ['accepted' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('acceptee', $offer->fresh()->status->value);
    }

    public function test_only_the_owner_can_edit_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('projects.edit', $project))
            ->assertForbidden()
            ->assertSee('Accès refusé');
    }

    public function test_an_unknown_page_shows_a_french_error(): void
    {
        $this->get('/projets/01JAAAAAAAAAAAAAAAAAAAAAAA')->assertNotFound()->assertSee('Page introuvable');
    }
}
