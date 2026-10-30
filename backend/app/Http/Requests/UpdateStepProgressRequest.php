<?php

namespace App\Http\Requests;

use App\Enums\StepStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStepProgressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StepStatus::class)],
            'remind_on' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Choisissez où vous en êtes sur cette étape.',
            'remind_on.after_or_equal' => "Le rappel doit tomber aujourd'hui ou plus tard.",
        ];
    }
}
