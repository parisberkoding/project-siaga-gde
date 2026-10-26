<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini (AI Assistant - masih pengembangan / belum stabil)
    |--------------------------------------------------------------------------
    | Model: Gemini 3 Flash Live (function calling untuk query data real-time).
    | API key didapat dari Google AI Studio. Simpan di .env sebagai GEMINI_API_KEY.
    */
    'gemini' => [
        'api_key' => env('GEMINI_CHAT_API_KEY', env('GEMINI_API_KEY')),
        'model'   => env('GEMINI_CHAT_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),
        'url'     => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq (AI Assistant — inference aktif)
    |--------------------------------------------------------------------------
    | OpenAI-compatible endpoint. API key dari https://console.groq.com.
    | Simpan di .env sebagai GROQ_API_KEY. Model default: llama-3.3-70b-versatile.
    */
    'groq' => [
        'api_key'  => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
        'model'    => env('GROQ_CHAT_MODEL', 'llama-3.3-70b-versatile'),
    ],

];
