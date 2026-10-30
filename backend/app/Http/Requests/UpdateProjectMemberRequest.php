<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', 'string', 'max:120'],
            'share_percent' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Indiquez son rôle dans le projet.',
            'share_percent.numeric' => 'Indiquez la part envisagée en pourcentage, par exemple 30.',
            'share_percent.min' => 'La part envisagée est comprise entre 0 et 100 %.',
            'share_percent.max' => 'La part envisagée est comprise entre 0 et 100 %.',
        ];
    }
}
