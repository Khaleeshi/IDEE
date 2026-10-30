<?php

namespace Tests\Feature;

use App\Contracts\CodeSender;
use App\Enums\VerificationLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $sentCodes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $sent = &$this->sentCodes;
        $this->app->instance(CodeSender::class, new class($sent) implements CodeSender
        {
            /**
             * @param  array<string, string>  $sent
             */
            public function __construct(private array &$sent) {}

            public function send(string $phone, string $code): void
            {
                $this->sent[$phone] = $code;
            }
        });
    }

    public function test_a_new_member_signs_up_with_a_one_time_code(): void
    {
        $this->postJson('/api/auth/code', ['phone' => '77 123 45 67'])->assertAccepted();

        $code = $this->sentCodes['+221771234567'];

        $response = $this->postJson('/api/auth/verify', [
            'phone' => '77 123 45 67',
            'code' => $code,
            'name' => 'Aminata Diop',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Aminata Diop')
            ->assertJsonPath('user.phone', '+221771234567')
            ->assertJsonPath('user.verification.value', VerificationLevel::Phone->value);

        $this->assertNotEmpty($response->json('token'));
        $this->assertNotNull(User::firstWhere('phone', '+221771234567')->phone_verified_at);
    }

    public function test_an_existing_member_logs_in_without_creating_a_duplicate(): void
    {
        $user = User::factory()->create(['phone' => '+221770000001']);

        $this->postJson('/api/auth/code', ['phone' => '+221770000001'])->assertAccepted();
        $this->postJson('/api/auth/verify', ['phone' => '+221770000001', 'code' => $this->sentCodes['+221770000001']])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertSame(1, User::count());
    }

    public function test_a_wrong_code_is_rejected_and_counts_as_an_attempt(): void
    {
        $this->postJson('/api/auth/code', ['phone' => '+221770000002']);
        $wrong = $this->sentCodes['+221770000002'] === '000000' ? '111111' : '000000';

        $this->postJson('/api/auth/verify', ['phone' => '+221770000002', 'code' => $wrong])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'Il vous reste 4 essai(s).']);
    }

    public function test_the_code_is_refused_after_five_wrong_attempts(): void
    {
        $this->postJson('/api/auth/code', ['phone' => '+221770000003']);
        $good = $this->sentCodes['+221770000003'];
        $wrong = $good === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/verify', ['phone' => '+221770000003', 'code' => $wrong]);
        }

        $this->postJson('/api/auth/verify', ['phone' => '+221770000003', 'code' => $good])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'expiré']);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->postJson('/api/auth/code', ['phone' => '+221770000004']);

        $this->travel(11)->minutes();

        $this->postJson('/api/auth/verify', ['phone' => '+221770000004', 'code' => $this->sentCodes['+221770000004']])
            ->assertUnprocessable();
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $this->postJson('/api/auth/code', ['phone' => '1234'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }
}
