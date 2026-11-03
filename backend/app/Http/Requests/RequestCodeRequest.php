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
            // CP-07 : WhatsApp (gratuit) ou SMS payé d'avance, pour un numéro.
            'channel' => ['nullable', 'in:whatsapp,sms'],
            'sms_purchase_id' => ['nullable', 'string', 'max:40'],
        ];
    }
}
