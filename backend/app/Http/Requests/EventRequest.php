<?php

namespace App\Http\Requests;

use App\Enums\MeetingMode;
use App\Services\Events\EventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Session de pitch organisée par l'équipe ou une organisation vérifiée (EV-01).
 */
class EventRequest extends FormRequest
{
    /**
     * Seuls l'équipe IDEE et les gestionnaires d'une organisation vérifiée organisent.
     */
    public function authorize(EventService $events): bool
    {
        return $events->canOrganise($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organisation_id' => ['nullable', 'string', 'exists:organisations,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:30', 'max:3000'],
            'mode' => ['required', Rule::enum(MeetingMode::class)],
            'starts_at' => ['required', 'date', 'after:now'],
            'place' => ['nullable', 'required_if:mode,'.MeetingMode::Presentiel->value, 'string', 'max:300'],
            'pitch_slots' => ['required', 'integer', 'min:0', 'max:50'],
            'apply_until' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à la session.',
            'description.required' => 'Présentez la session : public visé, déroulé, jury.',
            'description.min' => 'Présentez la session en au moins 30 caractères.',
            'mode.required' => 'Indiquez si la session a lieu en ligne ou en présentiel.',
            'starts_at.required' => 'Choisissez le jour et l\'heure.',
            'starts_at.after' => 'Choisissez une date à venir.',
            'place.required_if' => 'Indiquez le lieu de la session.',
            'pitch_slots.required' => 'Indiquez le nombre de projets qui présenteront (0 si aucun).',
            'apply_until.before_or_equal' => 'La fin des candidatures précède la session.',
        ];
    }
}
