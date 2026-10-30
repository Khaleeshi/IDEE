<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'due_on' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'string', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Indiquez la tâche à faire.',
            'title.max' => 'La tâche tient en 200 caractères au plus.',
            'due_on.date' => 'Choisissez une échéance valide.',
            'assignee_id.exists' => 'Choisissez une personne de la liste.',
        ];
    }
}
