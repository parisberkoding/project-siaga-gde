<?php

namespace App\Services;

use App\Models\MasterGasoline;

/**
 * Benchmark BBM per kendaraan:
 *   raw   = volume_tangki (liter) x harga per liter (region site)
 *   hasil = dibulatkan ke kelipatan 50.000, minimal 50.000, maksimal 3.000.000
 *
 * Opsi dropdown nominal = [benchmark - 500.000 .. benchmark + 500.000]
 * dengan langkah 50.000 (dibatasi batas min/maks).
 */
class FuelBenchmarkService
{
    public const STEP = 50000;
    public const MIN = 50000;
    public const MAX = 3000000;
    public const FLEX = 500000;

    /**
     * Provinsi yang disinkronkan dari bensin-api.
     */
    public const REGIONS = [
        'jawa-barat'  => 'Prov. Jawa Barat',
        'dki-jakarta' => 'Prov. DKI Jakarta',
        'jawa-tengah' => 'Prov. Jawa Tengah',
    ];

    /**
     * Konversi nama jenis bahan bakar di master vehicle -> kode produk API.
     */
    public static function fuelProductCode(?string $fuel): ?string
    {
        if (!$fuel) {
            return null;
        }

        $f = strtoupper(trim($fuel));

        if (str_contains($f, 'PERTAMAX')) {
            return 'PERTAMAX';
        }
        if (str_contains($f, 'DEX')) {
            return 'PERTAMINA DEX';
        }
        if (str_contains($f, 'SOLAR')) {
            return 'PERTAMINA BIOSOLAR SUBSIDI';
        }
        if (str_contains($f, 'BIOSOLAR')) {
            return 'PERTAMINA BIOSOLAR SUBSIDI';
        }

        return null;
    }

    /**
     * Region/slug provinsi untuk sebuah kendaraan (dari unit/site asal).
     * Fallback: jawa-barat.
     */
    public static function regionSlugForVehicle($vehicle): string
    {
        $site = $vehicle->detail?->site;

        return $site?->province_slug ?: 'jawa-barat';
    }

    /**
     * Nilai mentah benchmark (volume tangki x harga) sebelum pembulatan.
     */
    public static function rawForVehicle($vehicle): ?float
    {
        $detail = $vehicle->detail;

        if (!$detail || !$detail->volume_tangki) {
            return null;
        }

        $product = self::fuelProductCode($detail->jenis_bahan_bakar);
        if (!$product) {
            return null;
        }

        $price = MasterGasoline::latestPrice(self::regionSlugForVehicle($vehicle), $product);
        if (!$price) {
            return null;
        }

        return (float) $detail->volume_tangki * (float) $price;
    }

    /**
     * Benchmark final: dibulatkan ke kelipatan 50.000, dibatasi 50.000 - 3.000.000.
     */
    public static function benchmark(?float $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $rounded = round($raw / self::STEP) * self::STEP;

        return (int) max(self::MIN, min(self::MAX, $rounded));
    }

    /**
     * Benchmark untuk kendaraan (aksesor utama dari sisi model/view).
     */
    public static function benchmarkForVehicle($vehicle): ?int
    {
        return self::benchmark(self::rawForVehicle($vehicle));
    }

    /**
     * Rentang nominal yang diizinkan untuk kendaraan (benchmark +/- 500.000).
     * Jika benchmark tidak tersedia, gunakan batas global.
     */
    public static function rangeForBenchmark(?int $benchmark): array
    {
        if ($benchmark === null) {
            return ['min' => self::MIN, 'max' => self::MAX];
        }

        return [
            'min' => max(self::MIN, $benchmark - self::FLEX),
            'max' => min(self::MAX, $benchmark + self::FLEX),
        ];
    }

    /**
     * Opsi nominal (kelipatan 50.000) dalam rentang benchmark +/- 500.000.
     */
    public static function optionsForBenchmark(?int $benchmark): array
    {
        $range = self::rangeForBenchmark($benchmark);

        $options = [];
        for ($value = $range['min']; $value <= $range['max']; $value += self::STEP) {
            $options[] = $value;
        }

        return $options;
    }
}
