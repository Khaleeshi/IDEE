<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpertTest extends TestCase
{
    use RefreshDatabase;

    private function expert(array $attributes = [], bool $verified = true): User
    {
        $factory = User::factory();
        $user = ($verified ? $factory->identityVerified() : $factory)->create([
            'expertise' => 'Comptabilité des PME et dossiers de financement',
            'rate' => '15 000 FCFA l\'heure',
            ...$attributes,
        ]);
        $user->syncRoles([UserRole::Expert]);

        return $user;
    }

    public function test_only_verified_experts_are_listed_with_their_rates(): void
    {
        $this->seed(SectorSeeder::class);
        $verified = $this->expert(['name' => 'Fatou Ndiaye', 'region' => 'Thiès', 'sectors' => ['agro-transformation']]);
        $unverified = $this->expert(['name' => 'Moussa Faye'], verified: false);
        $other = $this->expert(['name' => 'Awa Sarr', 'region' => 'Dakar', 'expertise' => 'Statuts de SARL et droit OHADA', 'sectors' => ['numerique']]);
        User::factory()->identityVerified()->create(['name' => 'Ibou Diallo']);

        $names = collect($this->getJson('/api/experts')->assertOk()->json('data'))->pluck('name');
        $this->assertEqualsCanonicalizing(['Fatou Ndiaye', 'Awa Sarr'], $names->all());

        $this->getJson('/api/experts?q=OHADA')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->id);
        $this->getJson('/api/experts?sector=agro-transformation')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rate', '15 000 FCFA l\'heure')
            ->assertJsonMissingPath('data.0.phone');
        $this->getJson('/api/experts?region=Dakar')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Awa Sarr');

        $this->get(route('experts.index'))
            ->assertOk()
            ->assertSee('Fatou Ndiaye')
            ->assertSee('15 000 FCFA l', false)
            ->assertDontSee($unverified->name);
        $this->get(route('members.show', $verified))->assertSee('Tarif indicatif');
    }

    public function test_a_member_contacts_an_expert_about_one_of_their_projects(): void
    {
        $expert = $this->expert();
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $this->actingAs(User::factory()->create())
            ->get(route('members.contact', $expert))
            ->assertRedirect(route('members.show', $expert));

        $this->actingAs($owner)->get(route('members.show', $expert))->assertSee('Contacter à propos de mon projet');
        $this->get(route('members.contact', $expert))->assertOk()->assertSee($project->title);

        $this->post(route('contact-requests.store'), [
            'recipient_id' => $expert->id,
            'project_id' => $project->id,
            'message' => str_repeat('Bonjour, je cherche un comptable pour préparer mon dossier de financement. ', 2),
        ]);

        $request = ContactRequest::sole();
        $this->assertSame($expert->id, $request->recipient_id);
        $this->assertSame($project->id, $request->project_id);
    }

    public function test_experts_fill_in_their_services_and_rate_in_their_profile(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->patchJson('/api/me', ['expertise' => 'Formation à la vente en ligne', 'rate' => 'Sur devis'])->assertOk();

        $this->assertSame('Sur devis', $member->fresh()->rate);
        $this->actingAs($member)->get(route('profile.edit'))->assertSee('Votre tarif indicatif');
    }
}
