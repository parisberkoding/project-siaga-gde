<?php

namespace App\Http\Controllers;

use App\Models\MasterGasoline;
use App\Models\VehicleAndToolsConsumers;
use App\Services\GasolinePriceSyncService;
use Illuminate\Http\Request;
use Throwable;

class MasterGasolineController extends Controller
{
    /**
     * Halaman Master Harga BBM (Superadmin & Admin/GA).
     */
    public function index()
    {
        $title = 'Master Harga BBM';

        $prices = MasterGasoline::orderBy('province_slug')
            ->orderBy('fuel_type')
            ->get();

        $lastSync = MasterGasoline::max('synced_at');

        $vehicles = VehicleAndToolsConsumers::with('detail.site')
            ->where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->where('is_active', true)
            ->orderBy('consumerial_name')
            ->get();

        return view('admin.gasoline.index', compact('title', 'prices', 'lastSync', 'vehicles'));
    }

    /**
     * Sinkronisasi harga BBM secara manual dari bensin-api.
     */
    public function sync(Request $request)
    {
        try {
            $result = app(GasolinePriceSyncService::class)->sync();

            $message = 'Sinkronisasi selesai: ' . $result['updated'] . ' harga diperbarui, '
                . $result['unchanged'] . ' tidak berubah, '
                . $result['failed'] . ' region gagal.';

            return back()->with($result['failed'] > 0 ? 'error' : 'success', $message);
        } catch (Throwable $e) {
            return back()->with('error', safe_exception_message($e, 'Gagal sinkronisasi harga BBM. Silakan coba lagi atau hubungi admin.'));
        }
    }
}
