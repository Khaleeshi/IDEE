<?php

namespace App\Jobs;

use App\Models\AssistantDocument;
use App\Services\Assistant\AssistantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rédige en arrière-plan une préparation de rendez-vous ou une présentation (IA-05, IA-06).
 */
class GenerateAssistantDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public AssistantDocument $document) {}

    public function handle(AssistantService $assistant): void
    {
        $assistant->completeDocument($this->document);
    }
}
