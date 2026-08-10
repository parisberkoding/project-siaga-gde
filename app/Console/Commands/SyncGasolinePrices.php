<?php

namespace App\Console\Commands;

use App\Services\GasolinePriceSyncService;
use Illuminate\Console\Command;

class SyncGasolinePrices extends Command
{
    protected $signature = 'gasoline:sync';

    protected $description = 'Sinkronisasi harga BBM (Master Gasoline) dari bensin-api untuk Jawa Barat, DKI Jakarta, dan Jawa Tengah';

    public function handle(GasolinePriceSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->line("Total produk diproses : {$result['total']}");
        $this->line("Harga diperbarui      : {$result['updated']}");
        $this->line("Tidak berubah          : {$result['unchanged']}");
        $this->line("Gagal sinkron          : {$result['failed']}");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
