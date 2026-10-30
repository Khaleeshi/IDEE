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

    // RR-02 / RR-03 : recommandations quotidiennes selon le profil, chacune expliquée.
    // Les poids classent les raisons : rôle, compétence, secteur, suivi, région.
    'recommendations' => [
        'per_day' => (int) env('IDEE_RECOMMENDATIONS_PER_DAY', 5),
        'weights' => ['role' => 3, 'skill' => 3, 'sector' => 2, 'followed' => 2, 'region' => 1],
    ],

    // MR-04 : pièces jointes de la messagerie (taille maximale en Ko).
    'messages' => [
        'max_document_kb' => (int) env('IDEE_MESSAGE_DOCUMENT_KB', 10240),
        'max_voice_kb' => (int) env('IDEE_MESSAGE_VOICE_KB', 5120),
    ],

    // RG-10 : une fiche pratique non revue depuis ce délai (en mois) est signalée à l'équipe.
    'guide_review_after_months' => (int) env('IDEE_GUIDE_REVIEW_MONTHS', 6),

    // Code de connexion à usage unique (section 10).
    'otp' => [
        'length' => 6,
        'ttl_minutes' => (int) env('IDEE_OTP_TTL', 10),
        'max_attempts' => (int) env('IDEE_OTP_MAX_ATTEMPTS', 5),
        // Canal d'envoi : log (développement), sms (Orange), whatsapp, ou
        // whatsapp_sms (WhatsApp d'abord, SMS Orange en secours).
        'driver' => env('IDEE_OTP_DRIVER', 'log'),
    ],

];
