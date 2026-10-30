<?php

/*
|--------------------------------------------------------------------------
| Règles de gestion d'IDEE
|--------------------------------------------------------------------------
|
| Valeurs de départ définies dans le cahier des charges (section 6,
| règles RG-01 à RG-12). Elles sont regroupées ici pour pouvoir être
| ajustées après le pilote sans toucher au code.
|
*/

return [

    // RG-01 : nombre maximum de besoins ouverts en même temps par projet.
    'max_open_needs_per_project' => (int) env('IDEE_MAX_OPEN_NEEDS', 10),

    // RG-02 : longueur minimale du message d'une demande de contact.
    'contact_message_min_length' => (int) env('IDEE_CONTACT_MESSAGE_MIN', 100),

    // RG-03 : relance puis expiration des demandes sans réponse (en jours).
    'contact_reminder_after_days' => (int) env('IDEE_CONTACT_REMINDER_DAYS', 7),
    'contact_expire_after_days' => (int) env('IDEE_CONTACT_EXPIRE_DAYS', 14),

    // RG-04 : quota hebdomadaire de demandes de contact selon la vérification.
    'weekly_contact_quota' => [
        'unverified' => (int) env('IDEE_QUOTA_UNVERIFIED', 5),
        'verified' => (int) env('IDEE_QUOTA_VERIFIED', 20),
    ],

    // RG-07 : un projet sans mise à jour depuis ce délai passe « en sommeil ».
    'dormant_after_days' => (int) env('IDEE_DORMANT_DAYS', 90),

    // Indicateur de réactivité : période d'observation (en jours).
    'reactivity_window_days' => (int) env('IDEE_REACTIVITY_WINDOW_DAYS', 90),

    // Code de connexion à usage unique (section 10).
    'otp' => [
        'length' => 6,
        'ttl_minutes' => (int) env('IDEE_OTP_TTL', 10),
        'max_attempts' => (int) env('IDEE_OTP_MAX_ATTEMPTS', 5),
        // Canal d'envoi : « log » en développement ; WhatsApp puis SMS Orange
        // seront branchés ensuite (section 11).
        'driver' => env('IDEE_OTP_DRIVER', 'log'),
    ],

];
