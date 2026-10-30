<?php

namespace App\Http\Requests;

use App\Enums\HelpOfferKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpOfferRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'kind' => ['nullable', Rule::enum(HelpOfferKind::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Expliquez comment vous pouvez aider.',
            'message.min' => 'Expliquez comment vous pouvez aider en au moins 20 caractères.',
        ];
    }

    public function kind(): HelpOfferKind
    {
        return HelpOfferKind::tryFrom((string) $this->validated('kind')) ?? HelpOfferKind::Aide;
    }
}
