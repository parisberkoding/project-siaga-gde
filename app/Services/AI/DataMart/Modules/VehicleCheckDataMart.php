<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\VehicleCheckingSchedule;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart pemeriksaan kendaraan (fact table).
 *
 * Sumber: VehicleCheckingSchedule (jadwal periksa per kendaraan).
 */
class VehicleCheckDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'vehicle_check';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart pemeriksaan kendaraan (fact_vehicle_check)';
    }

    public function keywords(): array
    {
        return [
            'pemeriksaan',
            'pemeriksaan mobil',
            'periksa mobil',
            'checking',
            'inspection',
            'jadwal periksa',
            'jadwal pemeriksaan',
            'pengecekan',
            'cek kendaraan',
            'due date kendaraan',
            'masa periksa',
            'kendaraan telat periksa',
        ];
    }

    public function entities(): array
    {
        return [
            'vehicle',
            'status',
            'dispatcher',
        ];
    }

    public function dimensions(): array
    {
        return [
            'vehicle',
            'vehicle_code',
            'status',
            'check_frequency',
            'month',
        ];
    }

    public function measures(): array
    {
        return [
            'days_until_due',
            'days_late',
            'transaction_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'next_due_date';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = VehicleCheckingSchedule::query()
            ->select([
                'vehicle_checking_schedules.id',
                'vehicle_checking_schedules.schedule_code',
                'vehicle_checking_schedules.vehicle_id',
                'vehicle_checking_schedules.check_frequency',
                'vehicle_checking_schedules.assigned_dispatcher_id',
                'vehicle_checking_schedules.last_checked_at',
                'vehicle_checking_schedules.next_due_date',
                'vehicle_checking_schedules.status',
                'vehicle_checking_schedules.is_active',
            ])
            ->with([
                'vehicle:id,consumerial_code,consumerial_name',
                'assignedDispatcher:id,nama_lengkap',
            ])
            ->orderByDesc('next_due_date');

        $this->applyUnitScope($query, $options);

        if (! empty($filters['date_from'])) {
            $query->whereDate('next_due_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('next_due_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['check_frequency'])) {
            $query->whereIn('check_frequency', (array) $filters['check_frequency']);
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
            $rows->map(fn (VehicleCheckingSchedule $s) => $this->row($s))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(VehicleCheckingSchedule $s): array
    {
        $vehicle = $s->vehicle;
        $vehicleCode = $vehicle?->consumerial_code;
        $vehicleName = $vehicle?->consumerial_name;

        return [
            'schedule_code' => $s->schedule_code,
            'month' => $s->next_due_date?->format('Y-m'),
            'vehicle' => $vehicleCode !== null && $vehicleName !== null
                ? "{$vehicleCode} - {$vehicleName}"
                : ($vehicleName ?? ($vehicleCode ?? 'Tidak diketahui')),
            'vehicle_code' => $vehicleCode,
            'check_frequency' => $s->check_frequency,
            'dispatcher' => $s->assignedDispatcher?->nama_lengkap,
            'last_checked_at' => $s->last_checked_at?->toDateTimeString(),
            'next_due_date' => $s->next_due_date?->toDateString(),
            'status' => $s->status,
            'is_active' => (bool) $s->is_active,
            'days_until_due' => $s->days_until_due,
        ];
    }

    public function summary(array $records = []): array
    {
        $statusGroups = collect($records)->groupBy('status')->map->count();

        return [
            'total_records' => count($records),
            'total_overdue' => (int) $statusGroups->get('overdue', 0),
            'total_due' => (int) $statusGroups->get('due', 0),
            'total_completed' => (int) $statusGroups->get('completed', 0),
            'distinct_vehicles' => collect($records)->pluck('vehicle')->unique()->count(),
            'status_counts' => $statusGroups->all(),
        ];
    }
}