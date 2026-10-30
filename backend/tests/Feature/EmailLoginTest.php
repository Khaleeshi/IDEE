<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginCode;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailLoginTest extends TestCase
{
    use RefreshDatabase;

    private function sentCode(string $email): string
    {
        $code = null;
        Notification::assertSentOnDemand(LoginCode::class, function (LoginCode $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$code) {
            $code = $notification->code;

            return $notifiable->routes['mail'] === $email;
        });

        return $code;
    }

    public function test_a_member_signs_up_with_an_email_address(): void
    {
        Notification::fake();

        $this->post(route('login.send'), ['phone' => 'Awa.Diop@Exemple.sn'])->assertRedirect(route('login.code'));
        $this->get(route('login.code'))->assertSee('Envoyé à awa.diop@exemple.sn');

        $this->post(route('login.verify'), [
            'phone' => 'awa.diop@exemple.sn',
            'code' => $this->sentCode('awa.diop@exemple.sn'),
            'name' => 'Awa Diop',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'awa.diop@exemple.sn')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->get(route('profile.edit'))->assertOk()->assertSee('confirmée');
    }

    public function test_the_api_also_accepts_an_email(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/code', ['email' => 'investisseur@exemple.fr'])->assertAccepted();
        $this->postJson('/api/auth/verify', ['email' => 'investisseur@exemple.fr', 'code' => $this->sentCode('investisseur@exemple.fr')])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_an_unconfirmed_email_does_not_open_the_account_until_its_link_is_followed(): void
    {
        Notification::fake();
        $member = User::factory()->create();

        $this->actingAs($member)->put(route('profile.update'), ['name' => $member->name, 'email' => 'moussa@exemple.sn']);
        Notification::assertSentTo($member, VerifyEmailAddress::class);
        auth()->logout();

        $this->post(route('login.send'), ['phone' => 'moussa@exemple.sn']);
        $this->post(route('login.verify'), ['phone' => 'moussa@exemple.sn', 'code' => $this->sentCode('moussa@exemple.sn')])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->get(URL::temporarySignedRoute('email.verify', now()->addDay(), ['user' => $member->id, 'hash' => sha1('moussa@exemple.sn')]))
            ->assertRedirect(route('login'));
        $this->assertNotNull($member->fresh()->email_verified_at);

        Notification::fake();
        $this->travel(2)->minutes(); // un seul code par minute et par adresse
        $this->post(route('login.send'), ['phone' => 'moussa@exemple.sn']);
        $this->post(route('login.verify'), ['phone' => 'moussa@exemple.sn', 'code' => $this->sentCode('moussa@exemple.sn')]);
        $this->assertAuthenticatedAs($member->fresh());
    }

    public function test_a_tampered_confirmation_link_is_refused(): void
    {
        $member = User::factory()->create(['email' => 'fatou@exemple.sn', 'email_verified_at' => null]);

        $this->get(route('email.verify', ['user' => $member->id, 'hash' => sha1('fatou@exemple.sn')]))->assertForbidden();
        $this->assertNull($member->fresh()->email_verified_at);
    }
}
