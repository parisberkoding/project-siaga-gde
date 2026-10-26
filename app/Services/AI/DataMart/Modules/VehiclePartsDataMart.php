<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\VehiclePartsReplacement;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart suku cadang kendaraan / perbaikan (fact table).
 *
 * Sumber: VehiclePartsReplacement + VehiclePartsReplacementTransaction (service).
 */
class VehiclePartsDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'vehicle_parts';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart suku cadang dan perbaikan kendaraan (fact_vehicle_parts)';
    }

    public function keywords(): array
    {
        return [
            'suku cadang',
            'sparepart',
            'spare part',
            'service kendaraan',
            'perbaikan',
            'pergantian',
            'penggantian suku cadang',
            'vpr',
            'servis',
            'montir',
        ];
    }

    public function entities(): array
    {
        return [
            'vehicle',
            'nopol',
            'status',
            'dispatcher',
        ];
    }

    public function dimensions(): array
    {
        return [
            'month',
            'vehicle',
            'vehicle_code',
            'nopol',
            'status',
            'service_location',
        ];
    }

    public function measures(): array
    {
        return [
            'total_amount',
            'days_late',
            'transaction_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'scheduled_date';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = VehiclePartsReplacement::query()
            ->select([
                'vehicle_parts_replacements.id',
                'vehicle_parts_replacements.vpr_code',
                'vehicle_parts_replacements.vehicle_id',
                'vehicle_parts_replacements.nopol',
                'vehicle_parts_replacements.status',
                'vehicle_parts_replacements.scheduled_date',
                'vehicle_parts_replacements.service_location',
            ])
            ->with([
                'vehicle:id,consumerial_code,consumerial_name',
                'transaction:id,vehicle_parts_replacement_id,total_amount,service_date,is_overdue,driver_id',
                'transaction.driver:id,nama_lengkap',
            ])
            ->orderByDesc('scheduled_date');

        $this->applyUnitScope($query, $options);

        if (! empty($filters['date_from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['nopol'])) {
            $query->whereIn('nopol', (array) $filters['nopol']);
        }
        if (! empty($filters['service_location'])) {
            $query->whereIn('service_location', (array) $filters['service_location']);
        }
        if (! empty($filters['vehicle'])) {
            $query->whereHas('vehicle', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->whereIn('consumerial_code', (array) $filters['vehicle'])
                        ->orWhereIn('consumerial_name', (array) $filters['vehicle']);
                });
            });
        }

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (VehiclePartsReplacement $r) => $this->row($r))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(VehiclePartsReplacement $r): array
    {
        $vehicle = $r->vehicle;
        $vehicleCode = $vehicle?->consumerial_code;
        $vehicleName = $vehicle?->consumerial_name;
        $tx = $r->transaction;

        return [
            'vpr_code' => $r->vpr_code,
            'month' => $r->scheduled_date?->format('Y-m'),
            'vehicle' => $vehicleCode !== null && $vehicleName !== null
                ? "{$vehicleCode} - {$vehicleName}"
                : ($vehicleName ?? ($vehicleCode ?? $r->nopol ?? 'Tidak diketahui')),
            'vehicle_code' => $vehicleCode,
            'nopol' => $r->nopol,
            'status' => $r->status,
            'scheduled_date' => $r->scheduled_date?->toDateString(),
            'service_location' => $r->service_location,
            'total_amount' => (float) ($tx?->total_amount ?? 0),
            'service_date' => $tx?->service_date?->toDateString(),
            'driver' => $tx?->driver?->nama_lengkap,
            'is_overdue' => (bool) ($tx?->is_overdue ?? false),
        ];
    }

    public function summary(array $records = []): array
    {
        $statusGroups = collect($records)->groupBy('status')->map->count();

        return [
            'total_records' => count($records),
            'total_amount' => (float) round(collect($records)->sum('total_amount'), 2),
            'total_overdue' => (int) collect($records)->where('is_overdue', true)->count(),
            'distinct_vehicles' => collect($records)->pluck('vehicle')->unique()->count(),
            'status_counts' => $statusGroups->all(),
            'pending' => (int) $statusGroups->get('pending', 0),
            'completed' => (int) $statusGroups->get('completed', 0),
        ];
    }
}