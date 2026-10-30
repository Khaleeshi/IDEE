<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclareCollaborationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'min:10', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'summary.required' => 'Dites ce que vous avez accompli ensemble.',
            'summary.min' => 'Décrivez la collaboration en une phrase, par exemple « Premier lot de 200 kg livré à Thiès ».',
            'summary.max' => 'Résumez la collaboration en 300 caractères au plus.',
        ];
    }
}
