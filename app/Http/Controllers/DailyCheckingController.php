<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleCheckingItemResult;
use App\Models\VehicleCheckingSchedule;
use App\Models\VehiclePart;
use App\Models\VehicleAndToolsConsumers;
use App\Models\User;

class DailyCheckingController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  INDEX - Daftar Pengecekan Harian
    // ════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'dispatcher', 'currentDriver'])
            ->routine()
            ->where('sub_type', 'daily')
            ->orderBy('checked_at', 'desc');

        if ($request->filled('vehicle_id')) {
            $query->whereHas('vehicleChecking', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('checked_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('checked_at', '<=', $request->date_to);
        }

        $checkings = $query->paginate(15)->withQueryString();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        return view('dispatcher.daily-check.index', compact('checkings', 'vehicles'));
    }

    // ════════════════════════════════════════════════════════════════
    //  CREATE - Form Pengecekan Harian
    // ════════════════════════════════════════════════════════════════

    public function create(Request $request)
    {
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        $drivers = User::where('role', 'requester')
            ->orderBy('nama_lengkap')
            ->get();

        $parts = VehiclePart::active()->routine()->daily()->orderBy('sort_order')->get();

        return view('dispatcher.daily-check.create', compact('vehicles', 'drivers', 'parts'));
    }

    // ════════════════════════════════════════════════════════════════
    //  STORE - Submit Pengecekan Harian
    // ════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'        => 'required|exists:vehicle_and_tools_consumers,id',
            'current_driver_id' => 'required|exists:users,id',
            'items'             => 'required|array|min:1',
'items.*.status'    => ['sometimes', 'required_unless:items.*.needs_replacement,1', 'in:ok,ng'],
        ], [
            'vehicle_id.required'        => 'Kendaraan harus dipilih.',
            'current_driver_id.required' => 'Driver harus dipilih.',
            'items.required'             => 'Minimal 1 item harus dicek.',
            'items.*.status.required'    => 'Status setiap item harus diisi.',
            'items.*.status.in'          => 'Status item tidak valid.',
        ]);

        try {
            DB::beginTransaction();

            $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);
            $now     = Carbon::now();
            $nopol   = $vehicle->consumerial_code;

            // Cari atau buat header periode bulan ini
            $header = VehicleChecking::firstOrCreate(
                [
                    'vehicle_id'   => $vehicle->id,
                    'period_start' => $now->copy()->startOfMonth()->toDateString(),
                ],
                [
                    'checking_code' => VehicleChecking::generateCode($nopol, $now),
                    'nopol'         => $nopol,
                    'revision'      => 0,
                    'period_end'    => $now->copy()->endOfMonth()->toDateString(),
                    'generated_by'  => Auth::id(),
                    'status'        => 'active',
                ]
            );

            // Buat detail pengecekan
            $detail = VehicleCheckingDetail::create([
                'checking_detail_code' => VehicleCheckingDetail::generateCode($header),
                'vehicle_checking_id'  => $header->id,
                'type'                 => 'routine',
                'sub_type'             => 'daily',
                'dispatcher_id'        => Auth::id(),
                'current_driver_id'    => $request->current_driver_id,
                'checked_at'           => $now,
                'driver_acknowledged'  => false,
                'notes'                => $request->notes,
            ]);

            // Simpan hasil per item
            $hasReplacement = false;
            foreach ($request->items as $partId => $itemData) {
                $needsReplacement = !empty($itemData['needs_replacement']) && $itemData['needs_replacement'] == 1;
                if ($needsReplacement) $hasReplacement = true;

                VehicleCheckingItemResult::create([
                    'vehicle_checking_detail_id' => $detail->id,
                    'vehicle_part_id'            => $partId,
                    'status'                     => $itemData['status'],
                    'notes'                      => $itemData['notes'] ?? null,
                    'needs_replacement'          => $needsReplacement,
                ]);
            }

            // Update jadwal harian jika ada
            $schedule = VehicleCheckingSchedule::where('vehicle_id', $vehicle->id)
                ->where('check_frequency', 'daily')
                ->first();

            if ($schedule) {
                $schedule->markAsChecked(Auth::id());
            }

            DB::commit();

            if ($hasReplacement) {
                return redirect()
                    ->route('dispatcher.daily-check.show', $detail->checking_detail_code)
                    ->with('success', 'Pengecekan harian berhasil disimpan. Terdapat item yang perlu penggantian.')
                    ->with('show_replacement_prompt', true);
            }

            return redirect()
                ->route('dispatcher.daily-check.index')
                ->with('success', 'Pengecekan harian berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengecekan: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SHOW - Detail Pengecekan Harian
    // ════════════════════════════════════════════════════════════════

    public function show($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
            'partsReplacement',
        ])->where('checking_detail_code', $checkingDetailCode)
          ->where('type', 'routine')
          ->where('sub_type', 'daily')
          ->firstOrFail();

        // dd($detail->toArray());
        // cari apakah ada yang ng dari item results yang butuh replacement, untuk trigger prompt di view
        if($detail->itemResults->isNotEmpty() && $detail->itemResults->where('status', 'ng')->where('needs_replacement', true)->isNotEmpty()) {
            session()->flash('show_replacement_prompt', true);
        }

        $resultsByCategory = $detail->itemResults->groupBy(fn($item) => $item->vehiclePart->category);

        $summary = [
            'total'            => $detail->itemResults->count(),
            'ok'               => $detail->itemResults->where('status', 'ok')->count(),
            'ng'               => $detail->itemResults->where('status', 'ng')->count(),
            'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
        ];

        return view('dispatcher.daily-check.show', compact('detail', 'resultsByCategory', 'summary'));
    }
}
