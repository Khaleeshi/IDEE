<?php

namespace App\Contracts;

use App\Exceptions\AssistantUnavailable;
use App\Services\Assistant\AssistantReply;

/**
 * Modèle d'IA qui répond en JSON conforme au schéma demandé (section 7).
 */
interface Assistant
{
    /**
     * @param  array<string, mixed>  $schema  schéma JSON de la réponse attendue
     * @param  string  $effort  low, medium ou high : la profondeur de réflexion, donc le coût
     *
     * @throws AssistantUnavailable
     */
    public function ask(string $instructions, string $content, array $schema, string $effort): AssistantReply;
}
