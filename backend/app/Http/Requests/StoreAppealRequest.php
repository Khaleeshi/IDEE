<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['appeal' => ['required', 'string', 'min:20', 'max:2000']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['appeal.min' => 'Expliquez en au moins 20 caractères pourquoi vous contestez cette décision.'];
    }
}
