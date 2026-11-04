<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\VehiclePartsReplacementTransaction;
use App\Models\VehiclePartsReplacement;
use App\Models\VprtDetail;
use App\Models\Vehicle;
use App\Exports\FinancialReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    /**
     * GET /superadmin/financial-report
     * Tampilkan laporan keuangan service
     */
    public function index(Request $request)
    {
        // ── Determine periode ──
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Navigasi bulan
        $prevMonth = $startDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $startDate->copy()->addMonth()->format('Y-m');
        $currentMonthLabel = $startDate->translatedFormat('F Y');

        // ── Vehicles for filter ──
        $vehicles = Vehicle::orderBy('consumerial_code')->get();

        // ── Transactions query ──
        $trxQuery = VehiclePartsReplacementTransaction::with([
                'replacement.vehicle', 'driver', 'details'
            ])
            ->whereBetween('service_date', [$startDate, $endDate]);

        if ($request->vehicle_id) {
            $trxQuery->whereHas('replacement', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }

        // Ambil koleksi penuh sebelum paginate agar ringkasan tidak terpotong.
        $allTrx = (clone $trxQuery)->orderByDesc('service_date')->get();
        $transactions = $trxQuery->orderByDesc('service_date')->paginate(20);

        // ── Summary bulan ini ──
        $totalCost = $allTrx->sum('total_amount');

        // Summary bulan lalu (untuk perbandingan)
        $prevStart = $startDate->copy()->subMonth()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $prevCost  = VehiclePartsReplacementTransaction::whereBetween('service_date', [$prevStart, $prevEnd])
            ->sum('total_amount');

        $costChangePct = $prevCost > 0
            ? round(($totalCost - $prevCost) / $prevCost * 100, 1)
            : 0;

        // Hitung detail items
        $trxIds = $allTrx->pluck('id');
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

        // ── Top Kendaraan ──
        $topVehicles = VehiclePartsReplacementTransaction::select(
                'vehicle_parts_replacements.nopol',
                DB::raw('COUNT(vehicle_parts_replacement_transactions.id) as trx_count'),
                DB::raw('SUM(vehicle_parts_replacement_transactions.total_amount) as total_cost')
            )
            ->join('vehicle_parts_replacements', 'vehicle_parts_replacement_transactions.vehicle_parts_replacement_id', '=', 'vehicle_parts_replacements.id')
            ->whereBetween('vehicle_parts_replacement_transactions.service_date', [$startDate, $endDate])
            ->groupBy('vehicle_parts_replacements.nopol')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        // ── Top Items ──
        $topItems = VprtDetail::select(
                'part_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total_price) as total_cost')
            )
            ->whereIn('vprt_id', $trxIds)
            ->groupBy('part_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ── Trend 6 bulan ──
        $trendLabels = [];
        $trendCosts  = [];
        $trendCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $startDate->copy()->subMonths($i);
            $trendLabels[] = $m->translatedFormat('M Y');

            $mStart = $m->copy()->startOfMonth();
            $mEnd   = $m->copy()->endOfMonth();

            $mTrx = VehiclePartsReplacementTransaction::whereBetween('service_date', [$mStart, $mEnd]);
            $trendCosts[]  = $mTrx->sum('total_amount');
            $trendCounts[] = $mTrx->count();
        }

        // ── Vehicle cost data for chart ──
        $vehicleCostLabels = $topVehicles->pluck('nopol')->toArray();
        $vehicleCostValues = $topVehicles->pluck('total_cost')->toArray();

        return view('superadmin.financial-report.index', compact(
            'transactions', 'summary', 'vehicles', 'topVehicles', 'topItems',
            'currentMonthLabel', 'prevMonth', 'nextMonth',
            'trendLabels', 'trendCosts', 'trendCounts',
            'vehicleCostLabels', 'vehicleCostValues'
        ));
    }

    /**
     * GET /superadmin/financial-report/export
     * Download Excel atau PDF
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $month  = $request->get('month', now()->format('Y-m'));

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── Gather semua data ──
        $trxQuery = VehiclePartsReplacementTransaction::with([
            'replacement.vehicle', 'driver', 'details'
        ])->whereBetween('service_date', [$startDate, $endDate]);

        if ($request->vehicle_id) {
            $trxQuery->whereHas('replacement', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        $transactions = $trxQuery->orderByDesc('service_date')->get();
        $trxIds = $transactions->pluck('id');
        $totalCost = $transactions->sum('total_amount');
        $totalItems = VprtDetail::whereIn('vprt_id', $trxIds)->sum('quantity');

        // Summary bulan lalu
        $prevStart = $startDate->copy()->subMonth()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $prevCost  = VehiclePartsReplacementTransaction::whereBetween('service_date', [$prevStart, $prevEnd])
            ->sum('total_amount');
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
            ->leftJoin('vehicles', 'vehicle_parts_replacements.vehicle_id', '=', 'vehicles.id')
            ->whereBetween('vehicle_parts_replacement_transactions.service_date', [$startDate, $endDate])
            ->groupBy('vehicle_parts_replacements.nopol', 'vehicles.consumerial_name')
            ->orderByDesc('total_cost')
            ->limit(20)
            ->get();

        $topItems = VprtDetail::select(
                'part_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total_price) as total_cost')
            )
            ->whereIn('vprt_id', $trxIds)
            ->groupBy('part_name')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();

        // Trend data
        $trendLabels = [];
        $trendCosts  = [];
        $trendCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $startDate->copy()->subMonths($i);
            $trendLabels[] = $m->translatedFormat('M Y');
            $mStart = $m->copy()->startOfMonth();
            $mEnd   = $m->copy()->endOfMonth();
            $mTrx = VehiclePartsReplacementTransaction::whereBetween('service_date', [$mStart, $mEnd]);
            $trendCosts[]  = $mTrx->sum('total_amount');
            $trendCounts[] = $mTrx->count();
        }

        $monthLabel = $startDate->translatedFormat('F Y');

        // ══════════════════════════════════════════
        //  EXCEL
        // ══════════════════════════════════════════
        if ($format === 'excel') {
            $export = new FinancialReportExport([
                'month'       => $month,
                'vehicle_id'  => $request->vehicle_id,
                'transactions'=> $transactions,
                'summary'     => $summary,
                'topVehicles' => $topVehicles,
                'topItems'    => $topItems,
                'trendData'   => [
                    'labels' => $trendLabels,
                    'costs'  => $trendCosts,
                    'counts' => $trendCounts,
                ],
            ]);

            $path = $export->generate();
            $filename = "laporan-keuangan-service-{$month}.xlsx";

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // ══════════════════════════════════════════
        //  PDF
        // ══════════════════════════════════════════
        $pdf = Pdf::loadView('superadmin.financial-report.export-pdf', compact(
            'transactions', 'summary', 'topVehicles', 'topItems',
            'monthLabel', 'trendLabels', 'trendCosts', 'trendCounts'
        ))->setPaper('a4', 'landscape');

        $filename = "laporan-keuangan-service-{$month}.pdf";
        return $pdf->download($filename);
    }
}
