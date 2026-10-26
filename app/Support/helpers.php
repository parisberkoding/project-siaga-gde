<?php

use Illuminate\Support\Str;

if (!function_exists('csp_nonce')) {
    /**
     * Generate a Content Security Policy nonce for inline scripts/styles.
     * Used in Blade templates: <script nonce="{{ csp_nonce() }}">
     */
    function csp_nonce(): string
    {
        return base64_encode(random_bytes(16));
    }
}

if (!function_exists('feature_is_production')) {
    /**
     * Apakah sebuah fitur sudah production (boleh dilihat user biasa)?
     *
     * Fitur yang tidak terdaftar dianggap production (fail-open). Dipakai di
     * Blade: @if(feature_is_production('spd.lpj')) ... @endif
     *
     * Catatan: ini hanya menilai TAHAP fitur, BUKAN hak akses user. Pengecekan
     * RBAC dan gate modul unit tetap berjalan terpisah.
     */
    function feature_is_production(?string $code): bool
    {
        return \App\Models\Feature::isProduction($code);
    }
}

if (!function_exists('feature_stage')) {
    /**
     * Tahap sebuah fitur: 'production', 'development', atau null bila belum
     * terdaftar (diperlakukan sebagai production).
     */
    function feature_stage(?string $code): ?string
    {
        return \App\Models\Feature::stageFor($code);
    }
}

if (!function_exists('feature_is_visible')) {
    /**
     * Apakah fitur ini boleh DITAMPILKAN untuk user saat ini?
     *
     * Menggabungkan dua hal:
     *   - fitur production -> selalu tampil,
     *   - fitur development -> hanya tampil bagi pemegang features.manage
     *     (role developers).
     */
    function feature_is_visible(?string $code, ?\App\Models\User $user = null): bool
    {
        if (\App\Models\Feature::isProduction($code)) {
            return true;
        }

        $user ??= auth()->user();

        return $user !== null && $user->hasPermission('features.manage');
    }
}
