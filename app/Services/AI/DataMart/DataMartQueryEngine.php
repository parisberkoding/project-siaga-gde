<?php

namespace App\Services\AI\DataMart;

use App\Services\AI\DataMart\Contracts\DataMartContract;
use InvalidArgumentException;

/**
 * Query engine deterministik untuk Data Mart.
 *
 * Hanya field yang di-allowlist (dimensions/measures module) yang boleh
 * digunakan sebagai filter, aggregasi, group, maupun sort. Semua perhitungan
 * dilakukan oleh PHP/Laravel — tidak pernah oleh LLM.
 *
 * @phpstan-type AggregationArray array{function: string, field?: string}
 */
class DataMartQueryEngine
{
    protected const AGGREGATIONS = ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'];

    protected const SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * Eksekusi query terhadap module Data Mart.
     *
     * @param  array<string, mixed>  $query  {
     *                                       module: string,
     *                                       filters?: array<string, mixed>,
     *                                       aggregation?: array{function: string, field?: string},
     *                                       group_by?: string,
     *                                       sort_by?: string|null,
     *                                       sort_dir?: 'asc'|'desc',
     *                                       limit?: int|null,
     *                                       offset?: int,
     *                                       }
     * @return array<string, mixed>
     */
    public function execute(array $query, ?DataMartContract $dataMart = null): array
    {
        $start = hrtime(true);

        $module = (string) ($query['module'] ?? '');
        $dataMart ??= app(DataMartManager::class)->resolve($module);

        $this->validateQuery($query, $dataMart);

        $filters = $query['filters'] ?? [];
        $records = $dataMart->records(['filters' => $filters]);

        $aggregation = $this->buildAggregation($query, $dataMart);
        $groupBy = isset($query['group_by']) ? (string) $query['group_by'] : null;
        $sortBy = isset($query['sort_by']) && $query['sort_by'] !== null ? (string) $query['sort_by'] : null;
        $sortDir = strtolower((string) ($query['sort_dir'] ?? 'desc'));
        $limit = isset($query['limit']) && $query['limit'] !== null ? (int) $query['limit'] : null;
        $offset = (int) ($query['offset'] ?? 0);

        // Filter in-memory (idempotent terhadap filter DB di module).
        $matched = $this->filterRecords($records, $filters, $dataMart);

        if ($groupBy !== null) {
            $grouped = $this->groupRecords($matched, $groupBy, $aggregation);

            $result = [
                'module' => $module,
                'aggregation' => $aggregation !== null
                    ? ['function' => $aggregation['function'], 'field' => $aggregation['field'] ?? null]
                    : null,
                'group_by' => $groupBy,
                'groups' => $grouped,
                'matched' => count($matched),
            ];
        } else {
            if ($sortBy !== null) {
                usort($matched, fn ($a, $b) => $this->compare($a, $b, $sortBy, $sortDir));
            }

            $total = count($matched);

            if ($aggregation !== null) {
                // Query agregasi: hitung atas seluruh matched, tanpa membawa record mentah.
                $sliced = [];
                $returned = 0;
                $aggregated = [
                    'function' => $aggregation['function'],
                    'field' => $aggregation['field'] ?? null,
                    'value' => $this->aggregate($matched, $aggregation, $dataMart),
                ];
            } else {
                $sliced = $limit !== null ? array_slice($matched, $offset, $limit) : array_slice($matched, $offset);
                $returned = count($sliced);
                $aggregated = null;
            }

            $result = [
                'module' => $module,
                'aggregation' => $aggregated,
                'matched' => $total,
                'records' => $sliced,
                'returned' => $returned,
                'truncated' => $limit !== null && $limit < $total,
            ];
        }

        $result['source'] = config('ai.source', 'database');
        $result['execution_ms'] = (int) round((hrtime(true) - $start) / 1e6);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function validateQuery(array $query, DataMartContract $dataMart): void
    {
        $dimensions = $dataMart->dimensions();
        $measures = $dataMart->measures();
        $allowed = array_merge($dimensions, $measures);

        $filters = $query['filters'] ?? [];
        foreach (array_keys($filters) as $key) {
            if (in_array($key, ['date_from', 'date_to'], true)) {
                continue;
            }
            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException("Filter tidak diizinkan: {$key}");
            }
        }

        if (isset($query['aggregation'])) {
            $agg = $query['aggregation'];
            if (! is_array($agg) || ! isset($agg['function'])) {
                throw new InvalidArgumentException('Struktur aggregasi tidak valid.');
            }
            $function = strtoupper((string) $agg['function']);
            if (! in_array($function, self::AGGREGATIONS, true)) {
                throw new InvalidArgumentException("Fungsi agregasi tidak diizinkan: {$agg['function']}");
            }
            if ($function !== 'COUNT') {
                $field = (string) ($agg['field'] ?? '');
                if (! in_array($field, $measures, true)) {
                    throw new InvalidArgumentException("Field agregasi tidak diizinkan: {$field}");
                }
            }
        }

        if (isset($query['group_by'])) {
            $group = (string) $query['group_by'];
            if (! in_array($group, $dimensions, true)) {
                throw new InvalidArgumentException("Dimensi group tidak diizinkan: {$group}");
            }
        }

        if (isset($query['sort_by']) && $query['sort_by'] !== null) {
            $field = (string) $query['sort_by'];
            if (! in_array($field, $allowed, true)) {
                throw new InvalidArgumentException("Field sort tidak diizinkan: {$field}");
            }
        }

        if (isset($query['sort_dir'])) {
            $dir = strtolower((string) $query['sort_dir']);
            if (! in_array($dir, self::SORT_DIRECTIONS, true)) {
                throw new InvalidArgumentException("Arah sort tidak valid: {$query['sort_dir']}");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{function: string, field?: string}|null
     */
    protected function buildAggregation(array $query, DataMartContract $dataMart): ?array
    {
        if (! isset($query['aggregation'])) {
            return null;
        }

        $agg = $query['aggregation'];
        $function = strtoupper((string) $agg['function']);

        if ($function === 'COUNT') {
            return ['function' => 'COUNT'];
        }

        return [
            'function' => $function,
            'field' => (string) $agg['field'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    protected function filterRecords(array $records, array $filters, DataMartContract $dataMart): array
    {
        $dateField = $dataMart->dateDimension();
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return array_values(array_filter($records, function (array $row) use ($filters, $dateField, $dateFrom, $dateTo) {
            foreach ($filters as $key => $value) {
                if ($key === 'date_from' || $key === 'date_to') {
                    continue;
                }

                $rowValue = $row[$key] ?? null;
                if (! $this->matchesValue($rowValue, $value)) {
                    return false;
                }
            }

            if ($dateField !== null && ($dateFrom !== null || $dateTo !== null)) {
                $rowDate = $row[$dateField] ?? null;
                if ($rowDate === null) {
                    return false;
                }
                if ($dateFrom !== null && (string) $rowDate < (string) $dateFrom) {
                    return false;
                }
                if ($dateTo !== null && (string) $rowDate > (string) $dateTo) {
                    return false;
                }
            }

            return true;
        }));
    }

    protected function matchesValue(mixed $rowValue, mixed $needle): bool
    {
        if (is_array($needle)) {
            foreach ($needle as $single) {
                if ($this->matchesValue($rowValue, $single)) {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($rowValue) && in_array($needle, ['true', 'false', '1', '0', true, false], true)) {
            return (bool) $rowValue === filter_var($needle, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_numeric($rowValue)) {
            return (float) $rowValue === (float) $needle;
        }

        return mb_strtolower((string) $rowValue) === mb_strtolower(trim((string) $needle));
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array{function: string, field?: string}  $aggregation
     */
    protected function aggregate(array $records, array $aggregation, DataMartContract $dataMart): int|float|null
    {
        $function = $aggregation['function'];

        if ($function === 'COUNT') {
            return count($records);
        }

        $values = array_map(fn ($row) => (float) ($row[$aggregation['field']] ?? 0), $records);
        $values = array_values(array_filter($values, fn ($v) => $v !== null));

        return match ($function) {
            'SUM' => round(array_sum($values), 2),
            'AVG' => count($values) > 0 ? round(array_sum($values) / count($values), 2) : 0,
            'MIN' => count($values) > 0 ? min($values) : 0,
            'MAX' => count($values) > 0 ? max($values) : 0,
            default => 0,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array{function: string, field?: string}|null  $aggregation
     * @return array<string, array<string, mixed>>
     */
    protected function groupRecords(array $records, string $groupBy, ?array $aggregation): array
    {
        $groups = [];
        foreach ($records as $row) {
            $key = (string) ($row[$groupBy] ?? 'Tidak diketahui');
            if (! isset($groups[$key])) {
                $groups[$key] = ['group' => $key, 'count' => 0, 'value' => null];
            }
            $groups[$key]['count']++;
            if ($aggregation !== null && $aggregation['function'] !== 'COUNT') {
                $value = (float) ($row[$aggregation['field']] ?? 0);
                $groups[$key]['value'] = ($groups[$key]['value'] ?? 0) + $value;
            }
        }

        if ($aggregation !== null && $aggregation['function'] === 'AVG') {
            foreach ($groups as &$group) {
                $group['value'] = $group['count'] > 0 ? round($group['value'] / $group['count'], 2) : 0;
            }
        }

        // Urutkan descending berdasarkan value/count agar deterministik.
        uasort($groups, function ($a, $b) {
            return ($b['value'] ?? $b['count']) <=> ($a['value'] ?? $a['count']);
        });

        return array_values($groups);
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    protected function compare(array $a, array $b, string $field, string $direction): int
    {
        $av = $a[$field] ?? null;
        $bv = $b[$field] ?? null;
        $cmp = $av <=> $bv;

        return $direction === 'desc' ? -$cmp : $cmp;
    }
}
