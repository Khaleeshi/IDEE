<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil public d'un membre : jamais de téléphone ni d'e-mail.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'region' => $this->region,
            'bio' => $this->bio,
            'languages' => $this->languages ?? [],
            'skills' => $this->skills ?? [],
            'sectors' => $this->sectors ?? [],
            'roles' => array_map(EnumResource::make(...), $this->publicRoles()),
            'verification' => EnumResource::make($this->verification_level),
            'member_since' => $this->created_at?->toDateString(),
        ];
    }
}
