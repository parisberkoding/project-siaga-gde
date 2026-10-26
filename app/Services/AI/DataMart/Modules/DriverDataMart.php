<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\Permohonan;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart driver / pemohon (aggregate fact per requester).
 *
 * Sumber: Permohonan + User. Hanya informasi analitis; tidak mengekspos data
 * pribadi yang tidak diperlukan.
 */
class DriverDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'driver';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart statistik driver/pemohon BBM (dim_driver + agregasi)';
    }

    public function keywords(): array
    {
        return [
            'driver',
            'pengemudi',
            'pemohon',
            'sopir',
            'karyawan',
            'user',
            'driver bbm',
        ];
    }

    public function entities(): array
    {
        return ['driver'];
    }

    public function dimensions(): array
    {
        return ['driver'];
    }

    public function measures(): array
    {
        return [
            'total_requests',
            'completed',
            'rejected',
            'approved',
            'pending',
            'report_rate',
            'avg_nominal',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = Permohonan::query()
            ->with('requester:id,nama_lengkap')
            ->select(['id', 'requester_id', 'status', 'bill_amounts', 'request_date']);

        $this->applyUnitScope($query, $options);

        if (! empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }

        $rows = $query->limit($this->maxRecords())->get();

        $records = $rows
            ->groupBy('requester_id')
            ->map(function ($requests) {
                $nominals = $requests->pluck('bill_amounts')->filter()->toArray();
                $total = $requests->count();

                return [
                    'driver' => $requests->first()->requester?->nama_lengkap ?? 'Tidak diketahui',
                    'total_requests' => $total,
                    'rejected' => $requests->where('status', 'rejected')->count(),
                    'completed' => $requests->where('status', 'completed')->count(),
                    'approved' => $requests->where('status', 'approved')->count(),
                    'pending' => $requests->where('status', 'pending')->count(),
                    'report_rate' => $total > 0
                        ? round(($requests->where('status', 'completed')->count() / $total) * 100, 1)
                        : 0,
                    'avg_nominal' => ! empty($nominals)
                        ? round(array_sum($nominals) / count($nominals), 0)
                        : 0,
                ];
            })
            ->sortByDesc('total_requests')
            ->values()
            ->all();

        return $this->bound($records, (int) ($options['limit'] ?? 0));
    }

    public function summary(array $records = []): array
    {
        $totalRequests = collect($records)->sum('total_requests');

        return [
            'total_drivers' => count($records),
            'total_requests' => (int) $totalRequests,
            'total_completed' => (int) collect($records)->sum('completed'),
            'total_rejected' => (int) collect($records)->sum('rejected'),
            'total_pending' => (int) collect($records)->sum('pending'),
            'avg_report_rate' => count($records) > 0
                ? round(collect($records)->avg('report_rate'), 1)
                : 0,
        ];
    }
}
