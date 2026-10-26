<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\LpjPenggantian;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart Laporan Pertanggungjawaban (fact table).
 *
 * Sumber: LpjPenggantian + User (user_id) + SuratSpd (melayani_bidang, tanggal_berangkat).
 */
class LpjDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'lpj';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart laporan pertanggungjawaban (fact_lpj)';
    }

    public function keywords(): array
    {
        return [
            'lpj',
            'laporan pertanggungjawaban',
            'laporan keuangan',
            'reimbursement',
            'penggantian',
            'ganti rugi',
            'lpj spd',
            'buyback',
        ];
    }

    public function entities(): array
    {
        return [
            'user',
            'vehicle',
            'nomor_polisi',
            'status',
            'surat_spd_id',
        ];
    }

    public function dimensions(): array
    {
        return [
            'tanggal_lpj',
            'month',
            'driver',
            'vehicle',
            'vehicle_code',
            'nomor_polisi',
            'melayani_bidang',
            'status',
            'is_late',
            'is_over_budget',
            'is_extension',
        ];
    }

    public function measures(): array
    {
        return [
            'total_pengeluaran',
            'total_biaya_tol',
            'total_biaya_bbm',
            'total_biaya_portir',
            'total_biaya_lain',
            'jumlah_diberikan',
            'pengembalian',
            'kekurangan',
            'days_late',
            'transaction_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'tanggal_lpj';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = LpjPenggantian::query()
            ->select([
                'lpj_penggantians.id',
                'lpj_penggantians.lpj_number',
                'lpj_penggantians.surat_spd_id',
                'lpj_penggantians.user_id',
                'lpj_penggantians.nama',
                'lpj_penggantians.jabatan',
                'lpj_penggantians.vehicle_id',
                'lpj_penggantians.nomor_polisi',
                'lpj_penggantians.tanggal_lpj',
                'lpj_penggantians.due_date',
                'lpj_penggantians.is_late',
                'lpj_penggantians.days_late',
                'lpj_penggantians.total_biaya_tol',
                'lpj_penggantians.total_biaya_portir',
                'lpj_penggantians.total_biaya_bbm',
                'lpj_penggantians.total_biaya_lain',
                'lpj_penggantians.total_pengeluaran',
                'lpj_penggantians.jumlah_diberikan',
                'lpj_penggantians.pengembalian',
                'lpj_penggantians.kekurangan',
                'lpj_penggantians.is_over_budget',
                'lpj_penggantians.status',
            ])
            ->with([
                'user:id,nama_lengkap',
                'vehicle:id,consumerial_code,consumerial_name',
                'suratSpd:id,melayani_bidang,is_extension,status',
            ])
            ->orderByDesc('tanggal_lpj');

        $this->applyUnitScope($query, $options);
        $this->applyFilters($query, $filters);

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (LpjPenggantian $l) => $this->row($l))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<LpjPenggantian>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('tanggal_lpj', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('tanggal_lpj', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['nomor_polisi'])) {
            $query->whereIn('nomor_polisi', (array) $filters['nomor_polisi']);
        }
        if (array_key_exists('is_late', $filters) && $filters['is_late'] !== []) {
            $query->whereIn('is_late', array_map(
                fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN),
                (array) $filters['is_late']
            ));
        }
        if (array_key_exists('is_over_budget', $filters) && $filters['is_over_budget'] !== []) {
            $query->whereIn('is_over_budget', array_map(
                fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN),
                (array) $filters['is_over_budget']
            ));
        }
        if (! empty($filters['driver'])) {
            $query->whereHas('user', fn ($q) => $q->whereIn('nama_lengkap', (array) $filters['driver']))
                ->orWhereIn('nama', (array) $filters['driver']);
        }
        if (! empty($filters['vehicle'])) {
            $query->whereHas('vehicle', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->whereIn('consumerial_code', (array) $filters['vehicle'])
                        ->orWhereIn('consumerial_name', (array) $filters['vehicle']);
                });
            });
        }
        if (! empty($filters['vehicle_code'])) {
            $query->whereHas('vehicle', fn ($q) => $q->whereIn('consumerial_code', (array) $filters['vehicle_code']));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(LpjPenggantian $l): array
    {
        $vehicle = $l->vehicle;
        $vehicleCode = $vehicle?->consumerial_code;
        $vehicleName = $vehicle?->consumerial_name;

        return [
            'lpj_number' => $l->lpj_number,
            'surat_spd_id' => $l->surat_spd_id,
            'tanggal_lpj' => $l->tanggal_lpj?->toDateString(),
            'due_date' => $l->due_date?->toDateString(),
            'month' => $l->tanggal_lpj?->format('Y-m'),
            'driver' => $l->user?->nama_lengkap ?? $l->nama ?? 'Tidak diketahui',
            'nama' => $l->nama,
            'jabatan' => $l->jabatan,
            'vehicle' => $vehicleCode !== null && $vehicleName !== null
                ? "{$vehicleCode} - {$vehicleName}"
                : ($vehicleName ?? ($vehicleCode ?? $l->nomor_polisi ?? 'Tidak diketahui')),
            'vehicle_code' => $vehicleCode,
            'nomor_polisi' => $l->nomor_polisi,
            'melayani_bidang' => $l->suratSpd?->melayani_bidang,
            'is_extension' => (bool) ($l->suratSpd?->is_extension ?? false),
            'is_late' => (bool) $l->is_late,
            'days_late' => (int) $l->days_late,
            'total_biaya_tol' => (float) $l->total_biaya_tol,
            'total_biaya_bbm' => (float) $l->total_biaya_bbm,
            'total_biaya_portir' => (float) $l->total_biaya_portir,
            'total_biaya_lain' => (float) $l->total_biaya_lain,
            'total_pengeluaran' => (float) $l->total_pengeluaran,
            'jumlah_diberikan' => (float) $l->jumlah_diberikan,
            'pengembalian' => (float) $l->pengembalian,
            'kekurangan' => (float) $l->kekurangan,
            'is_over_budget' => (bool) $l->is_over_budget,
            'status' => $l->status,
        ];
    }

    public function summary(array $records = []): array
    {
        $statusGroups = collect($records)->groupBy('status')->map->count();

        return [
            'total_records' => count($records),
            'total_pengeluaran' => (float) round(collect($records)->sum('total_pengeluaran'), 2),
            'total_biaya_bbm' => (float) round(collect($records)->sum('total_biaya_bbm'), 2),
            'late' => (int) collect($records)->where('is_late', true)->count(),
            'on_time' => (int) collect($records)->where('is_late', false)->count(),
            'over_budget' => (int) collect($records)->where('is_over_budget', true)->count(),
            'status_counts' => $statusGroups->all(),
            'approved' => (int) $statusGroups->get('approved', 0),
            'pending' => (int) $statusGroups->get('pending', 0),
        ];
    }
}