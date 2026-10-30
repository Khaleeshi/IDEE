<?php

namespace App\Services\Assistant;

/**
 * Réponse structurée de l'assistant, avec sa consommation pour le suivi des coûts.
 */
final readonly class AssistantReply
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
    ) {}
}
