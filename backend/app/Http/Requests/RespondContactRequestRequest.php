<?php

namespace App\Http\Requests;

use App\Enums\ContactRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondContactRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ContactRequestStatus::class)->only(ContactRequestStatus::responses())],
            'refusal_reason' => ['nullable', 'required_if:status,'.ContactRequestStatus::Refusee->value, 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Choisissez une réponse : intéressé, pas maintenant ou refusée.',
            'refusal_reason.required_if' => 'Indiquez le motif du refus : il aide le porteur à améliorer sa demande.',
        ];
    }
}
