<?php

namespace App\Http\Resources;

use App\Models\HelpOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HelpOffer
 */
class HelpOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'need_id' => $this->need_id,
            'helper' => new MemberResource($this->whenLoaded('user')),
            'message' => $this->message,
            'kind' => EnumResource::make($this->kind),
            'status' => EnumResource::make($this->status),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
