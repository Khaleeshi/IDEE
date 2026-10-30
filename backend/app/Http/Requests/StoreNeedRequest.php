<?php

namespace App\Http\Requests;

use App\Enums\NeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNeedRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NeedType::class)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'amount_fcfa' => ['nullable', 'integer', 'min:1000', 'max:100000000000'],
            'profile_sought' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'counterpart' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Précisez le type de besoin.',
            'description.min' => 'Décrivez le besoin en au moins 30 caractères.',
            'deadline.after' => 'L\'échéance doit être dans le futur.',
        ];
    }
}
