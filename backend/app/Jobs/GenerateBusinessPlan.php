<?php

namespace App\Jobs;

use App\Models\BusinessPlan;
use App\Services\Assistant\AssistantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rédige en arrière-plan un business plan (IA-02) : la génération peut
 * durer plus longtemps qu'une page web n'attend.
 */
class GenerateBusinessPlan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public BusinessPlan $plan) {}

    public function handle(AssistantService $assistant): void
    {
        $assistant->completeBusinessPlan($this->plan);
    }
}
