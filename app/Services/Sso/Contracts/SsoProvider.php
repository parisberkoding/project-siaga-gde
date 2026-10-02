<?php

namespace App\Services\Sso\Contracts;

use App\Services\Sso\SsoException;
use App\Services\Sso\SsoUserInfo;
use Illuminate\Http\Request;

/**
 * Kontrak adapter SSO.
 *
 * Satu implementasi per protokol (OIDC / OAuth2 / SAML / lainnya). Seluruh
 * logika provisioning, mapping jabatan->role, dan audit TIDAK berada di sini —
 * itu tugas SsoAuthenticator. Adapter hanya bertanggung jawab atas hal-hal
 * yang spesifik protokol:
 *
 *   1. membangun URL redirect ke IdP
 *   2. memvalidasi state/CSRF saat callback
 *   3. menukar kode/assertion menjadi klaim
 *   4. menormalkan klaim menjadi SsoUserInfo
 *
 * Dengan pemisahan ini, keputusan protokol dari IT Pusat hanya menambah satu
 * class — bukan membongkar alur yang sudah berjalan.
 */
interface SsoProvider
{
    /**
     * Nama provider (disimpan di sso_identities.provider).
     */
    public function name(): string;

    /**
     * Apakah konfigurasi adapter sudah lengkap (endpoint, client id, dsb)?
     * Dipakai untuk menolak dengan pesan jelas alih-alih gagal misterius.
     */
    public function isConfigured(): bool;

    /**
     * URL untuk mengarahkan user ke IdP.
     *
     * @param  string  $state  nilai anti-CSRF yang harus ikut dipulangkan IdP
     */
    public function redirectUrl(string $state): string;

    /**
     * Proses callback dari IdP menjadi data user yang sudah dinormalisasi.
     *
     * @throws SsoException bila state tidak sah, kode tidak valid, atau klaim tidak lengkap
     */
    public function handleCallback(Request $request): SsoUserInfo;
}
