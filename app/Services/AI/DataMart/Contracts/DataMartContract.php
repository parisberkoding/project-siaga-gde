<?php

namespace App\Services\AI\DataMart\Contracts;

/**
 * Kontrak semua Temporary Data Mart module.
 *
 * Setiap module harus mengekspos skema standar: module, version, generated_at,
 * description, keywords, entities, dimensions, measures, summary, dan records.
 *
 * Prinsip: database adalah source of truth. Data Mart adalah representasi
 * analitis sementara yang dibangun saat runtime untuk kebutuhan AI.
 */
interface DataMartContract
{
    /**
     * Identitas unik module.
     */
    public function module(): string;

    /**
     * Versi skema Data Mart.
     */
    public function version(): string;

    /**
     * Deskripsi module untuk observability / debug.
     */
    public function description(): string;

    /**
     * Kata kunci bahasa alami yang memetakan pertanyaan user ke module ini.
     *
     * @return list<string>
     */
    public function keywords(): array;

    /**
     * Entity yang tersedia dalam module.
     *
     * @return list<string>
     */
    public function entities(): array;

    /**
     * Dimensi analitis (untuk filter / group by).
     *
     * @return list<string>
     */
    public function dimensions(): array;

    /**
     * Fields terukur (untuk agregasi).
     *
     * @return list<string>
     */
    public function measures(): array;

    /**
     * Dimensi tanggal default yang dipakai untuk filter date_from/date_to.
     * Return null bila module tidak punya dimensi tanggal.
     */
    public function dateDimension(): ?string;

    /**
     * Bangun rangkaian record ternormalisasi dari database.
     *
     * @param  array<string, mixed>  $options  Contoh: ['filters' => [...], 'limit' => int]
     * @return list<array<string, mixed>>
     */
    public function records(array $options = []): array;

    /**
     * Summary deterministik yang dihitung oleh PHP/Laravel.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public function summary(array $records = []): array;

    /**
     * Bangun skema Data Mart lengkap (JSON-serializable).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(array $options = []): array;
}
