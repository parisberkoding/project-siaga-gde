<?php

namespace App\Services\Gate;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API GATE HCIS (data pegawai Geodipa).
 *
 * Respons berformat OData: { "d": { "results": [ {pegawai}, ... ] } }.
 * Autentikasi Basic Auth; kredensial dari config/gate.php (.env).
 */
class GateHcisClient
{
    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException bila konfigurasi kurang, HTTP gagal, atau format tak dikenal.
     */
    public function employees(): array
    {
        // Satu titik untuk UI dan artisan: saklar mati = tanpa request keluar.
        if (! config('gate.enabled')) {
            throw new RuntimeException('Sinkron GATE dinonaktifkan di lingkungan ini (GATE_SYNC_ENABLED=false).');
        }

        $url = config('gate.url');
        $username = config('gate.username');
        $password = config('gate.password');

        if (! $url || ! $username || ! $password) {
            throw new RuntimeException('Konfigurasi GATE belum lengkap (GATE_HCIS_URL / USERNAME / PASSWORD di .env).');
        }

        $response = Http::withBasicAuth($username, $password)
            ->acceptJson()
            ->timeout(config('gate.timeout', 90))
            ->retry(2, 1000, throw: false)
            ->get($url);

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('GATE menolak kredensial (HTTP '.$response->status().'). Periksa username/password di .env.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('GATE tidak dapat dihubungi (HTTP '.$response->status().').');
        }

        $rows = $response->json('d.results');

        if (! is_array($rows)) {
            throw new RuntimeException('Format respons GATE tidak dikenal (d.results tidak ditemukan).');
        }

        return $rows;
    }
}
