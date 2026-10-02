<?php

namespace App\Services\AI\DataMart;

use App\Services\AI\DataMart\Contracts\DataMartContract;
use Carbon\Carbon;

/**
 * Basis semua module Data Mart.
 *
 * Menyusun skema JSON standar dan menerapkan batas `max_records` dari config.
 * Subclass hanya perlu mengimplementasikan metadata + records() + summary().
 */
abstract class AbstractDataMart implements DataMartContract
{
    /**
     * Batas baris maksimum per Data Mart (dari config ai.datamart.max_records).
     */
    protected function maxRecords(): int
    {
        return (int) config('ai.datamart.max_records', 50000);
    }

    public function dateDimension(): ?string
    {
        return null;
    }

    /**
     * Bangun skema lengkap Data Mart.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(array $options = []): array
    {
        $records = $this->records($options);

        return $this->schema(
            $this->module(),
            $this->description(),
            $this->keywords(),
            $this->entities(),
            $this->dimensions(),
            $this->measures(),
            $records,
            (int) ($options['limit'] ?? 0)
        );
    }

    /**
     * Batasi jumlah record sesuai config & opsi.
     * $requested <= 0 berarti tidak dibatasi (pakai max_records config).
     *
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    protected function bound(array $records, int $requested): array
    {
        if ($requested <= 0) {
            $requested = $this->maxRecords();
        }

        $limit = min($requested, $this->maxRecords());

        return array_slice($records, 0, $limit);
    }

    /**
     * Susun skema standar Data Mart.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    protected function schema(string $module, string $description, array $keywords, array $entities, array $dimensions, array $measures, array $records, int $requested = 0): array
    {
        return [
            'module' => $module,
            'version' => $this->version(),
            'generated_at' => Carbon::now()->toIso8601String(),
            'description' => $description,
            'keywords' => $keywords,
            'entities' => $entities,
            'dimensions' => $dimensions,
            'measures' => $measures,
            'summary' => $this->summary($records),
            'records' => $records,
        ];
    }

    /**
     * Blok filter database yang didukung module ini. Subclass boleh override.
     *
     * @return array<string, string>
     */
    protected function supportedFilters(): array
    {
        return [];
    }

    /**
     * Terapkan scope unit bisnis (inUnit by unit_id) ke query bila user bukan pusat.
     * Dipakai module yang modelnya sudah punya kolom unit_id (BBM, SPD, vehicle).
     *
     * @param  mixed  $query  \Illuminate\Database\Eloquent\Builder
     * @param  array<string, mixed>  $options
     */
    protected function applyUnitScope($query, array $options): void
    {
        $scope = $options['scope'] ?? null;

        if ($scope instanceof DataMartScope && ! $scope->isGlobal()) {
            $query->inUnit($scope->unitId);
        }
    }

    /**
     * Terapkan scope unit bisnis (inUnit by site ids) ke query.
     * Dipakai module yang modelnya masih memakai scope berbasis site (Lunch).
     *
     * @param  mixed  $query  \Illuminate\Database\Eloquent\Builder
     * @param  array<string, mixed>  $options
     */
    protected function applySiteScope($query, array $options): void
    {
        $scope = $options['scope'] ?? null;

        if ($scope instanceof DataMartScope && ! $scope->isGlobal()) {
            $query->inUnit($scope->siteIds);
        }
    }

    /**
     * Daftar module lain yang bertanggung jawab atas entity jika filter lintas
     * module diperlukan di masa depan. Kosong secara default.
     *
     * @return array<string, string>
     */
    public function filterHints(): array
    {
        return [];
    }
}
