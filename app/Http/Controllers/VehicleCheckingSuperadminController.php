<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementTransaction;
use App\Models\VprtDetail;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleAndToolsConsumers;
use App\Models\User;
use App\Models\TripValidation;
use App\Exports\FinancialReportExport;
use App\Services\UnitScope;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk role SUPERADMIN:
 * 1. Vehicle Health Matrix (overview semua kendaraan)
 * 2. Vehicle Health Show (detail per kendaraan)
 * 3. Laporan Keuangan Service (financial report)
 * 4. Export Excel / PDF
 * 5. Laporan Transaksi Sparepart (transaction report)
 */
class VehicleCheckingSuperadminController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  VEHICLE HEALTH MATRIX - INDEX
    //  View: superadmin/vehicle-health/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function vehicleHealthIndex(Request $request)
    {
        // Batas unit: superadmin unit hanya melihat kendaraan unitnya,
        // superadmin Pusat/global melihat seluruh unit.
        $unitId = UnitScope::for($request->user())->unitId();

        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->inUnit($unitId)
            ->with(['checkings.details.itemResults'])
            ->orderBy('consumerial_code')
            ->get();

        // Hitung health score per kendaraan.
        // Hitung VPR aktif sekaligus (GROUP BY) agar tidak N+1 per kendaraan.
        $activeReplacementCounts = VehiclePartsReplacement::query()
            ->activeRequests()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->selectRaw('vehicle_id, COUNT(*) as total')
            ->groupBy('vehicle_id')
            ->pluck('total', 'vehicle_id');

        $vehicles = $vehicles->map(function ($v) use ($activeReplacementCounts) {
            $allDetails = $v->checkings->flatMap->details;
            $allResults = $allDetails->flatMap->itemResults;

            $total = $allResults->count();
            $okCount = $allResults->filter(fn($r) => in_array($r->status, ['ok', 'baik']))->count();
            $v->health_score = $total > 0 ? round(($okCount / $total) * 100, 1) : 100;

            $v->active_replacement_count = (int) ($activeReplacementCounts[$v->id] ?? 0);
            $lastDetail = $allDetails->sortByDesc('checked_at')->first();
            $v->last_checked_at = $lastDetail?->checked_at;

            return $v;
        });

        // Filter health
        if ($request->filled('health')) {
            $vehicles = $vehicles->filter(function ($v) use ($request) {
                return match ($request->health) {
                    'excellent' => $v->health_score >= 85,
                    'good'      => $v->health_score >= 70 && $v->health_score < 85,
                    'warning'   => $v->health_score >= 50 && $v->health_score < 70,
                    'critical'  => $v->health_score < 50,
                    default     => true,
                };
            });
        }

        // Sort
        $sort = $request->get('sort', 'score_asc');
        $vehicles = match ($sort) {
            'score_desc' => $vehicles->sortByDesc('health_score'),
            'nopol'      => $vehicles->sortBy('consumerial_code'),
            'last_check' => $vehicles->sortByDesc('last_checked_at'),
            default      => $vehicles->sortBy('health_score'),
        };

        // Fleet stats
        $fleetStats = [
            'total'     => $vehicles->count(),
            'excellent' => $vehicles->filter(fn($v) => $v->health_score >= 85)->count(),
            'good'      => $vehicles->filter(fn($v) => $v->health_score >= 70 && $v->health_score < 85)->count(),
            'warning'   => $vehicles->filter(fn($v) => $v->health_score >= 50 && $v->health_score < 70)->count(),
            'critical'  => $vehicles->filter(fn($v) => $v->health_score < 50)->count(),
        ];

        return view('superadmin.vehicle-health.index', compact('vehicles', 'fleetStats'));
    }

    // ════════════════════════════════════════════════════════════════
    //  VEHICLE HEALTH - SHOW (detail per kendaraan)
    //  View: superadmin/vehicle-health/show.blade.php
    // ════════════════════════════════════════════════════════════════
    public function vehicleHealthShow(Request $request, $consumerialCode)
    {
        $vehicle = VehicleAndToolsConsumers::where('consumerial_code', $consumerialCode)
            ->inUnit(UnitScope::for($request->user())->unitId())
            ->firstOrFail();
        $vehicleId = $vehicle->id;

        // Semua checking details kendaraan ini
        $allDetails = VehicleCheckingDetail::whereHas('vehicleChecking', fn($q) => $q->where('vehicle_id', $vehicleId))
            ->with(['itemResults', 'dispatcher', 'currentDriver'])
            ->orderByDesc('checked_at')
            ->get();

        $allResults = $allDetails->flatMap->itemResults;
        $total = $allResults->count();
        $okCount = $allResults->filter(fn($r) => in_array($r->status, ['ok', 'baik']))->count();
        $vehicle->health_score = $total > 0 ? round(($okCount / $total) * 100, 1) : 100;

        $totalCheckings = $allDetails->count();
        $lastChecked = $allDetails->first()?->checked_at;

        $totalServiceCost = VehiclePartsReplacementTransaction::whereHas('replacement', fn($q) => $q->where('vehicle_id', $vehicleId))
            ->sum('total_amount');

        // Schedules
        $schedules = \App\Models\VehicleCheckingSchedule::where('vehicle_id', $vehicleId)->get();

        // Recent checkings (last 20)
        $recentCheckings = $allDetails->take(20);

        // Active replacements
        $activeReplacements = VehiclePartsReplacement::forVehicle($vehicleId)
            ->activeRequests()
            ->with(['replacementDetails', 'transaction'])
            ->get();

        // Trend 6 bulan
        $trendLabels = [];
        $trendScores = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $trendLabels[] = $m->translatedFormat('M Y');
            $monthStart = $m->copy()->startOfMonth();
            $monthEnd = $m->copy()->endOfMonth();

            $monthDetails = $allDetails->filter(fn($d) => $d->checked_at >= $monthStart && $d->checked_at <= $monthEnd);
            $monthResults = $monthDetails->flatMap->itemResults;
            $mTotal = $monthResults->count();
            $mOk = $monthResults->filter(fn($r) => in_array($r->status, ['ok', 'baik']))->count();
            $trendScores[] = $mTotal > 0 ? round(($mOk / $mTotal) * 100, 1) : null;
        }

        // Stats distribusi routine
        $routineResults = $allResults->filter(fn($r) => in_array($r->status, ['ok', 'ng']));
        $routineStats = [
            'ok' => $routineResults->where('status', 'ok')->count(),
            'ng' => $routineResults->where('status', 'ng')->count(),
        ];

        // Stats distribusi body
        $bodyResults = $allResults->filter(fn($r) => in_array($r->status, ['baik', 'cukup', 'kurang', 'tidak']));
        $bodyStats = [
            'baik'  => $bodyResults->where('status', 'baik')->count(),
            'cukup' => $bodyResults->where('status', 'cukup')->count(),
            'kurang'=> $bodyResults->where('status', 'kurang')->count(),
            'tidak' => $bodyResults->where('status', 'tidak')->count(),
        ];

        // Trip Analysis untuk kendaraan ini
        $tripAnalyses = TripValidation::with([
            'transactionProofPrevious.permohonan.requester',
            'validator',
        ])
        ->whereHas('transactionProofPrevious.permohonan', function ($q) use ($vehicleId) {
            $q->where('consumerial_tools_id', $vehicleId);
        })
        ->whereNotNull('actual_efficiency')
        ->orderBy('days_between_refuel', 'asc')
        ->orderByDesc('created_at')
        ->take(20)
        ->get();

        return view('superadmin.vehicle-health.show', compact(
            'vehicle', 'totalCheckings', 'lastChecked', 'totalServiceCost',
            'schedules', 'recentCheckings', 'activeReplacements',
            'trendLabels', 'trendScores', 'routineStats', 'bodyStats',
            'tripAnalyses'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  FINANCIAL REPORT - INDEX
    //  View: superadmin/financial-report/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function financialReportIndex(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $prevMonth = $startDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $startDate->copy()->addMonth()->format('Y-m');
        $currentMonthLabel = $startDate->translatedFormat('F Y');

        // Batas unit: laporan keuangan hanya memuat kendaraan/transaksi unit user.
        $unitId = UnitScope::for($request->user())->unitId();

        $vehicles = VehicleAndToolsConsumers::inUnit($unitId)
            ->orderBy('consumerial_code')
            ->get();

        // Transactions query
        $trxQuery = VehiclePartsReplacementTransaction::with(['replacement.vehicle', 'driver', 'details'])
            ->inUnit($unitId)
            ->whereBetween('service_date', [$startDate, $endDate]);

        if ($request->vehicle_id) {
            $trxQuery->whereHas('replacement', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        // Ambil koleksi penuh SEBELUM paginate() — paginate menambahkan
        // limit/offset ke builder, sehingga clone setelahnya ikut terpotong
        // dan total biaya bulan ini salah bila transaksi > 20 baris.
        $allTrx       = (clone $trxQuery)->orderByDesc('service_date')->get();
        $transactions = $trxQuery->orderByDesc('service_date')->paginate(20);

        $totalCost = $allTrx->sum('total_amount');

        // Bulan lalu comparison
        $prevStart = $startDate->copy()->subMonth()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $prevCost  = VehiclePartsReplacementTransaction::inUnit($unitId)
            ->whereBetween('service_date', [$prevStart, $prevEnd])->sum('total_amount');
        $costChangePct = $prevCost > 0 ? round(($totalCost - $prevCost) / $prevCost * 100, 1) : 0;

        $trxIds     = $allTrx->pluck('id');
        $totalItems = VprtDetail::whereIn('vprt_id', $trxIds)->sum('quantity');

        $summary = [
            'total_cost'        => $totalCost,
            'cost_change_pct'   => $costChangePct,
            'completed_count'   => $allTrx->filter(fn($t) => $t->replacement?->status === 'completed')->count(),
            'total_vpr'         => $allTrx->count(),
            'total_items'       => $totalItems,
            'avg_cost_per_item' => $totalItems > 0 ? round($totalCost / $totalItems) : 0,
            'avg_cost_per_trx'  => $allTrx->count() > 0 ? round($totalCost / $allTrx->count()) : 0,
            'overdue_count'     => $allTrx->where('is_overdue', true)->count(),
        ];

        // Top kendaraan
        $topVehicles = VehiclePartsReplacementTransaction::select(
                'vehicle_parts_replacements.nopol',
                DB::raw('COUNT(vehicle_parts_replacement_transactions.id) as trx_count'),
                DB::raw('SUM(vehicle_parts_replacement_transactions.total_amount) as total_cost')
            )
            ->join('vehicle_parts_replacements', 'vehicle_parts_replacement_transactions.vehicle_parts_replacement_id', '=', 'vehicle_parts_replacements.id')
            ->inUnit($unitId)
            ->whereBetween('vehicle_parts_replacement_transactions.service_date', [$startDate, $endDate])
            ->groupBy('vehicle_parts_replacements.nopol')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        // Top items
        $topItems = VprtDetail::select('part_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_cost'))
            ->whereIn('vprt_id', $trxIds)
            ->groupBy('part_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Trend 6 bulan
        $trendLabels = $trendCosts = $trendCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $startDate->copy()->subMonths($i);
            $trendLabels[] = $m->translatedFormat('M Y');
            $mStart = $m->copy()->startOfMonth();
            $mEnd   = $m->copy()->endOfMonth();
            $mQuery = VehiclePartsReplacementTransaction::inUnit($unitId)
                ->whereBetween('service_date', [$mStart, $mEnd]);
            $trendCosts[]  = $mQuery->sum('total_amount');
            $trendCounts[] = (clone $mQuery)->count();
        }

        $vehicleCostLabels = $topVehicles->pluck('nopol')->toArray();
        $vehicleCostValues = $topVehicles->pluck('total_cost')->toArray();

        return view('superadmin.financial-report.index', compact(
            'transactions', 'summary', 'vehicles', 'topVehicles', 'topItems',
            'currentMonthLabel', 'prevMonth', 'nextMonth',
            'trendLabels', 'trendCosts', 'trendCounts',
            'vehicleCostLabels', 'vehicleCostValues'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  FINANCIAL REPORT - EXPORT (Excel / PDF)
    //  Dipanggil dari tombol di financial-report/index
    // ════════════════════════════════════════════════════════════════
    public function financialReportExport(Request $request)
    {
        $format = $request->get('format', 'excel');
        $month  = $request->get('month', now()->format('Y-m'));

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Batas unit juga berlaku untuk export (Excel & PDF).
        $unitId = UnitScope::for($request->user())->unitId();

        $trxQuery = VehiclePartsReplacementTransaction::with(['replacement.vehicle', 'driver', 'details'])
            ->inUnit($unitId)
            ->whereBetween('service_date', [$startDate, $endDate]);

        if ($request->vehicle_id) {
            $trxQuery->whereHas('replacement', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        $transactions = $trxQuery->orderByDesc('service_date')->get();
        $trxIds    = $transactions->pluck('id');
        $totalCost = $transactions->sum('total_amount');
        $totalItems = VprtDetail::whereIn('vprt_id', $trxIds)->sum('quantity');

        $prevStart = $startDate->copy()->subMonth()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $prevCost  = VehiclePartsReplacementTransaction::inUnit($unitId)
            ->whereBetween('service_date', [$prevStart, $prevEnd])->sum('total_amount');
        $costChangePct = $prevCost > 0 ? round(($totalCost - $prevCost) / $prevCost * 100, 1) : 0;

        $summary = [
            'total_cost'        => $totalCost,
            'cost_change_pct'   => $costChangePct,
            'completed_count'   => $transactions->filter(fn($t) => $t->replacement?->status === 'completed')->count(),
            'total_vpr'         => $transactions->count(),
            'total_items'       => $totalItems,
            'avg_cost_per_item' => $totalItems > 0 ? round($totalCost / $totalItems) : 0,
            'avg_cost_per_trx'  => $transactions->count() > 0 ? round($totalCost / $transactions->count()) : 0,
            'overdue_count'     => $transactions->where('is_overdue', true)->count(),
        ];

        $topVehicles = VehiclePartsReplacementTransaction::select(
                'vehicle_parts_replacements.nopol',
                DB::raw("COALESCE(vehicles.consumerial_name, '-') as vehicle_name"),
                DB::raw('COUNT(vehicle_parts_replacement_transactions.id) as trx_count'),
                DB::raw('SUM(vehicle_parts_replacement_transactions.total_amount) as total_cost')
            )
            ->join('vehicle_parts_replacements', 'vehicle_parts_replacement_transactions.vehicle_parts_replacement_id', '=', 'vehicle_parts_replacements.id')
            ->leftJoin('vehicle_and_tools_consumers as vehicles', 'vehicle_parts_replacements.vehicle_id', '=', 'vehicles.id')
            ->inUnit($unitId)
            ->whereBetween('vehicle_parts_replacement_transactions.service_date', [$startDate, $endDate])
            ->groupBy('vehicle_parts_replacements.nopol', 'vehicles.consumerial_name')
            ->orderByDesc('total_cost')
            ->limit(20)->get();

        $topItems = VprtDetail::select('part_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_cost'))
            ->whereIn('vprt_id', $trxIds)->groupBy('part_name')->orderByDesc('total_qty')->limit(20)->get();

        $trendLabels = $trendCosts = $trendCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $startDate->copy()->subMonths($i);
            $trendLabels[] = $m->translatedFormat('M Y');
            $mStart = $m->copy()->startOfMonth();
            $mEnd   = $m->copy()->endOfMonth();
            $mQ = VehiclePartsReplacementTransaction::inUnit($unitId)
                ->whereBetween('service_date', [$mStart, $mEnd]);
            $trendCosts[]  = $mQ->sum('total_amount');
            $trendCounts[] = (clone $mQ)->count();
        }

        $monthLabel = $startDate->translatedFormat('F Y');

        // ── EXCEL ──
        if ($format === 'excel') {
            $export = new FinancialReportExport([
                'month'        => $month,
                'vehicle_id'   => $request->vehicle_id,
                'transactions' => $transactions,
                'summary'      => $summary,
                'topVehicles'  => $topVehicles,
                'topItems'     => $topItems,
                'trendData'    => ['labels' => $trendLabels, 'costs' => $trendCosts, 'counts' => $trendCounts],
            ]);
            $path = $export->generate();
            return response()->download($path, "laporan-keuangan-service-{$month}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // ── PDF ──
        $pdf = Pdf::loadView('superadmin.financial-report.export-pdf', compact(
            'transactions', 'summary', 'topVehicles', 'topItems',
            'monthLabel', 'trendLabels', 'trendCosts', 'trendCounts'
        ))->setPaper('a4', 'landscape');

        return $pdf->download("laporan-keuangan-service-{$month}.pdf");
    }

    // ════════════════════════════════════════════════════════════════
    //  TRANSACTION REPORT - INDEX
    //  View: superadmin/transaction-report/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function transactionReportIndex(Request $request)
    {
        $unitId = UnitScope::for($request->user())->unitId();

        $query = VehiclePartsReplacementTransaction::with([
            'replacement.vehicle', 'driver', 'details',
        ])->inUnit($unitId)->orderByDesc('service_date');

        if ($request->filled('vehicle_id')) {
            $query->whereHas('replacement', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }
        if ($request->filled('driver_id'))  $query->where('driver_id', $request->driver_id);
        if ($request->filled('date_from'))  $query->whereDate('service_date', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('service_date', '<=', $request->date_to);

        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('is_overdue', true)
                      ->whereHas('replacement', fn($q) => $q->where('status', '!=', 'completed'));
            } elseif ($request->status === 'completed') {
                $query->whereHas('replacement', fn($q) => $q->where('status', 'completed'));
            } elseif ($request->status === 'in_service') {
                $query->whereHas('replacement', fn($q) => $q->where('status', 'in_service'));
            }
        }

        $transactions = $query->paginate(20)->withQueryString();

        $totalTrx  = VehiclePartsReplacementTransaction::inUnit($unitId)->count();
        $totalCost = VehiclePartsReplacementTransaction::inUnit($unitId)->sum('total_amount');

        $stats = [
            'total'     => $totalTrx,
            'completed' => VehiclePartsReplacementTransaction::inUnit($unitId)
                ->whereHas('replacement', fn($q) => $q->completed())->count(),
            'overdue'   => VehiclePartsReplacementTransaction::inUnit($unitId)->overdue()->count(),
            'total_cost'=> $totalCost,
            'avg_cost'  => $totalTrx > 0 ? round($totalCost / $totalTrx) : 0,
        ];

        $vehicles = VehicleAndToolsConsumers::inUnit($unitId)->orderBy('consumerial_code')->get();
        $drivers  = User::where('role', 'requester')
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        return view('superadmin.transaction-report.index', compact(
            'transactions', 'stats', 'vehicles', 'drivers'
        ));
    }
}
