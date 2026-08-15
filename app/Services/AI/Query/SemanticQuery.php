<?php

namespace App\Services\AI\Query;

/**
 * Semantic Query Object: jembatan antara bahasa alami dan eksekusi deterministik.
 *
 * @phpstan-consistent-constructor
 */
class SemanticQuery
{
    public function __construct(
        public string $module,
        public string $intent,
        public ?string $measure = null,
        public ?string $aggregation = null,
        public array $filters = [],
        public ?string $groupBy = null,
        public ?string $seriesBy = null,
        public ?string $sortBy = null,
        public string $sortDir = 'desc',
        public ?int $limit = null,
        public ?string $chartType = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'intent' => $this->intent,
            'measure' => $this->measure,
            'aggregation' => $this->aggregation,
            'filters' => $this->filters,
            'group_by' => $this->groupBy,
            'series_by' => $this->seriesBy,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
            'limit' => $this->limit,
            'chart_type' => $this->chartType,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            module: (string) ($data['module'] ?? ''),
            intent: (string) ($data['intent'] ?? 'summary'),
            measure: isset($data['measure']) && $data['measure'] !== null ? (string) $data['measure'] : null,
            aggregation: isset($data['aggregation']) && $data['aggregation'] !== null ? (string) $data['aggregation'] : null,
            filters: (array) ($data['filters'] ?? []),
            groupBy: isset($data['group_by']) && $data['group_by'] !== null ? (string) $data['group_by'] : null,
            seriesBy: isset($data['series_by']) && $data['series_by'] !== null ? (string) $data['series_by'] : null,
            sortBy: isset($data['sort_by']) && $data['sort_by'] !== null ? (string) $data['sort_by'] : null,
            sortDir: (string) ($data['sort_dir'] ?? 'desc'),
            limit: isset($data['limit']) && $data['limit'] !== null ? (int) $data['limit'] : null,
            chartType: isset($data['chart_type']) && $data['chart_type'] !== null ? (string) $data['chart_type'] : null,
        );
    }
}
