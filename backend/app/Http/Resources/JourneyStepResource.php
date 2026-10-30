<?php

namespace App\Http\Resources;

use App\Models\JourneyStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JourneyStep
 */
class JourneyStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'title' => $this->title,
            'body' => $this->body,
            'documents' => $this->documents ?? [],
            'organisation' => $this->organisation,
            'cost' => $this->cost,
            'delay' => $this->delay,
            'source' => $this->source_url ? [
                'label' => $this->source_label,
                'url' => $this->source_url,
                'checked_on' => $this->checked_on?->toDateString(),
                'is_stale' => $this->isStale(),
            ] : null,
            'needs_expert' => $this->needs_expert,
            'action' => $this->action,
            'related_journey_id' => $this->related_journey_id,
            // Avancement du membre connecté, quand il a été chargé.
            'progress' => $this->whenLoaded('progress', fn () => $this->progress->isNotEmpty()
                ? new StepProgressResource($this->progress->first())
                : null),
        ];
    }
}
