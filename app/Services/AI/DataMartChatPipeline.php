<?php

namespace App\Services\AI;

use App\Services\AI\DataMart\DataMartManager;
use App\Services\AI\DataMart\DataMartQueryEngine;
use App\Services\AI\DataMart\DataMartScope;
use App\Services\AI\Query\SemanticQuery;
use App\Services\AI\Query\SemanticQueryBuilder;
use App\Services\AI\RAG\RAGContextBuilder;
use App\Services\AI\Resolvers\IntentResolver;
use App\Services\AI\Resolvers\ModuleResolver;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pipeline Data Mart untuk AI Assistant:
 *
 *   intent -> module -> semantic query -> data mart -> query engine -> context.
 *
 * Pipeline TIDAK memanggil Gemini. GeminiAssistantService yang memanggil Gemini
 * dengan konteks terverifikasi hasil pipeline ini.
 */
class DataMartChatPipeline
{
    public function __construct(
        protected IntentResolver $intents,
        protected ModuleResolver $modules,
        protected SemanticQueryBuilder $builder,
        protected DataMartManager $manager,
        protected DataMartQueryEngine $engine,
        protected RAGContextBuilder $context,
    ) {}

    /**
     * @param  array<string, mixed>|null  $previous  Konteks sesi (module/filters/measure pesan sebelumnya)
     * @return array<string, mixed>
     */
    public function run(string $question, ?array $previous = null, ?DataMartScope $scope = null): array
    {
        if (! config('ai.datamart.enabled', true)) {
            return ['resolved' => false, 'reason' => 'disabled'];
        }

        $intent = $this->intents->resolve($question);

        try {
            $module = $this->modules->resolve($question, $previous);
        } catch (Throwable $e) {
            Log::warning('DataMartChatPipeline: module resolution error: '.$e->getMessage());
            $module = null;
        }

        if ($module === null) {
            return [
                'resolved' => false,
                'reason' => 'module_not_resolved',
                'metadata' => [
                    'intent' => $intent['intent'],
                    'intent_confidence' => $intent['confidence'],
                    'module' => null,
                    'confidence' => 0.0,
                ],
            ];
        }

        $moduleName = $module['module'];
        $start = hrtime(true);

        try {
            $semanticQuery = $this->builder->build($question, $moduleName, $intent, $previous);
            $queryArray = $this->toEngineQuery($semanticQuery);
            $result = $this->engine->execute($queryArray, null, $scope);
            $context = $this->context->build($semanticQuery, $result);
        } catch (Throwable $e) {
            Log::error('DataMartChatPipeline: query engine error: '.$e->getMessage());

            return [
                'resolved' => false,
                'reason' => 'datamart_failure',
                'error' => $e->getMessage(),
                'metadata' => [
                    'module' => $moduleName,
                    'intent' => $intent['intent'],
                    'confidence' => $module['confidence'],
                ],
            ];
        }

        $duration = (int) round((hrtime(true) - $start) / 1e6);

        $chart = $this->buildChartSpec($semanticQuery, $result);

        $metadata = [
            'module' => $moduleName,
            'module_confidence' => round((float) $module['confidence'], 2),
            'module_reused' => (bool) ($module['reused'] ?? false),
            'intent' => $semanticQuery->intent,
            'intent_confidence' => round((float) $intent['confidence'], 2),
            'filters' => $semanticQuery->filters,
            'aggregation' => $semanticQuery->aggregation,
            'measure' => $semanticQuery->measure,
            'group_by' => $semanticQuery->groupBy,
            'series_by' => $semanticQuery->seriesBy,
            'limit' => $semanticQuery->limit,
            'records_matched' => (int) ($result['matched'] ?? 0),
            'records_injected' => in_array($semanticQuery->intent, ['detail', 'listing'], true)
                ? (int) count($result['records'] ?? [])
                : 0,
            'truncated' => (bool) ($result['truncated'] ?? false),
            'execution_ms' => $duration,
            'source' => $result['source'] ?? config('ai.source', 'database'),
            'semantic_query' => $semanticQuery->toArray(),
        ];

        if ($chart !== null) {
            $metadata['chart'] = $chart;
        }

        Log::info('AI DataMart resolved', [
            'module' => $moduleName,
            'module_confidence' => $metadata['module_confidence'],
            'module_reused' => $metadata['module_reused'],
            'intent' => $metadata['intent'],
            'intent_confidence' => $metadata['intent_confidence'],
            'filters' => $metadata['filters'],
            'aggregation' => $metadata['aggregation'],
            'records_matched' => $metadata['records_matched'],
            'records_injected' => $metadata['records_injected'],
            'truncated' => $metadata['truncated'],
            'execution_ms' => $duration,
            'source' => $metadata['source'],
        ]);

        return [
            'resolved' => true,
            'semantic_query' => $semanticQuery->toArray(),
            'result' => $result,
            'context' => $context,
            'chart' => $chart,
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toEngineQuery(SemanticQuery $query): array
    {
        $engineQuery = [
            'module' => $query->module,
            'filters' => $query->filters,
        ];

        if ($query->aggregation !== null) {
            $engineQuery['aggregation'] = [
                'function' => $query->aggregation,
                'field' => $query->measure,
            ];
        }

        if ($query->groupBy !== null) {
            $engineQuery['group_by'] = $query->groupBy;
        }

        if ($query->seriesBy !== null) {
            $engineQuery['series_by'] = $query->seriesBy;
        }

        if ($query->sortBy !== null) {
            $engineQuery['sort_by'] = $query->sortBy;
            $engineQuery['sort_dir'] = $query->sortDir;
        }

        if ($query->limit !== null) {
            $engineQuery['limit'] = $query->limit;
        }

        return $engineQuery;
    }

    /**
     * Bangun spesifikasi grafik mini dari hasil query ber-group.
     * Hasil ini dikirim ke frontend untuk dirender pakai Chart.js.
     *
     * Mendukung pivot: saat `series_by` hadir, keluar `datasets` (banyak seri),
     * mis. satu garis per driver sepanjang waktu. Tanpa series, keluar
     * `labels` + `values` tunggal (backward compatible).
     *
     * @return array{type: string, title: string, labels: list<string>, values: list<int|float>, datasets?: list<array{label: string, values: list<int|float>}>, currency: bool, x_label?: string, y_label?: string}|null
     */
    protected function buildChartSpec(SemanticQuery $query, array $result): ?array
    {
        $groups = $result['groups'] ?? [];

        if ($groups === [] || $query->groupBy === null) {
            return null;
        }

        // Urutkan naik (kronologis) bila group adalah dimensi waktu, agar
        // garis waktu tidak terbalik saat dipetakan ke sumbu X.
        if (in_array((string) $query->groupBy, ['month', 'distribution_date', 'request_date', 'tanggal_berangkat', 'tanggal_lpj'], true)) {
            usort($groups, fn ($a, $b) => (string) $a['group'] <=> (string) $b['group']);
        }

        $currency = in_array($query->measure, [
            'bill_amounts', 'total_budget', 'total_pengeluaran', 'total_amount',
            'avg_nominal', 'nominal_full_tank', 'price_per_liter', 'total_count',
        ], true);

        $labels = collect($groups)->pluck('group')->map(fn ($g) => (string) $g)->values()->all();

        $hasSeries = ($query->seriesBy !== null || ($result['series_by'] ?? null) !== null)
            && ! empty($groups[0]['series']);

        if ($hasSeries) {
            $seriesNames = collect($groups)
                ->flatMap(fn ($g) => array_column($g['series'], 'name'))
                ->unique()
                ->values()
                ->all();

            $datasets = array_map(function ($name) use ($groups) {
                return [
                    'label' => (string) $name,
                    'values' => collect($groups)
                        ->map(function ($g) use ($name) {
                            $match = collect($g['series'] ?? [])->first(fn ($s) => $s['name'] === $name);
                            if ($match === null) {
                                return 0;
                            }

                            return (int) round($match['value'] ?? $match['count']);
                        })
                        ->values()
                        ->all(),
                ];
            }, $seriesNames);

            $values = collect($groups)->map(fn ($g) => (int) round($g['value'] ?? $g['count']))->values()->all();

            return [
                'type' => $this->pickChartType($query, count($labels)),
                'title' => $this->chartTitle($query),
                'labels' => $labels,
                'values' => $values,
                'datasets' => $datasets,
                'currency' => $currency,
                'x_label' => $this->axisLabel($query->groupBy),
                'y_label' => $this->measureLabel($query),
            ];
        }

        $values = collect($groups)->map(fn ($g) => (int) round($g['value'] ?? $g['count']))->values()->all();

        return [
            'type' => $this->pickChartType($query, count($labels)),
            'title' => $this->chartTitle($query),
            'labels' => $labels,
            'values' => $values,
            'currency' => $currency,
            'x_label' => $this->axisLabel($query->groupBy),
            'y_label' => $this->measureLabel($query),
        ];
    }

    /**
     * Pilih jenis grafik terbaik:
     *   1. User eksplisit (pie/donut/bar/line/radar) -> ikuti.
     *   2. Data-driven: tren waktu -> line; distribusi (sedikit kategori) -> doughnut; ranking -> bar.
     *   Grafik multi-series (pivot) otomatis jadi line bila X = waktu.
     * Dipanggil LLM hanya sebagai pilihan final di reply; di sini backend yang memutuskan.
     */
    protected function pickChartType(SemanticQuery $query, int $groupCount): string
    {
        $requested = $query->chartType;
        if ($requested !== null && in_array($requested, ['bar', 'line', 'doughnut', 'pie', 'radar'], true)) {
            return $requested;
        }

        $group = (string) $query->groupBy;
        $isTimeGroup = in_array($group, ['month', 'distribution_date', 'request_date', 'tanggal_berangkat', 'tanggal_lpj'], true);

        if ($query->seriesBy !== null) {
            return $isTimeGroup && $requested === 'line' ? 'line' : 'bar';
        }

        if ($isTimeGroup) {
            return 'line';
        }

        if ($group === 'status' || $groupCount <= 6) {
            return 'doughnut';
        }

        return 'bar';
    }

    protected function chartTitle(SemanticQuery $query): string
    {
        $agg = strtolower((string) ($query->aggregation ?? 'count'));
        $group = str_replace('_', ' ', (string) $query->groupBy);

        $title = match (true) {
            str_contains($group, 'driver') => "Total per {$group}",
            str_contains($group, 'vehicle') => "Total per kendaraan",
            str_contains($group, 'month') => "Tren per bulan ({$agg})",
            $group === 'status' => "Distribusi status ({$agg})",
            default => "Total per {$group} ({$agg})",
        };

        if ($query->seriesBy !== null) {
            $series = str_replace('_', ' ', (string) $query->seriesBy);
            $title .= " — per {$series}";
        }

        return $title;
    }

    protected function axisLabel(string $dimension): string
    {
        return match ($dimension) {
            'month' => 'Bulan',
            'request_date', 'distribution_date', 'tanggal_berangkat', 'tanggal_lpj' => 'Tanggal',
            'driver' => 'Driver',
            'vehicle', 'vehicle_code' => 'Kendaraan',
            'status' => 'Status',
            'site' => 'Site',
            'gasoline_type', 'fuel_type' => 'Jenis BBM',
            default => str_replace('_', ' ', $dimension),
        };
    }

    protected function measureLabel(SemanticQuery $query): string
    {
        $agg = strtoupper((string) ($query->aggregation ?? 'COUNT'));
        $measure = str_replace('_', ' ', (string) ($query->measure ?? 'value'));

        return "{$agg} {$measure}";
    }
}
