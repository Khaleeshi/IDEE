<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposeMatchRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_project_id' => ['required', 'string', 'exists:projects,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_project_id.required' => 'Choisissez le projet à rapprocher du vôtre.',
            'target_project_id.exists' => 'Ce projet n\'existe plus.',
            'message.max' => 'Votre message tient en 500 caractères au plus.',
        ];
    }
}
