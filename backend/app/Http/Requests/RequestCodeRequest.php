<?php

namespace App\Http\Requests;

class RequestCodeRequest extends PhoneRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required_without:email', 'nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
        ];
    }
}
