<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehiclePartSeeder extends Seeder
{
    /**
     * Seeder lengkap untuk semua item pengecekan kendaraan
     *
     * Sheet A — "Kendaraan" (Pengecekan Rutin): 21 item
     *   • KESIAPAN      (1-11)  → Harian,   Binary V/X
     *   • KESELAMATAN   (12-16) → Mingguan,  Binary V/X
     *   • KELENGKAPAN   (17-21) → Mingguan & Bulanan, Binary V/X
     *
     * Sheet B — "Cek Body" (Pengecekan Body): 10 item
     *   • BODY           (1-10) → Bulanan, Scale Baik/Cukup/Kurang/Tidak
     *
     * Total: 31 item
     */
    public function run(): void
    {
        $now = now();

        $parts = [

            // ╔═══════════════════════════════════════════════════════════╗
            // ║  SHEET A — PENGECEKAN RUTIN KENDARAAN                     ║
            // ╠═══════════════════════════════════════════════════════════╣
            // ║  KESIAPAN — Harian (11 item, Binary V/X)                  ║
            // ╚═══════════════════════════════════════════════════════════╝

            ['part_code' => 'PRT-MESIN',           'name' => 'Mesin',                          'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Normal (Tidak ada kelainan)',             'sort_order' => 1],
            ['part_code' => 'PRT-SOLAR-BENSIN',    'name' => 'Solar/Bensin',                   'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Volume',                                  'sort_order' => 2],
            ['part_code' => 'PRT-BAN',             'name' => 'Ban',                            'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Tidak bocor (Tekanan Sesuai Standar)',    'sort_order' => 3],
            ['part_code' => 'PRT-BAN-CADANGAN',    'name' => 'Ban Cadangan',                   'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Tidak bocor (Tekanan Sesuai Standar)',    'sort_order' => 4],
            ['part_code' => 'PRT-REM',             'name' => 'Rem',                            'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Berfungsi',                              'sort_order' => 5],
            ['part_code' => 'PRT-COPLING-MATIC',   'name' => 'Copling/Matic',                  'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Berfungsi',                              'sort_order' => 6],
            ['part_code' => 'PRT-ELECTRIC-LAMPU',  'name' => 'Electric & Penerangan/Lampu',    'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Semua Lampu Harus Nyala',                'sort_order' => 7],
            ['part_code' => 'PRT-OLIE-REM',        'name' => 'Olie Rem',                       'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Sesuai Level',                           'sort_order' => 8],
            ['part_code' => 'PRT-OLIE-MESIN',      'name' => 'Olie Mesin',                     'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Sesuai standar & Kualitas',              'sort_order' => 9],
            ['part_code' => 'PRT-AKI-BATERAI',     'name' => 'Kondisi AKI/Baterai',            'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Sesuai Level/Kondisi Baik',              'sort_order' => 10],
            ['part_code' => 'PRT-AIR-RADIATOR',    'name' => 'Air Radiator',                   'category' => 'kesiapan',    'checking_group' => 'routine', 'check_frequency' => 'daily',   'status_type' => 'binary', 'standard_description' => 'Harus Penuh',                            'sort_order' => 11],

            // ╔═══════════════════════════════════════════════════════════╗
            // ║  KESELAMATAN — Mingguan (5 item, Binary V/X)              ║
            // ╚═══════════════════════════════════════════════════════════╝

            ['part_code' => 'PRT-SABUK-KESELAMATAN', 'name' => 'Sabuk Keselamatan',            'category' => 'keselamatan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Ada & Berfungsi',                       'sort_order' => 12],
            ['part_code' => 'PRT-EMERGENCY-P3K',     'name' => 'Peralatan Emergency/P3K',      'category' => 'keselamatan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Ada & Lengkap',                         'sort_order' => 13],
            ['part_code' => 'PRT-PEMECAH-KACA',      'name' => 'Pemecah Kaca',                 'category' => 'keselamatan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Ada & Berfungsi',                       'sort_order' => 14],
            ['part_code' => 'PRT-APAR',              'name' => 'APAR',                         'category' => 'keselamatan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Ada & Berfungsi',                       'sort_order' => 15],
            ['part_code' => 'PRT-DONKRAK-BAN',       'name' => 'Donkrak Ban',                  'category' => 'keselamatan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Ada & Berfungsi',                       'sort_order' => 16],

            // ╔═══════════════════════════════════════════════════════════╗
            // ║  KELENGKAPAN — Mingguan (3 item) + Bulanan (2 item)       ║
            // ╚═══════════════════════════════════════════════════════════╝

            ['part_code' => 'PRT-ALARM-MUNDUR',    'name' => 'Alarm Mundur',                   'category' => 'kelengkapan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Berfungsi',                              'sort_order' => 17],
            ['part_code' => 'PRT-DASHCAM',         'name' => 'Dashcam',                        'category' => 'kelengkapan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Berfungsi',                              'sort_order' => 18],
            ['part_code' => 'PRT-GPS',             'name' => 'GPS',                            'category' => 'kelengkapan', 'checking_group' => 'routine', 'check_frequency' => 'weekly',  'status_type' => 'binary', 'standard_description' => 'Berfungsi',                              'sort_order' => 19],
            ['part_code' => 'PRT-STNK',           'name' => 'Izin Kendaraan/STNK',            'category' => 'kelengkapan', 'checking_group' => 'routine', 'check_frequency' => 'monthly', 'status_type' => 'binary', 'standard_description' => 'Masih Berlaku',                          'sort_order' => 20],
            ['part_code' => 'PRT-KIR',            'name' => 'KIR',                             'category' => 'kelengkapan', 'checking_group' => 'routine', 'check_frequency' => 'monthly', 'status_type' => 'binary', 'standard_description' => 'Masih Berlaku',                          'sort_order' => 21],

            // ╔═══════════════════════════════════════════════════════════╗
            // ║  SHEET B — PENGECEKAN BODY KENDARAAN                      ║
            // ║  Bulanan (10 item, Scale Baik/Cukup/Kurang/Tidak)         ║
            // ╚═══════════════════════════════════════════════════════════╝

            ['part_code' => 'PRT-HEAD-LAMP',       'name' => 'Head Lamp',                      'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi & Kondisi Baik',              'sort_order' => 22],
            ['part_code' => 'PRT-FOG-LAMP',        'name' => 'Fog Lamp',                       'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi & Kondisi Baik',              'sort_order' => 23],
            ['part_code' => 'PRT-AC',              'name' => 'AC',                             'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi & Dingin',                    'sort_order' => 24],
            ['part_code' => 'PRT-KLAKSON',         'name' => 'Klakson',                        'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi',                              'sort_order' => 25],
            ['part_code' => 'PRT-LAMPU-JAUH',      'name' => 'Lampu Jarak Jauh',               'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi & Kondisi Baik',              'sort_order' => 26],
            ['part_code' => 'PRT-SEIN',            'name' => 'Sein',                           'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi',                              'sort_order' => 27],
            ['part_code' => 'PRT-LAMPU-REM',       'name' => 'Lampu Rem',                      'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi',                              'sort_order' => 28],
            ['part_code' => 'PRT-REM-BODY',        'name' => 'Rem',                            'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Berfungsi',                              'sort_order' => 29],
            ['part_code' => 'PRT-SEATBELT',        'name' => 'Seatbelt',                       'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => 'Ada & Berfungsi',                       'sort_order' => 30],
            ['part_code' => 'PRT-LAIN-LAIN',       'name' => 'Lain-lain',                      'category' => 'body',        'checking_group' => 'body',    'check_frequency' => 'monthly', 'status_type' => 'scale',  'standard_description' => null,                                     'sort_order' => 31],
        ];

        foreach ($parts as $part) {
            DB::table('vehicle_parts')->updateOrInsert(
                ['part_code' => $part['part_code']],
                array_merge($part, [
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
