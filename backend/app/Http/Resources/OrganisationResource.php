<?php

namespace App\Http\Resources;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Page publique d'une organisation : jamais le justificatif de vérification.
 *
 * @mixin Organisation
 */
class OrganisationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => EnumResource::make($this->kind),
            'description' => $this->description,
            'region' => $this->region,
            'website' => $this->website,
            'verification' => EnumResource::make($this->verification_status),
            'rccm' => $this->isVerified() ? $this->rccm : null,
            'ninea' => $this->isVerified() ? $this->ninea : null,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'role' => EnumResource::make($member->role),
                'user' => ['id' => $member->user_id, 'name' => $member->user?->name],
            ])->all()),
        ];
    }
}
