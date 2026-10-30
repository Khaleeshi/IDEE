<?php

namespace App\Http\Resources;

use App\Models\Agreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Agreement
 */
class AgreementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_request_id' => $this->contact_request_id,
            'project_id' => $this->project_id,
            'objective' => $this->objective,
            'due_on' => $this->due_on->toDateString(),
            'owner' => ['id' => $this->owner_id, 'name' => $this->owner?->name],
            'proposer_id' => $this->proposer_id,
            'status' => EnumResource::make($this->status),
            'overdue' => $this->isOverdue(),
            'done_at' => $this->done_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
