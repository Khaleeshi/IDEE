<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['document' => ['required', 'file', 'extensions:jpg,jpeg,png,pdf', 'max:5120']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Joignez une photo lisible de votre carte d\'identité ou de votre passeport.',
            'document.extensions' => 'Envoyez une photo (JPG ou PNG) ou un PDF.',
        ];
    }
}
