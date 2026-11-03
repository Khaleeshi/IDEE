<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

/**
 * Paiement d'un SMS de connexion (CP-07) : le numéro et le moyen de paiement.
 */
class StartSmsPurchaseRequest extends PhoneRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'phone.required' => 'Indiquez votre numéro de téléphone.',
            'method.required' => 'Choisissez un moyen de paiement.',
        ];
    }
}
