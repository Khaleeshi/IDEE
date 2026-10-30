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
    // Assistant IA (section 7) : clé d'API Anthropic et modèle utilisé.
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
