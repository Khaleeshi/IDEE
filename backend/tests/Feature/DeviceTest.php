<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Appareils connectés (CP-06).
 */
class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_sees_and_disconnects_their_devices_from_the_app(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('Application IDEE (Android)')->plainTextToken;
        $user->createToken('Application IDEE (iPhone)');

        $devices = collect($this->withToken($phone)->getJson('/api/me/devices')->assertOk()->json('data'));
        $this->assertEqualsCanonicalizing(['Application IDEE (Android)', 'Application IDEE (iPhone)'], $devices->pluck('name')->all());
        $this->assertSame('Application IDEE (Android)', $devices->firstWhere('current', true)['name']);

        $other = $devices->firstWhere('current', false)['id'];
        $this->withToken($phone)->deleteJson("/api/me/devices/{$other}")->assertNoContent();
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_member_cannot_disconnect_someone_elses_device(): void
    {
        $victim = User::factory()->create();
        $victim->createToken('Application IDEE');
        $victimToken = $victim->tokens()->first();

        $intruder = User::factory()->create()->createToken('Application IDEE')->plainTextToken;
        $this->withToken($intruder)->deleteJson("/api/me/devices/app-{$victimToken->id}")->assertNotFound();
        $this->assertSame(1, $victim->tokens()->count());
    }

    public function test_disconnecting_all_other_devices_keeps_the_current_one(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('Téléphone')->plainTextToken;
        $user->createToken('Ancien téléphone');
        $user->createToken('Tablette');

        $this->withToken($current)->postJson('/api/me/devices/logout-others')->assertOk()->assertJsonPath('message', '2 appareil(s) déconnecté(s).');
        $this->assertSame(['Téléphone'], $user->tokens()->pluck('name')->all());
    }

    public function test_browser_sessions_are_listed_without_revealing_their_id(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'secret-session-id', 'user_id' => $user->id, 'ip_address' => '41.82.0.1',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/131.0 Mobile Safari/537.36',
            'payload' => '', 'last_activity' => now()->subHour()->timestamp,
        ]);

        $devices = app(DeviceService::class)->list($user);
        $this->assertSame('Chrome sur Android', $devices->first()['name']);
        $this->assertStringNotContainsString('secret-session-id', json_encode($devices));

        $this->assertTrue(app(DeviceService::class)->revoke($user, $devices->first()['id']));
        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_the_devices_page_lists_the_app(): void
    {
        $user = User::factory()->create();
        $user->createToken('Application IDEE (Android)');

        $this->actingAs($user)->get(route('devices.index'))->assertOk()
            ->assertSee('Mes appareils')->assertSee('Application IDEE (Android)')->assertSee('Déconnecter');
    }

    public function test_browsers_are_described_in_plain_words(): void
    {
        $this->assertSame('Safari sur iPhone', DeviceService::describeBrowser('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'));
        $this->assertSame('Firefox sur Windows', DeviceService::describeBrowser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0'));
        $this->assertSame('Navigateur', DeviceService::describeBrowser(''));
    }
}
