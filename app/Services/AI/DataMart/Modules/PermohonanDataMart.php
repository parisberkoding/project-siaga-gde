<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\Permohonan;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart transaksi permohonan BBM (fact table).
 *
 * Sumber: Permohonan + User (requester) + VehicleAndToolsConsumers (vehicle/site).
 */
class PermohonanDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'permohonan_bbm';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart transaksi permohonan bahan bakar (fact_permohonan_bbm)';
    }

    public function keywords(): array
    {
        return [
            'bbm',
            'bahan bakar',
            'permohonan',
            'pengajuan',
            'pengeluaran',
            'pemakaian',
            'transaksi bbm',
            'permintaan bbm',
            'fuel',
            'request bbm',
            'pengisian bahan bakar',
        ];
    }

    public function entities(): array
    {
        return [
            'driver',
            'vehicle',
            'site',
            'gasoline_type',
            'status',
            'request_number',
        ];
    }

    public function dimensions(): array
    {
        return [
            'request_date',
            'month',
            'driver',
            'vehicle',
            'vehicle_code',
            'site',
            'gasoline_type',
            'status',
        ];
    }

    public function measures(): array
    {
        return [
            'bill_amounts',
            'transaction_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'request_date';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = Permohonan::query()
            ->select([
                'permohonans.id',
                'permohonans.request_number',
                'permohonans.request_date',
                'permohonans.requester_id',
                'permohonans.gasoline_type',
                'permohonans.bill_amounts',
                'permohonans.status',
                'permohonans.consumerial_tools_id',
            ])
            ->with([
                'requester:id,nama_lengkap',
                'consumerialTool:id,consumerial_code,consumerial_name',
                'consumerialTool.detail:id,vehicle_and_tools_consumer_id,site_id',
                'consumerialTool.detail.site:id,name,code',
            ])
            ->orderByDesc('request_date');

        $this->applyFilters($query, $filters);

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (Permohonan $p) => $this->row($p))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * Terapkan filter database-level bila memungkinkan (performa).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Permohonan>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['gasoline_type'])) {
            $query->whereIn('gasoline_type', (array) $filters['gasoline_type']);
        }
        if (! empty($filters['vehicle'])) {
            $query->whereHas('consumerialTool', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->whereIn('consumerial_code', (array) $filters['vehicle'])
                        ->orWhereIn('consumerial_name', (array) $filters['vehicle']);
                });
            });
        }
        if (! empty($filters['vehicle_code'])) {
            $query->whereHas('consumerialTool', fn ($q) => $q->whereIn('consumerial_code', (array) $filters['vehicle_code']));
        }
        if (! empty($filters['driver'])) {
            $query->whereHas('requester', fn ($q) => $q->whereIn('nama_lengkap', (array) $filters['driver']));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Permohonan $p): array
    {
        $vehicle = $p->consumerialTool;
        $vehicleCode = $vehicle?->consumerial_code;
        $vehicleName = $vehicle?->consumerial_name;

        return [
            'request_number' => $p->request_number,
            'request_date' => $p->request_date?->toDateString(),
            'month' => $p->request_date?->format('Y-m'),
            'driver' => $p->requester?->nama_lengkap ?? 'Tidak diketahui',
            'vehicle' => $vehicleCode !== null && $vehicleName !== null
                ? "{$vehicleCode} - {$vehicleName}"
                : ($vehicleName ?? ($vehicleCode ?? 'Tidak diketahui')),
            'vehicle_code' => $vehicleCode,
            'gasoline_type' => $p->gasoline_type,
            'bill_amounts' => (float) $p->bill_amounts,
            'status' => $p->status,
            'site' => $this->siteName($p),
        ];
    }

    protected function siteName(?Permohonan $p): ?string
    {
        return $p?->consumerialTool?->detail?->site?->name;
    }

    public function summary(array $records = []): array
    {
        $count = count($records);
        $statusGroups = collect($records)->groupBy('status')->map->count();

        $spending = collect($records)->filter(
            fn ($r) => in_array($r['status'] ?? '', ['completed', 'approved'], true)
        );

        return [
            'total_records' => $count,
            'total_expense' => (float) round($spending->sum('bill_amounts'), 2),
            'total_nominal' => (float) round(collect($records)->sum('bill_amounts'), 2),
            'status_counts' => $statusGroups->all(),
            'completed' => (int) $statusGroups->get('completed', 0),
            'approved' => (int) $statusGroups->get('approved', 0),
            'pending' => (int) $statusGroups->get('pending', 0),
            'rejected' => (int) $statusGroups->get('rejected', 0),
        ];
    }
}
