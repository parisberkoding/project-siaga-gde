<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemetaan (jabatan + unit) -> role.
 *
 * Inilah jawaban atas "data kiriman SSO jabatannya Asisten Manager, lalu di
 * tabel yang disiapkan: Asisten Manager itu bisa mengakses apa".
 *
 * PENTING — jabatan hanya menentukan ROLE AWAL, bukan otoritas final:
 *   - Role adalah kumpulan permission; mengubah role di sini mengubah akses
 *     seluruh orang dengan jabatan + unit yang sama.
 *   - Override per user tetap dimungkinkan lewat user_roles, dan override itu
 *     yang menang. Diperlukan karena vocabulary jabatan SSO lebih kasar
 *     daripada struktur nyata (mis. Manager dan Asisten Manager bisa sama-sama
 *     terkirim sebagai "asisten manager"), padahal hak aprovnya berbeda.
 */
class SsoRoleMapping extends Model
{
    protected $table = 'sso_role_mappings';

    protected $fillable = [
        'jabatan',
        'jabatan_key',
        'unit_id',
        'role_id',
        'is_active',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // `jabatan_key` selalu diturunkan dari `jabatan` supaya tidak mungkin
        // tidak sinkron — admin cukup mengisi satu kolom.
        static::saving(function (self $mapping) {
            if ($mapping->isDirty('jabatan') || empty($mapping->jabatan_key)) {
                $mapping->jabatan_key = static::normalizeJabatan((string) $mapping->jabatan);
            }
        });
    }

    /**
     * Bentuk ternormalisasi untuk pencocokan.
     *
     * Jabatan dari SSO adalah teks bebas, sehingga "Asisten manager",
     * "Asisten  Manager", dan " asisten manager " harus dianggap sama.
     */
    public static function normalizeJabatan(?string $jabatan): string
    {
        $jabatan = (string) $jabatan;

        // rapikan spasi ganda, buang spasi di ujung, lalu lowercase
        $jabatan = preg_replace('/\s+/u', ' ', trim($jabatan));

        return mb_strtolower($jabatan);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cari mapping untuk kombinasi jabatan + unit.
     *
     * Mengembalikan null bila belum ada mapping — pemanggil WAJIB memperlakukan
     * null sebagai fail-closed (nol permission + masuk antrian review), bukan
     * sebagai "beri default".
     */
    public static function resolve(?string $jabatan, ?int $unitId): ?self
    {
        $key = static::normalizeJabatan($jabatan);

        if ($key === '' || $unitId === null) {
            return null;
        }

        return static::query()
            ->active()
            ->where('jabatan_key', $key)
            ->where('unit_id', $unitId)
            ->first();
    }
}
