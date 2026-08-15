<?php

namespace App\Services\AI\DataMart\Modules;

use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Services\AI\DataMart\AbstractDataMart;

/**
 * Data Mart makan siang (fact table).
 *
 * Sumber: LunchDistribution (porsi/hari/site) + LunchForecast (prediksi realisasi).
 */
class LunchDataMart extends AbstractDataMart
{
    public function module(): string
    {
        return 'lunch';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function description(): string
    {
        return 'Data mart makan siang (fact_lunch)';
    }

    public function keywords(): array
    {
        return [
            'makan siang',
            'lunch',
            'porsi makan',
            'makanan',
            'katering',
            'makan',
            'sarapan',
            'snack',
        ];
    }

    public function entities(): array
    {
        return [
            'site',
            'distribution_date',
            'porsi',
        ];
    }

    public function dimensions(): array
    {
        return [
            'distribution_date',
            'month',
            'site',
            'site_name',
            'is_locked',
        ];
    }

    public function measures(): array
    {
        return [
            'employee_count',
            'guest_count',
            'total_count',
        ];
    }

    public function dateDimension(): ?string
    {
        return 'distribution_date';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array
    {
        $filters = $options['filters'] ?? [];

        $query = LunchDistribution::query()
            ->select([
                'lunch_distributions.id',
                'lunch_distributions.distribution_date',
                'lunch_distributions.site_id',
                'lunch_distributions.employee_count',
                'lunch_distributions.guest_count',
                'lunch_distributions.total_count',
                'lunch_distributions.forecast_batch_id',
                'lunch_distributions.is_locked',
            ])
            ->with('site:id,name')
            ->orderByDesc('distribution_date');

        $this->applyUnitScope($query, $options);

        if (! empty($filters['date_from'])) {
            $query->whereDate('distribution_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('distribution_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['site_name'])) {
            $query->whereHas('site', fn ($q) => $q->whereIn('name', (array) $filters['site_name']));
        }

        $rows = $query->limit($this->maxRecords())->get();

        return $this->bound(
            $rows->map(fn (LunchDistribution $d) => $this->row($d))->values()->all(),
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(LunchDistribution $d): array
    {
        return [
            'distribution_date' => $d->distribution_date?->toDateString(),
            'month' => $d->distribution_date?->format('Y-m'),
            'site' => $d->site?->name ?? 'Tidak diketahui',
            'site_name' => $d->site?->name,
            'employee_count' => (int) $d->employee_count,
            'guest_count' => (int) $d->guest_count,
            'total_count' => (int) $d->total_count,
            'is_locked' => (bool) $d->is_locked,
        ];
    }

    public function summary(array $records = []): array
    {
        return [
            'total_records' => count($records),
            'total_employee_porsi' => (int) collect($records)->sum('employee_count'),
            'total_guest_porsi' => (int) collect($records)->sum('guest_count'),
            'total_porsi' => (int) collect($records)->sum('total_count'),
            'distinct_sites' => collect($records)->pluck('site')->unique()->values()->all(),
        ];
    }

    /**
     * Hitung jumlah karyawan yang makan di setiap site pada satu tanggal,
     * dari fakta riil LunchForecast (prediksi/aktual) — bukan target distribution.
     */
    public function actualCountsForDate(string $date): array
    {
        $forecasts = LunchForecast::query()
            ->whereDate('lunch_date', $date)
            ->with(['predictedSite:id,name', 'actualSite:id,name'])
            ->limit($this->maxRecords())
            ->get();

        $perSite = [];

        foreach ($forecasts as $f) {
            $site = $f->actualSite ?? $f->predictedSite;
            $key = $site?->name ?? 'Tidak diketahui';
            $perSite[$key] = ($perSite[$key] ?? 0) + 1;
        }

        return $perSite;
    }
}