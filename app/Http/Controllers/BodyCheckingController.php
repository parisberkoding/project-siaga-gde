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
use App\Models\DamagePhoto;
use App\Models\User;
use App\Services\UnitScope;
use Illuminate\Validation\ValidationException;

class BodyCheckingController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  INDEX - Daftar Pengecekan Body
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan daftar pengecekan body
     */
    public function index(Request $request)
    {
        // Batas unit: daftar & filter hanya kendaraan unit user.
        $unitId = UnitScope::for($request->user())->unitId();

        $query = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'dispatcher', 'currentDriver', 'itemResults', 'damagePhotos'])
            ->body()
            ->inUnit($unitId)
            ->orderBy('checked_at', 'desc');

        // Filter by kendaraan
        if ($request->filled('vehicle_id')) {
            $query->whereHas('vehicleChecking', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
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
            ->inUnit($unitId)
            ->orderBy('consumerial_code')
            ->get();

        return view('dispatcher.body-check.index', compact('checkings', 'vehicles'));
    }

    // ════════════════════════════════════════════════════════════════
    //  CREATE - Form Pengecekan Body
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan form pengecekan body
     * Semua item body muncul dengan radio button 4 level
     */
    public function create()
    {
        $unitId = UnitScope::for(Auth::user())->unitId();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->inUnit($unitId)
            ->orderBy('consumerial_code')
            ->get();

        $drivers = User::where('role', 'requester')
            ->when($unitId !== null, fn ($query) => $query->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        // Ambil semua parts body
        $bodyParts = VehiclePart::active()
            ->body()
            ->orderBy('sort_order')
            ->get();

        return view('dispatcher.body-check.create', compact(
            'vehicles',
            'drivers',
            'bodyParts'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  STORE - Submit Pengecekan Body
    // ════════════════════════════════════════════════════════════════

    /**
     * Simpan hasil pengecekan body
     *
     * Request body:
     * - vehicle_id: required
     * - current_driver_id: required
     * - notes: optional
     * - items[part_id][status]: baik|cukup|kurang|tidak
     * - items[part_id][notes]: optional keterangan
     * - items[part_id][needs_replacement]: 0|1
     */
    public function store(Request $request)
    {

    // dd($request->all());

        $request->validate([
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'current_driver_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
'items.*.status' => ['sometimes', 'required_unless:items.*.needs_replacement,1', 'in:baik,cukup,kurang,tidak'],
            'damage_photos' => 'nullable|array',
            'damage_photos.*.photo' => 'nullable|file',
            'damage_photos.*.zone' => 'nullable|string|in:depan,belakang,kiri,kanan,atas,bawah,interior',
        ], [
            'vehicle_id.required' => 'Kendaraan harus dipilih',
            'current_driver_id.required' => 'Driver harus dipilih',
            'items.required' => 'Minimal 1 item harus dicek',
            'items.*.status.required' => 'Status setiap item harus diisi',
            'damage_photos.*.zone.in' => 'Zona foto tidak valid',
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

        $this->ensureAllBodyItemsPresent($request);
        $this->ensureFourSidePhotosPresent($request);

        try {
            DB::beginTransaction();

            // 1. Cari atau buat header periode bulan ini
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
                'type' => 'body',
                'sub_type' => null, // Body tidak punya sub_type
                'dispatcher_id' => Auth::id(),
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

            // 4. Simpan damage photos (jika ada)
            if (! empty($request->file('damage_photos'))) {
                foreach ($request->file('damage_photos') as $index => $photoData) {
                    if (isset($photoData['photo']) && $photoData['photo']->isValid()) {
                        $path = $photoData['photo']->store('damage-photos/' . $detail->checking_detail_code, 'public');

                        DamagePhoto::create([
                            'vehicle_checking_detail_id' => $detail->id,
                            'photo' => $path,
                            'zone' => $request->input("damage_photos.{$index}.zone", 'depan'),
                            'description' => $request->input("damage_photos.{$index}.description"),
                        ]);
                    }
                }
            }

            // 5. Update jadwal body checking
            $schedule = VehicleCheckingSchedule::where('vehicle_id', $vehicle->id)
                ->where('check_frequency', 'body')
                ->first();

            if ($schedule) {
                $schedule->markAsChecked(Auth::id());
            }

            DB::commit();

            if ($hasReplacement) {
                return redirect()
                    ->route('dispatcher.body-check.show', $detail->checking_detail_code)
                    ->with('success', 'Pengecekan body berhasil disimpan. Terdapat item yang perlu penggantian.')
                    ->with('show_replacement_prompt', true);
            }

            return redirect()
                ->route('dispatcher.body-check.index')
                ->with('success', 'Pengecekan body kendaraan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', safe_exception_message($e, 'Gagal menyimpan pengecekan body. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Pastikan seluruh item body aktif dikirim dengan status skala yang valid.
     */
    private function ensureAllBodyItemsPresent(Request $request): void
    {
        $activeParts = VehiclePart::active()->body()->get(['id', 'name']);
        $items       = $request->input('items', []);
        $allowed     = ['baik', 'cukup', 'kurang', 'tidak'];
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
                'items' => 'Pengecekan body belum lengkap. ' . count($missing)
                    . ' item aktif belum diisi statusnya: ' . implode(', ', $missing) . '.',
            ]);
        }
    }

    /**
     * Wajibkan foto empat sisi kendaraan: depan, belakang, kiri, kanan.
     * Memakai field yang sudah ada: damage_photos[<index>][photo] + [zone].
     */
    private function ensureFourSidePhotosPresent(Request $request): void
    {
        $requiredSides = [
            'depan'    => 'Depan',
            'belakang' => 'Belakang',
            'kiri'     => 'Kiri',
            'kanan'    => 'Kanan',
        ];

        $files = $request->file('damage_photos', []);
        $zones = $request->input('damage_photos', []);
        $provided = [];

        foreach ($files as $index => $photoData) {
            $photo = $photoData['photo'] ?? null;

            if (! $photo instanceof \Illuminate\Http\UploadedFile || ! $photo->isValid()) {
                continue;
            }

            $zone = strtolower(trim((string) ($zones[$index]['zone'] ?? '')));
            if (array_key_exists($zone, $requiredSides)) {
                $provided[$zone] = true;
            }
        }

        $missing = [];
        foreach ($requiredSides as $zone => $label) {
            if (! isset($provided[$zone])) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'damage_photos' => 'Foto empat sisi kendaraan wajib diunggah. Sisi yang belum diisi: '
                    . implode(', ', $missing) . '.',
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SHOW - Detail Pengecekan Body
    // ════════════════════════════════════════════════════════════════

    /**
     * Tampilkan detail hasil pengecekan body
     */
    public function show($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
            'partsReplacement',
            'damagePhotos',
        ])->inUnit(UnitScope::for(request()->user())->unitId())
          ->where('checking_detail_code', $checkingDetailCode)
          ->where('type', 'body')
          ->firstOrFail();

        // Summary per status level
        $summary = [
            'total' => $detail->itemResults->count(),
            'baik' => $detail->itemResults->where('status', 'baik')->count(),
            'cukup' => $detail->itemResults->where('status', 'cukup')->count(),
            'kurang' => $detail->itemResults->where('status', 'kurang')->count(),
            'tidak' => $detail->itemResults->where('status', 'tidak')->count(),
            'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
        ];

        return view('dispatcher.body-check.show', compact('detail', 'summary'));
    }
}
