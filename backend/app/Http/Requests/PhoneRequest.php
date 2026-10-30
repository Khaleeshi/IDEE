<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Normalise le numéro de téléphone avant validation.
 */
abstract class PhoneRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('phone');

        // Le même champ accepte une adresse e-mail (CP-01).
        if (is_string($value) && str_contains($value, '@')) {
            $this->merge(['email' => mb_strtolower(trim($value)), 'phone' => null]);
        } elseif (is_string($value)) {
            $this->merge(['phone' => PhoneNumber::normalize($value) ?? $value]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * Numéro de téléphone ou adresse e-mail à qui envoyer le code.
     */
    public function identifier(): string
    {
        return $this->validated('phone') ?? $this->validated('email');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required_without' => 'Indiquez votre numéro de téléphone ou votre adresse e-mail.',
            'email.email' => 'Adresse e-mail invalide.',
            'phone.regex' => 'Numéro invalide. Exemple : 77 123 45 67, ou +33 6 12 34 56 78 depuis l\'étranger.',
        ];
    }
}
