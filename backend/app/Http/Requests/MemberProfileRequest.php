<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profil professionnel d'un membre (PM-02, PM-03). Le métier se choisit à la
 * création et ne change plus ensuite : pour un autre métier, on ajoute un profil.
 */
class MemberProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'profession_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('professions', 'id')],
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'offers_paid' => ['sometimes', 'boolean'],
            'offers_volunteer' => ['sometimes', 'boolean'],
            'offers_mentoring' => ['sometimes', 'boolean'],
            'rate' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profession_id.required' => 'Choisissez un métier.',
            'profession_id.exists' => 'Ce métier n\'existe pas dans la liste.',
            'profession_id.prohibited' => 'Le métier d\'un profil ne change pas : ajoutez plutôt un nouveau profil.',
            'title.max' => 'L\'intitulé ne doit pas dépasser 120 caractères.',
            'description.max' => 'La présentation ne doit pas dépasser 2 000 caractères.',
            'experience_years.integer' => 'Indiquez un nombre d\'années, par exemple 5.',
            'experience_years.max' => 'Indiquez un nombre d\'années entre 0 et 60.',
            'rate.max' => 'Le tarif indicatif ne doit pas dépasser 120 caractères.',
        ];
    }
}
