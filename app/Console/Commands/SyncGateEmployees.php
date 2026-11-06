<?php

namespace App\Console\Commands;

use App\Services\Gate\GateEmployeeSync;
use Illuminate\Console\Command;

class SyncGateEmployees extends Command
{
    protected $signature = 'gate:sync-employees {--dry-run : Tampilkan hasil tanpa menyimpan perubahan}';

    protected $description = 'Sinkronisasi akun Pengguna dari GATE HCIS (nama, jabatan, unit bisnis, username SSO; role baru = Pegawai)';

    public function handle(GateEmployeeSync $sync): int
    {
        try {
            $result = $sync->run((bool) $this->option('dry-run'));
        } catch (\Throwable $e) {
            $this->error('Sinkron GATE gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($result['dry_run']) {
            $this->warn('DRY RUN — tidak ada perubahan yang disimpan.');
        }

        $this->line("Data dari GATE  : {$result['total']}");
        $this->line("Akun dibuat     : {$result['created']}");
        $this->line("Diperbarui      : {$result['updated']}");
        $this->line("Dinonaktifkan   : {$result['deactivated']}");
        $this->line("Tidak berubah   : {$result['unchanged']}");
        $this->line('Dilewati        : '.count($result['skipped']));

        foreach ($result['skipped'] as [$who, $why]) {
            $this->warn("  - {$who}: {$why}");
        }
        foreach ($result['warnings'] as [$who, $why]) {
            $this->line("  ! {$who}: {$why}");
        }

        return self::SUCCESS;
    }
}
