<?php

namespace App\Http\Requests;

use App\Enums\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'min:30', 'max:2000'],
            'details' => ['nullable', 'string', 'max:20000'],
            'sector_id' => ['nullable', 'integer', Rule::exists('sectors', 'id')],
            'region' => ['nullable', 'string', 'max:80'],
            'stage' => ['nullable', Rule::enum(ProjectStage::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à votre projet.',
            'summary.required' => 'Décrivez votre projet en quelques phrases.',
            'summary.min' => 'Le résumé doit contenir au moins 30 caractères.',
        ];
    }
}
