<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => EnumResource::make($this->type),
            'actor' => new MemberResource($this->whenLoaded('actor')),
            'project' => [
                'id' => $this->project->id,
                'title' => $this->project->title,
                'summary' => $this->project->summary,
                'region' => $this->project->region,
                'sector' => $this->project->sector?->name,
            ],
            'need' => $this->need ? [
                'id' => $this->need->id,
                'type' => EnumResource::make($this->need->type),
                'title' => $this->need->title,
                'outcome' => $this->need->outcome,
            ] : null,
            'update' => $this->projectUpdate ? new ProjectUpdateResource($this->projectUpdate) : null,
            'collaboration' => $this->collaboration ? new CollaborationResource($this->collaboration) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
