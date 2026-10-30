<?php

namespace App\Http\Requests;

use App\Enums\MeetingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposeMeetingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', Rule::enum(MeetingMode::class)],
            'place' => ['nullable', 'required_if:mode,'.MeetingMode::Presentiel->value, 'string', 'max:300'],
            'agenda' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'Choisissez le jour et l\'heure du rendez-vous.',
            'starts_at.date' => 'Choisissez un jour et une heure valides.',
            'starts_at.after' => 'Choisissez une date à venir.',
            'mode.required' => 'Indiquez si le rendez-vous a lieu en visio ou en présentiel.',
            'mode.enum' => 'Indiquez si le rendez-vous a lieu en visio ou en présentiel.',
            'place.required_if' => 'Indiquez le lieu du rendez-vous.',
            'place.max' => 'Le lieu ou le lien tient en 300 caractères au plus.',
            'agenda.max' => 'L\'ordre du jour tient en 2 000 caractères au plus.',
        ];
    }
}
