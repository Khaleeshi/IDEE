<?php

namespace App\Http\Resources;

use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContactRequest
 */
class ContactRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => new MemberResource($this->whenLoaded('sender')),
            'recipient' => new MemberResource($this->whenLoaded('recipient')),
            'project' => $this->whenLoaded('project', fn () => $this->project ? [
                'id' => $this->project->id,
                'title' => $this->project->title,
            ] : null),
            'need' => $this->whenLoaded('need', fn () => $this->need ? [
                'id' => $this->need->id,
                'title' => $this->need->title,
                'type' => EnumResource::make($this->need->type),
            ] : null),
            'message' => $this->message,
            'status' => EnumResource::make($this->status),
            'refusal_reason' => $this->refusal_reason,
            'read_at' => $this->read_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
