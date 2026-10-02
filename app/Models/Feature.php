<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Fitur beserta tahap rilisnya (item 6).
 *
 * Menjawab: "halaman/fitur ini sudah PRODUCTION atau masih DEVELOPMENT?"
 *
 * Aturan yang dipegang:
 *
 *   1. Fitur yang TIDAK terdaftar = production (fail-open). Kalau fail-closed,
 *      setiap halaman yang lupa didaftarkan akan hilang dan terlihat seperti
 *      outage saat deploy.
 *   2. stage 'development' -> hanya pemegang permission features.manage
 *      (role `developers`) yang boleh melihat.
 *   3. stage 'production'  -> tunduk aturan normal (RBAC + gate modul unit).
 *
 * Tahap fitur TIDAK menggantikan RBAC maupun gate modul: ketiganya bertingkat.
 */
class Feature extends Model
{
    public const STAGE_DEVELOPMENT = 'development';

    public const STAGE_PRODUCTION = 'production';

    private const CACHE_KEY = 'features.stage_map';

    private const VERSION_KEY = 'features.version';

    protected $table = 'features';

    protected $fillable = [
        'code',
        'name',
        'description',
        'module_id',
        'stage',
        'activated_at',
        'activated_by',
        'note',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    /* =========================================================
     * Relasi
     * =======================================================*/

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    /**
     * User yang menaikkan fitur ini ke production.
     */
    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /* =========================================================
     * Resolusi tahap (dipakai pada setiap request -> di-cache)
     * =======================================================*/

    /**
     * Peta code => stage untuk seluruh fitur terdaftar.
     *
     * Tabelnya kecil dan jarang berubah, sehingga cukup di-cache dan
     * di-invalidasi lewat flushCache() saat ada perubahan.
     *
     * @return array<string, string>
     */
    public static function stageMap(): array
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        return Cache::rememberForever(self::CACHE_KEY.'.v'.$version, function () {
            return static::query()->pluck('stage', 'code')->all();
        });
    }

    /**
     * Tahap sebuah fitur. null = belum terdaftar (dianggap production).
     */
    public static function stageFor(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return static::stageMap()[$code] ?? null;
    }

    /**
     * Apakah fitur ini boleh dilihat oleh user biasa?
     *
     * true bila: belum terdaftar (fail-open) ATAU sudah production.
     */
    public static function isProduction(?string $code): bool
    {
        return static::stageFor($code) !== self::STAGE_DEVELOPMENT;
    }

    /**
     * Apakah fitur ini terdaftar dan masih development?
     */
    public static function isRestricted(?string $code): bool
    {
        return static::stageFor($code) === self::STAGE_DEVELOPMENT;
    }

    /**
     * WAJIB dipanggil setiap kali stage berubah, kalau tidak perubahan tidak
     * akan terbaca sampai cache kedaluwarsa.
     */
    public static function flushCache(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
    }

    /* =========================================================
     * Perubahan tahap
     * =======================================================*/

    public function activate(?int $byUserId = null, ?string $note = null): void
    {
        $this->stage = self::STAGE_PRODUCTION;
        $this->activated_at = now();
        $this->activated_by = $byUserId;

        if ($note !== null) {
            $this->note = $note;
        }

        $this->save();
        static::flushCache();
    }

    public function deactivate(?int $byUserId = null, ?string $note = null): void
    {
        $this->stage = self::STAGE_DEVELOPMENT;
        $this->activated_at = null;
        $this->activated_by = $byUserId;

        if ($note !== null) {
            $this->note = $note;
        }

        $this->save();
        static::flushCache();
    }

    public function isProductionStage(): bool
    {
        return $this->stage === self::STAGE_PRODUCTION;
    }
}
