<?php

namespace App\Http\Resources;

use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil professionnel d'un membre (PM-02).
 *
 * @mixin MemberProfile
 */
class MemberProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label(),
            'title' => $this->title,
            'profession' => [
                'id' => $this->profession->id,
                'slug' => $this->profession->slug,
                'name' => $this->profession->name,
                'category' => $this->profession->category->name,
            ],
            'description' => $this->description,
            'experience_years' => $this->experience_years,
            'offers_paid' => $this->offers_paid,
            'offers_volunteer' => $this->offers_volunteer,
            'offers_mentoring' => $this->offers_mentoring,
            'rate' => $this->rate,
            'is_primary' => $this->is_primary,
            'active' => $this->active,
            // Pour le membre lui-même : son profil est-il visible des autres (PM-05) ?
            'is_public' => $this->isPublic(),
        ];
    }
}
