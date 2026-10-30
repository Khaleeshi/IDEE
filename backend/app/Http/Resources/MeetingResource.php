<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meeting
 */
class MeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_request_id' => $this->contact_request_id,
            'proposer_id' => $this->proposer_id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'mode' => EnumResource::make($this->mode),
            'place' => $this->place,
            'agenda' => $this->agenda,
            'status' => EnumResource::make($this->status),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
