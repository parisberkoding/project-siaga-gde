<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SIAGA - Lunch Management
    |--------------------------------------------------------------------------
    |
    | Konfigurasi modul Lunch Management. Seluruh konfigurasi trip/vehicle
    | tracking telah dihapus: perusahaan memakai GPS Tracker vendor dan
    | Lunch Management berdiri sendiri di atas QR Attendance.
    |
    */

    /*
     * Window jam makan siang (format H:i, 24-jam).
     * Ditampilkan pada halaman QR Attendance sebagai informasi jam layanan.
     */
    'lunch_window' => [
        'start' => env('SIAGA_LUNCH_START', '11:30'),
        'end' => env('SIAGA_LUNCH_END', '13:30'),
    ],

    /*
     * Jam generate otomatis Forecast H+1 (setelah jam makan siang selesai,
     * sehingga jawaban "Besok makan dimana?" hari ini sudah masuk).
     */
    'forecast_generate_at' => env('SIAGA_FORECAST_AT', '15:00'),

    /*
     * Kop laporan format pengecekan kendaraan dispatcher.
     * Semua nilai bisa di-override lewat env, atau per-export lewat form
     * (nama perusahaan & logo), sehingga kop bisa dicustom tanpa ubah kode.
     */
    'report' => [
        'company_name' => env('SIAGA_REPORT_COMPANY_NAME', 'PT. Geo Dipa Energi (Persero)'),
        'unit_name' => env('SIAGA_REPORT_UNIT_NAME', 'Unit Patuha'),
        'app_name' => env('SIAGA_REPORT_APP_NAME', 'SIAGA — Sistem Informasi Pengelolaan Kendaraan'),
        // Path default logo yang dipakai pada kop laporan (bisa di-override per-export).
        'logo_path' => env('SIAGA_REPORT_LOGO_PATH', public_path('DashboardTemplates/assets/img/geodipa-logo.png')),
    ],

];
