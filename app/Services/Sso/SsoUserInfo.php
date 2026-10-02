<?php

namespace App\Services\Sso;

/**
 * Data user yang sudah dinormalisasi dari klaim IdP.
 *
 * Adapter per protokol bertugas mengubah payload mentah (ID token OIDC,
 * assertion SAML, respons OAuth) menjadi objek ini. Seluruh alur provisioning
 * dan mapping hanya bekerja dengan objek ini, sehingga menambah protokol baru
 * tidak menyentuh logika bisnis sama sekali.
 *
 * Field boleh null: sampai IT Pusat memastikan isi klaimnya, kita TIDAK boleh
 * mengasumsikan IdP mengirim unit / jabatan. Semua konsumen wajib menangani
 * nilai null sebagai "tidak diketahui", bukan menebak.
 */
class SsoUserInfo
{
    public function __construct(
        /** ID unik user di IdP — kunci pencocokan utama, tidak pernah berubah. */
        public readonly string $subject,

        public readonly ?string $name = null,
        public readonly ?string $email = null,

        /** Kode unit dari IdP (mis. "PATUHA"). Belum pasti tersedia. */
        public readonly ?string $unitCode = null,

        /** Jabatan dari IdP (mis. "Asisten Manager"). Belum pasti tersedia. */
        public readonly ?string $jabatan = null,

        /** NIK dari IdP/HR. Belum pasti tersedia. */
        public readonly ?string $nik = null,

        public readonly ?string $division = null,

        /** Payload mentah — disimpan utuh untuk keperluan penelusuran. */
        public readonly array $raw = [],

        public readonly string $provider = 'geodipa',
    ) {
    }

    /**
     * Bangun dari klaim mentah memakai pemetaan nama claim di config/sso.php.
     *
     * Semua nama claim bisa diubah lewat konfigurasi, sehingga saat IT Pusat
     * memberi contoh payload yang berbeda, penyesuaiannya tidak menyentuh kode.
     */
    public static function fromClaims(array $claims, ?string $provider = null): self
    {
        $provider ??= (string) config('sso.provider_name', 'geodipa');
        $map = (array) config('sso.claims', []);

        $value = function (string $attribute) use ($claims, $map): ?string {
            $claimName = $map[$attribute] ?? null;

            if ($claimName === null || $claimName === '') {
                return null;
            }

            $raw = $claims[$claimName] ?? null;

            // IdP kadang mengirim array (mis. group/role) — ambil yang pertama
            // agar tidak pecah, dan sisanya tetap tersimpan di `raw`.
            if (is_array($raw)) {
                $raw = $raw[0] ?? null;
            }

            if ($raw === null) {
                return null;
            }

            $raw = trim((string) $raw);

            return $raw === '' ? null : $raw;
        };

        return new self(
            subject: (string) ($value('subject') ?? ''),
            name: $value('name'),
            email: $value('email'),
            unitCode: $value('unit'),
            jabatan: $value('jabatan'),
            nik: $value('nik'),
            division: $value('division'),
            raw: $claims,
            provider: $provider,
        );
    }

    public function hasSubject(): bool
    {
        return $this->subject !== '';
    }

    /**
     * Ringkasan untuk log — JANGAN menyertakan token/secret.
     */
    public function toLogContext(): array
    {
        return [
            'provider' => $this->provider,
            'subject' => $this->subject,
            'name' => $this->name,
            'unit_code' => $this->unitCode,
            'jabatan' => $this->jabatan,
            'has_nik' => $this->nik !== null,
        ];
    }
}
