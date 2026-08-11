<?php

namespace App\Services\AI\Resolvers;

/**
 * Memetakan istilah bahasa alami ke konsep semantik:
 * aggregasi, measure, dimensi, dan nilai filter (jenis BBM, status, site).
 */
class KeywordMapper
{
    /** @var array<string, list<string>> */
    protected array $aggregationPatterns = [
        'SUM' => [
            'berapa total pengeluaran', 'total pengeluaran', 'total biaya',
            'jumlah pengeluaran', 'jumlah pemakaian', 'pengeluaran', 'pemakaian',
            'berapa', 'jumlah', 'total', 'sum', 'berapakah', 'banyaknya',
        ],
        'COUNT' => [
            'berapa banyak transaksi', 'berapa banyak', 'jumlah transaksi',
            'jumlah permohonan', 'banyak transaksi', 'banyak permohonan',
            'berapa transaksi', 'berapa kali', 'jumlah request',
        ],
        'AVG' => [
            'rata-rata pengeluaran', 'rata-rata nominal', 'rata rata pengeluaran',
            'rata rata nominal', 'rata-rata', 'rata rata', 'rerata', 'average', 'avg',
        ],
        'MAX' => ['tertinggi', 'terbesar', 'maksimal', 'terbanyak'],
        'MIN' => ['terendah', 'terkecil', 'minimal', 'tersedikit'],
    ];

    /** @var array<string, array<string, list<string>>> */
    protected array $measurePatterns = [
        'permohonan_bbm' => [
            'bill_amounts' => ['pengeluaran', 'biaya', 'nominal', 'rupiah', 'uang', 'dana', 'pemakaian', 'nilai', 'total rupiah'],
            'transaction_count' => ['jumlah transaksi', 'jumlah permohonan', 'banyak transaksi', 'banyak permohonan', 'berapa transaksi'],
        ],
        'gasoline' => [
            'price_per_liter' => ['harga', 'harga per liter', 'liter', 'biaya per liter'],
        ],
        'vehicle' => [
            'tank_liter' => ['kapasitas tangki', 'volume tangki', 'liter tangki', 'tank', 'kapasitas bbm'],
            'nominal_full_tank' => ['benchmark', 'nominal full tank', 'nominal fulltank', 'nominal pengisian'],
            'kapasitas_penumpang' => ['kapasitas penumpang', 'penumpang', 'kursi'],
        ],
        'driver' => [
            'total_requests' => ['jumlah permohonan', 'banyak permohonan', 'jumlah pengajuan', 'total request', 'transaksi terbanyak'],
            'avg_nominal' => ['rata-rata nominal', 'rata rata nominal', 'avg nominal'],
            'report_rate' => ['persentase pelaporan', 'persentase laporan', 'report rate', 'tingkat pelaporan'],
        ],
    ];

    /** @var array<string, list<string>> */
    protected array $gasolineTypes = [
        'Pertamax' => ['pertamax', 'pertalite turbo', 'pertamax turbo', 'perta max'],
        'Pertamina Dex' => ['pertamina dex', 'pertadex', 'dex', 'pertamina deks', 'dexlite'],
        'Solar' => ['solar', 'biosolar', 'biodiesel'],
    ];

    /** @var array<string, string> */
    protected array $statusPatterns = [
        'selesai' => 'completed',
        'completed' => 'completed',
        'disetujui' => 'approved',
        'approved' => 'approved',
        'ditolak' => 'rejected',
        'rejected' => 'rejected',
        'menunggu' => 'pending',
        'pending' => 'pending',
        'diproses' => 'pending',
        'batal' => 'cancelled',
        'cancelled' => 'cancelled',
    ];

    /** @var array<string, list<string>> */
    protected array $siteAliases = [
        'PMU' => ['pmu', 'pusat mesin'],
        'BCMP' => ['bcmp', 'basecamp', 'base camp'],
        'Dieng' => ['dieng'],
        'Pusat' => ['pusat', 'kantor pusat'],
        'Patuha' => ['patuha', 'pth'],
    ];

    /**
     * Fungsi agregasi yang tersirat oleh pertanyaan (pattern terpanjang menang).
     */
    public function aggregation(?string $query): ?string
    {
        if ($query === null) {
            return null;
        }

        $normalized = $this->normalize($query);
        $best = null;
        $bestLength = 0;

        foreach ($this->aggregationPatterns as $function => $patterns) {
            foreach ($patterns as $pattern) {
                $needle = $this->normalize($pattern);
                if ($needle !== '' && mb_strlen($needle) > $bestLength && str_contains($normalized, $needle)) {
                    $best = $function;
                    $bestLength = mb_strlen($needle);
                }
            }
        }

        return $best;
    }

    /**
     * Measure yang relevan untuk module tertentu.
     */
    public function measure(?string $query, string $module): ?string
    {
        $config = $this->measurePatterns[$module] ?? null;
        if ($config === null || $query === null) {
            return null;
        }

        $normalized = $this->normalize($query);
        $best = null;
        $bestWeight = 0;

        foreach ($config as $measure => $patterns) {
            foreach ($patterns as $pattern) {
                $needle = $this->normalize($pattern);
                if ($needle !== '' && str_contains($normalized, $needle) && mb_strlen($needle) > $bestWeight) {
                    $best = $measure;
                    $bestWeight = mb_strlen($needle);
                }
            }
        }

        return $best;
    }

    /**
     * Jenis BBM yang disebut dalam pertanyaan.
     */
    public function gasolineType(?string $query): ?string
    {
        if ($query === null) {
            return null;
        }

        $normalized = $this->normalize($query);
        foreach ($this->gasolineTypes as $type => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($normalized, $this->normalize($alias))) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Status yang disebut dalam pertanyaan.
     */
    public function status(?string $query): ?string
    {
        if ($query === null) {
            return null;
        }

        $normalized = $this->normalize($query);
        foreach ($this->statusPatterns as $alias => $status) {
            if (str_contains($normalized, $this->normalize($alias))) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Site/unit yang disebut dalam pertanyaan (dari alias atau nama asli).
     */
    public function site(?string $query, array $knownSites = []): ?string
    {
        if ($query === null) {
            return null;
        }

        $normalized = $this->normalize($query);

        foreach ($this->siteAliases as $site => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($normalized, $this->normalize($alias))) {
                    return $site;
                }
            }
        }

        foreach ($knownSites as $site) {
            $name = $this->normalize(is_array($site) ? ($site['name'] ?? '') : $site);
            $code = $this->normalize(is_array($site) ? ($site['code'] ?? '') : '');
            if ($name !== '' && str_contains($normalized, $name)) {
                return is_array($site) ? ($site['name'] ?? $site['code']) : $site;
            }
            if ($code !== '' && str_contains($normalized, $code)) {
                return is_array($site) ? ($site['code'] ?? $site['name']) : $site;
            }
        }

        return null;
    }

    protected function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
