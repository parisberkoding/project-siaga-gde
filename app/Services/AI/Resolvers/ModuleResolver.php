<?php

namespace App\Services\AI\Resolvers;

use App\Services\AI\DataMart\DataMartManager;

/**
 * Menentukan module Data Mart yang paling relevan dengan pertanyaan user
 * menggunakan deterministic keyword scoring, lengkap dengan confidence score.
 *
 * Jika confidence di bawah ambang: JANGAN menebak. Bila ada konteks sesi
 * (pertanyaan lanjutan), modul konteks diutamakan kecuali query mengandung
 * istilah domain (strong term) modul lain yang jelas.
 */
class ModuleResolver
{
    /** @var array<string, list<string>> */
    protected array $aliases = [
        'permohonan_bbm' => [
            'bbm', 'bahan bakar', 'permohonan', 'pengajuan', 'pengeluaran',
            'pemakaian', 'transaksi', 'fuel', 'request bbm', 'pengisian',
            'permintaan bbm', 'transaksi bbm', 'pengeluaran bbm',
        ],
        'vehicle' => [
            'kendaraan', 'armada', 'alat', 'mobil', 'truk', 'vehicle',
            'unit kendaraan', 'kendaraan aktif', 'kapasitas tangki',
        ],
        'driver' => [
            'driver', 'pengemudi', 'pemohon', 'sopir', 'karyawan', 'user',
            'driver bbm',
        ],
        'gasoline' => [
            'harga', 'harga bbm', 'harga pertamax', 'harga dex', 'pertamax',
            'pertamina dex', 'per liter', 'master gasoline', 'spbu', 'liter',
        ],
        'spd' => [
            'spd', 'surat perjalanan dinas', 'surat tugas', 'perjalanan dinas',
            'dinas', 'surat keputusan', 'surat tgs', 'spd keluar', 'surat spd',
        ],
        'lpj' => [
            'lpj', 'laporan pertanggungjawaban', 'laporan keuangan',
            'reimbursement', 'penggantian', 'ganti rugi', 'lpj spd', 'buyback',
        ],
        'lunch' => [
            'makan siang', 'lunch', 'porsi makan', 'makanan', 'katering',
            'makan', 'sarapan', 'snack',
        ],
        'vehicle_parts' => [
            'suku cadang', 'sparepart', 'spare part', 'service kendaraan',
            'perbaikan', 'pergantian', 'penggantian suku cadang', 'vpr',
            'servis', 'montir',
        ],
        'vehicle_check' => [
            'pemeriksaan', 'pemeriksaan mobil', 'periksa mobil', 'checking',
            'inspection', 'jadwal periksa', 'jadwal pemeriksaan', 'pengecekan',
            'cek kendaraan', 'due date kendaraan', 'masa periksa',
            'telat diperiksa', 'telat periksa', 'belum diperiksa',
            'kendaraan diperiksa',
        ],
    ];

    /**
     * Istilah domain kuat (indikator modul) yang TIDAK boleh dikalahkan oleh
     * konteks sesi. Istilah di luar ini dianggap nilai filter (weak term).
     *
     * @var array<string, list<string>>
     */
    protected array $strongTerms = [
        'permohonan_bbm' => [
            'bbm', 'bahan bakar', 'permohonan', 'pengajuan', 'pengeluaran',
            'pemakaian', 'transaksi', 'fuel', 'request bbm', 'pengisian',
            'permintaan bbm',
        ],
        'vehicle' => [
            'kendaraan', 'armada', 'mobil', 'truk', 'vehicle', 'unit kendaraan',
        ],
        'driver' => [
            'driver', 'pengemudi', 'pemohon', 'sopir', 'karyawan',
        ],
        'gasoline' => [
            'harga', 'harga bbm', 'harga pertamax', 'harga dex', 'per liter',
            'spbu', 'master gasoline',
        ],
        'spd' => [
            'spd', 'surat perjalanan dinas', 'surat tugas', 'perjalanan dinas',
            'dinas', 'surat keputusan', 'surat tgs', 'spd keluar', 'surat spd',
        ],
        'lpj' => [
            'lpj', 'laporan pertanggungjawaban', 'laporan keuangan',
            'reimbursement', 'penggantian', 'ganti rugi', 'lpj spd',
        ],
        'lunch' => [
            'makan siang', 'lunch', 'porsi makan', 'makanan', 'katering',
            'makan', 'sarapan',
        ],
        'vehicle_parts' => [
            'suku cadang', 'sparepart', 'spare part', 'service kendaraan',
            'perbaikan', 'pergantian', 'penggantian suku cadang', 'vpr',
            'servis', 'montir',
        ],
        'vehicle_check' => [
            'pemeriksaan', 'pemeriksaan mobil', 'periksa mobil', 'checking',
            'jadwal periksa', 'jadwal pemeriksaan', 'pengecekan',
            'cek kendaraan', 'due date kendaraan', 'masa periksa',
            'telat diperiksa', 'telat periksa', 'belum diperiksa',
            'jadwal periksaan', 'kendaraan diperiksa',
        ],
    ];

    public function __construct(protected DataMartManager $manager) {}

    /**
     * Resolve module paling relevan.
     *
     * @param  array<string, mixed>|null  $context  Sesi sebelumnya: ['module' => string, ...]
     * @return array{module: string, confidence: float, reused?: bool}|null
     */
    public function resolve(string $query, ?array $context = null): ?array
    {
        $normalized = $this->normalize($query);

        if ($normalized === '') {
            return null;
        }

        $best = null;
        $bestWeight = 0;
        $bestPhrase = 0;

        foreach ($this->moduleTerms() as $module => $terms) {
            $weight = 0;
            $maxPhrase = 0;

            foreach ($terms as $term) {
                if (str_contains($normalized, $this->normalize($term))) {
                    $weight += mb_strlen($term) * $this->termBoost($term);
                    $maxPhrase = max($maxPhrase, mb_strlen($term));
                }
            }

            if ($weight > $bestWeight || ($weight === $bestWeight && $maxPhrase > $bestPhrase)) {
                $bestWeight = $weight;
                $bestPhrase = $maxPhrase;
                $best = $module;
            }
        }

        $contextModule = $context['module'] ?? null;

        if ($best === null || $bestWeight === 0) {
            return $this->reuseContext($context);
        }

        $threshold = (float) config('ai.confidence.module_threshold', 0.70);
        $confidence = $this->confidence($bestWeight, mb_strlen($normalized));

        // Pertanyaan lanjutan pada modul yang sama.
        if ($contextModule !== null && $best === $contextModule && $this->manager->has($contextModule)) {
            return [
                'module' => $best,
                'confidence' => round(min(max($confidence, $threshold + 0.05), 1.0), 2),
                'reused' => true,
            ];
        }

        // Follow-up: best hanya karena weak/filter terms + ada konteks sesi => reuse konteks.
        if ($contextModule !== null && $best !== $contextModule && $this->manager->has($contextModule)) {
            if (! $this->hasStrongTerm($normalized, $best)) {
                return $this->reuseContext($context);
            }
        }

        if ($confidence >= $threshold) {
            return ['module' => $best, 'confidence' => round($confidence, 2)];
        }

        return $this->reuseContext($context);
    }

    /**
     * @param  array<string, mixed>|null  $context
     * @return array{module: string, confidence: float, reused: bool}|null
     */
    protected function reuseContext(?array $context): ?array
    {
        $contextModule = $context['module'] ?? null;

        if ($contextModule === null || ! $this->manager->has($contextModule)) {
            return null;
        }

        $confidence = (float) config('ai.confidence.module_threshold', 0.70) + 0.05;

        return ['module' => $contextModule, 'confidence' => round($confidence, 2), 'reused' => true];
    }

    protected function hasStrongTerm(string $normalized, string $module): bool
    {
        foreach ($this->strongTerms[$module] ?? [] as $term) {
            if (str_contains($normalized, $this->normalize($term))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Boost untuk istilah singkat/akronim khas domain (lpj, spd, vpr, bbm)
     * agar query pendek tetap melewati ambang confidence.
     */
    protected function termBoost(string $term): float
    {
        $clean = preg_replace('/[\s\-]/u', '', $term);

        if ($clean !== null && mb_strlen($clean) <= 4 && preg_match('/^[a-z]{2,4}$/u', $clean)) {
            return 3.0;
        }

        return 1.0;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function moduleTerms(): array
    {
        $terms = [];

        foreach ($this->manager->modules() as $module) {
            $dataMart = $this->manager->resolve($module);
            $terms[$module] = array_unique(array_merge(
                $dataMart->keywords(),
                $this->aliases[$module] ?? []
            ));
        }

        return $terms;
    }

    protected function confidence(int $weight, int $queryLength): float
    {
        $base = max(7.0, sqrt($queryLength) * 1.7);

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
