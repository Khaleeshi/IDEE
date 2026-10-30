<?php

namespace Tests\Feature;

use App\Exceptions\CodeDeliveryFailed;
use App\Services\Messaging\FallbackCodeSender;
use App\Services\Messaging\OrangeSmsCodeSender;
use App\Services\Messaging\WhatsAppCodeSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeSendersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.orange_sms.client_id' => 'client',
            'services.orange_sms.client_secret' => 'secret',
            'services.orange_sms.sender' => 'tel:+2210000',
            'services.orange_sms.sender_name' => 'IDEE',
            'services.whatsapp.token' => 'jeton',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.graph_version' => 'v23.0',
        ]);
    }

    public function test_orange_sends_the_code_by_sms_to_a_senegalese_number(): void
    {
        Http::fake([
            'api.orange.com/oauth/v3/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response([], 201),
        ]);

        (new OrangeSmsCodeSender)->send('+221771234567', '482913');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.orange.com/smsmessaging/v1/outbound/tel%3A%2B2210000/requests'
            && $request->hasHeader('Authorization', 'Bearer abc')
            && $request['outboundSMSMessageRequest']['address'] === 'tel:+221771234567'
            && $request['outboundSMSMessageRequest']['senderName'] === 'IDEE'
            && str_contains($request['outboundSMSMessageRequest']['outboundSMSTextMessage']['message'], '482913'));
    }

    public function test_the_orange_token_is_reused_between_sms(): void
    {
        Http::fake([
            'api.orange.com/oauth/v3/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response([], 201),
        ]);

        $sender = new OrangeSmsCodeSender;
        $sender->send('+221771234567', '111111');
        $sender->send('+221771234568', '222222');

        Http::assertSentCount(3);
    }

    public function test_orange_refuses_numbers_outside_senegal(): void
    {
        Http::fake();

        $this->expectException(CodeDeliveryFailed::class);
        (new OrangeSmsCodeSender)->send('+33612345678', '482913');
    }

    public function test_whatsapp_sends_the_authentication_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid']]])]);

        (new WhatsAppCodeSender)->send('+33612345678', '482913');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v23.0/123456789/messages'
            && $request['to'] === '33612345678'
            && $request['template']['name'] === 'idee_code_connexion'
            && $request['template']['components'][0]['parameters'][0]['text'] === '482913');
    }

    public function test_the_sms_takes_over_when_whatsapp_fails(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Numéro sans WhatsApp']], 400),
            'api.orange.com/oauth/v3/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response([], 201),
        ]);

        (new FallbackCodeSender([new WhatsAppCodeSender, new OrangeSmsCodeSender]))->send('+221771234567', '482913');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'smsmessaging'));
    }

    public function test_the_member_gets_a_clear_message_when_no_channel_works(): void
    {
        config(['idee.otp.driver' => 'whatsapp_sms']);
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 500),
            'api.orange.com/*' => Http::response([], 503),
        ]);

        $this->postJson('/api/auth/code', ['phone' => '77 123 45 67'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'Impossible d\'envoyer le code']);
    }
}
