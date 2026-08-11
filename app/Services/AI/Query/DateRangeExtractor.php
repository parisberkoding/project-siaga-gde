<?php

namespace App\Services\AI\Query;

use Carbon\Carbon;

/**
 * Mengekstrak rentang tanggal dari teks bahasa alami secara deterministik.
 * Semua tanggal dinormalisasi ke format Y-m-d dengan timezone aplikasi.
 */
class DateRangeExtractor
{
    /** @var array<string, int> */
    protected array $months = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * @return array{from: string, to: string}|null
     */
    public function extract(string $query): ?array
    {
        $text = mb_strtolower(trim($query));

        if ($text === '') {
            return null;
        }

        $today = Carbon::today();

        foreach ([
            'hari ini' => ['from' => $today->copy(), 'to' => $today->copy()],
            'sekarang' => ['from' => $today->copy(), 'to' => $today->copy()],
            'kemarin' => ['from' => $today->copy()->subDay(), 'to' => $today->copy()->subDay()],
            'bulan ini' => ['from' => $today->copy()->startOfMonth(), 'to' => $today->copy()],
            'bulan lalu' => ['from' => $today->copy()->subMonthNoOverflow()->startOfMonth(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'minggu ini' => ['from' => $today->copy()->startOfWeek(), 'to' => $today->copy()],
            'tahun ini' => ['from' => $today->copy()->startOfYear(), 'to' => $today->copy()],
            'tahun lalu' => ['from' => $today->copy()->subYear()->startOfYear(), 'to' => $today->copy()->subYear()->endOfYear()],
            'tahun ini' => ['from' => $today->copy()->startOfYear(), 'to' => $today->copy()],
        ] as $phrase => $range) {
            if (str_contains($text, $phrase)) {
                return $this->format($range['from'], $range['to']);
            }
        }

        if ($explicit = $this->extractExplicitRange($text, $today)) {
            return $explicit;
        }

        if ($month = $this->extractMonthOnly($text, $today)) {
            return $month;
        }

        if ($single = $this->extractSingleDate($text, $today)) {
            return $single;
        }

        return null;
    }

    /**
     * Pola eksplisit: "10 sampai 15 Juli", "tanggal 10-15 agustus 2026",
     * "dari 1 s/d 5 September", "10 sd 15 Juli".
     */
    protected function extractExplicitRange(string $text, Carbon $today): ?array
    {
        $separators = ['sampai', 'hingga', 's\/d', 's\.d\.', 's\.d', 'sd'];

        if (preg_match(
            '/(?:tanggal|tgl|dari|antara)?\s*(\d{1,2})\s*(?:'.implode('|', $separators).'|-)\s*(\d{1,2})\s*('.implode('|', array_keys($this->months)).')(?:\s*(\d{4}))?/iu',
            $text,
            $matches
        )) {
            $dayFrom = (int) $matches[1];
            $dayTo = (int) $matches[2];
            $month = $this->months[mb_strtolower($matches[3])];
            $year = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : $today->year;

            $from = Carbon::createSafe($year, $month, min($dayFrom, $dayTo)) ?? $today;
            $to = Carbon::createSafe($year, $month, max($dayFrom, $dayTo)) ?? $today;

            return $this->format($from, $to);
        }

        return null;
    }

    /**
     * "bulan Juli", "Juli 2026", "agustus" (hanya nama bulan).
     */
    protected function extractMonthOnly(string $text, Carbon $today): ?array
    {
        foreach ($this->months as $name => $number) {
            if (preg_match('/\b'.$name.'(?:\s*(\d{4}))?\b/iu', $text, $matches)) {
                $year = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : $today->year;
                $from = Carbon::createSafe($year, $number, 1) ?? $today;

                return $this->format($from->copy()->startOfMonth(), $from->copy()->endOfMonth());
            }
        }

        return null;
    }

    /**
     * "tanggal 12 Agustus 2026" / "12 agustus 2026".
     */
    protected function extractSingleDate(string $text, Carbon $today): ?array
    {
        if (preg_match('/(?:tanggal|tgl)?\s*(\d{1,2})\s*('.implode('|', array_keys($this->months)).')(?:\s*(\d{4}))?/iu', $text, $matches)) {
            $day = (int) $matches[1];
            $month = $this->months[mb_strtolower($matches[2])];
            $year = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : $today->year;

            $date = Carbon::createSafe($year, $month, $day) ?? $today;

            return $this->format($date, $date);
        }

        return null;
    }

    /**
     * @return array{from: string, to: string}
     */
    protected function format(Carbon $from, Carbon $to): array
    {
        return [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ];
    }
}
