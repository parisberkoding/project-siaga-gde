<?php

namespace App\Services\AI\Resolvers;

/**
 * Mengklasifikasikan intent pertanyaan user secara deterministik.
 *
 * Intent: summary, aggregation, detail, listing, comparison, trend, lookup, unknown.
 */
class IntentResolver
{
    /** @var array<string, list<string>> */
    protected array $patterns = [
        'comparison' => [
            'bandingkan', 'perbandingan', 'versus', 'selisih', 'lebih besar',
            'lebih banyak', 'lebih murah', 'lebih mahal', 'vs ',
        ],
        'trend' => [
            'tren', 'trend', 'pertumbuhan', 'meningkat', 'menurun',
            'naik', 'turun', 'per bulan', 'bulanan', 'dari waktu ke waktu',
            'kecenderungan', 'pola',
        ],
        'aggregation' => [
            'berapa', 'total', 'jumlah', 'sum', 'pengeluaran', 'pemakaian',
            'rata-rata', 'average', 'rerata', 'total pengeluaran', 'total biaya',
            'berapa total', 'jumlah total', 'berapakah', 'banyaknya',
        ],
        'lookup' => [
            'harga', 'berapa harga', 'daftar harga', 'update harga',
            'harga per liter', 'master harga',
        ],
        'listing' => [
            'daftar', 'list', 'sebutkan', 'apa saja', 'apa aja',
            'nama-nama', 'jenis-jenis', 'semua',
        ],
        'detail' => [
            'tampilkan', 'transaksi', 'rincian', 'terbesar', 'tertinggi',
            'terbanyak', 'terkecil', 'terendah', 'riwayat', 'rekaman',
            'catatan', 'detail',
        ],
        'summary' => [
            'ringkas', 'rangkum', 'ringkasan', 'dashboard', 'statistik',
            'rekap', 'ikhtisar', 'gambaran', 'kesimpulan',
        ],
    ];

    /**
     * @return array{intent: string, confidence: float}
     */
    public function resolve(string $query): array
    {
        $normalized = $this->normalize($query);

        if ($normalized === '') {
            return ['intent' => 'unknown', 'confidence' => 0.0];
        }

        $bestIntent = 'unknown';
        $bestWeight = 0;
        $bestPhrase = 0;

        foreach ($this->patterns as $intent => $phrases) {
            $weight = 0;
            $maxPhrase = 0;

            foreach ($phrases as $phrase) {
                if ($phrase === 'vs ') {
                    $found = (bool) preg_match('/\bvs\b/u', $normalized);
                } else {
                    $found = str_contains($normalized, $this->normalize($phrase));
                }

                if ($found) {
                    $weight += mb_strlen($phrase);
                    $maxPhrase = max($maxPhrase, mb_strlen($phrase));
                }
            }

            if ($weight > $bestWeight || ($weight === $bestWeight && $maxPhrase > $bestPhrase)) {
                $bestWeight = $weight;
                $bestPhrase = $maxPhrase;
                $bestIntent = $intent;
            }
        }

        $confidence = $this->confidence($bestWeight, mb_strlen($normalized));

        return ['intent' => $bestIntent, 'confidence' => round($confidence, 2)];
    }

    protected function confidence(int $weight, int $queryLength): float
    {
        if ($weight === 0) {
            return 0.0;
        }

        $base = max(6.0, sqrt($queryLength) * 1.6);

        return min(1.0, $weight / $base);
    }

    protected function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
