<?php

/*
|--------------------------------------------------------------------------
| Registry Fitur (item 6 — feature stage)
|--------------------------------------------------------------------------
|
| Daftar fitur yang dapat diatur tahap rilisnya (development / production).
| Dipakai oleh FeatureSeeder untuk mendaftarkan fitur, dan oleh UI "Tahap
| Fitur" untuk menampilkannya.
|
| Kunci array adalah `code` fitur (unik). Nilai:
|   name        -> nama tampilan
|   description -> keterangan opsional
|   module      -> kode modul siklus (untuk pengelompokan di UI)
|   stage       -> tahap awal saat PERTAMA KALI didaftarkan. Setelah itu,
|                  keputusan developer TIDAK ditimpa oleh seeder.
|
| Daftar ini adalah TITIK AWAL — developer dapat menambah fitur baru lewat UI
| tanpa menyentuh berkas ini. Fitur yang TIDAK terdaftar di manapun
| diperlakukan sebagai production (fail-open) supaya halaman yang lupa
| didaftarkan tidak hilang.
|
*/

return [

    'registry' => [

        // ── BBM & Permohonan ──
        'bbm.permohonan' => [
            'name' => 'Permohonan BBM',
            'description' => 'Pembuatan, riwayat, dan approval permohonan BBM.',
            'module' => 'bbm',
            'stage' => 'production',
        ],
        'bbm.pelaporan_operasional' => [
            'name' => 'Pelaporan BBM Operasional',
            'description' => 'Upload struk & laporan pembelian BBM operasional.',
            'module' => 'bbm',
            'stage' => 'production',
        ],

        // ── Perjalanan Dinas ──
        'spd.surat_tugas' => [
            'name' => 'Surat Tugas SPD',
            'description' => 'Pembuatan dan validasi surat perjalanan dinas.',
            'module' => 'spd',
            'stage' => 'production',
        ],
        'spd.lpj' => [
            'name' => 'LPJ SPD',
            'description' => 'Laporan pertanggungjawaban perjalanan dinas.',
            'module' => 'spd',
            'stage' => 'production',
        ],
        'spd.perpanjangan' => [
            'name' => 'Perpanjangan SPD (ETD)',
            'description' => 'Perpanjangan tanggal perjalanan dinas.',
            'module' => 'spd',
            'stage' => 'production',
        ],

        // ── Kendaraan & Alat ──
        'vehicle.master' => [
            'name' => 'Master Kendaraan & Alat',
            'description' => 'Pendaftaran, aktivasi, dan detail kendaraan.',
            'module' => 'vehicle',
            'stage' => 'production',
        ],
        'vehicle.pengecekan' => [
            'name' => 'Pengecekan Kendaraan',
            'description' => 'Body checking, harian, mingguan, dan bulanan.',
            'module' => 'vehicle',
            'stage' => 'production',
        ],
        'vehicle.sparepart' => [
            'name' => 'Sparepart & Service',
            'description' => 'Permohonan VPR, jadwal service, dan monitoring transaksi.',
            'module' => 'vehicle',
            'stage' => 'production',
        ],

        // ── Lunch ──
        'lunch.dashboard' => [
            'name' => 'Dashboard Lunch',
            'description' => 'Kehadiran, forecast, dan distribusi makan siang.',
            'module' => 'lunch',
            'stage' => 'production',
        ],

        // ── AI ──
        'ai.assistant' => [
            'name' => 'AI Assistant',
            'description' => 'Asisten AI & analisis struk (OCR).',
            'module' => 'ai',
            'stage' => 'development', // masih beta — contoh fitur yang sengaja development
        ],
    ],
];
