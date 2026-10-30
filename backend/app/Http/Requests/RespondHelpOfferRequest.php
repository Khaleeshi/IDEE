<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondHelpOfferRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accepted' => ['required', 'boolean'],
        ];
    }
}
