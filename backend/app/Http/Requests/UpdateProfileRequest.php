<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users')->ignore($this->user())],
            'region' => ['sometimes', 'nullable', 'string', 'max:80'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'languages' => ['sometimes', 'array', 'max:10'],
            'languages.*' => ['string', 'max:40'],
            'skills' => ['sometimes', 'array', 'max:30'],
            'skills.*' => ['string', 'max:60'],
            'sectors' => ['sometimes', 'array', 'max:10'],
            'sectors.*' => ['string', Rule::exists('sectors', 'slug')],
            'roles' => ['sometimes', 'array'],
            'roles.*' => [Rule::enum(UserRole::class)],
            'contact_preferences' => ['sometimes', 'array'],
            'contact_preferences.accepts_requests' => ['boolean'],
        ];
    }
}
