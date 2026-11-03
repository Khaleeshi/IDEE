<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // API SMS d'Orange Sénégal (developer.orange.com) : envoi des codes de connexion.
    'orange_sms' => [
        'client_id' => env('ORANGE_SMS_CLIENT_ID'),
        'client_secret' => env('ORANGE_SMS_CLIENT_SECRET'),
        // Numéro d'expéditeur attribué par Orange, au format tel:+221…
        'sender' => env('ORANGE_SMS_SENDER'),
        // Nom affiché à la place du numéro, s'il a été validé par Orange.
        'sender_name' => env('ORANGE_SMS_SENDER_NAME'),
        'base_url' => env('ORANGE_SMS_BASE_URL', 'https://api.orange.com'),
    ],

    // WhatsApp Business Platform (Cloud API de Meta) : codes envoyés par un
    // modèle de message « authentification » validé par Meta.
    // Notifications push (NO-01) : clés VAPID créées par « php artisan idee:cles-push ».
    'webpush' => [
        'public_key' => env('WEBPUSH_PUBLIC_KEY'),
        'private_key' => env('WEBPUSH_PRIVATE_KEY'),
        'subject' => env('WEBPUSH_SUBJECT', 'mailto:contact@idee.sn'),
    ],

    // Assistant IA (section 7) : clé d'API Anthropic et modèle utilisé.
    // PA-01 : paiements Wave Business (API Checkout).
    'wave' => [
        'key' => env('WAVE_API_KEY'),
        'base_url' => env('WAVE_API_URL', 'https://api.wave.com'),
    ],

    // Bictorys (docs.bictorys.com) : Wave, Orange Money et carte bancaire par une seule API.
    // Clé publique pour créer les paiements et relire leur statut ; secret choisi dans le
    // tableau de bord (Développeurs → Webhooks), renvoyé par Bictorys dans l'en-tête X-Secret-Key.
    'bictorys' => [
        'public_key' => env('BICTORYS_PUBLIC_KEY'),
        'webhook_secret' => env('BICTORYS_WEBHOOK_SECRET'),
        'base_url' => env('BICTORYS_API_URL', 'https://api.bictorys.com'),
        'country' => env('BICTORYS_COUNTRY', 'SN'),
    ],

    // Twilio (API Messages) : SMS des codes de connexion, payés d'avance par le membre.
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        // Numéro ou nom d'expéditeur, ou service de messagerie (MG…) si vous en avez créé un.
        'from' => env('TWILIO_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'base_url' => env('TWILIO_API_URL', 'https://api.twilio.com'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('IDEE_AI_MODEL', 'claude-opus-5-5'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_CODE_TEMPLATE', 'idee_code_connexion'),
        'language' => env('WHATSAPP_CODE_LANGUAGE', 'fr'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        // Modèle « utilitaire » des alertes, avec une variable : le texte de l'alerte.
        'notification_template' => env('WHATSAPP_NOTIFICATION_TEMPLATE', 'idee_alerte'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
