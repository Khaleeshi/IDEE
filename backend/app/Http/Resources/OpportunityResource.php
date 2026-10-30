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
            // OR-01 : page de l'organisation vérifiée qui a publié le programme.
            'organisation_id' => $this->organisation_id,
            'kind' => EnumResource::make($this->kind),
            'summary' => $this->summary,
            'criteria' => $this->criteria,
            // OP-02 : critères structurés ; une liste vide signifie « ouvert à tous ».
            'eligibility_criteria' => [
                'stages' => $this->stages ?? [],
                'sectors' => $this->sectors ?? [],
                'max_amount_fcfa' => $this->max_amount_fcfa,
            ],
            'eligibility' => $this->when($this->resource->getAttribute('eligibility') !== null, fn () => $this->resource->getAttribute('eligibility')),
            'amount' => $this->amount,
            'deadline' => $this->deadline?->toDateString(),
            'deadline_note' => $this->deadline_note,
            'url' => $this->url,
            'source' => ['label' => $this->source_label, 'checked_on' => $this->checked_on?->toDateString()],
        ];
    }
}
