<?php

namespace App\Http\Requests;

use App\Enums\OrganisationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddOrganisationMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(OrganisationRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Indiquez le numéro de téléphone ou l\'e-mail du membre.',
            'role.required' => 'Choisissez son rôle.',
            'role.enum' => 'Choisissez son rôle.',
        ];
    }
}
