<?php

namespace App\Http\Requests;

class VerifyCodeRequest extends PhoneRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'code' => ['required', 'string', 'digits:'.config('idee.otp.length')],
            'name' => ['nullable', 'string', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'code.required' => 'Saisissez le code reçu.',
            'code.digits' => 'Le code contient '.config('idee.otp.length').' chiffres.',
        ];
    }
}
