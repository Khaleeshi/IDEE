<?php

namespace App\Jobs;

use App\Models\IdeaClarification;
use App\Services\Assistant\AssistantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Prépare en arrière-plan les questions de l'assistant (IA-01).
 */
class GenerateClarification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public IdeaClarification $clarification) {}

    public function handle(AssistantService $assistant): void
    {
        $assistant->completeClarification($this->clarification);
    }
}
