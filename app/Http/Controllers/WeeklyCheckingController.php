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
use App\Services\UnitScope;
use Illuminate\Validation\ValidationException;

class WeeklyCheckingController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  INDEX - Daftar Pengecekan Mingguan
    // ════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        // Batas unit: daftar & filter hanya kendaraan unit user.
        $unitId = UnitScope::for($request->user())->unitId();

        $query = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'dispatcher', 'currentDriver'])
            ->routine()
            ->where('sub_type', 'weekly')
            ->inUnit($unitId)
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
            ->inUnit($unitId)
            ->orderBy('consumerial_code')
            ->get();

        return view('dispatcher.weekly-check.index', compact('checkings', 'vehicles'));
    }

    // ════════════════════════════════════════════════════════════════
    //  CREATE - Form Pengecekan Mingguan
    // ════════════════════════════════════════════════════════════════

    public function create(Request $request)
    {
        $unitId = UnitScope::for($request->user())->unitId();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->inUnit($unitId)
            ->orderBy('consumerial_code')
            ->get();

        $drivers = User::where('role', 'requester')
            ->when($unitId !== null, fn ($query) => $query->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        $parts = VehiclePart::active()->routine()->weekly()->orderBy('sort_order')->get();

        // Info jadwal mingguan untuk kendaraan yang dipilih
        $scheduleInfo = null;
        if ($request->filled('vehicle_id')) {
            $scheduleInfo = VehicleCheckingSchedule::where('vehicle_id', $request->vehicle_id)
                ->where('check_frequency', 'weekly')
                ->active()
                ->first();
        }

        return view('dispatcher.weekly-check.create', compact('vehicles', 'drivers', 'parts', 'scheduleInfo'));
    }

    // ════════════════════════════════════════════════════════════════
    //  STORE - Submit Pengecekan Mingguan
    // ════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'        => 'required|exists:vehicle_and_tools_consumers,id',
            'current_driver_id' => 'required|exists:users,id',
            'items'             => 'required|array|min:1',
            'items.*.status'    => 'required|in:ok,ng',
        ], [
            'vehicle_id.required'        => 'Kendaraan harus dipilih.',
            'current_driver_id.required' => 'Driver harus dipilih.',
            'items.required'             => 'Minimal 1 item harus dicek.',
            'items.*.status.required'    => 'Status setiap item harus diisi.',
            'items.*.status.in'          => 'Status item tidak valid.',
        ]);

        $scope = UnitScope::for($request->user());

        $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);
        if (! $vehicle->isVisibleTo($request->user())) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Kendaraan yang dipilih berada di luar unit Anda.',
            ]);
        }

        $driver = User::findOrFail($request->current_driver_id);
        if (! $scope->allowsUnit($driver->unit_id)) {
            throw ValidationException::withMessages([
                'current_driver_id' => 'Driver yang dipilih berada di luar unit Anda.',
            ]);
        }

        $this->ensureAllWeeklyItemsPresent($request);

        try {
            DB::beginTransaction();

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
                'sub_type'             => 'weekly',
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

            // Update jadwal mingguan
            $schedule = VehicleCheckingSchedule::where('vehicle_id', $vehicle->id)
                ->where('check_frequency', 'weekly')
                ->first();

            if ($schedule) {
                $schedule->markAsChecked(Auth::id());
            }

            DB::commit();

            if ($hasReplacement) {
                return redirect()
                    ->route('dispatcher.weekly-check.show', $detail->checking_detail_code)
                    ->with('success', 'Pengecekan mingguan berhasil disimpan. Terdapat item yang perlu penggantian.')
                    ->with('show_replacement_prompt', true);
            }

            return redirect()
                ->route('dispatcher.weekly-check.index')
                ->with('success', 'Pengecekan mingguan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', safe_exception_message($e, 'Gagal menyimpan pengecekan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Pastikan seluruh item aktif pengecekan mingguan dikirim dengan status valid.
     */
    private function ensureAllWeeklyItemsPresent(Request $request): void
    {
        $activeParts = VehiclePart::active()->routine()->weekly()->get(['id', 'name']);
        $items       = $request->input('items', []);
        $allowed     = ['ok', 'ng'];
        $missing     = [];

        foreach ($activeParts as $part) {
            $item   = $items[$part->id] ?? null;
            $status = is_array($item) ? ($item['status'] ?? null) : null;

            if (! is_string($status) || ! in_array($status, $allowed, true)) {
                $missing[] = $part->name;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'items' => 'Pengecekan belum lengkap. ' . count($missing)
                    . ' item aktif belum diisi statusnya: ' . implode(', ', $missing) . '.',
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SHOW - Detail Pengecekan Mingguan
    // ════════════════════════════════════════════════════════════════

    public function show($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
            'partsReplacement',
        ])->inUnit(UnitScope::for(request()->user())->unitId())
          ->where('checking_detail_code', $checkingDetailCode)
          ->where('type', 'routine')
          ->where('sub_type', 'weekly')
          ->firstOrFail();

        $resultsByCategory = $detail->itemResults->groupBy(fn($item) => $item->vehiclePart->category);

        $summary = [
            'total'             => $detail->itemResults->count(),
            'ok'                => $detail->itemResults->where('status', 'ok')->count(),
            'ng'                => $detail->itemResults->where('status', 'ng')->count(),
            'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
        ];

        return view('dispatcher.weekly-check.show', compact('detail', 'resultsByCategory', 'summary'));
    }
}
