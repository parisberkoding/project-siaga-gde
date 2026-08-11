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
        public ?string $sortBy = null,
        public string $sortDir = 'desc',
        public ?int $limit = null,
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
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
            'limit' => $this->limit,
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
            sortBy: isset($data['sort_by']) && $data['sort_by'] !== null ? (string) $data['sort_by'] : null,
            sortDir: (string) ($data['sort_dir'] ?? 'desc'),
            limit: isset($data['limit']) && $data['limit'] !== null ? (int) $data['limit'] : null,
        );
    }
}
