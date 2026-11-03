<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required_without:identifier', 'nullable', 'string', 'max:120'],
            // EQ-01 : téléphone ou e-mail d'un membre d'IDEE à inviter.
            'identifier' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:120'],
            'share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required_without' => 'Indiquez le nom de la personne, ou le numéro d\'un membre d\'IDEE à inviter.',
            'role.required' => 'Indiquez son rôle dans le projet, par exemple « Production » ou « Ventes ».',
            'share_percent.numeric' => 'Indiquez la part envisagée en pourcentage, par exemple 30.',
            'share_percent.min' => 'La part envisagée est comprise entre 0 et 100 %.',
            'share_percent.max' => 'La part envisagée est comprise entre 0 et 100 %.',
        ];
    }
}
