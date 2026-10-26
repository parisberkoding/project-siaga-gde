<?php

namespace App\Services;

use App\Models\MasterGasoline;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sinkronisasi harga BBM dari bensin-api (GitHub Pages).
 *
 * URL: https://nasgunawann.github.io/bensin-api/v1/provinsi/{province_slug}.json
 * Region: jawa-barat, dki-jakarta, jawa-tengah
 * Hanya produk PERTAMAX & PERTAMINA DEX yang disimpan.
 */
class GasolinePriceSyncService
{
    protected const BASE = 'https://nasgunawann.github.io/bensin-api/v1/provinsi';

    protected const PRODUCTS = ['PERTAMAX', 'PERTAMINA DEX'];

    public function sync(): array
    {
        $result = [
            'total'      => 0,
            'updated'    => 0,
            'unchanged'  => 0,
            'failed'     => 0,
            'errors'     => [],
        ];

        foreach (array_keys(FuelBenchmarkService::REGIONS) as $slug) {
            try {
                $response = Http::timeout(20)->get(self::BASE . '/' . $slug . '.json');

                if (!$response->successful()) {
                    $result['failed']++;
                    $result['errors'][] = $slug . ': HTTP ' . $response->status();
                    continue;
                }

                $data = $response->json();
                $province = $data['province'] ?? (FuelBenchmarkService::REGIONS[$slug] ?? $slug);
                $priceDate = $data['pertamina_updated_at'] ?? null;

                foreach ($data['products'] ?? [] as $product) {
                    $fuelType = strtoupper(trim($product['product'] ?? ''));
                    if (!in_array($fuelType, self::PRODUCTS, true)) {
                        continue;
                    }

                    $price = $product['price_rupiah'] ?? null;
                    if ($price === null) {
                        continue;
                    }

                    $result['total']++;

                    $existing = MasterGasoline::where('province_slug', $slug)
                        ->where('fuel_type', $fuelType)
                        ->first();

                    if ($existing && (int) $existing->price_per_liter === (int) $price) {
                        $result['unchanged']++;
                        continue;
                    }

                    MasterGasoline::updateOrCreate(
                        ['province_slug' => $slug, 'fuel_type' => $fuelType],
                        [
                            'province'         => $province,
                            'price_per_liter'  => (int) $price,
                            'availability'     => $product['availability'] ?? 'available',
                            'price_date'       => $priceDate ? Carbon::parse($priceDate) : now(),
                            'synced_at'        => now(),
                        ]
                    );

                    $result['updated']++;
                }
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][] = $slug . ': ' . $e->getMessage();
            }
        }

        return $result;
    }
}
