<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI / OCR Analisis Struk (VLM Multimodal) — Gemini via OpenAI-compat
    |--------------------------------------------------------------------------
    |
    | Integrasi vision-language-model untuk membaca & mengkategorikan struk/bon
    | secara otomatis. Semua kredensial diambil dari .env, jangan hardcode.
    |
    | GEMINI_API_KEY   -> API key Google AI Studio (Google Cloud Console)
    | GEMINI_ENDPOINT  -> base URL endpoint OpenAI-compatible Gemini
    | GEMINI_MODEL     -> (opsional) nama model, default gemini-2.0-flash
    |
    | Endpoint OpenAI-compatible Gemini:
    |   https://generativelanguage.googleapis.com/v1beta/openai/
    | Path /chat/completions ditambahkan otomatis oleh service.
    |
    */

    'api_key'  => env('GEMINI_API_KEY'),
    'base_url' => env('GEMINI_ENDPOINT'),
    'endpoint' => env('IMAGE_ANALYSIS_ENDPOINT', '/chat/completions'),
    'model'    => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'timeout'  => (int) env('IMAGE_ANALYSIS_TIMEOUT', 60),

    /*
    | Mode fallback saat API key tidak dikonfigurasi. Jika true, service
    | mengembalikan hasil kosong (kategori Lainnya, tanpa metadata) supaya
    | aplikasi tetap berfungsi dan driver mengisi manual.
    */
    'fallback_enabled' => (bool) env('IMAGE_ANALYSIS_FALLBACK', true),

];
