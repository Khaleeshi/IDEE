<?php

namespace Tests\Unit;

use Anthropic\Client;
use App\Exceptions\AssistantUnavailable;
use App\Services\Assistant\ClaudeAssistant;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * Vérifie la requête envoyée à l'API d'Anthropic, sans appel réel ni dépense.
 */
class ClaudeAssistantTest extends TestCase
{
    private ?RequestInterface $sent = null;

    private function assistant(array $reply): ClaudeAssistant
    {
        $transport = new class($this, $reply) implements ClientInterface
        {
            public function __construct(private ClaudeAssistantTest $test, private array $reply) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->test->capture($request);

                return new Response(200, ['Content-Type' => 'application/json'], json_encode($this->reply));
            }
        };

        return new ClaudeAssistant(new Client(apiKey: 'cle-de-test', requestOptions: ['transporter' => $transport, 'maxRetries' => 0]));
    }

    public function capture(RequestInterface $request): void
    {
        $this->sent = $request;
    }

    private function message(string $text, string $stopReason = 'end_turn'): array
    {
        return [
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5-5',
            'content' => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
            'stop_reason' => $stopReason,
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 321, 'output_tokens' => 45],
        ];
    }

    public function test_it_sends_a_structured_request_with_the_default_fallback(): void
    {
        $schema = ['type' => 'object', 'properties' => ['questions' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['questions'], 'additionalProperties' => false];

        $reply = $this->assistant($this->message('{"questions":["Qui sont vos clients ?"]}'))
            ->ask('Consignes', 'Projet : savons au karité', $schema, 'low');

        $this->assertSame(['questions' => ['Qui sont vos clients ?']], $reply->data);
        $this->assertSame([321, 45], [$reply->inputTokens, $reply->outputTokens]);

        $body = json_decode((string) $this->sent->getBody(), true);
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('low', $body['output_config']['effort']);
        $this->assertSame($schema, $body['output_config']['format']['schema']);
        $this->assertSame('Consignes', $body['system']);
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $this->sent->getHeaderLine('anthropic-beta'));
        $this->assertSame('Bearer cle-de-test', $this->sent->getHeaderLine('Authorization'));
    }

    public function test_a_refusal_is_reported_instead_of_read_as_content(): void
    {
        $this->expectException(AssistantUnavailable::class);
        $this->expectExceptionMessage('L\'assistant a refusé de traiter cette demande.');

        $this->assistant($this->message('', 'refusal'))->ask('Consignes', 'Projet', ['type' => 'object'], 'low');
    }
}
