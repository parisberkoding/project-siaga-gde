<?php

namespace App\Console\Commands;

use App\Services\Lunch\LunchDistributionService;
use App\Services\Lunch\LunchForecastService;
use Illuminate\Console\Command;

class GenerateLunchForecast extends Command
{
    protected $signature = 'lunch:generate-forecast {date? : Tanggal target (Y-m-d), default besok}';

    protected $description = 'Generate Lunch Forecast H+1 (manual override > konfirmasi besok > pola historis > default site) lalu hitung distribusi per site';

    public function handle(LunchForecastService $forecast, LunchDistributionService $distribution): int
    {
        $date = $this->argument('date') ?? now()->addDay()->toDateString();

        $this->info("Generating lunch forecast untuk {$date}…");

        $batch = $forecast->generateForDate($date, null, 'system');

        $count = $batch->forecasts()->count();
        $this->info("Batch #{$batch->id}: {$count} baris forecast.");

        $distribution->buildForDate($date);
        $this->info('Distribusi per site diperbarui (baris terkunci tidak disentuh).');

        return self::SUCCESS;
    }
}
