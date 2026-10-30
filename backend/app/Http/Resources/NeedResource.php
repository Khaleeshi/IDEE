<?php

namespace App\Http\Resources;

use App\Models\Need;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Need
 */
class NeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'type' => EnumResource::make($this->type),
            'title' => $this->title,
            'description' => $this->description,
            'amount_fcfa' => $this->amount_fcfa,
            'profile_sought' => $this->profile_sought,
            'deadline' => $this->deadline?->toDateString(),
            'counterpart' => $this->counterpart,
            'status' => EnumResource::make($this->status),
            'outcome' => $this->outcome,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'help_offers_count' => $this->whenCounted('helpOffers'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
