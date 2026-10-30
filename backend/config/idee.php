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

    // PR-02 : nombre de personnes affichées dans l'équipe d'un projet.
    'max_team_members' => (int) env('IDEE_MAX_TEAM_MEMBERS', 12),

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

    // RR-04 et OP-03 : alertes enregistrées par membre, et rappels avant la date
    // limite d'une candidature en préparation (en jours).
    'alerts' => [
        'max_saved_searches' => (int) env('IDEE_MAX_SAVED_SEARCHES', 10),
        'deadline_days' => [7, 1],
    ],

    // NO-04 : résumé hebdomadaire par e-mail, aux membres dont l'adresse est confirmée.
    'weekly_digest' => [
        'enabled' => (bool) env('IDEE_WEEKLY_DIGEST', true),
        // Opportunités compatibles dont la date limite tombe dans ce délai (en jours).
        'deadline_window_days' => 30,
    ],

    // MR-04 : pièces jointes de la messagerie (taille maximale en Ko).
    'messages' => [
        'max_document_kb' => (int) env('IDEE_MESSAGE_DOCUMENT_KB', 10240),
        'max_voice_kb' => (int) env('IDEE_MESSAGE_VOICE_KB', 5120),
    ],

    // MR-07 : rappel des rendez-vous confirmés, ce nombre d'heures avant (en heures).
    'collaboration' => [
        'meeting_reminder_hours' => (int) env('IDEE_MEETING_REMINDER_HOURS', 24),
    ],

    // PR-05 : documents privés d'un projet (taille maximale en Ko).
    // CF-05 : version de l'accord de confidentialité à accepter avant d'y accéder ;
    // la changer quand le texte change, pour garder la trace de ce qui a été accepté.
    'documents' => [
        'max_kb' => (int) env('IDEE_DOCUMENT_KB', 10240),
        'nda_version' => '2026-10',
    ],

    // FC-05 : messages par membre et par jour dans les groupes, contre les envois en masse.
    'groups' => [
        'posts_per_day' => (int) env('IDEE_GROUP_POSTS_PER_DAY', 10),
    ],

    // OR-02 : questions propres à un programme, en plus des trois questions communes.
    'programmes' => [
        'max_questions' => 3,
    ],

    // PR-08 : nombre de projets proches proposés au porteur.
    'matching' => [
        'suggestions' => (int) env('IDEE_MATCHING_SUGGESTIONS', 5),
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
        // NO-03 : SMS Orange de secours quand une alerte critique n'a pas pu partir sur WhatsApp.
        'sms_fallback' => (bool) env('IDEE_SMS_FALLBACK', false),
    ],

    // Assistant IA (section 7) : désactivé tant qu'aucune clé n'est fournie.
    // Les plafonds par projet maîtrisent la dépense (environ 100 à 200 FCFA par business plan).
    'ai' => [
        'enabled' => (bool) env('IDEE_AI_ENABLED', false),
        'business_plans_per_month' => (int) env('IDEE_AI_PLANS_PER_MONTH', 3),
        'clarifications_per_day' => (int) env('IDEE_AI_CLARIFICATIONS_PER_DAY', 3),
        // IA-05 et IA-06 : préparations de rendez-vous par membre et par jour, présentations par projet et par mois.
        'meeting_preparations_per_day' => (int) env('IDEE_AI_PREPARATIONS_PER_DAY', 3),
        'pitch_decks_per_month' => (int) env('IDEE_AI_DECKS_PER_MONTH', 3),
    ],

    // RG-10 : une fiche pratique non revue depuis ce délai (en mois) est signalée à l'équipe.
    'guide_review_after_months' => (int) env('IDEE_GUIDE_REVIEW_MONTHS', 6),

    // PG-07 : taille maximale de l'enregistrement audio en wolof d'une fiche (en Ko).
    'guide_audio_max_kb' => (int) env('IDEE_GUIDE_AUDIO_KB', 10240),

    // Section 13 et PA-01 à PA-03 : offres payantes en packs prépayés, sans prélèvement
    // automatique. Prix de départ choisis dans les fourchettes du cahier des charges,
    // à ajuster après le pilote. Prix toutes taxes comprises, en FCFA.
    'offers' => [
        'pro' => [
            'price' => (int) env('IDEE_PRICE_PRO', 5000),
            'days' => 30,
            'weekly_contacts' => 15,
            'saved_searches' => 30,
        ],
        'investisseur' => [
            'price' => (int) env('IDEE_PRICE_INVESTOR', 20000),
            'days' => 30,
            'weekly_contacts' => 40,
            'saved_searches' => 50,
        ],
        // PA-02 et RG-08 : mise en avant signalée « Sponsorisé », sans effet sur le score
        // ni sur l'ordre des recommandations personnalisées.
        'sponsorise' => [
            'price' => (int) env('IDEE_PRICE_SPONSORED_WEEK', 5000),
            'days' => 7,
            'max_weeks' => 4,
            'slots' => 2,
        ],
    ],

    // Moyens de paiement. Wave : API Checkout, clé à créer dans l'espace Wave Business.
    // Orange Money : transfert vers le numéro marchand d'IDEE, vérifié à la main par l'équipe.
    'payments' => [
        'wave' => (bool) env('IDEE_PAY_WAVE', false),
        'orange_money_number' => env('IDEE_ORANGE_MONEY_NUMBER'),
        // Un paiement Wave non confirmé passe « expiré » après ce délai (en heures).
        'expire_after_hours' => 24,
    ],

    // Mentions des reçus et factures (PA-03).
    'company' => [
        'name' => env('IDEE_COMPANY_NAME', 'IDEE'),
        'address' => env('IDEE_COMPANY_ADDRESS'),
        'ninea' => env('IDEE_COMPANY_NINEA'),
        'rccm' => env('IDEE_COMPANY_RCCM'),
    ],

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
