<?php

namespace App\Http\Requests;

use App\Services\Members\AccountService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DeleteAccountRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['confirmation' => Str::upper(trim((string) $this->input('confirmation')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', 'in:'.AccountService::CONFIRMATION]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $message = 'Écrivez '.AccountService::CONFIRMATION.' pour confirmer la suppression.';

        return ['confirmation.required' => $message, 'confirmation.in' => $message];
    }
}
