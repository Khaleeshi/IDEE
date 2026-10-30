<?php

namespace App\Services\Assistant;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Contracts\Assistant;
use App\Exceptions\AssistantUnavailable;
use Illuminate\Support\Facades\Log;

/**
 * Assistant IA d'IDEE, servi par Claude via le SDK officiel d'Anthropic.
 *
 * Les réponses sont contraintes par un schéma JSON (sorties structurées).
 * En cas de refus pour raison de sécurité, l'API rejoue la demande sur le
 * modèle de secours prévu par Anthropic (« fallbacks: default »).
 */
class ClaudeAssistant implements Assistant
{
    private ?Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client;
    }

    public function ask(string $instructions, string $content, array $schema, string $effort): AssistantReply
    {
        try {
            $message = $this->client()->beta->messages->create(
                maxTokens: 16000,
                messages: [['role' => 'user', 'content' => $content]],
                model: config('services.anthropic.model'),
                fallbacks: 'default',
                outputConfig: [
                    'effort' => $effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                system: $instructions,
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (RateLimitException $exception) {
            Log::warning('Assistant IA : limite de requêtes atteinte.', ['message' => $exception->getMessage()]);

            throw new AssistantUnavailable('L\'assistant est très demandé en ce moment. Réessayez dans quelques minutes.', previous: $exception);
        } catch (APIConnectionException $exception) {
            Log::warning('Assistant IA injoignable.', ['message' => $exception->getMessage()]);

            throw new AssistantUnavailable('L\'assistant est injoignable pour le moment. Réessayez plus tard.', previous: $exception);
        } catch (APIStatusException $exception) {
            Log::error('Assistant IA : erreur de l\'API.', ['type' => $exception->type?->value, 'message' => $exception->getMessage()]);

            throw new AssistantUnavailable('L\'assistant n\'a pas pu traiter la demande.', previous: $exception);
        }

        if ($message->stopReason === 'refusal') {
            throw new AssistantUnavailable('L\'assistant a refusé de traiter cette demande. Reformulez le projet ou contactez l\'équipe IDEE.');
        }

        if ($message->stopReason === 'max_tokens') {
            throw new AssistantUnavailable('La réponse de l\'assistant était trop longue. Réessayez.');
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);

                if (is_array($data)) {
                    return new AssistantReply($data, $message->model, $message->usage->inputTokens, $message->usage->outputTokens);
                }
            }
        }

        throw new AssistantUnavailable('La réponse de l\'assistant est illisible. Réessayez.');
    }

    /**
     * Le client n'est créé qu'au premier appel : sans clé, le reste du site fonctionne.
     */
    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: config('services.anthropic.key'));
    }
}
