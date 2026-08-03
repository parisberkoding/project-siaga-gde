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
        'end'   => env('SIAGA_LUNCH_END',   '13:30'),
    ],

    /*
     * Jam generate otomatis Forecast H+1 (setelah jam makan siang selesai,
     * sehingga jawaban "Besok makan dimana?" hari ini sudah masuk).
     */
    'forecast_generate_at' => env('SIAGA_FORECAST_AT', '15:00'),

];
