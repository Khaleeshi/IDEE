<?php

namespace Tests\Feature;

use App\Enums\Offer;
use App\Enums\PaymentMethod;
use App\Models\Entitlement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\OrganisationNotice;
use App\Notifications\PaymentNotice;
use App\Services\Organisations\OrganisationService;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function enableWave(string $paymentStatus = 'succeeded'): void
    {
        config(['idee.payments.wave' => true, 'services.wave.key' => 'cle-de-test']);

        Http::fake([
            'api.wave.com/v1/checkout/sessions/*' => Http::response(['id' => 'cos-123', 'checkout_status' => 'complete', 'payment_status' => $paymentStatus]),
            'api.wave.com/v1/checkout/sessions' => Http::response(['id' => 'cos-123', 'wave_launch_url' => 'https://pay.wave.com/c/cos-123']),
        ]);
    }

    public function test_payments_stay_off_until_a_method_is_configured(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('offers.index'))->assertOk()->assertSee('arrive bientôt')->assertSee('Offre Pro entrepreneur');
        Sanctum::actingAs($member);
        $this->getJson('/api/offers')->assertOk()->assertJsonCount(0, 'data.methods')->assertJsonPath('data.offers.0.price_fcfa', 5000);
        $this->postJson('/api/payments', ['offer' => 'pro', 'method' => 'wave'])->assertUnprocessable()->assertJsonValidationErrors('method');
    }

    public function test_a_wave_payment_is_confirmed_by_asking_wave_then_opens_the_pro_offer(): void
    {
        Notification::fake();
        $this->enableWave();
        $member = User::factory()->create();
        $this->assertSame(5, $member->weeklyContactQuota());
        Sanctum::actingAs($member);

        $paymentId = $this->postJson('/api/payments', ['offer' => 'pro', 'method' => 'wave'])
            ->assertCreated()
            ->assertJsonPath('data.amount_fcfa', 5000)
            ->assertJsonPath('data.launch_url', 'https://pay.wave.com/c/cos-123')
            ->json('data.id');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer cle-de-test')
            && $request['amount'] === '5000'
            && $request['currency'] === 'XOF'
            && $request['client_reference'] === $paymentId);

        // L'avis de Wave déclenche une relecture : c'est la réponse de Wave qui valide.
        $this->postJson('/api/webhooks/wave', ['type' => 'checkout.session.completed', 'data' => ['id' => 'cos-123', 'client_reference' => $paymentId]])->assertNoContent();

        $payment = Payment::find($paymentId);
        $this->assertTrue($payment->isPaid());
        $this->assertSame('REC-'.now()->year.'-000001', $payment->receipt_number);
        Notification::assertSentTo($member, PaymentNotice::class);

        $member->refresh();
        $this->assertSame([Offer::Pro], $member->activeOffers());
        $this->assertSame(15, $member->weeklyContactQuota());
        $this->assertSame(30, $member->savedSearchLimit());

        $this->actingAs($member)->get(route('payments.receipt', $payment))->assertOk()->assertSee($payment->receipt_number)->assertSee("5\u{00A0}000 FCFA");
        $this->actingAs(User::factory()->create())->get(route('payments.receipt', $payment))->assertNotFound();
    }

    public function test_a_forged_notice_does_not_validate_an_unpaid_session(): void
    {
        $this->enableWave(paymentStatus: 'processing');
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        $paymentId = $this->postJson('/api/payments', ['offer' => 'investisseur', 'method' => 'wave'])->json('data.id');

        $this->postJson('/api/webhooks/wave', ['data' => ['client_reference' => $paymentId, 'payment_status' => 'succeeded']])->assertNoContent();

        $this->assertFalse(Payment::find($paymentId)->isPaid());
        $this->assertSame([], $member->fresh()->activeOffers());

        // Un achat abandonné expire.
        $this->travel(config('idee.payments.expire_after_hours') + 1)->hours();
        $this->artisan('idee:payments')->expectsOutputToContain('1 paiement(s) expiré(s)');
        $this->assertSame('expiree', Payment::find($paymentId)->status->value);
    }

    public function test_an_orange_money_transfer_is_checked_by_the_team(): void
    {
        Notification::fake();
        config(['idee.payments.orange_money_number' => '77 000 00 00']);
        $member = User::factory()->create();
        $this->actingAs($member);

        $this->get(route('offers.checkout', 'pro'))->assertOk()->assertSee('Orange Money');
        $this->post(route('payments.store'), ['offer' => 'pro', 'method' => 'orange_money']);
        $payment = Payment::sole();
        $this->get(route('payments.show', $payment))->assertSee('77 000 00 00')->assertSee("5\u{00A0}000 FCFA");
        $this->post(route('payments.reference', $payment), ['payer_reference' => 'CI261030.1205.A12345'])->assertRedirect(route('payments.show', $payment));

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.payments'))->assertOk()->assertSee('CI261030.1205.A12345');
        $this->post(route('admin.payments.decide', $payment), ['approved' => 1])->assertRedirect(route('admin.payments'));

        $this->assertTrue($payment->fresh()->isPaid());
        $this->assertSame($admin->id, $payment->fresh()->reviewed_by);
        Notification::assertSentTo($member, PaymentNotice::class);
    }

    public function test_a_second_pack_extends_the_current_period(): void
    {
        config(['idee.payments.orange_money_number' => '77 000 00 00']);
        $this->freezeSecond();
        $member = User::factory()->create();
        $service = app(PaymentService::class);

        $service->markPaid($service->start($member, Offer::Pro, PaymentMethod::OrangeMoney));
        $service->markPaid($service->start($member, Offer::Pro, PaymentMethod::OrangeMoney));

        $this->assertEquals(now()->addDays(60), Entitlement::whereBelongsTo($member)->orderByDesc('ends_at')->first()->ends_at);
        $this->assertEqualsCanonicalizing(
            ['REC-'.now()->year.'-000001', 'REC-'.now()->year.'-000002'],
            Payment::pluck('receipt_number')->all(),
        );
    }

    public function test_a_sponsored_project_is_labelled_and_limited_to_its_owner(): void
    {
        config(['idee.payments.orange_money_number' => '77 000 00 00']);
        $project = Project::factory()->create(['title' => 'Savons au karité de Thiès']);
        Project::factory()->create(['title' => 'Autre projet']);
        $service = app(PaymentService::class);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/payments', ['offer' => 'sponsorise', 'method' => 'orange_money', 'project_id' => $project->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');

        Sanctum::actingAs($project->owner);
        $this->postJson('/api/payments', ['offer' => 'sponsorise', 'method' => 'orange_money', 'project_id' => $project->id, 'quantity' => 9])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
        $paymentId = $this->postJson('/api/payments', ['offer' => 'sponsorise', 'method' => 'orange_money', 'project_id' => $project->id, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.amount_fcfa', 10000)
            ->json('data.id');
        $service->markPaid(Payment::find($paymentId));

        $this->assertTrue(Project::sponsored()->whereKey($project->id)->exists());
        $this->get(route('home', ['vue' => 'projets']))->assertSee('Sponsorisé')->assertSee('Savons au karité de Thiès');
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('Sponsorisé jusqu');

        // RG-08 : la mise en avant ne compte pas pour la fiche d'un autre membre.
        $this->assertSame([], $project->owner->fresh()->activeOffers());
    }

    public function test_pro_members_see_the_visibility_of_their_projects(): void
    {
        $project = Project::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('projects.show', $project));
        $this->actingAs(User::factory()->create())->get(route('projects.show', $project));
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('offre Pro');

        Sanctum::actingAs($project->owner);
        $this->getJson("/api/projects/{$project->id}/stats")->assertStatus(402);

        Entitlement::forceCreate(['user_id' => $project->owner_id, 'offer' => 'pro', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $this->getJson("/api/projects/{$project->id}/stats")->assertOk()->assertJsonPath('data.views_7', 2);
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('Vues, 7 jours');
    }

    public function test_the_team_invoices_an_organisation(): void
    {
        Notification::fake();
        $manager = User::factory()->create();
        $organisation = app(OrganisationService::class)->create($manager, [
            'name' => 'Incubateur de Thiès',
            'kind' => 'accompagnement',
            'description' => 'Nous accompagnons les jeunes entrepreneurs de la région de Thiès.',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'organisation_id' => $organisation->id,
            'label' => 'Espace organisations, cohorte 2027, 12 mois',
            'amount_fcfa' => 600000,
        ])->assertRedirect(route('admin.payments'));

        $invoice = Invoice::sole();
        $this->assertSame('FAC-'.now()->year.'-0001', $invoice->number);
        Notification::assertSentTo($manager, OrganisationNotice::class);

        $this->actingAs($manager)->get(route('organisations.manage', $organisation))->assertSee($invoice->number);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee("600\u{00A0}000 FCFA");
        $this->actingAs(User::factory()->create())->get(route('invoices.show', $invoice))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.invoices.settle', $invoice), ['paid' => 1]);
        $this->assertSame('payee', $invoice->fresh()->status->value);
    }
}
