<?php

namespace App\Http\Resources;

use App\Models\Collaboration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Collaboration entre deux membres : seulement leurs noms, jamais leurs coordonnées.
 *
 * @mixin Collaboration
 */
class CollaborationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'summary' => $this->summary,
            'status' => EnumResource::make($this->status),
            'declared_by' => ['id' => $this->declared_by, 'name' => $this->declarer?->name],
            'partner' => ['id' => $this->partner_id, 'name' => $this->partner?->name],
            'project' => $this->project ? ['id' => $this->project->id, 'title' => $this->project->title] : null,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
        ];
    }
}
