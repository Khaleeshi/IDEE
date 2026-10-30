<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposeAgreementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'objective' => ['required', 'string', 'min:5', 'max:300'],
            'due_on' => ['required', 'date', 'after_or_equal:today'],
            'owner_id' => ['required', 'string', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'objective.required' => 'Décrivez la prochaine étape, par exemple « Envoyer le devis du séchoir ».',
            'objective.min' => 'Décrivez la prochaine étape en quelques mots.',
            'objective.max' => 'Résumez la prochaine étape en 300 caractères au plus.',
            'due_on.required' => 'Choisissez une échéance.',
            'due_on.date' => 'Choisissez une échéance valide.',
            'due_on.after_or_equal' => 'L\'échéance ne peut pas être dans le passé.',
            'owner_id.required' => 'Choisissez qui est responsable de l\'étape.',
            'owner_id.exists' => 'Choisissez qui est responsable de l\'étape.',
        ];
    }
}
