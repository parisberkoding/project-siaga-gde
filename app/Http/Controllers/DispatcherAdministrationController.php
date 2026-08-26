<?php

namespace App\Http\Controllers;

use App\Exports\DispatcherCheckingExport;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleCheckingItemResult;
use App\Models\VehicleCheckingSchedule;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementDetail;
use App\Models\VehiclePartsReplacementTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatcherAdministrationController extends Controller
{
    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  DASHBOARD - Landing Page Dispatcher                        ║
    // ║  Jadwal pengecekan: Overdue, Due Today, Upcoming            ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Dashboard dispatcher: jadwal pengecekan + badge summary
     */
    public function dashboard()
    {
        $dispatcherId = Auth::id();

        // Refresh status jadwal (bisa juga via cron, tapi fallback di sini)
        VehicleCheckingSchedule::refreshStatuses();

        // ── Jadwal Overdue ──
        $overdueSchedules = VehicleCheckingSchedule::with(['vehicle'])
            ->active()
            ->overdue()
            ->orderBy('next_due_date', 'asc')
            ->get();

        // ── Jadwal Due Today ──
        $dueTodaySchedules = VehicleCheckingSchedule::with(['vehicle'])
            ->active()
            ->dueToday()
            ->orderBy('check_frequency')
            ->get();

        // ── Jadwal Upcoming (7 hari ke depan) ──
        $upcomingSchedules = VehicleCheckingSchedule::with(['vehicle'])
            ->active()
            ->where('status', 'completed')
            ->where('next_due_date', '>', Carbon::today())
            ->where('next_due_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('next_due_date', 'asc')
            ->get();

        // ── Badge counts ──
        $badges = [
            'overdue_count' => $overdueSchedules->count(),
            'due_today_count' => $dueTodaySchedules->count(),
            'pending_replacements' => VehiclePartsReplacement::pending()->count(),
            'recently_serviced' => VehiclePartsReplacementTransaction::recentlyCompleted()->count(),
        ];

        // ── Kendaraan yang baru selesai service (badge 24 jam) ──
        $recentlyServiced = VehiclePartsReplacementTransaction::with(['replacement.vehicle', 'driver'])
            ->recentlyCompleted()
            ->get();

        // ── Periode bulan ini ──
        $currentPeriodExists = VehicleChecking::currentPeriod()->exists();
        $currentPeriodCount = VehicleChecking::currentPeriod()->count();
        $totalActiveVehicles = VehicleAndToolsConsumers::where('is_active', true)->count();

        // dd('Dashboard data:', [
        //     'overdue' => $overdueSchedules->pluck('schedule_code'),
        //     'due_today' => $dueTodaySchedules->pluck('schedule_code'),
        //     'upcoming' => $upcomingSchedules->pluck('schedule_code'),
        //     'badges' => $badges,
        //     'recently_serviced' => $recentlyServiced->pluck('transaction_code'),
        //     'current_period_exists' => $currentPeriodExists,
        //     'current_period_count' => $currentPeriodCount,
        //     'total_active_vehicles' => $totalActiveVehicles,
        // ]);

        return view('dispatcher.dashboard', compact(
            'overdueSchedules',
            'dueTodaySchedules',
            'upcomingSchedules',
            'badges',
            'recentlyServiced',
            'currentPeriodExists',
            'currentPeriodCount',
            'totalActiveVehicles'
        ));
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  GENERATE PERIODE - Buat Header Bulanan Semua Kendaraan     ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Generate header pengecekan bulanan untuk semua kendaraan aktif
     * 1 tombol → loop semua kendaraan → create header + schedule
     */
    public function generatePeriod()
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->startOfMonth()->toDateString();
        $periodEnd = $now->copy()->endOfMonth()->toDateString();

        // Cek apakah sudah di-generate bulan ini
        $existingCount = VehicleChecking::where('period_start', $periodStart)->count();
        $activeVehicles = VehicleAndToolsConsumers::where('is_active', true)->get();

        if ($existingCount >= $activeVehicles->count()) {
            return redirect()
                ->route('dispatcher.dispatcher-dashboard')
                ->with('info', 'Periode '.$now->translatedFormat('F Y').' sudah di-generate untuk semua kendaraan.');
        }

        try {
            DB::beginTransaction();

            $generated = 0;

            foreach ($activeVehicles as $vehicle) {
                $nopol = $vehicle->consumerial_code;

                // Skip jika header sudah ada untuk kendaraan ini di bulan ini
                if (VehicleChecking::periodExists($vehicle->id, $now)) {
                    continue;
                }

                // 1. Buat header pengecekan
                VehicleChecking::create([
                    'checking_code' => VehicleChecking::generateCode($nopol, $now),
                    'vehicle_id' => $vehicle->id,
                    'nopol' => $nopol,
                    'revision' => 0,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'generated_by' => Auth::id(),
                    'status' => 'active',
                ]);

                // 2. Buat jadwal pengecekan (3 record per kendaraan) jika belum ada
                // Harian tidak dibuatkan jadwal — dicatat langsung tanpa tracking due-date
                $frequencies = ['weekly', 'monthly', 'body'];

                foreach ($frequencies as $frequency) {
                    VehicleCheckingSchedule::firstOrCreate(
                        [
                            'vehicle_id' => $vehicle->id,
                            'check_frequency' => $frequency,
                        ],
                        [
                            'schedule_code' => VehicleCheckingSchedule::generateCode($nopol, $frequency),
                            'assigned_dispatcher_id' => null, // Bisa di-assign nanti
                            'last_checked_at' => null,
                            'last_checked_by' => null,
                            'next_due_date' => $periodStart, // Due dari hari pertama
                            'status' => 'due',
                            'is_active' => true,
                        ]
                    );
                }

                $generated++;
            }

            DB::commit();

            return redirect()
                ->route('dispatcher.dispatcher-dashboard')
                ->with('success', "Periode {$now->translatedFormat('F Y')} berhasil di-generate untuk {$generated} kendaraan.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('dispatcher.dispatcher-dashboard')
                ->with('error', 'Gagal generate periode: '.$e->getMessage());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  ASSIGN DISPATCHER - Assign PIC dispatcher ke kendaraan     ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Halaman assign dispatcher ke kendaraan
     */
    public function assignIndex()
    {
        $schedules = VehicleCheckingSchedule::with(['vehicle', 'assignedDispatcher'])
            ->active()
            ->orderBy('vehicle_id')
            ->orderBy('check_frequency')
            ->get()
            ->groupBy('vehicle_id');

        $dispatchers = User::where('role', 'dispatchers')->orderBy('nama_lengkap')->get();
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        return view('dispatcher.assign.index', compact('schedules', 'dispatchers', 'vehicles'));
    }

    /**
     * Update assignment dispatcher
     */
    public function assignUpdate(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.schedule_id' => 'required|exists:vehicle_checking_schedules,id',
            'assignments.*.dispatcher_id' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->assignments as $assignment) {
                VehicleCheckingSchedule::where('id', $assignment['schedule_id'])
                    ->update(['assigned_dispatcher_id' => $assignment['dispatcher_id'] ?: null]);
            }

            DB::commit();

            return redirect()
                ->route('dispatcher.assign.index')
                ->with('success', 'Assignment dispatcher berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal memperbarui assignment: '.$e->getMessage());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  RIWAYAT - History Pengecekan (by kendaraan/driver/disp.)   ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Riwayat pengecekan
     * Filter: kendaraan, driver, dispatcher, jenis, tanggal
     */
    public function history(Request $request)
    {
        $query = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults',
        ])->orderBy('checked_at', 'desc');

        // Filter by kendaraan
        if ($request->filled('vehicle_id')) {
            $query->whereHas('vehicleChecking', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }

        // Filter by driver
        if ($request->filled('driver_id')) {
            $query->where('current_driver_id', $request->driver_id);
        }

        // Filter by dispatcher
        if ($request->filled('dispatcher_id')) {
            $query->where('dispatcher_id', $request->dispatcher_id);
        }

        // Filter by jenis (routine/body)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('checked_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('checked_at', '<=', $request->date_to);
        }

        $checkings = $query->paginate(20)->withQueryString();

        // Data untuk dropdown filter
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)->orderBy('consumerial_code')->get();
        $drivers = User::where('role', 'requester')->orderBy('nama_lengkap')->get();
        $dispatchers = User::where('role', 'dispatcher')->orderBy('nama_lengkap')->get();

        return view('dispatcher.history.index', compact(
            'checkings',
            'vehicles',
            'drivers',
            'dispatchers'
        ));
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  PERMOHONAN PENGGANTIAN SPAREPART (VPR)                     ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * List permohonan penggantian
     */
    public function replacementIndex(Request $request)
    {
        $query = VehiclePartsReplacement::with([
            'vehicle',
            'dispatcher',
            'lastDriver',
            'desiredDriver',
            'approver',
            'replacementDetails',
            'transaction',
        ])->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $replacements = $query->paginate(15)->withQueryString();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)->orderBy('consumerial_code')->get();

        // Hitung badge per status
        $statusCounts = [
            'pending' => VehiclePartsReplacement::pending()->count(),
            'approved' => VehiclePartsReplacement::approved()->count(),
            'scheduled' => VehiclePartsReplacement::scheduled()->count(),
            'in_service' => VehiclePartsReplacement::inService()->count(),
            'completed' => VehiclePartsReplacement::completed()->count(),
        ];

        return view('dispatcher.replacement.index', compact('replacements', 'vehicles', 'statusCounts'));
    }

    /**
     * Form buat permohonan penggantian
     * Muncul setelah pengecekan yang ada item needs_replacement
     */
    public function replacementCreate($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'currentDriver',
            'itemResults' => function ($q) {
                $q->where('needs_replacement', true)->with('vehiclePart');
            },
        ])->where('checking_detail_code', $checkingDetailCode)
            ->firstOrFail();

        // Cek apakah sudah ada VPR dari pengecekan ini
        $existingVpr = VehiclePartsReplacement::where('vehicle_checking_detail_id', $detail->id)->first();
        if ($existingVpr) {
            return redirect()
                ->route('dispatcher.replacement.show', $existingVpr->vpr_code)
                ->with('info', 'Permohonan penggantian dari pengecekan ini sudah ada.');
        }

        // Cek apakah memang ada item yang perlu diganti
        if ($detail->itemResults->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ada item yang perlu penggantian dari pengecekan ini.');
        }

        $drivers = User::where('role', 'requester')->orderBy('nama_lengkap')->get();

        return view('dispatcher.replacement.create', compact('detail', 'drivers'));
    }

    /**
     * Simpan permohonan penggantian sparepart
     *
     * Request body:
     * - desired_driver_id: nullable (driver yang akan bawa ke service)
     * - items[]: array of item_result_id yang dipilih untuk diganti
     * - notes[item_result_id]: catatan per item
     */
    public function replacementStore(Request $request, $checkingDetailCode)
    {
        $request->validate([
            'desired_driver_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*' => 'exists:vehicle_checking_item_results,id',
        ], [
            'items.required' => 'Pilih minimal 1 item yang perlu diganti',
        ]);

        $detail = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'currentDriver'])
            ->where('checking_detail_code', $checkingDetailCode)
            ->firstOrFail();

        // Double check: belum ada VPR dari pengecekan ini
        if (VehiclePartsReplacement::where('vehicle_checking_detail_id', $detail->id)->exists()) {
            return redirect()
                ->route('dispatcher.replacement.index')
                ->with('error', 'Permohonan penggantian dari pengecekan ini sudah ada.');
        }

        try {
            DB::beginTransaction();

            $vehicle = $detail->vehicleChecking->vehicle;
            $nopol = $vehicle->consumerial_code;

            // 1. Buat header VPR
            $vpr = VehiclePartsReplacement::create([
                'vpr_code' => VehiclePartsReplacement::generateCode($nopol),
                'vehicle_checking_detail_id' => $detail->id,
                'vehicle_id' => $vehicle->id,
                'nopol' => $nopol,
                'dispatcher_id' => Auth::id(),
                'last_driver_id' => $detail->current_driver_id,
                'desired_driver_id' => $request->desired_driver_id,
                'status' => 'pending',
            ]);

            // 2. Buat detail items dari item results yang dipilih
            $selectedResults = VehicleCheckingItemResult::with('vehiclePart')
                ->whereIn('id', $request->items)
                ->where('needs_replacement', true)
                ->get();

            $seq = 1;
            foreach ($selectedResults as $result) {
                VehiclePartsReplacementDetail::create([
                    'vprd_code' => VehiclePartsReplacementDetail::generateCode($vpr->id, $seq),
                    'vehicle_parts_replacement_id' => $vpr->id,
                    'vehicle_part_id' => $result->vehicle_part_id,
                    'item_name' => $result->vehiclePart->name,
                    'condition_status' => $result->status,
                    'dispatcher_notes' => $request->input("notes.{$result->id}"),
                ]);
                $seq++;
            }

            DB::commit();

            return redirect()
                ->route('dispatcher.replacement.show', $vpr->vpr_code)
                ->with('success', 'Permohonan penggantian sparepart berhasil diajukan dengan '.$selectedResults->count().' item.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengajukan permohonan: '.$e->getMessage());
        }
    }

    /**
     * Detail permohonan penggantian
     */
    public function replacementShow($vprCode)
    {
        $replacement = VehiclePartsReplacement::with([
            'checkingDetail.itemResults.vehiclePart',
            'vehicle',
            'dispatcher',
            'lastDriver',
            'desiredDriver',
            'approver',
            'replacementDetails.vehiclePart',
            'transaction.details',
            'transaction.driver',
        ])->where('vpr_code', $vprCode)
            ->firstOrFail();

        return view('dispatcher.replacement.show', compact('replacement'));
    }

    /**
     * Auto-create VPR dari semua item yang flagged needs_replacement.
     * Dipanggil dari tombol "Auto-Ajukan" di show routine/body check.
     */
    public function replacementAutoCreate($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'currentDriver',
            'itemResults' => fn ($q) => $q->where('needs_replacement', true)->with('vehiclePart'),
        ])->where('checking_detail_code', $checkingDetailCode)
            ->firstOrFail();

        if ($detail->itemResults->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ada item yang perlu diganti.');
        }

        if (VehiclePartsReplacement::where('vehicle_checking_detail_id', $detail->id)->exists()) {
            return redirect()
                ->route('dispatcher.replacement.index')
                ->with('error', 'Permohonan penggantian dari pengecekan ini sudah ada.');
        }

        try {
            DB::beginTransaction();

            $vehicle = $detail->vehicleChecking->vehicle;
            $nopol = $vehicle->consumerial_code;

            $vpr = VehiclePartsReplacement::create([
                'vpr_code' => VehiclePartsReplacement::generateCode($nopol),
                'vehicle_checking_detail_id' => $detail->id,
                'vehicle_id' => $vehicle->id,
                'nopol' => $nopol,
                'dispatcher_id' => Auth::id(),
                'last_driver_id' => $detail->current_driver_id,
                'desired_driver_id' => null,
                'status' => 'pending',
            ]);

            $seq = 1;
            foreach ($detail->itemResults as $result) {
                VehiclePartsReplacementDetail::create([
                    'vprd_code' => VehiclePartsReplacementDetail::generateCode($vpr->id, $seq),
                    'vehicle_parts_replacement_id' => $vpr->id,
                    'vehicle_part_id' => $result->vehicle_part_id,
                    'item_name' => $result->vehiclePart->name,
                    'condition_status' => $result->status,
                    'dispatcher_notes' => $result->notes,
                ]);
                $seq++;
            }

            DB::commit();

            return redirect()
                ->route('dispatcher.replacement.show', $vpr->vpr_code)
                ->with('success', 'Permohonan penggantian sparepart berhasil diajukan otomatis dengan '.$detail->itemResults->count().' item.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal mengajukan permohonan: '.$e->getMessage());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  EXPORT - Vehicle Checklist, Routine Check, Body Check       ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Export Vehicle Checklist (summary + detail semua pengecekan per kendaraan)
     */
    public function exportVehicleChecklist(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        $checkings = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
        ])
            ->whereBetween('checked_at', [$dateFrom, $dateTo])
            ->orderBy('checked_at', 'desc')
            ->get();

        // Group by vehicle
        $groupedByVehicle = $checkings->groupBy(function ($c) {
            return $c->vehicleChecking->vehicle_id ?? 0;
        });

        $periodLabel = $dateFrom->translatedFormat('d M Y').' - '.$dateTo->translatedFormat('d M Y');

        $pdf = Pdf::loadView('dispatcher.export-templates.vehicle-checklist', compact(
            'vehicles', 'checkings', 'groupedByVehicle', 'periodLabel', 'dateFrom', 'dateTo'
        ))->setPaper('a4', 'landscape');

        $filename = 'vehicle-checklist-'.$dateFrom->format('Ym').'-'.$dateTo->format('Ym').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Routine Check Report (harian, mingguan, bulanan)
     */
    public function exportRoutineCheck(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();

        $checkings = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
        ])
            ->where('type', 'routine')
            ->whereBetween('checked_at', [$dateFrom, $dateTo])
            ->orderBy('checked_at', 'desc')
            ->get();

        $periodLabel = $dateFrom->translatedFormat('d M Y').' - '.$dateTo->translatedFormat('d M Y');

        $pdf = Pdf::loadView('dispatcher.export-templates.routine-check-report', compact(
            'checkings', 'periodLabel', 'dateFrom', 'dateTo'
        ))->setPaper('a4', 'landscape');

        $filename = 'routine-check-report-'.$dateFrom->format('Ym').'-'.$dateTo->format('Ym').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Body Check Report
     */
    public function exportBodyCheck(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();

        $checkings = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
            'damagePhotos',
        ])
            ->where('type', 'body')
            ->whereBetween('checked_at', [$dateFrom, $dateTo])
            ->orderBy('checked_at', 'desc')
            ->get();

        $periodLabel = $dateFrom->translatedFormat('d M Y').' - '.$dateTo->translatedFormat('d M Y');

        $pdf = Pdf::loadView('dispatcher.export-templates.body-check-report', compact(
            'checkings', 'periodLabel', 'dateFrom', 'dateTo'
        ))->setPaper('a4', 'landscape');

        $filename = 'body-check-report-'.$dateFrom->format('Ym').'-'.$dateTo->format('Ym').'.pdf';

        return $pdf->download($filename);
    }

    // ╔═══════════════════════════════════════════════════════════════╗
    // ║  EXPORT EXCEL - Form Pengecekan Dispatcher per Kendaraan     ║
    // ║  Dapat diakses oleh: Superadmin, Dispatcher, dan Driver       ║
    // ╚═══════════════════════════════════════════════════════════════╝

    /**
     * Halaman form export Excel format pengecekan dispatcher.
     * Header/kop (nama perusahaan, unit, logo) bisa dicustom di sini.
     */
    public function exportCheckingIndex()
    {
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->orderBy('consumerial_code')
            ->get();

        $months = collect(range(1, 12))->map(fn ($m) => Carbon::create()->month($m));
        $years = range(Carbon::now()->year - 1, Carbon::now()->year + 1);

        $unitName = auth()->user()->unit?->name ?? config('siaga.report.unit_name');

        return view('dispatcher.export-checking.index', compact('vehicles', 'months', 'years', 'unitName'));
    }

    /**
     * Generate & unduh Excel format pengecekan dispatcher.
     */
    public function exportCheckingDownload(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000|max:2100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'vehicle_id.required' => 'Pilih kendaraan terlebih dahulu.',
            'month.required' => 'Pilih bulan periode.',
            'year.required' => 'Tahun periode wajib diisi.',
        ]);

        $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);
        $month = Carbon::create((int) $request->year, (int) $request->month, 1);

        $header = [
            'company_name' => config('siaga.report.company_name'),
            'unit_name' => auth()->user()->unit?->name ?? config('siaga.report.unit_name'),
        ];

        // Logo custom per-export (opsional) — fallback ke logo default/config.
        if ($request->hasFile('logo')) {
            $header['logo_path'] = $request->file('logo')->getRealPath();
        }

        $export = new DispatcherCheckingExport($vehicle, $month, $header);
        $nopol = str_replace(' ', '', $vehicle->consumerial_code);

        return $export->download("form-pengecekan-{$nopol}-{$month->format('Y-m')}.xlsx");
    }
}
