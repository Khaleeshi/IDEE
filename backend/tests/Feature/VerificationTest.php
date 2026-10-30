<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_investor_is_shown_as_such_once_the_team_verifies_their_identity(): void
    {
        Storage::fake('local');
        $investor = User::factory()->create();
        $investor->syncRoles([UserRole::Investisseur]);
        Sanctum::actingAs($investor);

        $this->getJson("/api/members/{$investor->id}")->assertJsonCount(0, 'data.roles');

        $this->post('/api/me/verification', ['document' => UploadedFile::fake()->image('cni.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'en_attente');
        $this->post('/api/me/verification', ['document' => UploadedFile::fake()->image('cni.jpg')], ['Accept' => 'application/json'])
            ->assertUnprocessable();

        $verification = Verification::firstOrFail();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.verifications.document', $verification))->assertOk();
        $this->post(route('admin.verifications.decide', $verification), ['approved' => 1])->assertRedirect(route('admin.verifications'));

        $this->assertSame(VerificationLevel::Identity, $investor->fresh()->verification_level);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'La pièce d\'identité est effacée après examen.');
        $this->getJson("/api/members/{$investor->id}")->assertJsonPath('data.roles.0.value', 'investisseur');
    }

    public function test_a_refusal_needs_a_reason_sent_to_the_member(): void
    {
        Storage::fake('local');
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        $this->post('/api/me/verification', ['document' => UploadedFile::fake()->image('cni.jpg')], ['Accept' => 'application/json']);
        $verification = Verification::firstOrFail();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.verifications.decide', $verification), ['approved' => 0])
            ->assertSessionHasErrors('reason');
        $this->post(route('admin.verifications.decide', $verification), ['approved' => 0, 'reason' => 'Photo illisible']);

        Sanctum::actingAs($member);
        $this->getJson('/api/me/verification')->assertJsonPath('data.refusal_reason', 'Photo illisible');
    }
}
