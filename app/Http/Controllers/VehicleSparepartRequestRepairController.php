<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementTransaction;
use App\Models\VehicleAndToolsConsumers;
use App\Models\User;
use App\Services\UnitScope;

/**
 * Controller untuk role ADMIN (admin/superadmin):
 * 1. Dashboard mini VPR overview + monitoring service
 * 2. List VPR (filter status/kendaraan/dispatcher)
 * 3. Show detail VPR
 * 4. Approve / Reject VPR
 * 5. Set jadwal service (schedule) + assign driver
 * 6. Mark completed
 */
class VehicleSparepartRequestRepairController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  INDEX - Daftar VPR + Mini Dashboard
    //  View: admin/replacement-approval/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        // Batas unit: admin/superadmin unit hanya melihat VPR unitnya.
        $unitId = UnitScope::for($request->user())->unitId();

        $counts = [
            'pending'    => VehiclePartsReplacement::inUnit($unitId)->pending()->count(),
            'approved'   => VehiclePartsReplacement::inUnit($unitId)->approved()->count(),
            'scheduled'  => VehiclePartsReplacement::inUnit($unitId)->scheduled()->count(),
            'in_service' => VehiclePartsReplacement::inUnit($unitId)->inService()->count(),
            'completed'  => VehiclePartsReplacement::inUnit($unitId)->completed()->count(),
            'rejected'   => VehiclePartsReplacement::inUnit($unitId)->rejected()->count(),
        ];

        $query = VehiclePartsReplacement::with([
            'vehicle', 'dispatcher', 'lastDriver', 'desiredDriver',
            'checkingDetail', 'replacementDetails', 'transaction', 'approver',
        ])->inUnit($unitId)
          ->orderByRaw("CASE status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'scheduled' THEN 3
                WHEN 'in_service' THEN 4
                WHEN 'completed' THEN 5
                WHEN 'rejected' THEN 6
                ELSE 7 END")
          ->orderByDesc('created_at');

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('vehicle_id'))    $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('dispatcher_id')) $query->where('dispatcher_id', $request->dispatcher_id);
        if ($request->filled('date_from'))     $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))       $query->whereDate('created_at', '<=', $request->date_to);

        $replacements = $query->paginate(15)->withQueryString();

        $vehicles    = VehicleAndToolsConsumers::inUnit($unitId)->orderBy('consumerial_code')->get();
        $dispatchers = User::where('role', 'dispatcher')
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        $statusCounts = $counts;

        return view('admin.replacement-approval.index', compact(
            'replacements', 'statusCounts', 'vehicles', 'dispatchers'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  SHOW - Detail VPR
    //  View: admin/replacement-approval/show.blade.php
    // ════════════════════════════════════════════════════════════════
    public function show($vprCode)
    {
        $unitId = UnitScope::for(Auth::user())->unitId();

        $replacement = VehiclePartsReplacement::with([
            'vehicle', 'dispatcher', 'lastDriver', 'desiredDriver',
            'approver', 'checkingDetail.itemResults.vehiclePart',
            'replacementDetails.vehiclePart', 'transaction.details',
        ])->inUnit($unitId)->where('vpr_code', $vprCode)->firstOrFail();

        $drivers = User::where('role', 'requester')
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();
        $checkResults = $replacement->checkingDetail?->itemResults ?? collect();

        return view('admin.replacement-approval.show', compact('replacement', 'drivers', 'checkResults'));
    }

    // ════════════════════════════════════════════════════════════════
    //  APPROVE - POST
    // ════════════════════════════════════════════════════════════════
    public function approve(Request $request, $vprCode)
    {
        $replacement = VehiclePartsReplacement::inUnit(UnitScope::for($request->user())->unitId())
            ->where('vpr_code', $vprCode)
            ->pending()->firstOrFail();

        $replacement->approve(Auth::id());

        return redirect()
            ->route('admin.sparepart-request.show', $vprCode)
            ->with('success', "VPR {$vprCode} berhasil disetujui.");
    }

    // ════════════════════════════════════════════════════════════════
    //  REJECT - POST
    // ════════════════════════════════════════════════════════════════
    public function reject(Request $request, $vprCode)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $replacement = VehiclePartsReplacement::inUnit(UnitScope::for($request->user())->unitId())
            ->where('vpr_code', $vprCode)
            ->pending()->firstOrFail();

        $replacement->reject(Auth::id(), $request->rejection_reason);

        return redirect()
            ->route('admin.sparepart-request.index')
            ->with('success', "VPR {$vprCode} ditolak.");
    }

    // ════════════════════════════════════════════════════════════════
    //  SCHEDULE - POST (Set jadwal + assign driver)
    // ════════════════════════════════════════════════════════════════
public function schedule(Request $request, $vprCode)
    {
        $request->validate([
            'service_date'    => 'required|date|after_or_equal:today',
            'service_location' => 'required|string|max:255',
            'driver_id'       => 'required|exists:users,id',
        ], [
            'service_date.required'    => 'Tanggal service wajib diisi.',
            'service_date.after_or_equal' => 'Tanggal service tidak boleh di masa lalu.',
            'service_location.required' => 'Lokasi service wajib diisi.',
            'driver_id.required'       => 'Driver wajib dipilih.',
            'driver_id.exists'         => 'Driver tidak valid.',
        ]);

        $replacement = VehiclePartsReplacement::inUnit(UnitScope::for($request->user())->unitId())
            ->where('vpr_code', $vprCode)
            ->whereIn('status', ['approved', 'scheduled'])
            ->firstOrFail();

        $replacement->setSchedule(
            $request->service_date,
            $request->service_location,
            $request->driver_id,
        );

        return redirect()
            ->route('admin.sparepart-request.show', $vprCode)
            ->with('success', "Jadwal service VPR {$vprCode} berhasil diatur.");
    }

    // ════════════════════════════════════════════════════════════════
    //  SERVICE SCHEDULING - INDEX (dashboard jadwal)
    //  View: admin/service-scheduling/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function schedulingIndex(Request $request)
    {
        $today = now()->startOfDay();

        // Batas unit: jadwal service hanya kendaraan unit user.
        $unitId = UnitScope::for($request->user())->unitId();

        // Month navigation
        $month      = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $prevMonth  = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth  = $monthStart->copy()->addMonth()->format('Y-m');
        $currentMonthLabel = $monthStart->translatedFormat('F Y');

        // Query for selected month — include all statuses so completed items appear
        $query = VehiclePartsReplacement::with([
            'vehicle', 'dispatcher', 'desiredDriver', 'lastDriver',
            'replacementDetails', 'transaction',
        ])->inUnit($unitId)->whereNotNull('scheduled_date')
          ->whereDate('scheduled_date', '>=', $monthStart)
          ->whereDate('scheduled_date', '<=', $monthEnd);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Return plain Collection (view uses ->where('group', x), not paginator)
        $schedules = $query->orderBy('scheduled_date')->get();

        // Tag each item with a group for the view's section rendering
        $schedules->each(function ($item) use ($today) {
            if ($item->status === 'completed') {
                $item->group = 'completed';
            } elseif ($item->scheduled_date && $item->scheduled_date->lt($today) && $item->status !== 'completed') {
                $item->group = 'overdue';
            } elseif ($item->scheduled_date && $item->scheduled_date->isToday()) {
                $item->group = 'today';
            } else {
                $item->group = 'upcoming';
            }
        });

        $vehicles = VehicleAndToolsConsumers::inUnit($unitId)->orderBy('consumerial_code')->get();
        $drivers  = User::where('role', 'requester')
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        // Global counts (not filtered by month)
        $counts = [
            'overdue'   => VehiclePartsReplacement::inUnit($unitId)->whereNotNull('scheduled_date')
                               ->whereDate('scheduled_date', '<', $today)
                               ->whereNotIn('status', ['completed', 'rejected'])
                               ->count(),
            'today'     => VehiclePartsReplacement::inUnit($unitId)->whereDate('scheduled_date', $today)
                               ->whereNotIn('status', ['completed', 'rejected'])
                               ->count(),
            'upcoming'  => VehiclePartsReplacement::inUnit($unitId)->whereDate('scheduled_date', '>', $today)
                               ->whereNotIn('status', ['completed', 'rejected'])
                               ->count(),
            'completed' => VehiclePartsReplacement::inUnit($unitId)->completed()
                               ->whereMonth('scheduled_date', now()->month)
                               ->whereYear('scheduled_date', now()->year)
                               ->count(),
        ];

        return view('admin.service-scheduling.index', compact(
            'schedules', 'vehicles', 'drivers', 'counts',
            'currentMonthLabel', 'prevMonth', 'nextMonth'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  TRANSACTION MONITORING - INDEX (dashboard: driver sudah lapor?)
    //  View: admin/transaction-monitoring/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function transactionMonitoringIndex(Request $request)
    {
        $unitId = UnitScope::for($request->user())->unitId();

        $query = VehiclePartsReplacementTransaction::with([
            'replacement.vehicle',
            'driver',
        ])->inUnit($unitId)->orderByDesc('service_date');

        if ($request->filled('vehicle_id')) {
            $query->whereHas('replacement', fn ($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('is_overdue', true);
            } else {
                $query->whereHas('replacement', fn ($q) => $q->where('status', $request->status));
            }
        }

        if ($request->filled('date_from')) $query->whereDate('service_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('service_date', '<=', $request->date_to);

        $transactions = $query->paginate(20)->withQueryString();
        $vehicles     = VehicleAndToolsConsumers::inUnit($unitId)->orderBy('consumerial_code')->get();

        $counts = [
            'overdue'    => VehiclePartsReplacementTransaction::inUnit($unitId)->overdue()->count(),
            'in_progress' => VehiclePartsReplacementTransaction::inUnit($unitId)->whereHas(
                                'replacement', fn ($q) => $q->where('status', 'in_service')
                             )->count(),
            'completed'   => VehiclePartsReplacementTransaction::inUnit($unitId)->whereHas(
                                'replacement', fn ($q) => $q->where('status', 'completed')
                             )->whereMonth('service_date', now()->month)
                              ->whereYear('service_date', now()->year)
                              ->count(),
            'total_cost'  => VehiclePartsReplacementTransaction::inUnit($unitId)->whereHas(
                                'replacement', fn ($q) => $q->where('status', 'completed')
                             )->whereMonth('service_date', now()->month)
                              ->whereYear('service_date', now()->year)
                              ->sum('total_amount'),
        ];

        return view('admin.transaction-monitoring.index', compact('transactions', 'vehicles', 'counts'));
    }

    // ════════════════════════════════════════════════════════════════
    //  MARK COMPLETED - POST
    // ════════════════════════════════════════════════════════════════
    public function markCompleted(Request $request, $vprCode)
    {
        // BUG-VPR-001: Admin GA menutup VPR lewat UI beserta biaya aktual
        // hasil service. Biaya aktual disimpan ke total_amount transaksi
        // (satu-satunya kolom biaya aktual pada skema VPR).
        $validated = $request->validate([
            'actual_cost' => 'nullable|numeric|min:0|max:9999999999999',
        ], [
            'actual_cost.numeric' => 'Biaya aktual harus berupa angka.',
            'actual_cost.min'     => 'Biaya aktual tidak boleh negatif.',
        ]);

        $replacement = VehiclePartsReplacement::inUnit(UnitScope::for($request->user())->unitId())
            ->where('vpr_code', $vprCode)
            ->whereIn('status', ['in_service', 'scheduled'])
            ->firstOrFail();

        $transaction = $replacement->transaction;

        if (! $transaction) {
            return redirect()->back()->with('error', 'Driver belum melaporkan transaksi service.');
        }

        if ($request->filled('actual_cost')) {
            $transaction->update(['total_amount' => $validated['actual_cost']]);
        }

        $replacement->markCompleted();

        return redirect()
            ->route('admin.replacement-approval.show', $vprCode)
            ->with('success', "VPR {$vprCode} ditandai selesai.");
    }
}
