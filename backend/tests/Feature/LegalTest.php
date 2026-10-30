<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_legal_pages_are_public_and_linked_from_every_page(): void
    {
        config(['idee.company.rccm' => 'SN-DKR-2026-B-12345']);

        $this->get(route('home'))->assertSee(route('legal.notice'))->assertSee(route('legal.privacy'));
        $this->get(route('legal.notice'))->assertOk()->assertSee('SN-DKR-2026-B-12345')->assertSee('Texte provisoire');
        $this->get(route('legal.terms'))->assertOk()->assertSee('Aucun argent entre membres ne transite par IDEE');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('jamais affichés aux autres membres');
        $this->get(route('legal.charter'))->assertOk()->assertSee('ne demande jamais d');

        config(['idee.legal.validated' => true]);
        $this->get(route('legal.terms'))->assertDontSee('Texte provisoire');
    }

    public function test_a_new_version_of_the_texts_is_accepted_again(): void
    {
        $member = User::factory()->create();
        config(['idee.legal.version' => '2027-01']);

        $this->actingAs($member)->get(route('projects.mine'))->assertRedirect(route('terms.show'));
        $this->get(route('terms.show'))->assertSee('Nos textes ont changé');
        $this->get(route('legal.terms'))->assertOk();
        $this->get(route('account.export'))->assertOk();

        $this->post(route('terms.accept'), ['accept' => '1'])->assertRedirect(route('projects.mine'));
        $this->get(route('projects.mine'))->assertOk();
    }

    public function test_the_app_asks_for_the_acceptance_before_anything_else(): void
    {
        $member = User::factory()->create(['terms_version' => null, 'terms_accepted_at' => null]);
        Sanctum::actingAs($member);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.terms_accepted', false);
        $this->getJson('/api/me/projects')->assertForbidden()->assertJsonPath('terms_required', true);
        $this->postJson('/api/me/terms')->assertUnprocessable()->assertJsonValidationErrors('accept');
        $this->postJson('/api/me/terms', ['accept' => true])->assertOk()->assertJsonPath('data.terms_accepted', true);
        $this->getJson('/api/me/projects')->assertOk();
    }
}
