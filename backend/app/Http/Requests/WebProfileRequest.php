<?php

namespace App\Http\Requests;

/**
 * Version formulaire du profil : les listes sont saisies séparées par des
 * virgules et la case « J'accepte les demandes » peut être décochée.
 */
class WebProfileRequest extends UpdateProfileRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['skills', 'languages'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => array_values(array_filter(array_map('trim', explode(',', $this->input($field)))))]);
            }
        }

        $this->merge([
            'roles' => $this->input('roles', []),
            'sectors' => $this->input('sectors', []),
            'contact_preferences' => [
                'accepts_requests' => $this->boolean('accepts_requests'),
                'whatsapp_alerts' => $this->boolean('whatsapp_alerts'),
                'weekly_digest' => $this->boolean('weekly_digest'),
            ],
        ]);
    }
}
