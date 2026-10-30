<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'string', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'project_id' => ['nullable', 'required_without:need_id', 'string', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'need_id' => ['nullable', 'string', Rule::exists('needs', 'id')],
            'message' => ['required', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_id.exists' => 'Ce membre est introuvable.',
            'project_id.required_without' => 'Une demande de contact doit viser un projet ou un besoin précis.',
            'message.required' => 'Présentez votre demande.',
        ];
    }
}
