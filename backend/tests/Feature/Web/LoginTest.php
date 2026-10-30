<?php

namespace Tests\Feature\Web;

use App\Contracts\CodeSender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private ?string $lastCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        $code = &$this->lastCode;
        $this->app->instance(CodeSender::class, new class($code) implements CodeSender
        {
            public function __construct(private ?string &$code) {}

            public function send(string $phone, string $code): void
            {
                $this->code = $code;
            }
        });
    }

    public function test_a_visitor_signs_up_with_the_code_received_by_phone(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Recevoir mon code');

        $this->post('/connexion', ['phone' => '77 123 45 67'])
            ->assertRedirect(route('login.code'));

        $this->get(route('login.code'))->assertOk()->assertSee('+221771234567');

        $this->post(route('login.verify'), ['phone' => '+221771234567', 'code' => $this->lastCode, 'name' => 'Aminata Diop'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs(User::firstWhere('phone', '+221771234567'));

        // Un nouveau membre accepte d'abord les conditions et la charte (section 8).
        $this->get('/')->assertRedirect(route('terms.show'));
        $this->get(route('terms.show'))->assertOk()->assertSee('Bienvenue sur IDEE, Aminata Diop')->assertSee('ne demande jamais d');
        $this->post(route('terms.accept'))->assertSessionHasErrors('accept');
        $this->post(route('terms.accept'), ['accept' => '1'])->assertRedirect('/');
        $this->assertSame(config('idee.legal.version'), User::firstWhere('phone', '+221771234567')->terms_version);
        $this->get('/')->assertOk()->assertSee('Activité');
    }

    public function test_a_wrong_code_shows_an_error_in_french(): void
    {
        $this->post('/connexion', ['phone' => '+221770000009']);
        $wrong = $this->lastCode === '000000' ? '111111' : '000000';

        $this->from(route('login.code'))
            ->post(route('login.verify'), ['phone' => '+221770000009', 'code' => $wrong])
            ->assertRedirect(route('login.code'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_the_code_page_needs_a_phone_number_first(): void
    {
        $this->get(route('login.code'))->assertRedirect(route('login'));
    }

    public function test_a_member_logs_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/deconnexion')
            ->assertRedirect(route('home'))
            ->assertSessionHas('clear_offline_cache', true);

        $this->assertGuest();
        $this->get('/')->assertSee('caches.delete', escape: false);
    }
}
