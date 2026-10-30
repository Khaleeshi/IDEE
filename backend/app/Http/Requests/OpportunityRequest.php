<?php

namespace App\Http\Requests;

use App\Enums\OpportunityKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Fiche d'opportunité saisie par l'équipe (AD-02).
 */
class OpportunityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'organisation' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(OpportunityKind::class)],
            'summary' => ['required', 'string', 'max:3000'],
            'criteria' => ['nullable', 'string', 'max:3000'],
            'amount' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'deadline_note' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'source_label' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ];
    }
}
