<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Réponse oui ou non à une proposition : étape, rendez-vous, collaboration.
 */
class DecisionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accepted' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accepted.required' => 'Choisissez votre réponse.',
            'accepted.boolean' => 'Choisissez votre réponse.',
        ];
    }
}
