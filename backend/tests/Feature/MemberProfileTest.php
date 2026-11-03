<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\MemberProfile;
use App\Models\Profession;
use App\Models\User;
use App\Services\Members\MemberProfileService;
use Database\Seeders\ProfessionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Profils multiples et référentiel des métiers (PM-01 à PM-05).
 */
class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProfessionSeeder::class);
    }

    private function profession(string $slug): Profession
    {
        return Profession::where('slug', $slug)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function addProfile(User $user, string $slug, array $attributes = []): MemberProfile
    {
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/me/profiles', [
            'profession_id' => $this->profession($slug)->id,
            'offers_volunteer' => true,
            ...$attributes,
        ])->assertCreated()->json('data.id');

        return MemberProfile::findOrFail($id);
    }

    public function test_the_catalogue_lists_active_professions_by_category(): void
    {
        $this->profession('scrum-master')->update(['active' => false]);

        $categories = collect($this->getJson('/api/professions')->assertOk()->json('data'));
        $names = $categories->pluck('professions')->flatten(1)->pluck('name');

        $this->assertTrue($categories->pluck('name')->contains('Coaching et mentorat'));
        $this->assertTrue($names->contains('Coach de projet'));
        $this->assertTrue($names->contains('Chef de projet'));
        $this->assertFalse($names->contains('Scrum Master'));
    }

    public function test_one_account_holds_several_profiles_and_the_first_is_primary(): void
    {
        $aissatou = User::factory()->create(['name' => 'Aïssatou']);

        $coach = $this->addProfile($aissatou, 'coach-de-projet', ['offers_paid' => true, 'rate' => '25 000 FCFA la séance']);
        $chef = $this->addProfile($aissatou, 'chef-de-projet');
        $this->addProfile($aissatou, 'formateur-professionnel', ['offers_mentoring' => true]);

        $this->assertTrue($coach->fresh()->is_primary);
        $this->assertFalse($chef->fresh()->is_primary);

        $profiles = $this->getJson('/api/me')->assertOk()->json('data.profiles');
        $this->assertCount(3, $profiles);
        $this->assertSame('Coach de projet', $profiles[0]['label']);
        $this->assertTrue($profiles[0]['offers_paid']);
        $this->assertSame('25 000 FCFA la séance', $profiles[0]['rate']);
        $this->assertTrue($profiles[2]['offers_mentoring']);
    }

    public function test_the_rules_on_profiles_are_enforced(): void
    {
        config(['idee.member_profiles.max_per_member' => 2]);
        $user = User::factory()->create();
        $this->addProfile($user, 'coach-de-projet');

        // PM-03 : au moins une forme d'intervention.
        $this->postJson('/api/me/profiles', ['profession_id' => $this->profession('juriste')->id])
            ->assertUnprocessable()->assertJsonValidationErrors('offers');

        // Un seul profil par métier.
        $this->postJson('/api/me/profiles', ['profession_id' => $this->profession('coach-de-projet')->id, 'offers_paid' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('profession_id');

        // PM-04 : un métier désactivé ne se choisit plus.
        $this->profession('juriste')->update(['active' => false]);
        $this->postJson('/api/me/profiles', ['profession_id' => $this->profession('juriste')->id, 'offers_paid' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('profession_id');

        // Nombre maximum de profils.
        $this->addProfile($user, 'chef-de-projet');
        $this->postJson('/api/me/profiles', ['profession_id' => $this->profession('comptable')->id, 'offers_paid' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('profession_id');

        // Le métier d'un profil ne change pas.
        $profile = $user->memberProfiles()->first();
        $this->patchJson("/api/member-profiles/{$profile->id}", ['profession_id' => $this->profession('comptable')->id])
            ->assertUnprocessable()->assertJsonValidationErrors('profession_id');
    }

    public function test_there_is_always_exactly_one_primary_profile(): void
    {
        $user = User::factory()->create();
        $first = $this->addProfile($user, 'coach-de-projet');
        $second = $this->addProfile($user, 'chef-de-projet');
        $third = $this->addProfile($user, 'formateur-professionnel');

        $this->patchJson("/api/member-profiles/{$third->id}", ['is_primary' => true])->assertOk();
        $this->assertSame([$third->id], $user->memberProfiles()->where('is_primary', true)->pluck('id')->all());

        // Supprimer le principal : le plus ancien des autres le devient, le compte reste.
        $this->deleteJson("/api/member-profiles/{$third->id}")->assertNoContent();
        $this->assertTrue($first->fresh()->is_primary);
        $this->assertFalse($second->fresh()->is_primary);
        $this->assertNotNull($user->fresh());
    }

    public function test_paid_mentoring_and_regulated_profiles_are_public_only_after_identity_verification(): void
    {
        $user = User::factory()->create();
        $this->addProfile($user, 'chef-de-projet');
        $this->addProfile($user, 'coach-de-projet', ['offers_paid' => true]);
        $this->addProfile($user, 'avocat');

        $public = fn () => collect($this->getJson("/api/members/{$user->id}")->assertOk()->json('data.profiles'))->pluck('label')->all();

        $this->assertSame(['Chef de projet'], $public());

        $user->forceFill(['verification_level' => VerificationLevel::Identity])->save();
        $this->assertEqualsCanonicalizing(['Chef de projet', 'Coach de projet', 'Avocat'], $public());

        // Un profil en pause n'est plus affiché.
        $user->memberProfiles()->whereHas('profession', fn ($query) => $query->where('slug', 'avocat'))->update(['active' => false]);
        $this->assertEqualsCanonicalizing(['Chef de projet', 'Coach de projet'], $public());
    }

    public function test_a_member_cannot_touch_someone_elses_profile(): void
    {
        $profile = $this->addProfile(User::factory()->create(), 'coach-de-projet');

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/member-profiles/{$profile->id}", ['title' => 'Piratage'])->assertForbidden();
        $this->deleteJson("/api/member-profiles/{$profile->id}")->assertForbidden();
        $this->assertNull($profile->fresh()->title);
    }

    public function test_the_directory_finds_verified_coaches_by_profession(): void
    {
        $coach = User::factory()->identityVerified()->create(['name' => 'Aïssatou Diallo']);
        $coach->syncRoles([UserRole::Coach]);
        $this->addProfile($coach, 'coach-en-levee-de-fonds', ['offers_paid' => true]);

        $expert = User::factory()->identityVerified()->create(['name' => 'Ousmane Sy']);
        $expert->syncRoles([UserRole::Expert]);
        $this->addProfile($expert, 'expert-comptable', ['offers_paid' => true]);

        $names = fn (array $query) => collect($this->getJson('/api/experts?'.http_build_query($query))->assertOk()->json('data'))->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Aïssatou Diallo', 'Ousmane Sy'], $names([]));
        $this->assertSame(['Aïssatou Diallo'], $names(['metier' => 'coach-en-levee-de-fonds']));
    }

    public function test_a_member_manages_profiles_from_the_website(): void
    {
        $user = User::factory()->create(['name' => 'Aïssatou Diallo']);

        $this->actingAs($user)->get(route('member-profiles.index'))->assertOk()->assertSee('Mes métiers')->assertSee('Coach de projet');

        $this->post(route('member-profiles.store'), [
            'profession_id' => $this->profession('chef-de-projet')->id,
            'title' => 'Cheffe de projet digital',
            'description' => 'Je structure des projets numériques de bout en bout.',
            'offers_paid' => '0',
            'offers_volunteer' => '1',
            'offers_mentoring' => '0',
        ])->assertRedirect(route('member-profiles.index'));

        $profile = $user->memberProfiles()->firstOrFail();
        $this->assertSame('Cheffe de projet digital', $profile->title);

        // Décocher le bénévolat sans rien cocher d'autre est refusé.
        $this->put(route('member-profiles.update', $profile), ['offers_paid' => '0', 'offers_volunteer' => '0', 'offers_mentoring' => '0'])
            ->assertSessionHasErrors('offers');

        $this->get(route('members.show', $user))->assertOk()->assertSee('Cheffe de projet digital')->assertSee('Bénévolat');

        // Le formulaire d'ajout reste vide, même avec des profils déjà enregistrés.
        $this->get(route('member-profiles.index'))->assertOk()->assertSeeInOrder(['Ajouter un métier', 'Intitulé affiché'])
            ->assertDontSee('value="Cheffe de projet digital"', false);
    }

    public function test_the_team_manages_the_catalogue_without_touching_the_code(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get(route('admin.professions'))->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = $this->profession('coach-de-projet')->category;

        $this->actingAs($admin)->get(route('admin.professions'))->assertOk()->assertSee('Coaching et mentorat');
        $this->post(route('admin.professions.store'), ['profession_category_id' => $category->id, 'name' => 'Coach en export'])->assertRedirect();
        $this->assertTrue(Profession::where('slug', 'coach-en-export')->where('active', true)->exists());

        $this->post(route('admin.professions.categories.store'), ['name' => 'Économie sociale et solidaire'])->assertRedirect();
        $this->assertDatabaseHas('profession_categories', ['slug' => 'economie-sociale-et-solidaire']);

        // Désactiver un métier utilisé : les profils existants le gardent.
        $profile = $this->addProfile($member, 'coach-de-projet');
        $coach = $this->profession('coach-de-projet');
        $this->actingAs($admin)->put(route('admin.professions.update', $coach), ['profession_category_id' => $category->id, 'name' => 'Coach de projets'])
            ->assertRedirect(route('admin.professions'));

        $this->assertFalse($coach->fresh()->active);
        $this->assertSame('Coach de projets', $coach->fresh()->name);
        $this->assertSame('coach-de-projet', $coach->fresh()->slug);
        $this->assertTrue($profile->fresh()->exists);
    }

    public function test_the_seeder_never_overwrites_the_teams_changes(): void
    {
        $this->profession('coach-de-projet')->update(['name' => 'Coach de projets', 'active' => false]);

        $this->seed(ProfessionSeeder::class);

        $this->assertSame('Coach de projets', $this->profession('coach-de-projet')->name);
        $this->assertFalse($this->profession('coach-de-projet')->active);
        $this->assertSame(1, Profession::where('slug', 'coach-de-projet')->count());
    }

    public function test_profiles_are_exported_and_erased_with_the_account(): void
    {
        $user = User::factory()->create();
        $this->addProfile($user, 'coach-de-projet', ['rate' => '10 000 FCFA']);

        $export = $this->getJson('/api/me/export')->assertOk()->json('data.profil.profils_professionnels');
        $this->assertSame('Coach de projet', $export[0]['metier']);

        $this->deleteJson('/api/me', ['confirmation' => 'SUPPRIMER'])->assertSuccessful();
        $this->assertSame(0, MemberProfile::count());
    }

    public function test_a_profile_is_active_and_public_as_soon_as_it_is_created(): void
    {
        $user = User::factory()->create();
        $profile = app(MemberProfileService::class)->create($user, [
            'profession_id' => $this->profession('chef-de-projet')->id,
            'offers_volunteer' => true,
        ]);

        $this->assertTrue($profile->active);
        $this->assertTrue($profile->isPublic());
    }
}
