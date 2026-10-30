<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil complet du membre connecté.
 *
 * @mixin User
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new MemberResource($this->resource))->toArray($request),
            'phone' => $this->phone,
            'email' => $this->email,
            'roles' => array_map(EnumResource::make(...), $this->roles()),
            'contact_preferences' => $this->contact_preferences ?? ['accepts_requests' => true],
            'weekly_contact_quota' => $this->weeklyContactQuota(),
            'contact_requests_sent_this_week' => $this->sentContactRequests()->where('created_at', '>=', now()->subWeek())->count(),
        ];
    }
}
