<?php

namespace App\Support;

/**
 * Embedding grafik mini di dalam pesan chat (kolom `content` pada SQL).
 *
 * SVG marker `<!--AI_CHART:{json}-->` disisipkan di akhir konten assistant
 * sehingga grafik tersimpan permanen bersama isi percakapan di database dan
 * tetap bisa diakses saat sesi browser selesai / halaman dimuat ulang.
 *
 * Font end memakai AiChartEmbed::has() sebagai identifier untuk mengecek apakah
 * sebuah pesan memiliki kode embedding grafik atau tidak.
 */
class AiChartEmbed
{
    final public const START = '<!--AI_CHART:';

    final public const END = '-->';

    public static function embed(array $chart): string
    {
        return self::START.json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).self::END;
    }

    public static function has(string $content): bool
    {
        return preg_match('/'.preg_quote(self::START, '/').'.*?'.preg_quote(self::END, '/').'/s', $content) === 1;
    }

    public static function extract(string $content): ?array
    {
        if (preg_match('/'.preg_quote(self::START, '/').'(.*?)'.preg_quote(self::END, '/').'/s', $content, $m) !== 1) {
            return null;
        }

        $decoded = json_decode($m[1], true);

        if (! is_array($decoded) || ! isset($decoded['type'], $decoded['labels'], $decoded['values'])) {
            return null;
        }

        return $decoded;
    }

    public static function strip(string $content): string
    {
        $quote = preg_quote(self::START, '/');
        $end = preg_quote(self::END, '/');

        return (string) preg_replace('/\s*'.$quote.'.*?'.$end.'/s', '', $content);
    }
}