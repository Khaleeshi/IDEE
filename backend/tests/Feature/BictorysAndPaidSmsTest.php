<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\OneTimeCode;
use App\Models\Payment;
use App\Models\SmsPurchase;
use App\Models\User;
use App\Services\Messaging\TwilioSmsCodeSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Paiements par Bictorys (PA-04) et SMS de connexion payé d'avance (CP-07).
 * Branchements vérifiés avec des réponses simulées de Bictorys et de Twilio.
 */
class BictorysAndPaidSmsTest extends TestCase
{
    use RefreshDatabase;

    private string $bictorysStatus = 'pending';

    private bool $twilioFails = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'idee.payments.bictorys' => true,
            'services.bictorys.public_key' => 'test_public-cle',
            'services.bictorys.webhook_secret' => 'secret-du-webhook',
            'services.bictorys.base_url' => 'https://api.test.bictorys.com',
            'idee.otp.paid_sms' => true,
            'idee.otp.sms_driver' => 'twilio',
            'services.twilio.account_sid' => 'AC123',
            'services.twilio.auth_token' => 'jeton',
            'services.twilio.from' => 'IDEE',
        ]);

        Http::fake(function (Request $request) {
            return match (true) {
                str_contains($request->url(), '/pay/v1/charges') => Http::response([
                    'transactionId' => 'trx-'.$request['paymentReference'],
                    'link' => $request['amount'] === 500 ? 'https://pay.test.bictorys.com/sms' : 'https://pay.test.bictorys.com/offre',
                ], 201),
                str_contains($request->url(), '/status') => Http::response(['status' => $this->bictorysStatus]),
                str_contains($request->url(), 'api.twilio.com') => $this->twilioFails
                    ? Http::response(['code' => 21211, 'message' => 'Numéro invalide'], 400)
                    : Http::response(['sid' => 'SM1', 'status' => 'queued'], 201),
                default => Http::response([], 404),
            };
        });
    }

    public function test_the_login_options_show_free_whatsapp_and_email_and_the_paid_sms(): void
    {
        $data = $this->getJson('/api/auth/options')->assertOk()->json('data');

        $this->assertSame([0, 0, 500], array_column($data['channels'], 'price_fcfa'));
        $this->assertSame(['wave', 'orange_money', 'carte'], array_column($data['payment_methods'], 'value'));
        $this->assertSame('SN', $data['countries'][0]['code']);
    }

    public function test_no_sms_leaves_before_the_payment_is_confirmed_by_bictorys(): void
    {
        $id = $this->postJson('/api/auth/sms-purchases', ['phone' => '77 123 45 67', 'method' => 'wave'])
            ->assertCreated()
            ->assertJsonPath('data.amount_fcfa', 500)
            ->assertJsonPath('data.payment_url', 'https://pay.test.bictorys.com/sms')
            ->json('data.id');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/pay/v1/charges?payment_type=wave_money')
            && $request->hasHeader('X-Api-Key', 'test_public-cle')
            && $request['currency'] === 'XOF'
            && $request['paymentReference'] === $id);

        // Sans paiement confirmé : refus, et aucun SMS.
        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])
            ->assertUnprocessable()->assertJsonValidationErrors('channel');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'twilio'));

        // Bictorys confirme : le SMS part par Twilio, et le code fonctionne.
        $this->travel(2)->minutes();
        $this->bictorysStatus = 'succeeded';
        $this->getJson("/api/auth/sms-purchases/{$id}")->assertOk()->assertJsonPath('data.status.value', 'payee')->assertJsonPath('data.sends_remaining', 2);
        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])->assertAccepted();

        $sent = null;
        Http::assertSent(function (Request $request) use (&$sent) {
            if (! str_contains($request->url(), 'api.twilio.com/2010-04-01/Accounts/AC123/Messages.json')) {
                return false;
            }
            preg_match('/(\d{6})/', $request['Body'], $match);
            $sent = $match[1] ?? null;

            return $request['To'] === '+221771234567' && $request['From'] === 'IDEE';
        });

        $this->postJson('/api/auth/verify', ['phone' => '+221771234567', 'code' => $sent, 'name' => 'Awa Ndiaye'])->assertOk();
        $this->assertSame(1, SmsPurchase::find($id)->sends_remaining);
        $this->assertStringStartsWith('SMS-'.now()->year.'-', SmsPurchase::find($id)->receipt_number);
    }

    public function test_a_purchase_serves_only_its_number_and_its_number_of_sends(): void
    {
        $this->bictorysStatus = 'succeeded';
        $id = $this->postJson('/api/auth/sms-purchases', ['phone' => '+221771234567', 'method' => 'orange_money'])->json('data.id');

        // Un autre numéro ne profite pas de ce paiement.
        $this->postJson('/api/auth/code', ['phone' => '+221779999999', 'channel' => 'sms', 'sms_purchase_id' => $id])
            ->assertUnprocessable()->assertJsonValidationErrors('channel');

        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])->assertAccepted();
        $this->travel(2)->minutes();
        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])->assertAccepted();
        $this->travel(2)->minutes();
        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])
            ->assertUnprocessable()->assertJsonValidationErrors('channel');
    }

    public function test_a_sms_that_fails_to_leave_keeps_the_purchase(): void
    {
        $this->bictorysStatus = 'succeeded';
        $this->twilioFails = true;
        Log::spy();

        $id = $this->postJson('/api/auth/sms-purchases', ['phone' => '+221771234567', 'method' => 'wave'])->json('data.id');
        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'sms', 'sms_purchase_id' => $id])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone' => 'reste valable']);

        $this->assertSame(2, SmsPurchase::find($id)->sends_remaining);
    }

    public function test_whatsapp_and_email_stay_free_without_any_payment(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/code', ['phone' => '+221771234567', 'channel' => 'whatsapp'])->assertAccepted();
        $this->postJson('/api/auth/code', ['email' => 'awa@exemple.sn'])->assertAccepted();

        $this->assertSame(2, OneTimeCode::count());
        $this->assertSame(0, SmsPurchase::count());
    }

    public function test_the_country_adds_its_dialing_code_for_the_diaspora(): void
    {
        $this->postJson('/api/auth/sms-purchases', ['country' => 'FR', 'phone' => '06 12 34 56 78', 'method' => 'carte'])->assertCreated();

        $this->assertSame('+33612345678', SmsPurchase::first()->phone);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'payment_type=card') && str_contains($request->url(), 'payment_category=card'));
    }

    public function test_the_whole_sms_login_works_on_the_website(): void
    {
        $this->post(route('login.send'), ['country' => 'SN', 'phone' => '77 123 45 67', 'channel' => 'sms'])->assertRedirect(route('login.sms'));
        $this->get(route('login.sms'))->assertOk()->assertSee('500')->assertSee('Wave')->assertSee('Carte bancaire');

        $this->post(route('login.sms.start'), ['phone' => '+221771234567', 'method' => 'wave'])->assertRedirect('https://pay.test.bictorys.com/sms');
        $purchase = SmsPurchase::firstOrFail();

        // Retour de Bictorys avant la confirmation : on attend.
        $this->get(route('login.sms.show', $purchase))->assertOk()->assertSee('Payer maintenant');

        // Paiement confirmé : le SMS part et on arrive sur le code.
        $this->bictorysStatus = 'succeeded';
        $this->get(route('login.sms.show', $purchase))->assertRedirect(route('login.code'));
        $this->get(route('login.code'))->assertOk()->assertSee('Renvoyer le code par SMS');
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.twilio.com'));
    }

    public function test_someone_else_cannot_use_a_purchase_from_another_browser(): void
    {
        $this->postJson('/api/auth/sms-purchases', ['phone' => '+221771234567', 'method' => 'wave']);

        $this->get(route('login.sms.show', SmsPurchase::firstOrFail()))->assertNotFound();
    }

    public function test_offers_are_paid_through_bictorys_and_confirmed_by_asking_bictorys(): void
    {
        Notification::fake();
        Sanctum::actingAs($member = User::factory()->create());

        $id = $this->postJson('/api/payments', ['offer' => 'pro', 'method' => 'orange_money'])
            ->assertCreated()->assertJsonPath('data.launch_url', 'https://pay.test.bictorys.com/offre')->json('data.id');
        $this->assertSame('bictorys', Payment::find($id)->provider);

        // Un avis sans le bon secret est ignoré ; un avis authentique déclenche une relecture.
        $this->bictorysStatus = 'succeeded';
        $this->postJson('/api/webhooks/bictorys', ['paymentReference' => $id], ['X-Secret-Key' => 'faux'])->assertUnauthorized();
        $this->assertSame(PaymentStatus::EnAttente, Payment::find($id)->status);

        $this->postJson('/api/webhooks/bictorys', ['paymentReference' => $id, 'status' => 'succeeded'], ['X-Secret-Key' => 'secret-du-webhook'])->assertNoContent();
        $this->assertSame(PaymentStatus::Payee, Payment::find($id)->status);
        $this->assertSame(15, $member->fresh()->weeklyContactQuota());
    }

    public function test_a_forged_bictorys_notice_never_validates_an_unpaid_purchase(): void
    {
        $id = $this->postJson('/api/auth/sms-purchases', ['phone' => '+221771234567', 'method' => 'wave'])->json('data.id');

        $this->postJson('/api/webhooks/bictorys', ['paymentReference' => $id, 'status' => 'succeeded'], ['X-Secret-Key' => 'secret-du-webhook'])->assertNoContent();

        $this->assertSame(PaymentStatus::EnAttente, SmsPurchase::find($id)->status);
    }

    public function test_twilio_can_use_a_messaging_service(): void
    {
        config(['services.twilio.messaging_service_sid' => 'MG42']);

        (new TwilioSmsCodeSender)->send('+33612345678', '123456');

        Http::assertSent(fn (Request $request) => $request['MessagingServiceSid'] === 'MG42' && ! isset($request['From'])
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('AC123:jeton')));
    }
}
