<?php

namespace App\Services\Sso\Providers;

use App\Services\Sso\Contracts\SsoProvider;
use App\Services\Sso\SsoException;
use App\Services\Sso\SsoUserInfo;
use Illuminate\Http\Request;

/**
 * Adapter placeholder.
 *
 * Dipakai selama protokol SSO belum ditetapkan IT Pusat. Setiap pemanggilan
 * gagal dengan pesan yang menjelaskan APA yang kurang, sehingga tidak ada
 * kegagalan misterius dan tidak ada user yang diam-diam lolos tanpa
 * autentikasi.
 *
 * Menggantinya cukup dengan:
 *   1. buat implementasi SsoProvider untuk protokol yang disepakati,
 *   2. daftarkan di config/sso.php bagian `providers`,
 *   3. set SSO_DRIVER ke kunci adapter tersebut.
 */
class NotConfiguredProvider implements SsoProvider
{
    public function name(): string
    {
        return (string) config('sso.provider_name', 'geodipa');
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function redirectUrl(string $state): string
    {
        throw SsoException::notConfigured();
    }

    public function handleCallback(Request $request): SsoUserInfo
    {
        throw SsoException::notConfigured();
    }
}
