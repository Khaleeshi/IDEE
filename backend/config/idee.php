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

    // PR-05 : documents privés d'un projet (taille maximale en Ko).
    'documents' => [
        'max_kb' => (int) env('IDEE_DOCUMENT_KB', 10240),
    ],

    // Modération (section 8) : durée d'une suspension et délai pour contester une décision (en jours).
    'moderation' => [
        'suspension_days' => (int) env('IDEE_SUSPENSION_DAYS', 30),
        'appeal_days' => (int) env('IDEE_APPEAL_DAYS', 14),
    ],

    // CF-02 : durée de validité d'une vérification d'identité (en mois).
    'identity_valid_months' => (int) env('IDEE_IDENTITY_VALID_MONTHS', 24),

    // AD-03 : cibles des indicateurs au mois 12 (section 18).
    'indicator_targets' => [
        'useful_response_rate' => 50,
        'reply_rate' => 80,
        'days_to_first_match' => 14,
        'active_projects' => 300,
        'active_members' => 1000,
        'active_investors' => 30,
        'finished_steps' => 100,
        'retention_rate' => 30,
    ],

    // NO-02 : alertes WhatsApp (demandes, rappels, décisions), à activer une fois
    // le modèle « idee_alerte » approuvé par Meta.
    'notifications' => [
        'whatsapp' => (bool) env('IDEE_WHATSAPP_ALERTS', false),
    ],

    // Assistant IA (section 7) : désactivé tant qu'aucune clé n'est fournie.
    // Les plafonds par projet maîtrisent la dépense (environ 100 à 200 FCFA par business plan).
    'ai' => [
        'enabled' => (bool) env('IDEE_AI_ENABLED', false),
        'business_plans_per_month' => (int) env('IDEE_AI_PLANS_PER_MONTH', 3),
        'clarifications_per_day' => (int) env('IDEE_AI_CLARIFICATIONS_PER_DAY', 3),
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
