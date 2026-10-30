<?php

namespace App\Http\Resources;

use App\Models\StepProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StepProgress
 */
class StepProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'journey_step_id' => $this->journey_step_id,
            'status' => EnumResource::make($this->status),
            'note' => $this->note,
            'remind_on' => $this->remind_on?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
