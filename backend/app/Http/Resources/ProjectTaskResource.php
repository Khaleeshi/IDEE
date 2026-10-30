<?php

namespace App\Http\Resources;

use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectTask
 */
class ProjectTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'status' => EnumResource::make($this->status),
            'due_on' => $this->due_on?->toDateString(),
            'overdue' => $this->isOverdue(),
            'assignee' => $this->assignee_id ? ['id' => $this->assignee_id, 'name' => $this->assignee?->name] : null,
            'agreement_id' => $this->agreement_id,
            'done_at' => $this->done_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
