<?php

namespace App\Http\Requests;

use App\Enums\OrganisationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganisationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:150'],
            'kind' => [$required, Rule::enum(OrganisationKind::class)],
            'description' => [$required, 'string', 'min:30', 'max:3000'],
            'region' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indiquez le nom de l\'organisation.',
            'kind.required' => 'Choisissez le type d\'organisation.',
            'kind.enum' => 'Choisissez le type d\'organisation.',
            'description.required' => 'Présentez l\'organisation en quelques phrases.',
            'description.min' => 'Présentez l\'organisation en au moins 30 caractères.',
            'website.url' => 'Indiquez une adresse de site complète, commençant par https://.',
        ];
    }
}
