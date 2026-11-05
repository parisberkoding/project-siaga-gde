<?php

/*
|--------------------------------------------------------------------------
| GATE HCIS — sumber data pegawai Geodipa
|--------------------------------------------------------------------------
|
| Dipakai untuk menyinkronkan akun Pengguna dari HR (menu Pengguna → "Sync
| dari GATE" dan perintah `php artisan gate:sync-employees`).
|
| Kredensial WAJIB lewat .env — jangan ditulis di file ini.
|
*/

return [

    /*
    | Saklar utama. Selama false (default, termasuk di lokal) TIDAK ada request
    | ke GATE sama sekali: tombol "Sync dari GATE" nonaktif dan perintah
    | gate:sync-employees berhenti dengan pesan. Aktifkan di server yang memang
    | boleh mengakses GATE (GATE_SYNC_ENABLED=true + kredensial).
    */
    'enabled'  => (bool) env('GATE_SYNC_ENABLED', false),

    'url'      => env('GATE_HCIS_URL', 'https://gate.geodipa.co.id/api/v1/hcis/employees'),
    'username' => env('GATE_HCIS_USERNAME'),
    'password' => env('GATE_HCIS_PASSWORD'),
    'timeout'  => (int) env('GATE_HCIS_TIMEOUT', 90),

    /*
    | Role yang diberikan kepada akun BARU hasil sinkron. Akun yang sudah ada
    | tidak diubah role-nya, supaya penyesuaian oleh admin tidak tertimpa.
    */
    'default_role' => 'pegawai',

    /*
    | Prefiks user_code untuk akun hasil sinkron (format: P00.0042.PST),
    | sama dengan prefiks role Pegawai di PegawaiDemoSeeder.
    */
    'user_code_prefix' => 'P00',

    /*
    | worklocation_code GATE → kode Unit Bisnis SIAGA (tabel `units`).
    | Pegawai dengan kode yang tidak terdaftar di sini DILEWATI dan
    | dilaporkan — unit tidak dikosongkan, karena unit kosong di SIAGA
    | berarti akses lintas unit (global).
    |
    |   PST = Kantor Pusat        HPR = Proyek (Engineering/GS, berkantor pusat)
    |   DNG = Dieng               DPR = Project Manager 1 (Dieng)
    |   PTH = Patuha              PPR = Project Manager 2 (Patuha)
    */
    'unit_map' => [
        'PST' => 'PUSAT',
        'HPR' => 'PUSAT',
        'DNG' => 'DIENG',
        'DPR' => 'DIENG',
        'PTH' => 'PATUHA',
        'PPR' => 'PATUHA',
    ],
];
