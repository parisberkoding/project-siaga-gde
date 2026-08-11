<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Data Mart & Retrieval
    |--------------------------------------------------------------------------
    |
    | Konfigurasi AI Analytical Layer: Temporary JSON Data Mart, aggressive
    | deterministic retrieval/aggregation backend, dan konteks RAG untuk Gemini.
    |
    | Prinsip: database adalah source of truth; Data Mart bersifat sementara;
    | laravel menghitung fakta secara deterministik; Gemini hanya menjelaskan.
    |
    */

    'datamart' => [
        /*
         * Aktifkan/nonaktifkan jalur Data Mart pada AI Assistant.
         * Jika false, GeminiAssistantService kembali ke function calling lama.
         */
        'enabled' => env('AI_DATAMART_ENABLED', true),

        /*
         * TTL cache Data Mart sementara (detik). Data Mart TIDAK disimpan permanen.
         */
        'cache_ttl' => (int) env('AI_DATAMART_CACHE_TTL', 300),

        /*
         * Batas maksimum baris yang dimuat ke Data Mart per modul.
         */
        'max_records' => (int) env('AI_DATAMART_MAX_RECORDS', 50000),
    ],

    'rag' => [
        /*
         * TOP-K default untuk deep drill (jumlah record teratas yang diinjeksi).
         */
        'top_k' => (int) env('AI_RAG_TOP_K', 10),

        /*
         * Batas maksimum TOP-K yang diizinkan (diterapkan backend).
         */
        'max_top_k' => (int) env('AI_RAG_MAX_TOP_K', 50),
    ],

    'confidence' => [
        /*
         * Ambang kepercayaan resolusi modul. Di bawah ambang => jangan menebak.
         */
        'module_threshold' => (float) env('AI_MODULE_CONFIDENCE_THRESHOLD', 0.70),

        /*
         * Ambang kepercayaan klasifikasi intent. Di bawah ambang => klarifikasi.
         */
        'intent_threshold' => (float) env('AI_INTENT_CONFIDENCE_THRESHOLD', 0.70),
    ],

    /*
     * Nilai tetap untuk observability: sumber data yang diandalkan.
     */
    'source' => 'database',

];
