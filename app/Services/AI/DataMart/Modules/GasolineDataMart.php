<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\MasterGasoline;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart harga BBM (dimension/lookup table).
 *
 * Sumber: MasterGasoline (province, fuel_type, price_per_liter).
 */
class GasolineDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'gasoline';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart harga BBM per liter per provinsi (dim_gasoline)';
    }

    public function keywords(): array
    {
        return [
            'harga',
            'harga bbm',
            'harga pertamax',
            'harga dex',
            'pertamax',
            'pertamina dex',
            'per liter',
            'master gasoline',
            'spbu',
        ];
    }

    public function entities(): array
    {
        return [
            'province',
            'province_slug',
            'fuel_type',
            'price_date',
        ];
    }

    public function dimensions(): array
    {
        return [
            'province',
            'province_slug',
            'fuel_type',
            'price_date',
        ];
    }

    public function measures(): array
    {
        return [
            'price_per_liter',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'price_date';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = MasterGasoline::query()->orderBy('province_slug')->orderBy('fuel_type');

        if (! empty($filters['province'])) {
            $query->whereIn('province', (array) $filters['province']);
        }
        if (! empty($filters['province_slug'])) {
            $query->whereIn('province_slug', (array) $filters['province_slug']);
        }
        if (! empty($filters['fuel_type'])) {
            $query->whereIn('fuel_type', array_map(
                fn ($fuel) => mb_strtoupper((string) $fuel),
                (array) $filters['fuel_type']
            ));
        }

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (MasterGasoline $m) => [
                'province' => $m->province,
                'province_slug' => $m->province_slug,
                'fuel_type' => ucwords(mb_strtolower($m->fuel_type)),
                'price_per_liter' => (int) $m->price_per_liter,
                'price_date' => $m->price_date?->toDateString(),
            ])->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    public function summary(array $records = []): array
    {
        return [
            'total_records' => count($records),
            'distinct_provinces' => collect($records)->pluck('province')->unique()->count(),
            'distinct_fuels' => collect($records)->pluck('fuel_type')->unique()->values()->all(),
            'avg_price_per_liter' => count($records) > 0
                ? round(collect($records)->avg('price_per_liter'), 0)
                : 0,
        ];
    }
}
