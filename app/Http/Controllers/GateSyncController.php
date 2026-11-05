<?php

namespace App\Http\Controllers;

use App\Services\Gate\GateEmployeeSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menu Pengguna → "Sync dari GATE".
 * Dua langkah: Pratinjau (dry run, tanpa menyimpan) lalu Jalankan.
 */
class GateSyncController extends Controller
{
    public function sync(Request $request, GateEmployeeSync $sync)
    {
        $dryRun = $request->boolean('dry_run');

        try {
            $result = $sync->run($dryRun);
        } catch (\Throwable $e) {
            Log::error('Sinkron GATE gagal', ['error' => $e->getMessage(), 'by' => $request->user()?->id]);

            return redirect()->route('users', ['tab' => 'list'])
                ->with('error', 'Sinkron GATE gagal: '.$e->getMessage());
        }

        if (! $dryRun) {
            Log::info('Sinkron GATE selesai', array_merge(
                array_intersect_key($result, array_flip(['total', 'created', 'updated', 'deactivated', 'unchanged'])),
                ['skipped' => count($result['skipped']), 'by' => $request->user()?->id]
            ));
        }

        return redirect()->route('users', ['tab' => 'list'])->with('gate_sync', $result);
    }
}
