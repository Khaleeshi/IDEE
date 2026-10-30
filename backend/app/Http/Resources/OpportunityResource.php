<?php

namespace App\Http\Resources;

use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Opportunity
 */
class OpportunityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organisation' => $this->organisation,
            'kind' => EnumResource::make($this->kind),
            'summary' => $this->summary,
            'criteria' => $this->criteria,
            'amount' => $this->amount,
            'deadline' => $this->deadline?->toDateString(),
            'deadline_note' => $this->deadline_note,
            'url' => $this->url,
            'source' => ['label' => $this->source_label, 'checked_on' => $this->checked_on?->toDateString()],
        ];
    }
}
