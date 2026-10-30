<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Models\Sector;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_updates_their_profile_with_comma_separated_lists(): void
    {
        $user = User::factory()->create();
        Sector::create(['slug' => 'numerique', 'name' => 'Numérique']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Mes rôles');

        $this->put(route('profile.update'), [
            'name' => 'Fatou Sow',
            'region' => 'Dakar',
            'skills' => 'Laravel, Flutter ,  design',
            'languages' => 'français, wolof',
            'roles' => ['contributeur'],
            'sectors' => ['numerique'],
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(['Laravel', 'Flutter', 'design'], $user->skills);
        $this->assertSame(['numerique'], $user->sectors);
        $this->assertTrue($user->hasRole(UserRole::Contributeur));
        $this->assertFalse($user->contact_preferences['accepts_requests']);
    }

    public function test_the_public_profile_shows_the_reactivity_but_never_the_phone(): void
    {
        $user = User::factory()->create(['name' => 'Moussa Ba', 'bio' => 'Transformateur de céréales à Kaolack.']);

        $this->get(route('members.show', $user))->assertOk()
            ->assertSee('Moussa Ba')
            ->assertSee('Transformateur de céréales')
            ->assertSee('Pas encore de demandes reçues')
            ->assertDontSee(PhoneNumber::format($user->phone))
            ->assertDontSee(ltrim($user->phone, '+'));
    }

    public function test_an_unverified_investor_role_is_not_shown_publicly(): void
    {
        $user = User::factory()->create();
        $user->syncRoles([UserRole::Investisseur]);

        $this->get(route('members.show', $user))->assertDontSee('Investisseur');

        $user->forceFill(['verification_level' => 2])->save();
        $this->get(route('members.show', $user))->assertSee('Investisseur');
    }

    public function test_the_app_can_be_installed_and_works_offline(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $this->get(route('offline'))->assertOk()->assertSee('Pas de connexion pour le moment');
    }
}
