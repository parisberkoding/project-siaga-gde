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
        // Hanya reuse bila modul sama — filter modul lain (mis. is_late SPD) tidak valid untuk BBM.
        $sameModule = ($previous['module'] ?? null) === $module;
        if ($sameModule) {
            $filters = $this->mergePreviousFilters($filters, $previous);
        }

        $aggregation = $this->keywords->aggregation($question)
            ?? (($sameModule && $previous['aggregation'] !== null) ? $previous['aggregation'] : 'SUM');

        $measure = $this->keywords->measure($question, $module)
            ?? (($sameModule && $previous['measure'] !== null) ? $previous['measure'] : $this->defaultMeasure($module));

        $query = new SemanticQuery(
            module: $module,
            intent: $intent['intent'],
            measure: $measure,
            aggregation: $aggregation,
            filters: $filters,
        );

        $query->chartType = $this->detectChartType($question);

        $this->applyIntentShape($query, $question, $module, $previous, $intent['intent']);

        return $query;
    }

    /**
     * Bentuk query sesuai intent (summary/aggregation vs detail/listing).
     *
     * @param  array<string, mixed>|null  $previous
     */
    protected function applyIntentShape(SemanticQuery $query, string $question, string $module, ?array $previous = null, ?string $fallbackIntent = null): void
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

        // Grouping untuk top-entity / tren / grafik.
        $sameModule = ($previous['module'] ?? null) === $module;
        $continuesChart = $previous !== null
            && $sameModule
            && ($previous['group_by'] ?? null) !== null
            && $this->isFollowUpRegroup($question);

        if ($this->wantsGrouping($question) || $this->wantsChart($question) || $continuesChart) {
            $axes = $this->detectAxes($query, $module, $question, $previous);
            $query->groupBy = $axes['x'];
            $query->seriesBy = $axes['series'];

            $query->aggregation = $axes['aggregation'] ?? $this->keywords->aggregation($question)
                ?? (($sameModule && $previous['aggregation'] !== null) ? $previous['aggregation'] : ($this->wantsChart($question) ? 'SUM' : 'COUNT'));
            if (! empty($axes['measure'])) {
                $query->measure = $axes['measure'];
            }
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
            '/driver|pengemudi|pemohon|sopir|kendaraan|vehicle|armada|mobil|per bulan|bulanan|status|nama|name|nama driver|per nama|by nama|by name/i',
            $question
        );

        if (! $hasEntity) {
            return false;
        }

        $hasRanking = (bool) preg_match('/terbanyak|tertinggi|terbesar|teratas|top\b|teraktif|paling aktif/i', $question);

        return $hasRanking || (bool) preg_match('/per bulan|bulanan|per kendaraan|per driver|per nama|per nama|by nama|by name|per driver/i', $question);
    }

    /**
     * Follow-up regrouping seperti "kalau by nama?", "kalo per bulan?", "bagaimana
     * kalau dibagi per status?" — pertanyaan lanjutan dari visualisasi sebelumnya
     * yang mengubah dimensi group tanpa menyebut kata "grafik/chart" lagi.
     */
    protected function isFollowUpRegroup(string $question): bool
    {
        return (bool) preg_match(
            '/kalau|kalo|bagaimana|gimana|by nama|by name|per nama|per bulan|dibagi|dipecah|per status|per kendaraan|per driver|per site/i',
            $question
        );
    }

    /**
     * @param  array<string, mixed>|null  $previous
     */
    protected function detectGroupDimension(string $question, string $module, ?array $previous = null): string
    {
        $dimensions = $this->dimensionsOf($module);

        if ($module === 'gasoline') {
            return preg_match('/provinsi|region/i', $question) ? 'province' : 'fuel_type';
        }

        if (preg_match('/driver|pengemudi|pemohon|sopir|requester|by nama|per nama|nama|name/i', $question)) {
            return in_array('driver', $dimensions, true) ? 'driver' : ($dimensions[0] ?? 'status');
        }

        if (preg_match('/kendaraan|vehicle|armada|mobil/i', $question)) {
            return in_array('vehicle', $dimensions, true) ? 'vehicle' : ($dimensions[0] ?? 'status');
        }

        if (preg_match('/per bulan|bulanan|tren/i', $question)) {
            return in_array('month', $dimensions, true) ? 'month' : ($dimensions[0] ?? 'status');
        }

        // Pertanyaan lanjutan memakai dimensi dari sesi sebelumnya.
        if ($previous !== null
            && ($previous['module'] ?? null) === $module
            && ($previous['group_by'] ?? null) !== null) {
            $dimension = (string) $previous['group_by'];
            if (in_array($dimension, $dimensions, true)) {
                return $dimension;
            }
        }

        return in_array('status', $dimensions, true) ? 'status' : ($dimensions[0] ?? 'status');
    }

    /**
     * Tentukan sumbu X (group) dan sumbu series (pivot) untuk grafik.
     *
     * Prioritas:
     *  1. User eksplisit menyebut "sumbu X ..." / "X = ..." -> pakai dimensi tsb.
     *  2. Trend + entity (mis. "pivot bulan x driver") -> X=time, series=entity.
     *  3. Jenis grafik line + entity -> X=time (default), series=entity.
     *  4. Fallback: detectGroupDimension lama.
     *
     * @param  array<string, mixed>|null  $previous
     * @return array{x: string, series: string|null, aggregation?: string|null, measure?: string|null}
     */
    protected function detectAxes(SemanticQuery $query, string $module, string $question, ?array $previous = null): array
    {
        $dimensions = $this->dimensionsOf($module);

        if ($explicit = $this->detectExplicitAxes($question, $module, $dimensions)) {
            return $explicit;
        }

        $timeDim = $this->detectTimeDimension($question, $module, $dimensions);
        $entityDim = $this->detectEntityDimension($question, $module, $dimensions);

        $isLine = $query->chartType === 'line'
            || (bool) preg_match('/line\b|line chart|linechart|garis|tren|time series|sepanjang waktu|waktu/i', $question);

        // Trend + entity -> pivot: X = waktu, series = entity.
        if ($timeDim !== null && $entityDim !== null && $entityDim !== $timeDim) {
            return ['x' => $timeDim, 'series' => $entityDim];
        }

        // Line chart + entity -> pivot ke time untuk X, entity sebagai series.
        if ($isLine && $entityDim !== null && $entityDim !== $timeDim) {
            $x = $timeDim ?? $this->defaultTimeDimension($module, $dimensions);

            return ['x' => $x, 'series' => $entityDim];
        }

        if ($timeDim !== null) {
            return ['x' => $timeDim, 'series' => null];
        }

        return ['x' => $this->detectGroupDimension($question, $module, $previous), 'series' => null];
    }

    /**
     * User eksplisit menyebut sumbu X/Y atau "X = <dimensi>".
     *
     * @return array{x: string, series: string|null, aggregation?: string|null, measure?: string|null}|null
     */
    protected function detectExplicitAxes(string $question, string $module, array $dimensions): ?array
    {
        if (! preg_match('/sumbu\s*x|sumbu-x|sumbu_x|\bx\s*[:=]|poros\s*x/i', $question)) {
            return null;
        }

        $normalized = mb_strtolower($question);

        $xDim = null;
        foreach ($this->dimensionAliases() as $dim => $patterns) {
            if (! in_array($dim, $dimensions, true)) {
                continue;
            }
            if ($this->axisMentionsX($normalized, $patterns)) {
                $xDim = $dim;
                break;
            }
        }

        if ($xDim === null) {
            return null;
        }

        $axis = ['x' => $xDim, 'series' => null];

        if ($this->axisMentionsY($normalized, ['bulan', 'tahun', 'tanggal', 'waktu', 'date', 'month'])) {
            $y = $this->detectTimeDimension($question, $module, $dimensions);
            if ($y !== null && $y !== $xDim) {
                $axis['series'] = $y;
            }
        } elseif ($this->axisMentionsY($normalized, ['driver', 'nama', 'per nama', 'by nama'])) {
            $axis['series'] = in_array('driver', $dimensions, true) ? 'driver' : null;
        } elseif ($this->axisMentionsY($normalized, ['kendaraan', 'vehicle', 'armada'])) {
            $axis['series'] = in_array('vehicle', $dimensions, true) ? 'vehicle' : null;
        }

        return $axis;
    }

    /**
     * Apakah pertanyaan menyebut sumbu X lalu menyebut dimensi/pola tertentu?
     */
    protected function axisMentionsX(string $question, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/sumbu\s*x.{0,40}'.$pattern.'|sumbu-x\s*'.$pattern.'|\bx\s*[:=]\s*'.$pattern.'/iu', $question)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah pertanyaan menyebut sumbu Y lalu mengenali dimensi/pola tersebut?
     */
    protected function axisMentionsY(string $question, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/sumbu\s*y.{0,40}'.$pattern.'/iu', $question)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function dimensionAliases(): array
    {
        return [
            'month' => ['bulan|month'],
            'driver' => ['nama|driver|pengemudi|pemohon|sopir|requester'],
            'vehicle' => ['kendaraan|vehicle|armada|mobil'],
            'status' => ['status'],
            'site' => ['site|lokasi|unit'],
            'gasoline_type' => ['jenis|fuel|bbm'],
            'fuel_type' => ['jenis|fuel|bbm'],
            'request_date' => ['tanggal|date'],
            'distribution_date' => ['tanggal|date'],
            'tanggal_berangkat' => ['tanggal|date'],
            'tanggal_lpj' => ['tanggal|date'],
        ];
    }

    /**
     * Dimensi waktu dari pola natural. Prioritas: daily date -> month.
     */
    protected function detectTimeDimension(string $question, string $module, array $dimensions): ?string
    {
        $dateDim = $this->manager->resolve($module)->dateDimension();

        if (in_array('month', $dimensions, true)
            && preg_match('/per bulan|bulanan|monthly|tren|time series|per tanggal|per hari|per minggu/i', $question)) {
            return 'month';
        }

        if ($dateDim !== null && in_array($dateDim, $dimensions, true)
            && preg_match('/per tanggal|per hari|per date|daily|per day/i', $question)) {
            return $dateDim;
        }

        return null;
    }

    /**
     * Dimensi entity/kategori yang disebut user (driver, kendaraan, status, site).
     */
    protected function detectEntityDimension(string $question, string $module, array $dimensions): ?string
    {
        $map = [
            'driver' => ['driver|pengemudi|pemohon|sopir|requester|by nama|per nama|nama|name'],
            'vehicle' => ['kendaraan|vehicle|armada|mobil'],
            'vehicle_code' => ['kendaraan|vehicle|armada|mobil|nopol'],
            'status' => ['status'],
            'site' => ['site|lokasi|unit|cabang'],
        ];

        foreach ($map as $dim => $patterns) {
            if (! in_array($dim, $dimensions, true)) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/iu', $question)) {
                    return $dim;
                }
            }
        }

        return null;
    }

    protected function defaultTimeDimension(string $module, array $dimensions): string
    {
        if (in_array('month', $dimensions, true)) {
            return 'month';
        }

        $dateDim = $this->manager->resolve($module)->dateDimension();

        return $dateDim !== null && in_array($dateDim, $dimensions, true) ? $dateDim : 'month';
    }

    protected function wantsChart(string $question): bool
    {
        return (bool) preg_match('/grafik|chart|diagram|visual|barchart|pie\b|bar\b|piechart|bar chart|line chart|plot|donut|doughnut|polar|radar/i', $question);
    }

    /**
     * Deteksi jenis grafik yang EKSPLISIT diminta user. Hanya dikembalikan
     * bila user menyebut jenis tertentu (pie, donut, bar, line, polar, radar);
     * bila tidak disebut, backend/AI yang memutuskan sendiri.
     */
    protected function detectChartType(string $question): ?string
    {
        if (preg_match('/polar|radar/i', $question)) {
            return 'radar';
        }
        if (preg_match('/donut|doughnut/i', $question)) {
            return 'doughnut';
        }
        if (preg_match('/pie\b|piechart|pie chart/i', $question)) {
            return 'pie';
        }
        if (preg_match('/line\b|line chart|linechart|garis|tren/i', $question)) {
            return 'line';
        }
        if (preg_match('/bar\b|barchart|bar chart|batang/i', $question)) {
            return 'bar';
        }

        return null;
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
