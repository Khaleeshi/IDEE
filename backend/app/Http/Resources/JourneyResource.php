<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class JourneyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'situation' => $this->situation,
            'situation_hint' => $this->situation_hint,
            'title' => $this->title,
            'summary' => $this->summary,
            'steps_count' => $this->whenCounted('steps'),
            'done_steps_count' => $this->whenHas('done_steps_count'),
            'steps' => JourneyStepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
