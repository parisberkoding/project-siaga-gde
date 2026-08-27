<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * GeminiStatusService
 *
 * Menyimpan flag "provider OCR (Gemini) sedang kena rate-limit" di cache
 * agar statusnya bisa ditampilkan ke superadmin/admin/general affairs
 * melalui banner dashboard, tanpa perlu tabel database.
 *
 * Siklus hidup:
 *  - markRateLimited() : dipanggil saat deteksi HTTP 429 / RESOURCE_EXHAUSTED.
 *    Flag kedaluwarsa otomatis setelah imageanalysis.rate_limit_flag_ttl menit.
 *  - markHealthy()     : dipanggil saat ada panggilan analisis yang berhasil,
 *    sehingga banner hilang segera setelah kuota pulih.
 */
class GeminiStatusService
{
    public const CACHE_KEY = 'gemini_rate_limited_at';

    public static function markRateLimited(): void
    {
        $ttlMinutes = max(1, (int) config('imageanalysis.rate_limit_flag_ttl', 60));

        Cache::put(self::CACHE_KEY, now(), now()->addMinutes($ttlMinutes));
    }

    public static function markHealthy(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isActive(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }

    public static function since(): ?Carbon
    {
        $since = Cache::get(self::CACHE_KEY);

        return $since instanceof Carbon ? $since : null;
    }
}
