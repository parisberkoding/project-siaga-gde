<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\SuratSpd;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart Surat Perjalanan Dinas (fact table).
 *
 * Sumber: SuratSpd + User (user_id) + VehicleAndToolsConsumers (vehicle_id).
 */
class SpdDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'spd';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart surat perjalanan dinas (fact_spd)';
    }

    public function keywords(): array
    {
        return [
            'spd',
            'surat perjalanan dinas',
            'surat tugas',
            'perjalanan dinas',
            'dinas',
            'surat keputusan',
            'surat tgs',
            'spd keluar',
            'surat spd',
        ];
    }

    public function entities(): array
    {
        return [
            'user',
            'vehicle',
            'tempat_tujuan',
            'status',
            'type',
        ];
    }

    public function dimensions(): array
    {
        return [
            'tanggal_berangkat',
            'month',
            'driver',
            'vehicle',
            'vehicle_code',
            'tempat_tujuan',
            'melayani_bidang',
            'status',
            'type',
            'is_extension',
        ];
    }

    public function measures(): array
    {
        return [
            'total_budget',
            'lama_perjalanan_hari',
            'jumlah_pemakai',
            'transaction_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'tanggal_berangkat';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = SuratSpd::query()
            ->select([
                'surat_spds.id',
                'surat_spds.nomor_surat',
                'surat_spds.user_id',
                'surat_spds.pengemudi_nama',
                'surat_spds.tempat_tujuan',
                'surat_spds.melayani_bidang',
                'surat_spds.jumlah_pemakai',
                'surat_spds.vehicle_id',
                'surat_spds.tanggal_berangkat',
                'surat_spds.tanggal_kembali',
                'surat_spds.lama_perjalanan_hari',
                'surat_spds.total_budget',
                'surat_spds.is_extension',
                'surat_spds.type',
                'surat_spds.status',
            ])
            ->with([
                'user:id,nama_lengkap',
                'vehicle:id,consumerial_code,consumerial_name',
            ])
            ->orderByDesc('tanggal_berangkat');

        $this->applyFilters($query, $filters);

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (SuratSpd $s) => $this->row($s))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * Terapkan filter database-level bila memungkinkan (performa).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SuratSpd>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('tanggal_berangkat', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('tanggal_berangkat', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->whereIn('type', (array) $filters['type']);
        }
        if (! empty($filters['tempat_tujuan'])) {
            $query->whereIn('tempat_tujuan', (array) $filters['tempat_tujuan']);
        }
        if (! empty($filters['melayani_bidang'])) {
            $query->whereIn('melayani_bidang', (array) $filters['melayani_bidang']);
        }
        if (! empty($filters['is_extension'])) {
            $query->whereIn('is_extension', array_map(
                fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN),
                (array) $filters['is_extension']
            ));
        }
        if (! empty($filters['driver'])) {
            $query->whereHas('user', fn ($q) => $q->whereIn('nama_lengkap', (array) $filters['driver']));
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
    protected function row(SuratSpd $s): array
    {
        $vehicle = $s->vehicle;
        $vehicleCode = $vehicle?->consumerial_code;
        $vehicleName = $vehicle?->consumerial_name;

        return [
            'nomor_surat' => $s->nomor_surat,
            'tanggal_berangkat' => $s->tanggal_berangkat?->toDateString(),
            'tanggal_kembali' => $s->tanggal_kembali?->toDateString(),
            'month' => $s->tanggal_berangkat?->format('Y-m'),
            'driver' => $s->user?->nama_lengkap ?? $s->pengemudi_nama ?? 'Tidak diketahui',
            'pengemudi_nama' => $s->pengemudi_nama,
            'tempat_tujuan' => $s->tempat_tujuan,
            'melayani_bidang' => $s->melayani_bidang,
            'jumlah_pemakai' => (int) $s->jumlah_pemakai,
            'lama_perjalanan_hari' => (int) $s->lama_perjalanan_hari,
            'vehicle' => $vehicleCode !== null && $vehicleName !== null
                ? "{$vehicleCode} - {$vehicleName}"
                : ($vehicleName ?? ($vehicleCode ?? 'Tidak diketahui')),
            'vehicle_code' => $vehicleCode,
            'total_budget' => (float) $s->total_budget,
            'is_extension' => (bool) $s->is_extension,
            'type' => $s->type,
            'status' => $s->status,
        ];
    }

    public function summary(array $records = []): array
    {
        $statusGroups = collect($records)->groupBy('status')->map->count();

        return [
            'total_records' => count($records),
            'total_budget' => (float) round(collect($records)->sum('total_budget'), 2),
            'status_counts' => $statusGroups->all(),
            'completed' => (int) $statusGroups->get('completed', 0),
            'approved' => (int) $statusGroups->get('approved', 0),
            'pending' => (int) $statusGroups->get('pending', 0),
            'rejected' => (int) $statusGroups->get('rejected', 0),
            'extensions' => (int) collect($records)->where('is_extension', true)->count(),
        ];
    }
}
