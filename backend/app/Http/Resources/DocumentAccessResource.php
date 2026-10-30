<?php

namespace App\Http\Resources;

use App\Models\DocumentAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentAccess
 */
class DocumentAccessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user' => new MemberResource($this->whenLoaded('user')),
            'status' => EnumResource::make($this->status),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'nda' => $this->nda_accepted_at ? ['version' => $this->nda_version, 'accepted_at' => $this->nda_accepted_at->toIso8601String()] : null,
        ];
    }
}
