<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Services\Projects\ReadinessScore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Les détails privés et les conseils de préparation ne sont visibles
 * que du porteur du projet (section 8, deux couches d'information).
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $this->isOwnedBy($request->user('sanctum'));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'details' => $this->when($isOwner, $this->details),
            'sector' => new SectorResource($this->whenLoaded('sector')),
            'region' => $this->region,
            'stage' => EnumResource::make($this->stage),
            'status' => EnumResource::make($this->status),
            'readiness_score' => $this->readiness_score,
            'readiness_advice' => $this->when($isOwner, fn () => app(ReadinessScore::class)->evaluate($this->resource)['missing']),
            'owner' => new MemberResource($this->whenLoaded('owner')),
            'open_needs' => NeedResource::collection($this->whenLoaded('openNeeds')),
            // PR-07 : les parts envisagées restent visibles du seul porteur.
            'team' => $this->whenLoaded('team', fn () => $this->team->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
                ...($this->isOwnedBy($request->user('sanctum')) ? ['share_percent' => $member->share_percent] : []),
            ])->all()),
            'is_owner' => $isOwner,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
