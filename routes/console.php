<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Lunch Management — Forecast H+1 otomatis setiap sore.
 * Jawaban popup "Besok makan dimana?" hari ini menjadi input prioritas
 * ke-2, sehingga dijalankan setelah jam makan siang selesai.
 */
use Illuminate\Support\Facades\Schedule;

Schedule::command('lunch:generate-forecast')
    ->dailyAt(config('siaga.forecast_generate_at', '15:00'));

/*
 * Master Gasoline — sinkronisasi harga BBM (PERTAMAX & PERTAMINA DEX)
 * dari bensin-api setiap pagi sebelum jam operasional.
 */
Schedule::command('gasoline:sync')
    ->dailyAt('06:00');
