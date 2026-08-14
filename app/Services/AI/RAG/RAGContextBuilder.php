<?php

namespace App\Services\AI\RAG;

use App\Services\AI\Query\SemanticQuery;

/**
 * Menyusun konteks ringkas yang diinjeksi ke Gemini.
 *
 * Konteks berisi data TERVERIFIKASI hasil backend — bukan data mentah besar.
 * Konteks detail hanya menyertakan TOP-K record yang relevan + flag truncated.
 */
class RAGContextBuilder
{
    /**
     * @param  array<string, mixed>  $result  Hasil DataMartQueryEngine::execute()
     */
    public function build(SemanticQuery $query, array $result): string
    {
        $lines = [];
        $lines[] = 'DATA SOURCE: Temporary Data Mart: '.$query->module;
        $lines[] = 'SOURCE STATUS: Verified from application database.';
        $lines[] = 'INTENT: '.$query->intent;

        if (! empty($query->filters)) {
            $lines[] = 'FILTERS: '.$this->formatFilters($query->filters);
        }

        if ($result['group_by'] ?? null) {
            $lines[] = 'GROUPED BY: '.$result['group_by'];
            $lines[] = 'TOTAL MATCHED: '.($result['matched'] ?? 0);
            $lines[] = 'GROUPS:';
            foreach (($result['groups'] ?? []) as $group) {
                $lines[] = '  - '.$group['group'].': '.$group['count'].' transaksi'
                    .($group['value'] !== null ? ' (nilai '.$this->formatNumber($group['value']).')' : '');
            }
        } else {
            $lines[] = 'VERIFIED RESULT: '.$this->formatAggregation($result);
            $lines[] = 'RECORD COUNT: '.($result['matched'] ?? 0);
            $lines[] = 'RETURNED TO MODEL: '.($result['returned'] ?? 0);
            $lines[] = 'TRUNCATED: '.(($result['truncated'] ?? false) ? 'true' : 'false');

            if ($result['truncated'] ?? false) {
                $returned = $result['returned'] ?? 0;
                $matched = $result['matched'] ?? 0;
                $lines[] = 'NOTE FOR MODEL: Anda HANYA menerima '.$returned.' dari total '.$matched.' record (data terbaru). Sebutkan jumlah ini dalam jawaban Anda. Contoh: "Saya hanya bisa menampilkan '.$returned.' dari total '.$matched.' record terbaru."';
            }
        }

        if (in_array($query->intent, ['detail', 'listing'], true) && ($result['records'] ?? []) !== []) {
            $returned = $result['returned'] ?? count($result['records']);
            $matched = $result['matched'] ?? $returned;
            $lines[] = 'RECORDS DISPLAYED: '.$returned.' of '.$matched.' (data terbaru)';
            $lines[] = 'RESULTS:';
            $lines[] = json_encode(
                array_map(fn ($row) => $this->sanitize($row), $result['records']),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function formatAggregation(array $result): string
    {
        $aggregation = $result['aggregation'] ?? null;

        if ($aggregation === null || ! isset($aggregation['value'])) {
            return 'tidak ada agregasi';
        }

        $field = $aggregation['field'] ?? '';
        $isCurrency = in_array($field, ['bill_amounts', 'nominal_full_tank', 'price_per_liter', 'avg_nominal'], true);

        return $aggregation['function'].'('.$field.') = '
            .($isCurrency ? 'Rp '.number_format((float) $aggregation['value'], 0, ',', '.') : $this->formatNumber($aggregation['value']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function formatFilters(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if ($key === 'date_from' && isset($filters['date_to'])) {
                $parts[] = 'date='.$value.' → '.$filters['date_to'];

                continue;
            }
            if ($key === 'date_to') {
                continue;
            }
            $parts[] = $key.'='.(is_array($value) ? implode(',', $value) : $value);
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sanitize(array $row): array
    {
        unset($row['id'], $row['requester_id'], $row['consumerial_tools_id']);

        return $row;
    }

    protected function formatNumber(int|float $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}
