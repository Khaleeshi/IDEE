<?php

namespace App\Http\Requests;

use App\Enums\OpportunityKind;
use App\Enums\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Programme publié par une organisation vérifiée (OR-01).
 */
class ProgrammeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(OpportunityKind::class)],
            'summary' => ['required', 'string', 'max:3000'],
            'criteria' => ['nullable', 'string', 'max:3000'],
            'stages' => ['nullable', 'array'],
            'stages.*' => [Rule::enum(ProjectStage::class)],
            'sectors' => ['nullable', 'array'],
            'sectors.*' => ['string', Rule::exists('sectors', 'slug')],
            'max_amount_fcfa' => ['nullable', 'integer', 'min:0'],
            'amount' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'deadline_note' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'accepts_applications' => ['boolean'],
            'application_questions' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Indiquez le nom du programme.',
            'kind.required' => 'Choisissez le type de programme.',
            'summary.required' => 'Présentez le programme.',
            'deadline.after_or_equal' => 'La date limite ne peut pas être passée.',
            'url.url' => 'Indiquez une adresse complète, commençant par https://.',
        ];
    }
}
