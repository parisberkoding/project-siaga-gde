<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\VehicleAndToolsConsumers;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart master kendaraan/alat (dimension table).
 *
 * Sumber: VehicleAndToolsConsumers + VehicleDetail + Site.
 */
class VehicleDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'vehicle';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart master kendaraan dan alat (dim_vehicle)';
    }

    public function keywords(): array
    {
        return [
            'kendaraan',
            'armada',
            'alat',
            'mobil',
            'truk',
            'vehicle',
            'unit kendaraan',
            'kendaraan aktif',
            'jumlah kendaraan',
            'kapasitas tangki',
        ];
    }

    public function entities(): array
    {
        return [
            'vehicle',
            'site',
            'fuel_type',
            'consumer_type',
            'active_status',
        ];
    }

    public function dimensions(): array
    {
        return [
            'vehicle_code',
            'site',
            'fuel_type',
            'consumerial_type',
            'is_active',
            'is_approved',
        ];
    }

    public function measures(): array
    {
        return [
            'tank_liter',
            'kapasitas_penumpang',
            'nominal_full_tank',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = VehicleAndToolsConsumers::query()
            ->with(['detail.site:id,name,code'])
            ->orderBy('consumerial_name');

        if (! empty($filters['consumerial_type'])) {
            $query->whereIn('consumerial_type', (array) $filters['consumerial_type']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (array_key_exists('is_approved', $filters) && $filters['is_approved'] !== null) {
            $query->where('is_approved', (bool) $filters['is_approved']);
        }
        if (! empty($filters['vehicle_code'])) {
            $query->whereIn('consumerial_code', (array) $filters['vehicle_code']);
        }

        $rows = $query->limit($this->maxRecords())->get();

        $records = $rows->map(function (VehicleAndToolsConsumers $v) {
            $detail = $v->detail;

            return [
                'vehicle_code' => $v->consumerial_code,
                'vehicle_name' => $v->consumerial_name,
                'vehicle' => trim("{$v->consumerial_code} - {$v->consumerial_name}"),
                'consumerial_type' => $v->consumerial_type,
                'site' => $detail?->site?->name,
                'fuel_type' => $detail?->jenis_bahan_bakar,
                'tank_liter' => $detail?->volume_tangki !== null ? (float) $detail->volume_tangki : null,
                'kapasitas_penumpang' => $detail?->kapasitas_penumpang,
                'nominal_full_tank' => $detail?->nominal_full_tank !== null ? (float) $detail->nominal_full_tank : null,
                'is_active' => (bool) $v->is_active,
                'is_approved' => (bool) $v->is_approved,
                'active_status' => $v->is_active ? 'aktif' : 'nonaktif',
            ];
        })->filter(fn ($r) => $this->passesInMemoryFilters($r, $filters))->values()->all();

        return $this->bound($records, (int) ($options['limit'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $filters
     */
    protected function passesInMemoryFilters(array $record, array $filters): bool
    {
        if (! empty($filters['site'])) {
            $site = $record['site'] ?? null;
            if ($site === null || ! $this->valueMatches($site, $filters['site'])) {
                return false;
            }
        }
        if (! empty($filters['fuel_type'])) {
            if (! $this->valueMatches($record['fuel_type'] ?? '', $filters['fuel_type'])) {
                return false;
            }
        }

        return true;
    }

    protected function valueMatches(?string $value, string|array $needles): bool
    {
        $value = mb_strtolower(trim((string) $value));
        foreach ((array) $needles as $needle) {
            if (mb_str_contains($value, mb_strtolower(trim((string) $needle)))) {
                return true;
            }
        }

        return false;
    }

    public function summary(array $records = []): array
    {
        $active = collect($records)->where('is_active', true)->count();
        $byType = collect($records)->groupBy('consumerial_type')->map->count();

        return [
            'total_records' => count($records),
            'active' => (int) $active,
            'inactive' => count($records) - (int) $active,
            'counts_by_type' => $byType->all(),
        ];
    }
}
