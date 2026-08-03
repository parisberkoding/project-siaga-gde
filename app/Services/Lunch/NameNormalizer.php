<?php

namespace App\Services\Lunch;

/**
 * Normalisasi input nama untuk Identity Resolution.
 *
 * Semua perbandingan (alias lookup, employee lookup, fuzzy match)
 * dilakukan terhadap bentuk ternormalisasi, sehingga "Pak DEDI  S."
 * dan "dedi s" bertemu di titik yang sama.
 */
class NameNormalizer
{
    /**
     * Sapaan/gelar umum yang tidak membedakan identitas.
     */
    private const HONORIFICS = [
        'bapak', 'bpk', 'pak', 'ibu', 'bu', 'mas', 'mbak', 'sdr', 'sdri',
        'tuan', 'nyonya', 'nn', 'ny', 'tn',
    ];

    public static function normalize(?string $raw): string
    {
        $value = mb_strtolower(trim((string) $raw));

        // Buang tanda baca — sisakan huruf, angka, dan spasi.
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);

        // Rapikan spasi ganda.
        $value = preg_replace('/\s+/', ' ', trim($value));

        // Buang sapaan di posisi kata pertama saja ("pak dedi" → "dedi",
        // tapi "dedi pakpahan" tidak tersentuh).
        $words = explode(' ', $value);
        while (count($words) > 1 && in_array($words[0], self::HONORIFICS, true)) {
            array_shift($words);
        }

        return implode(' ', $words);
    }

    /**
     * Skor kemiripan 0–100 antara dua string yang sudah dinormalisasi.
     */
    public static function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 100.0;
        }

        // Prefix match kuat: "dedi" vs "dedi supriadi".
        if (str_starts_with($b, $a . ' ') || str_starts_with($a, $b . ' ')) {
            return 90.0;
        }

        // Kata utuh yang terkandung: "dedi" vs "muhamad dedi".
        $wordsA = explode(' ', $a);
        $wordsB = explode(' ', $b);
        if (count(array_intersect($wordsA, $wordsB)) > 0
            && (count($wordsA) === 1 || count($wordsB) === 1)) {
            return 85.0;
        }

        similar_text($a, $b, $percent);

        return round($percent, 2);
    }
}
