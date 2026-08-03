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

class RoutineCheckingController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  INDEX - Daftar Pengecekan Routine
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan daftar pengecekan routine
     * Filter: kendaraan, sub_type (daily/weekly/monthly), tanggal
     */
    public function index(Request $request)
    {
        $query = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'dispatcher', 'currentDriver'])
            ->routine()
            ->orderBy('checked_at', 'desc');

        // Filter by kendaraan
        if ($request->filled('vehicle_id')) {
            $query->whereHas('vehicleChecking', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }

        // Filter by sub_type
        if ($request->filled('sub_type')) {
            $query->where('sub_type', $request->sub_type);
        }

        // Filter by tanggal
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

        return view('dispatcher.routine-check.index', compact('checkings', 'vehicles'));
    }

    // ════════════════════════════════════════════════════════════════
    //  CREATE - Form Pengecekan Routine
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan form pengecekan routine
     * Item muncul berdasarkan frekuensi yang due/overdue
     */
    public function create(Request $request)
    {
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        // Ambil semua user dengan role requester (driver)
        $drivers = User::where('role', 'requester')
            ->orderBy('nama_lengkap')
            ->get();

        // Ambil parts routine grouped by frequency
        $dailyParts = VehiclePart::active()->routine()->daily()->orderBy('sort_order')->get();
        $weeklyParts = VehiclePart::active()->routine()->weekly()->orderBy('sort_order')->get();
        $monthlyParts = VehiclePart::active()->routine()->monthly()->orderBy('sort_order')->get();

        // Jika kendaraan sudah dipilih, cek jadwal mana yang due
        $scheduleInfo = null;
        if ($request->filled('vehicle_id')) {
            $scheduleInfo = VehicleCheckingSchedule::where('vehicle_id', $request->vehicle_id)
                ->whereIn('check_frequency', ['daily', 'weekly', 'monthly'])
                ->active()
                ->get()
                ->keyBy('check_frequency');
        }

        return view('dispatcher.routine-check.create', compact(
            'vehicles',
            'drivers',
            'dailyParts',
            'weeklyParts',
            'monthlyParts',
            'scheduleInfo'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  STORE - Submit Pengecekan Routine
    // ════════════════════════════════════════════════════════════════

    /**
     * Simpan hasil pengecekan routine
     *
     * Request body:
     * - vehicle_id: required
     * - current_driver_id: required
     * - sub_type: daily|weekly|monthly
     * - notes: optional
     * - items[part_id][status]: ok|ng
     * - items[part_id][notes]: optional
     * - items[part_id][needs_replacement]: 0|1
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'current_driver_id' => 'required|exists:users,id',
            'sub_type' => 'required|in:daily,weekly,monthly',
            'items' => 'required|array|min:1',
'items.*.status' => ['sometimes', 'required_unless:items.*.needs_replacement,1', 'in:ok,ng'],
        ], [
            'vehicle_id.required' => 'Kendaraan harus dipilih',
            'current_driver_id.required' => 'Driver harus dipilih',
            'sub_type.required' => 'Jenis pengecekan harus dipilih',
            'items.required' => 'Minimal 1 item harus dicek',
            'items.*.status.required' => 'Status setiap item harus diisi',
        ]);

        try {
            DB::beginTransaction();

            // 1. Cari atau buat header periode bulan ini
            $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);
            $nopol = $vehicle->consumerial_code;
            $now = Carbon::now();

            $header = VehicleChecking::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'period_start' => $now->copy()->startOfMonth()->toDateString(),
                ],
                [
                    'checking_code' => VehicleChecking::generateCode($nopol, $now),
                    'nopol' => $nopol,
                    'revision' => 0,
                    'period_end' => $now->copy()->endOfMonth()->toDateString(),
                    'generated_by' => Auth::id(),
                    'status' => 'active',
                ]
            );

            // 2. Buat detail pengecekan
            $detail = VehicleCheckingDetail::create([
                'checking_detail_code' => VehicleCheckingDetail::generateCode($header),
                'vehicle_checking_id' => $header->id,
                'type' => 'routine',
                'sub_type' => $request->sub_type,
                'dispatcher_id' => Auth::user()->id(),
                'current_driver_id' => $request->current_driver_id,
                'checked_at' => $now,
                'driver_acknowledged' => false,
                'notes' => $request->notes,
            ]);

            // 3. Simpan hasil per item
            $hasReplacement = false;
            foreach ($request->items as $partId => $itemData) {
                $needsReplacement = isset($itemData['needs_replacement']) && $itemData['needs_replacement'] == 1;
                if ($needsReplacement) $hasReplacement = true;

                VehicleCheckingItemResult::create([
                    'vehicle_checking_detail_id' => $detail->id,
                    'vehicle_part_id' => $partId,
                    'status' => $itemData['status'],
                    'notes' => $itemData['notes'] ?? null,
                    'needs_replacement' => $needsReplacement,
                ]);
            }

            // 4. Update jadwal pengecekan
            $schedule = VehicleCheckingSchedule::where('vehicle_id', $vehicle->id)
                ->where('check_frequency', $request->sub_type)
                ->first();

            if ($schedule) {
                $schedule->markAsChecked(Auth::id());
            }

            DB::commit();

            // Redirect sesuai apakah ada item yang perlu diganti
            if ($hasReplacement) {
                return redirect()
                    ->route('dispatcher.routine-check.show', $detail->checking_detail_code)
                    ->with('success', 'Pengecekan rutin berhasil disimpan. Terdapat item yang perlu penggantian.')
                    ->with('show_replacement_prompt', true);
            }

            return redirect()
                ->route('dispatcher.routine-check.index')
                ->with('success', 'Pengecekan rutin (' . $detail->getSubTypeLabel() . ') berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengecekan: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SHOW - Detail Pengecekan Routine
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan detail hasil pengecekan routine
     */
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
          ->firstOrFail();

        // Grouping item results by category
        $resultsByCategory = $detail->itemResults->groupBy(function ($item) {
            return $item->vehiclePart->category;
        });

        // Hitung summary
        $summary = [
            'total' => $detail->itemResults->count(),
            'ok' => $detail->itemResults->where('status', 'ok')->count(),
            'ng' => $detail->itemResults->where('status', 'ng')->count(),
            'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
        ];

        return view('dispatcher.routine-check.show', compact('detail', 'resultsByCategory', 'summary'));
    }

    // ════════════════════════════════════════════════════════════════
    //  API HELPERS - Untuk AJAX di form
    // ════════════════════════════════════════════════════════════════

    /**
     * Get schedule info per kendaraan (AJAX)
     * Digunakan saat dispatcher pilih kendaraan di dropdown
     */
    public function getVehicleScheduleInfo(Request $request)
    {
        $request->validate(['vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id']);

        $schedules = VehicleCheckingSchedule::where('vehicle_id', $request->vehicle_id)
            ->whereIn('check_frequency', ['daily', 'weekly', 'monthly'])
            ->active()
            ->get()
            ->map(function ($schedule) {
                return [
                    'frequency' => $schedule->check_frequency,
                    'frequency_label' => $schedule->getFrequencyLabel(),
                    'status' => $schedule->status,
                    'status_badge' => $schedule->getStatusBadgeClass(),
                    'last_checked_at' => $schedule->last_checked_at?->format('d/m/Y H:i'),
                    'next_due_date' => $schedule->next_due_date?->format('d/m/Y'),
                    'days_until_due' => $schedule->days_until_due,
                ];
            })
            ->keyBy('frequency');

        return response()->json([
            'success' => true,
            'schedules' => $schedules,
        ]);
    }
}
