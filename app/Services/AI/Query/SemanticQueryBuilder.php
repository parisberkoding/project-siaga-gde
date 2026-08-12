<?php

namespace App\Services\AI\Query;

use App\Models\LpjPenggantian;
use App\Models\Permohonan;
use App\Models\Site;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Services\AI\DataMart\DataMartManager;
use App\Services\AI\Resolvers\IntentResolver;
use App\Services\AI\Resolvers\KeywordMapper;
use Illuminate\Support\Facades\Cache;

/**
 * Menyusun Semantic Query Object dari pertanyaan + intent + module.
 *
 * Entity (jenis BBM, status, site, driver, vehicle, rentang tanggal) diekstrak
 * secara deterministik dan di-allowlist sebelum masuk ke query engine.
 */
class SemanticQueryBuilder
{
    public function __construct(
        protected IntentResolver $intents,
        protected KeywordMapper $keywords,
        protected DateRangeExtractor $dates,
        protected DataMartManager $manager,
    ) {}

    /**
     * @param  array{intent: string, confidence: float}  $intent
     * @param  array<string, mixed>|null  $previous  Semantic query sesi sebelumnya (follow-up).
     */
    public function build(string $question, string $module, array $intent, ?array $previous = null): SemanticQuery
    {
        $filters = $this->extractFilters($question, $module);

        // Rentang tanggal dari pertanyaan saat ini; jika tidak ada, reuse sesi sebelumnya.
        $range = $this->dates->extract($question);
        if ($range !== null) {
            $filters['date_from'] = $range['from'];
            $filters['date_to'] = $range['to'];
        } elseif (isset($previous['filters']['date_from'], $previous['filters']['date_to'])) {
            $filters['date_from'] = $previous['filters']['date_from'];
            $filters['date_to'] = $previous['filters']['date_to'];
        }

        // Merger filter sesi sebelumnya (pertanyaan lanjutan), kecuali yang baru ditentukan.
        $filters = $this->mergePreviousFilters($filters, $previous);

        $aggregation = $this->keywords->aggregation($question)
            ?? ($previous['aggregation'] ?? 'SUM');

        $measure = $this->keywords->measure($question, $module)
            ?? ($previous['measure'] ?? $this->defaultMeasure($module));

        $query = new SemanticQuery(
            module: $module,
            intent: $intent['intent'],
            measure: $measure,
            aggregation: $aggregation,
            filters: $filters,
        );

        $this->applyIntentShape($query, $question, $module);

        return $query;
    }

    /**
     * Bentuk query sesuai intent (summary/aggregation vs detail/listing).
     */
    protected function applyIntentShape(SemanticQuery $query, string $question, string $module): void
    {
        $topK = (int) config('ai.rag.top_k', 10);
        $maxTopK = (int) config('ai.rag.max_top_k', 50);

        $sortBy = match ($module) {
            'gasoline' => 'price_per_liter',
            'vehicle' => 'vehicle_code',
            'spd' => 'tanggal_berangkat',
            'lpj' => 'tanggal_lpj',
            'lunch' => 'distribution_date',
            'vehicle_parts' => 'scheduled_date',
            'vehicle_check' => 'next_due_date',
            default => 'request_date',
        };

        if (in_array($query->intent, ['detail', 'listing', 'lookup'], true)) {
            // Daftar/detail/lookup => deep drill: sort + limit (top-k).
            $query->aggregation = null;
            $limit = $this->extractLimit($question) ?? $topK;
            $query->limit = max(1, min($limit, $maxTopK));

            if (preg_match('/terbesar|tertinggi|terbanyak/i', $question)) {
                $query->sortBy = $query->measure ?? ($module === 'gasoline' ? 'price_per_liter' : 'bill_amounts');
                $query->sortDir = 'desc';
            } elseif (preg_match('/terkecil|terendah|tersedikit/i', $question)) {
                $query->sortBy = $query->measure ?? ($module === 'gasoline' ? 'price_per_liter' : 'bill_amounts');
                $query->sortDir = 'asc';
            } else {
                $query->sortBy = $sortBy;
                $query->sortDir = 'desc';
            }
        }

        // Grouping untuk top-entity / tren.
        if ($this->wantsGrouping($question)) {
            $query->groupBy = $this->detectGroupDimension($question, $module);
            $query->aggregation = $this->keywords->aggregation($question) ?? 'COUNT';
            $query->limit = $topK;
        }

        if ($query->groupBy === null && in_array($query->intent, ['summary', 'aggregation', 'lookup', 'comparison', 'trend'], true)) {
            $query->measure = $this->keywords->measure($question, $module) ?? $query->measure;
            $query->aggregation = $this->keywords->aggregation($question) ?? 'SUM';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractFilters(string $question, string $module): array
    {
        $filters = [];

        $gasolineType = $this->keywords->gasolineType($question);
        if ($gasolineType !== null && in_array('gasoline_type', $this->dimensionsOf($module), true)) {
            $filters['gasoline_type'] = $gasolineType;
        }

        $status = $this->keywords->status($question);
        if ($status !== null && in_array('status', $this->dimensionsOf($module), true)) {
            $filters['status'] = $status;
        }

        // Status khusus per modul: telat / melebihi anggaran.
        if (in_array('is_late', $this->dimensionsOf($module), true) && preg_match('/telat|terlambat|late|lewat batas|overdue/i', $question)) {
            $filters['is_late'] = true;
        }

        if (in_array('is_over_budget', $this->dimensionsOf($module), true) && preg_match('/over budget|melebihi anggaran|kelebihan budget|overbudget/i', $question)) {
            $filters['is_over_budget'] = true;
        }

        $site = $this->keywords->site($question, $this->knownSites());
        if ($site !== null && in_array('site', $this->dimensionsOf($module), true)) {
            $filters['site'] = $site;
        }

        if ($driver = $this->extractDriver($question)) {
            $filters['driver'] = $driver;
        }

        if ($vehicle = $this->extractVehicle($question)) {
            $filters['vehicle'] = $vehicle;
        }

        if ($fuelType = $this->extractFuelType($question)) {
            if (in_array('fuel_type', $this->dimensionsOf($module), true)) {
                $filters['fuel_type'] = $fuelType;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>|null  $previous
     * @return array<string, mixed>
     */
    protected function mergePreviousFilters(array $filters, ?array $previous): array
    {
        if ($previous === null || empty($previous['filters'])) {
            return $filters;
        }

        foreach ($previous['filters'] as $key => $value) {
            if (! array_key_exists($key, $filters)) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    protected function defaultMeasure(string $module): string
    {
        return match ($module) {
            'gasoline' => 'price_per_liter',
            'vehicle' => 'nominal_full_tank',
            'driver' => 'total_requests',
            'spd' => 'total_budget',
            'lpj' => 'total_pengeluaran',
            'lunch' => 'total_count',
            'vehicle_parts' => 'total_amount',
            'vehicle_check' => 'transaction_count',
            default => 'bill_amounts',
        };
    }

    /**
     * @return list<string>
     */
    protected function dimensionsOf(string $module): array
    {
        return $this->manager->resolve($module)->dimensions();
    }

    protected function extractLimit(string $question): ?int
    {
        if (preg_match('/(\d{1,3})\s*(?:transaksi|record|baris|kendaraan|data|entry|driver)/iu', $question, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function wantsGrouping(string $question): bool
    {
        $hasEntity = (bool) preg_match(
            '/driver|pengemudi|pemohon|sopir|kendaraan|vehicle|armada|mobil|per bulan|bulanan|status/i',
            $question
        );

        if (! $hasEntity) {
            return false;
        }

        $hasRanking = (bool) preg_match('/terbanyak|tertinggi|terbesar|teratas|top\b|teraktif|paling aktif/i', $question);

        return $hasRanking || (bool) preg_match('/per bulan|bulanan|per kendaraan|per driver/i', $question);
    }

    protected function detectGroupDimension(string $question, string $module): string
    {
        if ($module === 'gasoline') {
            return preg_match('/provinsi|region/i', $question) ? 'province' : 'fuel_type';
        }

        if (preg_match('/driver|pengemudi|pemohon|sopir/i', $question)) {
            return 'driver';
        }

        if (preg_match('/kendaraan|vehicle|armada|mobil/i', $question)) {
            return 'vehicle';
        }

        if (preg_match('/per bulan|bulanan|tren/i', $question)) {
            return 'month';
        }

        return 'status';
    }

    /**
     * Ekstrak driver dari nama lengkap yang dikenal (exact/substring).
     */
    protected function extractDriver(string $question): ?string
    {
        $names = $this->cachedUserNames();

        foreach ($names as $name) {
            $needle = mb_strtolower(trim((string) $name));
            if ($needle !== '' && mb_strlen($needle) >= 3 && mb_strpos(mb_strtolower($question), $needle) !== false) {
                return (string) $name;
            }
        }

        return null;
    }

    /**
     * Ekstrak kendaraan dari kode atau nama yang dikenal.
     */
    protected function extractVehicle(string $question): ?string
    {
        $vehicles = $this->cachedVehicles();

        foreach ($vehicles as $vehicle) {
            foreach ([(string) $vehicle['consumerial_code'], (string) $vehicle['consumerial_name']] as $needle) {
                $needle = mb_strtolower(trim($needle));
                if ($needle !== '' && mb_strlen($needle) >= 3 && mb_strpos(mb_strtolower($question), $needle) !== false) {
                    return (string) $vehicle['consumerial_code'];
                }
            }
        }

        return null;
    }

    protected function extractFuelType(string $question): ?string
    {
        return $this->keywords->gasolineType($question);
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    protected function knownSites(): array
    {
        return Cache::remember('ai.datamart.sites', 300, function () {
            return Site::query()->orderBy('name')->get(['code', 'name'])->toArray();
        });
    }

    /**
     * @return list<string>
     */
    protected function cachedUserNames(): array
    {
        return Cache::remember('ai.datamart.driver-names', 300, function () {
            return User::query()
                ->whereIn('id', collect([
                    Permohonan::query()->distinct()->pluck('requester_id'),
                    SuratSpd::query()->distinct()->pluck('user_id'),
                    LpjPenggantian::query()->distinct()->pluck('user_id'),
                ])->flatten()->unique()->filter())
                ->orderBy('nama_lengkap')
                ->pluck('nama_lengkap')
                ->all();
        });
    }

    /**
     * @return list<array{consumerial_code: string, consumerial_name: string}>
     */
    protected function cachedVehicles(): array
    {
        return Cache::remember('ai.datamart.vehicles', 300, function () {
            return VehicleAndToolsConsumers::query()
                ->orderBy('consumerial_code')
                ->get(['consumerial_code', 'consumerial_name'])
                ->toArray();
        });
    }
}
