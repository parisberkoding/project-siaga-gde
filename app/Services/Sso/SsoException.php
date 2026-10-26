<?php

namespace App\Services\Sso;

use RuntimeException;

/**
 * Kegagalan alur SSO yang aman untuk ditampilkan ke user.
 *
 * Pesannya sengaja tidak pernah memuat token, kode otorisasi, atau klaim
 * mentah — hanya penjelasan singkat yang bisa ditindaklanjuti.
 */
class SsoException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self(
            'SSO belum dikonfigurasi. Hubungi Superadmin Pusat untuk melengkapi '
            .'pengaturan SSO (protokol, endpoint, dan klaim yang dikirim).'
        );
    }

    public static function invalidState(): self
    {
        return new self('Permintaan login SSO tidak valid atau sudah kedaluwarsa. Silakan coba lagi.');
    }

    public static function missingSubject(): self
    {
        return new self(
            'SSO tidak mengirimkan identitas unik pengguna. Hubungi Superadmin Pusat '
            .'untuk memeriksa pemetaan klaim identitas.'
        );
    }

    public static function userDisabled(): self
    {
        return new self('Akun Anda dinonaktifkan. Hubungi Superadmin Pusat.');
    }

    public static function exchangeFailed(string $reason): self
    {
        return new self('Gagal memverifikasi login SSO: '.$reason);
    }
}
