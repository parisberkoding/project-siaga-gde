<?php

namespace App\Http\Controllers;

use App\Models\Approved_Request;
use App\Models\EmployeeDefault;
use App\Models\MasterGasoline;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Site;
use App\Models\StrukItem;
use App\Models\Transaction_Proofs;
use App\Models\TripValidation;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserCredentialReveal;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleBarcode;
use App\Models\VehicleDetail;
use App\Models\VehicleTripAnalysis;
use App\Services\UnitScope;
use App\Support\MediaStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperadminController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth', 'role:superadmin']);
    // }

    // =================== Private Helpers ===================

    private function paginateCollection(Collection $items, $perPage = 10, $pageName = 'page', $page = null)
    {
        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    // ==================== DASHBOARD ====================

    public function index()
    {
        // Superadmin kini menggunakan Dashboard Bisnis sebagai landing.
        // Route lama (superadmin-dashboard) dialihkan agar tidak menampilkan stub lama.
        return redirect()->route('superadmin.dashboard.bisnis');
    }

    public function getDashboardStats()
    {
        try {
            $stats = [
                'totalExpense' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->sum('bill_amounts'),
                // 'expense_in_requests' => Permohonan::whereIn('status', ['pending', 'approved'])
                //     ->sum('bill_amounts'),
                'totalRequests' => Permohonan::count(),
                'completedRequests' => Permohonan::where('status', 'completed')->count(),

                // Gasoline Distribution
                'gasolineDistribution' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->selectRaw('gasoline_type, SUM(bill_amounts) as total')
                    ->groupBy('gasoline_type')
                    ->pluck('total', 'gasoline_type')
                    ->toArray(),

                // Expense over time based on period
                'expenseOverTime' => $this->getExpenseOverTime($period),

                // Gasoline distribution by expense (untuk pie chart)
                'gasolineExpenseDistribution' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->whereBetween('request_date', [$startDate, $endDate])
                    ->selectRaw('gasoline_type, SUM(bill_amounts) as total')
                    ->groupBy('gasoline_type')
                    ->pluck('total', 'gasoline_type')
                    ->toArray(),

                // Period info
                'period' => $period,
                'periodLabel' => $this->getPeriodLabel($period),
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),

                // Vehicle Expense (top 10)
                'vehicleExpense' => Permohonan::with('consumerialTool')
                    ->whereIn('status', ['completed', 'approved'])
                    ->selectRaw('consumerial_tools_id, SUM(bill_amounts) as total')
                    ->groupBy('consumerial_tools_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $vehicle = $item->consumerialTool;
                        $vehicleName = $vehicle
                            ? "{$vehicle->plat_nomer} - {$vehicle->merk}"
                            : 'Unknown';

                        return [$vehicleName => $item->total];
                    })
                    ->toArray(),

                // Driver Expense (top 10)
                'driverExpense' => Permohonan::with('requester')
                    ->whereIn('status', ['completed', 'approved'])
                    ->selectRaw('requester_id, SUM(bill_amounts) as total')
                    ->groupBy('requester_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $driverName = $item->requester->fullname ?? 'Unknown';

                        return [$driverName => $item->total];
                    })
                    ->toArray(),

                // Driver Request Count
                'driverRequestCount' => Permohonan::with('requester')
                    ->selectRaw('requester_id, COUNT(*) as count')
                    ->groupBy('requester_id')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $driverName = $item->requester->fullname ?? 'Unknown';

                        return [$driverName => $item->count];
                    })
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat dashboard. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    /**
     * Get expense over time based on period for line chart.
     * DB-agnostic (MySQL & SQLite) — grouping dilakukan di PHP.
     */
    private function getExpenseOverTime($period = 'month', ?int $unitId = null)
    {
        $now = Carbon::now();

        $query = Permohonan::whereIn('status', ['completed', 'approved'])->inUnit($unitId);

        switch ($period) {
            case 'day':
                // Hourly data for current day
                return $query->whereDate('request_date', $now->toDateString())
                    ->get(['request_date', 'bill_amounts'])
                    ->groupBy(fn ($t) => $t->request_date->format('H'))
                    ->map(fn ($g) => $g->sum('bill_amounts'))
                    ->sortKeys()
                    ->mapWithKeys(fn ($total, $hour) => [sprintf('%02d:00', $hour) => $total])
                    ->toArray();

            case 'year':
                // Monthly data for current year
                return $query->whereYear('request_date', $now->year)
                    ->get(['request_date', 'bill_amounts'])
                    ->groupBy(fn ($t) => $t->request_date->format('n'))
                    ->map(fn ($g) => $g->sum('bill_amounts'))
                    ->sortKeys()
                    ->mapWithKeys(fn ($total, $month) => [Carbon::create()->month($month)->translatedFormat('M') => $total])
                    ->toArray();

            case 'month':
            default:
                // Daily data for current month
                return $query->whereYear('request_date', $now->year)
                    ->whereMonth('request_date', $now->month)
                    ->get(['request_date', 'bill_amounts'])
                    ->groupBy(fn ($t) => $t->request_date->toDateString())
                    ->map(fn ($g) => $g->sum('bill_amounts'))
                    ->sortKeys()
                    ->toArray();
        }
    }

    private function getPeriodLabel($period)
    {
        $labels = [
            'day' => 'Hari Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
        ];

        return $labels[$period] ?? 'Bulan Ini';
    }

    /**
     * Label rentang tanggal eksplisit, mis. "1 Jun 2026 – 30 Jun 2026".
     * Memakai locale id agar nama bulan konsisten berbahasa Indonesia.
     */
    private function formatDateRangeLabel(Carbon $startDate, Carbon $endDate): string
    {
        return $startDate->copy()->locale('id')->translatedFormat('d M Y')
            .' – '
            .$endDate->copy()->locale('id')->translatedFormat('d M Y');
    }

    /**
     * Calculate stats from filtered transactions collection.
     *
     * Bila request membawa start_date & end_date eksplisit, kartu ringkasan
     * dihitung dari koleksi yang sudah ter-filter pada rentang itu — TIDAK
     * dihitung ulang memakai rolling period ("bulan ini" dst).
     */
    private function calculateStats($transactions, $period = 'month', ?Request $request = null, ?int $unitId = null)
    {
        $hasExplicitRange = $request instanceof Request
            && $request->filled('start_date')
            && $request->filled('end_date');

        // Tanpa rentang eksplisit: pertahankan perilaku lama (period-based)
        // bila tidak ada transaksi sama sekali.
        if (! $hasExplicitRange && $transactions->isEmpty()) {
            return $this->getDashboardStatsData($period, $unitId);
        }

        if ($hasExplicitRange) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $periodLabel = $this->formatDateRangeLabel($startDate, $endDate);
        } else {
            // Define date range based on period
            $now = Carbon::now();
            switch ($period) {
                case 'day':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case 'year':
                    $startDate = $now->copy()->startOfYear();
                    $endDate = $now->copy()->endOfYear();
                    break;
                case 'month':
                default:
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfMonth();
                    break;
            }
            $periodLabel = $this->getPeriodLabel($period);
        }

        return [
            // Period info
            'period' => $period,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),

            // Total from filtered transactions
            'totalExpense' => $transactions->whereIn('status', ['completed', 'approved'])
                ->sum('bill_amounts'),

            // Total transactions count
            'totalTransactions' => $transactions->count(),
            'total' => $transactions->count(), // Alias

            // Average Request Value
            'avgRequestValue' => $transactions->whereIn('status', ['completed', 'approved'])
                ->avg('bill_amounts') ?? 0,

            // Average Request Frequency (dari filtered data)
            'avgRequestFrequency' => $this->calculateFilteredFrequency($transactions, $period),

            // Gasoline Distribution (dari filtered data)
            'gasolineDistribution' => $transactions->whereIn('status', ['completed', 'approved'])
                ->groupBy('gasoline_type')
                ->map(function ($group) {
                    return $group->sum('bill_amounts');
                })
                ->toArray(),

            // Gasoline Expense Distribution (untuk pie chart)
            'gasolineExpenseDistribution' => $transactions->whereIn('status', ['completed', 'approved'])
                ->groupBy('gasoline_type')
                ->map(function ($group) {
                    return $group->sum('bill_amounts');
                })
                ->toArray(),

            // Expense over time (dari filtered data)
            'expenseOverTime' => $this->getFilteredExpenseOverTime($transactions, $period),

            'vehicleExpense' => $transactions->whereIn('status', ['completed', 'approved'])
                ->groupBy('consumerial_tools_id')
                ->map(function ($group) {
                    $vehicle = $group->first()->consumerialTool;
                    if (! $vehicle) {
                        return null;
                    }

                    return [
                        'name' => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                        'total' => $group->sum('bill_amounts'),
                    ];
                })
                ->filter()
                ->sortByDesc('total')
                ->take(10)
                ->pluck('total', 'name')
                ->toArray(),

            'driverExpense' => $transactions->whereIn('status', ['completed', 'approved'])
                ->groupBy('requester_id')
                ->map(function ($group) {
                    $driver = $group->first()->requester;
                    if (! $driver) {
                        return null;
                    }

                    return [
                        'name' => $driver->nama_lengkap,
                        'total' => $group->sum('bill_amounts'),
                    ];
                })
                ->filter()
                ->sortByDesc('total')
                ->take(10)
                ->pluck('total', 'name')
                ->toArray(),
        ];
    }

    /**
     * Calculate frequency from filtered transactions
     */
    private function calculateFilteredFrequency($transactions, $period)
    {
        if ($transactions->isEmpty()) {
            return ['value' => 0, 'label' => 'per hari'];
        }

        switch ($period) {
            case 'day':
                // Group by hour
                $grouped = $transactions->groupBy(function ($item) {
                    return $item->request_date->format('H');
                });
                $avgPerPeriod = $grouped->avg(function ($group) {
                    return $group->count();
                }) ?? 0;
                $periodLabel = 'per jam';
                break;

            case 'year':
                // Group by month
                $grouped = $transactions->groupBy(function ($item) {
                    return $item->request_date->format('m');
                });
                $avgPerPeriod = $grouped->avg(function ($group) {
                    return $group->count();
                }) ?? 0;
                $periodLabel = 'per bulan';
                break;

            case 'month':
            default:
                // Group by day
                $grouped = $transactions->groupBy(function ($item) {
                    return $item->request_date->format('d');
                });
                $avgPerPeriod = $grouped->avg(function ($group) {
                    return $group->count();
                }) ?? 0;
                $periodLabel = 'per hari';
                break;
        }

        return [
            'value' => round($avgPerPeriod, 1),
            'label' => $periodLabel,
        ];
    }

    /**
     * Get expense over time from filtered transactions
     */
    private function getFilteredExpenseOverTime($transactions, $period)
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        $filtered = $transactions->whereIn('status', ['completed', 'approved']);

        switch ($period) {
            case 'day':
                // Hourly
                return $filtered->groupBy(function ($item) {
                    return $item->request_date->format('H:00');
                })->map(function ($group) {
                    return $group->sum('bill_amounts');
                })->sortKeys()->toArray();

            case 'year':
                // Monthly
                return $filtered->groupBy(function ($item) {
                    return $item->request_date->translatedFormat('M');
                })->map(function ($group) {
                    return $group->sum('bill_amounts');
                })->toArray();

            case 'month':
            default:
                // Daily
                return $filtered->groupBy(function ($item) {
                    return $item->request_date->format('Y-m-d');
                })->map(function ($group) {
                    return $group->sum('bill_amounts');
                })->sortKeys()->toArray();
        }
    }

    /**
     * Tampilkan halaman form cetak laporan BBM
     */
    public function printReportPage(Request $request)
    {
        $title = 'Cetak Laporan BBM';
        $approvers = User::whereIn('role', ['superadmin', 'admin'])->orderBy('nama_lengkap')->get();

        return view('superadmin.print-report', compact('title', 'approvers'));
    }

    /**
     * Generate dan tampilkan laporan PDF transaksi BBM
     */
    public function printReport(Request $request)
    {
        // Jika tidak ada parameter, tampilkan halaman form
        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            $title = 'Cetak Laporan BBM';
            $approvers = User::whereIn('role', ['superadmin', 'admin'])->orderBy('nama_lengkap')->get();

            return view('superadmin.print-report', compact('title', 'approvers'));
        }

        ini_set('memory_limit', '1024M'); // 1GB
        set_time_limit(300); // 5 minutes

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|array',
            'status.*' => 'in:pending,approved,completed,rejected',
            'gasoline_type' => 'nullable|string|in:Pertamax,Pertamina Dex,Pertalite',
            'authorizer_id' => 'nullable|integer|exists:users,id',
        ], [
            'start_date.required' => 'Tanggal mulai harus diisi',
            'start_date.date' => 'Format tanggal mulai tidak valid',
            'end_date.required' => 'Tanggal akhir harus diisi',
            'end_date.date' => 'Format tanggal akhir tidak valid',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai',
            'status.array' => 'Format status tidak valid',
            'status.*.in' => 'Status yang dipilih tidak valid',
            'gasoline_type.in' => 'Jenis BBM tidak valid',
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            // Query transaksi berdasarkan rentang tanggal
            $query = Permohonan::with([
                'requester',
                'vehicle',
                'transactionProof',
                'approvedRequest',
                'authorizer',
            ])
                ->whereBetween('request_date', [$startDate, $endDate]);

            // Filter berdasarkan status (dari checkbox modal)
            if ($request->filled('status') && is_array($request->status) && count($request->status) > 0) {
                $query->whereIn('status', $request->status);
            } else {
                // Default: approved + completed (backward compatible)
                $query->whereIn('status', ['approved', 'completed']);
            }

            // Filter berdasarkan jenis BBM (opsional)
            if ($request->filled('gasoline_type')) {
                $query->where('gasoline_type', $request->gasoline_type);
            }

            // Filter berdasarkan siapa yang approve (opsional)
            if ($request->filled('authorizer_id')) {
                $query->where('authorizer_id', $request->authorizer_id);
            }

            // Get results
            $transactions = $query->orderBy('request_date', 'desc')->get();

            // Validasi: cek apakah ada data
            if ($transactions->isEmpty()) {
                return redirect()
                    ->back()
                    ->with('warning', 'Tidak ada data transaksi untuk periode dan filter yang dipilih.');
            }

            // Hitung statistik
            $totalTransaksi = $transactions->count();
            $totalPengeluaran = $transactions->sum('bill_amounts');

            // Hitung total volume dari transaction_proofs
            $totalVolume = $transactions->reduce(function ($carry, $item) {
                return $carry + ($item->transactionProof->fuel_volume ?? 0);
            }, 0);

            // Generate filename untuk browser print
            $username = Auth::user()->username ?? 'SuperAdmin';

            // Tambahkan info filter di filename (opsional)
            $statusSuffix = $request->filled('status')
                ? '-'.implode('', array_map(fn ($s) => strtoupper(substr($s, 0, 3)), $request->status))
                : '';

            $gasolineSuffix = $request->filled('gasoline_type')
                ? '-'.str_replace(' ', '', $request->gasoline_type)
                : '';

            $filename = sprintf(
                'REPORT-BBMPTH-%s-to-%s-%s',
                $startDate->format('dmY'),
                $endDate->format('dmY'),
                $statusSuffix,
                $username
            );

            // Info filter untuk ditampilkan di laporan (opsional)
            $filterInfo = [
                'status' => $request->status ?? ['approved', 'completed'],
                'gasoline_type' => $request->gasoline_type ?? null,
                'authorizer_id' => $request->authorizer_id ?? null,
                'authorizer_name' => $request->filled('authorizer_id')
                    ? User::find($request->authorizer_id)?->nama_lengkap
                    : null,
            ];

            $compressionStart = microtime(true);
            $maxCompressionTime = 120; // max 120 detik untuk semua gambar

            foreach ($transactions as $trx) {
                if ($trx->transactionProof?->struk_bbm_path) {
                    // Cek timeout - skip sisa gambar jika sudah terlalu lama
                    if ((microtime(true) - $compressionStart) > $maxCompressionTime) {
                        error_log('Image compression timeout reached, skipping remaining images');
                        break;
                    }

                    // BUG-SEC-003: struk kini di disk privat; absolutePath()
                    // otomatis fallback ke file lama di disk publik.
                    $path = MediaStorage::absolutePath($trx->transactionProof->struk_bbm_path);
                    $trx->transactionProof->compressed_image = $path
                        ? $this->compressToJpeg($path, 65)
                        : null;
                }
            }

            $pdf = Pdf::loadView('superadmin.partials.export-report-transactions', compact(
                'transactions',
                'startDate',
                'endDate',
                'totalTransaksi',
                'totalPengeluaran',
                'totalVolume',
                'filename',
                'filterInfo'
            ))->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 100,
                    'isRemoteEnabled' => false,
                    'isJavascriptEnabled' => false,
                    'isPhpEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                ]);

            $response = $pdf->stream($filename.'.pdf');

            $downloadToken = $request->input('download_token', '');
            if ($downloadToken) {
                $response->cookie('download_ready_'.$downloadToken, '1', 3, '/', null, false, false);
            }

            return $response;

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal membuat laporan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // ==================== BUSINESS DASHBOARD VIEW ====================
    public function dashboardBisnis(Request $request)
    {
        // Dashboard ini analitik TINGKAT UNIT: memuat permohonan seluruh
        // driver (top pengeluaran, pivot, statistik per driver). Driver yang
        // membuka URL-nya langsung (deep link / bookmark lama) diarahkan ke
        // dashboard driver miliknya sendiri supaya tidak melihat agregat
        // data driver lain.
        $currentUser = $request->user();

        // Driver/requester tidak boleh melihat agregat lintas driver. Route
        // modul sudah dipersempit lewat permission analytics, tetapi deep link
        // lama tetap harus ditutup di sini (defence in depth).
        $isDriverAccount = $currentUser->hasRoleCode(['requester', 'driver'])
            || $currentUser->hasPermission(['bbm.request', 'spd.create', 'lpj.create', 'vehicle.acknowledge']);

        $hasAnalyticsAccess = $currentUser->hasPermission('dashboard.view')
            || $currentUser->hasPermission(['bbm.approve', 'bbm.export']);

        if ($isDriverAccount && ! $hasAnalyticsAccess) {
            return redirect()->route('driver.dashboard');
        }

        // Route yang sama dipakai dua konteks:
        //  - modules.dashboard.bbm : dashboard modul BBM (setelah pilih modul)
        //  - superadmin.dashboard.bisnis / dashboard-bisnis : dashboard legacy
        $title = $request->routeIs('modules.dashboard.bbm')
            ? 'Dashboard BBM & Permohonan'
            : 'Dashboard Bisnis';
        $period = $request->get('period', 'month');

        // ✅ Batas data per unit: null = global (Pusat/developers), selain itu
        // hanya data unit user. Diterapkan ke SEMUA dataset di halaman ini.
        $scope = UnitScope::for($currentUser);
        $unitId = $scope->unitId();

        // ✅ Bangun query transaksi + terapkan filter (realtime via query string)
        $query = Permohonan::query()
            ->with(['requester', 'consumerialTool', 'transactionProof'])
            ->inUnit($unitId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('request_date', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gasoline_type')) {
            $query->where('gasoline_type', $request->gasoline_type);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('consumerial_tools_id', $request->vehicle_id);
        }

        if ($request->filled('requester_id')) {
            $query->where('requester_id', $request->requester_id);
        }

        if ($request->filled('nominal_range')) {
            $range = $request->nominal_range;
            if ($range === '500000+') {
                $query->where('bill_amounts', '>=', 500000);
            } else {
                [$min, $max] = explode('-', $range);
                $query->whereBetween('bill_amounts', [(int) $min, (int) $max]);
            }
        }

        $transactions = $query->get();

        // ✅ Recalculate statistik berdasarkan data (ter-filter / tidak).
        // Dengan start_date/end_date eksplisit, kartu dihitung dari koleksi
        // yang sudah ter-filter (bukan rolling period seperti "bulan ini").
        $stats = $this->calculateStats($transactions, $period, $request, $unitId);

        // Data pendukung (tidak terpengaruh filter utama) — WRAP WITH PAGINATION
        $driverStats = $this->paginateCollection(
            $this->getDriverStatisticsData($unitId),
            10,
            'driver_page'
        )->appends($request->except(['pivot_page', 'driver_page', 'vehicle_page', 'trip_page', 'page']));

        $kmPerformance = $this->paginateCollection(
            $this->getVehicleKmPerformanceData($unitId),
            10,
            'vehicle_page'
        )->appends($request->except(['pivot_page', 'driver_page', 'vehicle_page', 'trip_page', 'page']));

        $pivotData = $this->paginateCollection(
            $this->getCompletePivotTableData($unitId),
            25,
            'pivot_page'
        )->appends($request->except(['pivot_page', 'driver_page', 'vehicle_page', 'trip_page', 'page']));

        // Opsi filter — hanya yang terlihat oleh user (unit-scoped).
        $vehicles = VehicleAndToolsConsumers::where('is_approved', true)
            ->visibleTo($currentUser)
            ->orderBy('consumerial_code')
            ->get();

        $drivers = User::where('role', 'requester')
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        $approvers = User::whereIn('role', ['superadmin', 'admin'])
            ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
            ->orderBy('nama_lengkap')
            ->get();

        return view('superadmin.partials.dashboard-bisnis', compact(
            'title', 'stats', 'driverStats',
            'kmPerformance', 'pivotData', 'vehicles', 'drivers', 'approvers'
        ));
    }

    // ==================== DATA METHODS (Internal Use) ====================

    private function getDashboardStatsData($period = 'month', ?int $unitId = null)
    {
        $now = Carbon::now();
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        $stats = [
            'period' => $period,
            'periodLabel' => $this->getPeriodLabel($period),
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),

            'totalExpense' => Permohonan::whereIn('status', ['completed', 'approved'])
                ->inUnit($unitId)
                ->whereBetween('request_date', [$startDate, $endDate])
                ->sum('bill_amounts'),

            'avgRequestFrequency' => $this->calculateAvgRequestFrequency($period, $unitId),

            'avgRequestValue' => Permohonan::whereIn('status', ['completed', 'approved'])
                ->inUnit($unitId)
                ->whereBetween('request_date', [$startDate, $endDate])
                ->avg('bill_amounts') ?? 0,

            'totalTransactions' => Permohonan::whereBetween('request_date', [$startDate, $endDate])
                ->inUnit($unitId)
                ->count(),
            'total' => Permohonan::whereBetween('request_date', [$startDate, $endDate])
                ->inUnit($unitId)
                ->count(),

            // ✅ PERBAIKAN: Vehicle Expense dengan nama yang benar
            'vehicleExpense' => Permohonan::with('consumerialTool')
                ->inUnit($unitId)
                ->whereIn('status', ['completed', 'approved'])
                ->whereBetween('request_date', [$startDate, $endDate])
                ->get()
                ->groupBy('consumerial_tools_id')
                ->map(function ($group) {
                    $vehicle = $group->first()->consumerialTool;
                    if (! $vehicle) {
                        return null;
                    }

                    return [
                        'name' => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                        'total' => $group->sum('bill_amounts'),
                    ];
                })
                ->filter() // Remove null values
                ->sortByDesc('total')
                ->take(10)
                ->pluck('total', 'name')
                ->toArray(),

            // ✅ PERBAIKAN: Driver Expense dengan nama yang benar
            'driverExpense' => Permohonan::with('requester')
                ->inUnit($unitId)
                ->whereIn('status', ['completed', 'approved'])
                ->whereBetween('request_date', [$startDate, $endDate])
                ->get()
                ->groupBy('requester_id')
                ->map(function ($group) {
                    $driver = $group->first()->requester;
                    if (! $driver) {
                        return null;
                    }

                    return [
                        'name' => $driver->nama_lengkap,
                        'total' => $group->sum('bill_amounts'),
                    ];
                })
                ->filter() // Remove null values
                ->sortByDesc('total')
                ->take(10)
                ->pluck('total', 'name')
                ->toArray(),

            // Expense over time
            'expenseOverTime' => $this->getExpenseOverTime($period, $unitId),

            // Gasoline distribution
            'gasolineExpenseDistribution' => Permohonan::whereIn('status', ['completed', 'approved'])
                ->inUnit($unitId)
                ->whereBetween('request_date', [$startDate, $endDate])
                ->selectRaw('gasoline_type, SUM(bill_amounts) as total')
                ->groupBy('gasoline_type')
                ->pluck('total', 'gasoline_type')
                ->toArray(),
        ];

        return $stats;
    }

    /**
     * Calculate average request frequency based on period.
     * DB-agnostic (MySQL & SQLite) — grouping dilakukan di PHP.
     */
    private function calculateAvgRequestFrequency($period = 'month', ?int $unitId = null)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'day':
                // Average per hour in current day
                $requests = Permohonan::whereDate('request_date', $now->toDateString())
                    ->inUnit($unitId)
                    ->get(['request_date'])
                    ->groupBy(fn ($t) => $t->request_date->format('H'));
                $avgPerPeriod = $requests->avg(fn ($g) => $g->count()) ?? 0;
                $periodLabel = 'per jam';
                break;

            case 'year':
                // Average per month in current year
                $requests = Permohonan::whereYear('request_date', $now->year)
                    ->inUnit($unitId)
                    ->get(['request_date'])
                    ->groupBy(fn ($t) => $t->request_date->format('n'));
                $avgPerPeriod = $requests->avg(fn ($g) => $g->count()) ?? 0;
                $periodLabel = 'per bulan';
                break;

            case 'month':
            default:
                // Average per day in current month
                $requests = Permohonan::whereYear('request_date', $now->year)
                    ->whereMonth('request_date', $now->month)
                    ->inUnit($unitId)
                    ->get(['request_date'])
                    ->groupBy(fn ($t) => $t->request_date->toDateString());
                $avgPerPeriod = $requests->avg(fn ($g) => $g->count()) ?? 0;
                $periodLabel = 'per hari';
                break;
        }

        return [
            'value' => round($avgPerPeriod, 1),
            'label' => $periodLabel,
        ];
    }

    private function getDriverStatisticsData(?int $unitId = null)
    {
        $stats = Permohonan::with(['requester', 'transactionProof'])
            ->inUnit($unitId)
            ->get()
            ->groupBy('requester_id')
            ->map(function ($requests, $requesterId) {
                $requester = $requests->first()->requester;
                $nominals = $requests->pluck('bill_amounts')->filter()->toArray();

                return [
                    'driver' => $requester->nama_lengkap ?? 'Unknown',
                    'total' => $requests->count(),
                    'rejected' => $requests->where('status', 'rejected')->count(),
                    'completed' => $requests->where('status', 'completed')->count(),
                    'approved' => $requests->where('status', 'approved')->count(),
                    'pending' => $requests->where('status', 'pending')->count(),
                    'reportRate' => $requests->count() > 0
                        ? round(($requests->where('status', 'completed')->count() / $requests->count()) * 100, 1)
                        : 0,
                    'avgNominal' => ! empty($nominals) ? array_sum($nominals) / count($nominals) : 0,
                    'maxNominal' => ! empty($nominals) ? max($nominals) : 0,
                    'minNominal' => ! empty($nominals) ? min($nominals) : 0,
                ];
            })
            ->values()
            ->sortByDesc('total');

        return $stats;
    }

    private function getVehicleFuelConsumptionData(?Request $request = null)
    {
        $query = Transaction_Proofs::with(['permohonan.consumerialTool', 'permohonan.requester'])
            ->whereHas('permohonan', function ($q) {
                $q->where('status', 'completed');
            });

        $proofs = $query->orderBy('purchase_datetime', 'desc')->get();

        $result = $proofs->map(function ($proof) {
            $permohonan = $proof->permohonan;
            $vehicle = $permohonan->consumerialTool;

            return [
                'requestNumber' => $permohonan->request_number,
                'vehicleId' => $vehicle->id ?? '',
                'vehicleDisplay' => $vehicle
                    ? "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}"
                    : '',
                'platNumber' => $vehicle->consumerial_code ?? '',
                'merk' => $vehicle->consumerial_name ?? '',
                'jenis' => $vehicle->consumerial_type ?? '',
                'driverName' => $permohonan->requester->nama_lengkap ?? 'Unknown',
                'gasolineType' => $permohonan->gasoline_type,
                'volume' => $proof->fuel_volume,
                'nominal' => $permohonan->bill_amounts,
                'tanggalPengisian' => $proof->purchase_datetime,
                'requestAt' => $permohonan->request_date,
            ];
        });

        return $result;
    }

    private function getVehicleKmPerformanceData(?int $unitId = null)
    {
        $query = Transaction_Proofs::with(['permohonan.consumerialTool'])
            ->inUnit($unitId)
            ->whereHas('permohonan.consumerialTool', function ($q) {
                $q->where('consumerial_type', 'KENDARAAN');
            })
            ->where('km_terakhir', '>', 0)
            ->orderBy('purchase_datetime', 'asc');

        $proofs = $query->get();

        // Group by vehicle
        $vehicleKmData = [];

        foreach ($proofs as $proof) {
            $vehicleId = $proof->permohonan->consumerial_tools_id;

            if (! isset($vehicleKmData[$vehicleId])) {
                $vehicleKmData[$vehicleId] = [];
            }

            $vehicleKmData[$vehicleId][] = [
                'km' => $proof->km_terakhir,
                'date' => $proof->purchase_datetime,
            ];
        }

        // Calculate KM differences
        $result = [];

        foreach ($vehicleKmData as $vehicleId => $records) {
            if (count($records) < 2) {
                continue;
            }

            // Sort by date
            usort($records, function ($a, $b) {
                return $a['date']->timestamp - $b['date']->timestamp;
            });

            $kmDifferences = [];

            for ($i = 1; $i < count($records); $i++) {
                $diff = $records[$i]['km'] - $records[$i - 1]['km'];

                // Sanity check positive difference and less than 10k km
                if ($diff > 0 && $diff < 10000) {
                    $kmDifferences[] = $diff;
                }
            }

            if (empty($kmDifferences)) {
                continue;
            }

            $vehicle = VehicleAndToolsConsumers::find($vehicleId);
            if (! $vehicle) {
                continue;
            }

            $avgKmDiff = array_sum($kmDifferences) / count($kmDifferences);

            $result[] = [
                'vehicleId' => $vehicleId,
                'vehicleDisplay' => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                'platNumber' => $vehicle->consumerial_code,
                'merk' => $vehicle->consumerial_name,
                'avgKmDiff' => round($avgKmDiff),
                'totalTrips' => count($kmDifferences),
                'maxKm' => max($kmDifferences),
                'minKm' => min($kmDifferences),
                'lastUpdate' => $records[count($records) - 1]['date'],
                'latestOdo' => $records[count($records) - 1]['km'],
            ];
        }

        // Sort by avgKmDiff descending
        usort($result, function ($a, $b) {
            return $b['avgKmDiff'] - $a['avgKmDiff'];
        });

        return collect($result);
    }

    private function getCompletePivotTableData(?int $unitId = null)
    {
        $query = Permohonan::with([
            'requester',
            'authorizer',
            'consumerialTool',
            'transactionProof',
            'approvedRequest',
        ])->whereIn('status', ['approved', 'completed'])
            ->inUnit($unitId);

        $permohonans = $query->orderBy('request_date', 'desc')->get();

        $result = $permohonans->map(function ($permohonan) {
            $vehicle = $permohonan->consumerialTool;
            $proof = $permohonan->transactionProof;
            $approved = $permohonan->approvedRequest;

            return [
                'requestId' => $permohonan->id,
                'requestNumber' => $permohonan->request_number,
                'driverName' => $permohonan->requester->nama_lengkap ?? 'Unknown',
                'vehicleDisplay' => $vehicle
                    ? "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}"
                    : '',
                'platNumber' => $vehicle->consumerial_code ?? '',
                'merk' => $vehicle->consumerial_name ?? '',
                'jenis' => $vehicle->consumerial_type ?? '',
                'requestAt' => $permohonan->request_date,
                'nominal' => $permohonan->bill_amounts,
                'gasolineType' => $permohonan->gasoline_type,
                'status' => $permohonan->status,
                'voucherCode' => $approved->voucher_code ?? '',
                'proofId' => $proof->transaction_id ?? '',
                'tanggalPengisian' => $proof ? $proof->purchase_datetime : null,
                'volumePengisian' => $proof->fuel_volume ?? 0,
                'kmTerakhir' => $proof->km_terakhir ?? 0,
                'tanggalLaporan' => $proof ? $proof->created_at : null,
            ];
        });

        return $result;
    }

    // ==================== VEHICLE DETAIL (DOKUMENTASI KENDARAAN) ====================

    public function showVehicleDetail($id)
    {
        $vehicle = VehicleAndToolsConsumers::with('detail.site', 'detail.site.unit', 'unit', 'creator', 'approver')->findOrFail($id);

        return view('superadmin.vehicles.detail', compact('vehicle'));
    }

    public function editVehicleDetail($id)
    {
        $vehicle = VehicleAndToolsConsumers::with('detail')->findOrFail($id);
        $detail = $vehicle->detail ?? new VehicleDetail;
        $sites = Site::active()->with('unit')->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        return view('superadmin.vehicles.detail-form', compact('vehicle', 'detail', 'sites', 'units'));
    }

    public function updateVehicleDetail(Request $request, $id)
    {
        $vehicle = VehicleAndToolsConsumers::findOrFail($id);

        $validated = $request->validate([
            'unit_id' => 'nullable|exists:units,id',
            'site_id' => 'nullable|exists:sites,id',
            'jenis_kendaraan' => 'nullable|string|max:100',
            'merk' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'nomor_rangka' => 'nullable|string|max:100',
            'nomor_mesin' => 'nullable|string|max:100',
            'nomor_bpkb' => 'nullable|string|max:100',
            'nomor_stnk' => 'nullable|string|max:100',
            'nomor_kir' => 'nullable|string|max:100',
            'pajak_berlaku_sampai' => 'nullable|date',
            'kir_berlaku_sampai' => 'nullable|date',
            'kapasitas_mesin' => 'nullable|integer|min:0|max:100000',
            'odometer' => 'nullable|integer|min:0|max:10000000',
            'status_kepemilikan' => 'nullable|in:'.implode(',', array_keys(VehicleDetail::statusKepemilikanOptions())),
            'warna' => 'nullable|string|max:50',
            'kapasitas_penumpang' => 'nullable|integer|min:1|max:100',
            'kapasitas_bahan_bakar' => 'nullable|numeric|min:0|max:99999',
            'jenis_bahan_bakar' => 'nullable|string|max:50',
            'volume_tangki' => 'nullable|numeric|min:0|max:99999',
            'nominal_full_tank' => 'nullable|numeric|min:0',
            'nominal_spd_default' => 'nullable|numeric|min:0',
            'kapasitas_bbm_tahunan' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:2000',
        ], [
            'unit_id.exists' => 'Unit bisnis yang dipilih tidak valid',
            'status_kepemilikan.in' => 'Status kepemilikan tidak dikenal',
        ]);

        $unitId = $this->resolveVehicleUnitId(
            $validated,
            $vehicle->unit_id ? (int) $vehicle->unit_id : null
        );

        if ($mismatch = $this->siteUnitMismatchMessage($validated['site_id'] ?? null, $unitId)) {
            return redirect()->back()->withInput()->with('error', $mismatch);
        }

        try {
            DB::beginTransaction();

            $detail = VehicleDetail::updateOrCreate(
                ['vehicle_and_tools_consumer_id' => $vehicle->id],
                $this->vehicleDetailPayload($validated)
            );

            // Unit pemilik ikut disinkronkan dari form detail.
            if ($unitId && (int) $vehicle->unit_id !== (int) $unitId) {
                $vehicle->update(['unit_id' => $unitId]);
            }

            if ($request->hasFile('foto')) {
                $request->validate(['foto' => 'image|mimes:jpeg,png,jpg|max:2048']);
                $path = $request->file('foto')->store('vehicle-photos', 'public');
                $detail->foto = $path;
                $detail->save();
            }

            DB::commit();

            return redirect()->route('superadmin.vehicle.detail', $vehicle->id)
                ->with('success', 'Detail kendaraan berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menyimpan detail. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Tampilkan dashboard approval kendaraan rental & permohonan
     */
    public function approvalDashboard()
    {
        $title = 'Dashboard Approval Superadmin';

        // Get pending vehicles (rental yang belum diapprove)
        $pendingVehicles = VehicleAndToolsConsumers::with(['creator', 'permohonans'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get permohonan yang terkait dengan kendaraan pending
        $pendingVehicleRequests = Permohonan::with(['requester', 'vehicle'])
            ->where('status', 'pending_vehicle_approval')
            ->orderBy('request_date', 'desc')
            ->get();

        return view('superadmin.partials.approval', compact(
            'title',
            'pendingVehicles',
            'pendingVehicleRequests'
        ));
    }

    /**
     * Approve kendaraan rental
     */
    public function approveVehicle(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $vehicle = VehicleAndToolsConsumers::findOrFail($id);

            if ($vehicle->is_approved) {
                return back()->with('error', 'Kendaraan sudah disetujui sebelumnya.');
            }

            // Update vehicle status
            $vehicle->update([
                'is_approved' => true,
                'approved_by' => Auth::user()->id,
            ]);

            // Update semua permohonan terkait dari pending_vehicle_approval ke pending
            Permohonan::where('consumerial_tools_id', $vehicle->id)
                ->where('status', 'pending_vehicle_approval')
                ->update(['status' => 'pending']);

            DB::commit();

            return redirect()
                ->route('superadmin.approval')
                ->with('success', "Kendaraan {$vehicle->consumerial_code} berhasil disetujui. Permohonan terkait dapat diproses Admin.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menyetujui kendaraan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Reject kendaraan rental
     */
    public function rejectVehicle(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $vehicle = VehicleAndToolsConsumers::findOrFail($id);

            if ($vehicle->is_approved) {
                return back()->with('error', 'Kendaraan sudah disetujui, tidak dapat ditolak.');
            }

            // ❌ Reject semua permohonan terkait
            $relatedRequests = Permohonan::where('consumerial_tools_id', $vehicle->id)
                ->where('status', 'pending_vehicle_approval')
                ->get();

            foreach ($relatedRequests as $req) {
                $req->update([
                    'status' => 'rejected',
                    'authorizer_id' => Auth::id(),
                    'authorization_date' => now(),
                    'authorizer_notes' => 'Kendaraan rental ditolak: '.($validated['rejection_notes'] ?? 'Tidak ada catatan'),
                ]);
            }

            // Soft delete kendaraan
            $vehicle->delete();

            DB::commit();

            return redirect()
                ->route('superadmin.approval')
                ->with('success', "Kendaraan {$vehicle->consumerial_code} ditolak. ".count($relatedRequests).' permohonan terkait otomatis ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menolak kendaraan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // ==================== DRIVER STATISTICS ====================

    public function getDriverStatistics()
    {
        try {
            $stats = Permohonan::with(['requester', 'transactionProof'])
                ->get()
                ->groupBy('requester_id')
                ->map(function ($requests, $requesterId) {
                    $requester = $requests->first()->requester;
                    $nominals = $requests->pluck('bill_amounts')->filter()->toArray();

                    return [
                        'driver' => $requester->nama_lengkap ?? 'Unknown',
                        'total' => $requests->count(),
                        'rejected' => $requests->where('status', 'rejected')->count(),
                        'completed' => $requests->where('status', 'completed')->count(),
                        'approved' => $requests->where('status', 'approved')->count(),
                        'pending' => $requests->where('status', 'pending')->count(),
                        'reportRate' => $requests->count() > 0
                            ? round(($requests->where('status', 'completed')->count() / $requests->count()) * 100, 1)
                            : 0,
                        'avgNominal' => ! empty($nominals) ? array_sum($nominals) / count($nominals) : 0,
                        'maxNominal' => ! empty($nominals) ? max($nominals) : 0,
                        'minNominal' => ! empty($nominals) ? min($nominals) : 0,
                    ];
                })
                ->values()
                ->sortByDesc('total');

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat statistik driver. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    // ==================== VEHICLE FUEL CONSUMPTION ====================

    public function getVehicleFuelConsumption(Request $request)
    {
        try {
            $query = Transaction_Proofs::with(['permohonan.consumerialTool', 'permohonan.requester'])
                ->whereHas('permohonan', function ($q) {
                    $q->where('status', 'completed');
                });

            // Apply filters
            if ($request->has('start_date')) {
                $query->whereDate('purchase_datetime', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('purchase_datetime', '<=', $request->end_date);
            }

            if ($request->has('min_volume')) {
                $query->where('fuel_volume', '>=', $request->min_volume);
            }

            if ($request->has('max_volume')) {
                $query->where('fuel_volume', '<=', $request->max_volume);
            }

            if ($request->has('min_nominal')) {
                $query->whereHas('permohonan', function ($q) use ($request) {
                    $q->where('bill_amounts', '>=', $request->min_nominal);
                });
            }

            $proofs = $query->orderBy('purchase_datetime', 'desc')->get();

            $result = $proofs->map(function ($proof) {
                $permohonan = $proof->permohonan;
                $vehicle = $permohonan->consumerialTool;

                return [
                    'requestNumber' => $permohonan->request_number,
                    'vehicleId' => $vehicle->id ?? '',
                    'vehicleDisplay' => $vehicle
                        ? "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}"
                        : '',
                    'platNumber' => $vehicle->consumerial_code ?? '',
                    'merk' => $vehicle->consumerial_name ?? '',
                    'jenis' => $vehicle->consumerial_type ?? '',
                    'driverName' => $permohonan->requester->nama_lengkap ?? 'Unknown',
                    'gasolineType' => $permohonan->gasoline_type,
                    'volume' => $proof->fuel_volume,
                    'nominal' => $permohonan->bill_amounts,
                    'tanggalPengisian' => $proof->purchase_datetime->toISOString(),
                    'requestAt' => $permohonan->request_date->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat konsumsi BBM. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    // ==================== VEHICLE KM PERFORMANCE ====================

    public function getVehicleKmPerformance(Request $request)
    {
        try {
            $query = Transaction_Proofs::with(['permohonan.consumerialTool'])
                ->whereHas('permohonan.consumerialTool', function ($q) {
                    $q->where('consumerial_type', 'Kendaraan');
                })
                ->where('km_terakhir', '>', 0)
                ->orderBy('purchase_datetime', 'asc');

            // Apply date filters
            if ($request->has('start_date')) {
                $query->whereDate('purchase_datetime', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('purchase_datetime', '<=', $request->end_date);
            }

            $proofs = $query->get();

            // Group by vehicle
            $vehicleKmData = [];

            foreach ($proofs as $proof) {
                $vehicleId = $proof->permohonan->consumerial_tools_id;

                if (! isset($vehicleKmData[$vehicleId])) {
                    $vehicleKmData[$vehicleId] = [];
                }

                $vehicleKmData[$vehicleId][] = [
                    'km' => $proof->km_terakhir,
                    'date' => $proof->purchase_datetime,
                ];
            }

            // Calculate KM differences
            $result = [];

            foreach ($vehicleKmData as $vehicleId => $records) {
                if (count($records) < 2) {
                    continue;
                }

                // Sort by date
                usort($records, function ($a, $b) {
                    return $a['date']->timestamp - $b['date']->timestamp;
                });

                $kmDifferences = [];

                for ($i = 1; $i < count($records); $i++) {
                    $diff = $records[$i]['km'] - $records[$i - 1]['km'];

                    // Sanity check: positive difference and less than 10k km
                    if ($diff > 0 && $diff < 10000) {
                        $kmDifferences[] = $diff;
                    }
                }

                if (empty($kmDifferences)) {
                    continue;
                }

                $vehicle = VehicleAndToolsConsumers::find($vehicleId);
                if (! $vehicle) {
                    continue;
                }

                $avgKmDiff = array_sum($kmDifferences) / count($kmDifferences);

                $result[] = [
                    'vehicleId' => $vehicleId,
                    'vehicleDisplay' => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                    'platNumber' => $vehicle->consumerial_code,
                    'merk' => $vehicle->consumerial_name,
                    'avgKmDiff' => round($avgKmDiff),
                    'totalTrips' => count($kmDifferences),
                    'maxKm' => max($kmDifferences),
                    'minKm' => min($kmDifferences),
                    'lastUpdate' => $records[count($records) - 1]['date']->toISOString(),
                    'latestOdo' => $records[count($records) - 1]['km'],
                ];
            }

            // Sort by avgKmDiff descending
            usort($result, function ($a, $b) {
                return $b['avgKmDiff'] - $a['avgKmDiff'];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat performa KM. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    // ==================== COMPLETE PIVOT TABLE ====================

    public function getCompletePivotTable(Request $request)
    {
        try {
            $query = Permohonan::with([
                'requester',
                'authorizer',
                'consumerialTool',
                'transactionProof',
                'approvedRequest',
            ]);

            // Apply status filter (default: approved and completed)
            if ($request->has('status_filter')) {
                $statuses = $request->status_filter;
                if (is_string($statuses)) {
                    $statuses = explode(',', $statuses);
                }
                $query->whereIn('status', $statuses);
            } else {
                $query->whereIn('status', ['approved', 'completed']);
            }

            // Apply date filters
            if ($request->has('start_date')) {
                $query->whereDate('request_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('request_date', '<=', $request->end_date);
            }

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                        ->orWhereHas('requester', function ($q2) use ($search) {
                            $q2->where('nama_lengkap', 'like', "%{$search}%");
                        })
                        ->orWhereHas('consumerialTool', function ($q2) use ($search) {
                            $q2->where('consumerial_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('transactionProof', function ($q2) use ($search) {
                            $q2->where('transaction_id', 'like', "%{$search}%");
                        });
                });
            }

            $permohonans = $query->orderBy('request_date', 'desc')->get();

            $result = $permohonans->map(function ($permohonan) {
                $vehicle = $permohonan->consumerialTool;
                $proof = $permohonan->transactionProof;
                $approved = $permohonan->approvedRequest;

                return [
                    'requestId' => $permohonan->id,
                    'requestNumber' => $permohonan->request_number,
                    'driverName' => $permohonan->requester->nama_lengkap ?? 'Unknown',
                    'vehicleDisplay' => $vehicle
                        ? "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}"
                        : '',
                    'platNumber' => $vehicle->consumerial_code ?? '',
                    'merk' => $vehicle->consumerial_name ?? '',
                    'jenis' => $vehicle->consumerial_type ?? '',
                    'requestAt' => $permohonan->request_date->toISOString(),
                    'nominal' => $permohonan->bill_amounts,
                    'gasolineType' => $permohonan->gasoline_type,
                    'status' => $permohonan->status,
                    'voucherCode' => $approved->voucher_code ?? '',
                    'proofId' => $proof->transaction_id ?? '',
                    'tanggalPengisian' => $proof
                        ? $proof->purchase_datetime->toISOString()
                        : '',
                    'volumePengisian' => $proof->fuel_volume ?? 0,
                    'kmTerakhir' => $proof->km_terakhir ?? 0,
                    'tanggalLaporan' => $proof
                        ? $proof->created_at->toISOString()
                        : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat tabel pivot. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    public function getDetailedDriverStats()
    {
        try {
            $requests = Permohonan::with(['requester', 'transactionProof'])
                ->get();

            $driverStats = [];

            foreach ($requests as $req) {
                $driverName = $req->requester->fullname ?? 'Unknown';

                if (! isset($driverStats[$driverName])) {
                    $driverStats[$driverName] = [
                        'total' => 0,
                        'rejected' => 0,
                        'completed' => 0,
                        'approved' => 0,
                        'pending' => 0,
                        'nominals' => [],
                    ];
                }

                $driverStats[$driverName]['total']++;
                $driverStats[$driverName][$req->status]++;

                if ($req->bill_amounts) {
                    $driverStats[$driverName]['nominals'][] = $req->bill_amounts;
                }
            }

            $result = [];
            foreach ($driverStats as $driver => $stats) {
                $reportRate = $stats['total'] > 0
                    ? round(($stats['completed'] / $stats['total']) * 100, 1)
                    : 0;

                $avgNominal = ! empty($stats['nominals'])
                    ? array_sum($stats['nominals']) / count($stats['nominals'])
                    : 0;

                $result[] = [
                    'driver' => $driver,
                    'total' => $stats['total'],
                    'rejected' => $stats['rejected'],
                    'completed' => $stats['completed'],
                    'approved' => $stats['approved'],
                    'pending' => $stats['pending'],
                    'reportRate' => $reportRate,
                    'avgNominal' => $avgNominal,
                    'maxNominal' => ! empty($stats['nominals']) ? max($stats['nominals']) : 0,
                    'minNominal' => ! empty($stats['nominals']) ? min($stats['nominals']) : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal memuat statistik driver. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    // ==================== USER MANAGEMENT ====================

    public function createUser(Request $request)
    {
        $scope = UnitScope::for($request->user());

        $title = 'Tambah User Baru';
        $roles = Role::where('is_active', true)
            ->when(! $scope->isGlobal(), fn ($q) => $q->availableFor($request->user()?->unit_id))
            ->with('permissions')
            ->orderBy('name')
            ->get();
        $sites = $scope->sites();
        $units = $scope->units();

        // Master Pekerja aktif yang belum punya akun (untuk mode Aktivasi)
        $employees = EmployeeDefault::active()
            ->inUnit($scope->siteIds())
            ->whereNull('user_id')
            ->with('defaultSite')
            ->orderBy('employee_name')
            ->get();

        return view('superadmin.users.create', compact('title', 'roles', 'sites', 'units', 'employees'));
    }

    public function indexUsers(Request $request)
    {
        $title = 'Kelola User';

        $scope = UnitScope::for($request->user());

        // Get filters
        $role = $request->get('role');
        $search = $request->get('search');
        $tab = $request->get('tab', 'list'); // list, add, deleted

        // Query users — admin unit hanya melihat user unitnya.
        $query = User::query()
            ->when(! $scope->isGlobal(), fn ($q) => $q->where('unit_id', $request->user()?->unit_id));

        if ($role) {
            $query->whereHas('roles', fn ($q) => $q->where('code', $role));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['roles', 'employeeDefault', 'defaultSite', 'unit'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get deleted users
        $deletedUsers = User::onlyTrashed()
            ->when(! $scope->isGlobal(), fn ($q) => $q->where('unit_id', $request->user()?->unit_id))
            ->with('roles')
            ->orderBy('deleted_at', 'desc')
            ->paginate(15, ['*'], 'deleted_page');

        $roles = Role::where('is_active', true)
            ->when(! $scope->isGlobal(), fn ($q) => $q->availableFor($request->user()?->unit_id))
            ->orderBy('name')
            ->get();

        $sites = $scope->sites();
        $units = $scope->units();

        return view('superadmin.partials.kelola-user', compact(
            'title',
            'users',
            'deletedUsers',
            'tab',
            'role',
            'search',
            'roles',
            'sites',
            'units'
        ));
    }

    public function storeUser(Request $request)
    {
        $mode = $request->get('mode', 'manual'); // manual | activate

        $rules = [
            'username' => 'required|unique:users,username|max:255',
            'password' => 'required|min:6',
            'email' => 'required|email|unique:users,email',
            'role_code' => 'required|exists:roles,code',
        ];

        if ($mode === 'activate') {
            $rules['employee_default_id'] = 'required|exists:employee_defaults,id';
        } else {
            $rules['nama_lengkap'] = 'required|max:255';
            $rules['jabatan'] = 'nullable|max:255';
            $rules['default_site_id'] = 'nullable|exists:sites,id';
            $rules['unit_id'] = 'nullable|exists:units,id';
            $rules['create_employee'] = 'nullable|boolean';
        }

        $validated = $request->validate($rules, [
            'username.required' => 'Username harus diisi',
            'username.unique' => 'Username sudah terdaftar',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 6 karakter',
            'nama_lengkap.required' => 'Nama lengkap harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'role_code.required' => 'Role harus dipilih',
            'role_code.exists' => 'Role tidak valid',
            'employee_default_id.required' => 'Pilih pekerja dari Master Pekerja',
            'employee_default_id.exists' => 'Pekerja tidak valid',
        ]);

        $role = Role::where('code', $validated['role_code'])->firstOrFail();

        $roleCodeMap = [
            'superadmin' => 'S00',
            'general_affairs' => 'A01',
            'requester' => 'R02',
            'manager' => 'M03',
            'dispatcher' => 'D04',
            'view_only' => 'N05',
        ];

        $rolePrefix = $roleCodeMap[$role->code] ?? 'X99';

        // Resolve data sesuai mode
        $employee = null;
        $unitId = $validated['unit_id'] ?? null;
        if ($mode === 'activate') {
            $employee = EmployeeDefault::with(['defaultSite', 'defaultSite.unit'])->findOrFail($validated['employee_default_id']);

            // Guard anti double-activation
            if ($employee->user_id) {
                return redirect()->back()->withInput()
                    ->with('error', 'Pekerja ini sudah memiliki akun (teraktivasi).');
            }

            $namaLengkap = $employee->employee_name;
            $jabatan = $employee->position;
            $defaultSiteId = $employee->default_site_id;
            $locationCode = $employee->defaultSite?->code ?? 'PTH';
            $unitId = $employee->defaultSite?->unit_id ?? $unitId;
        } else {
            $namaLengkap = $validated['nama_lengkap'];
            $jabatan = $validated['jabatan'] ?? null;
            $defaultSiteId = $validated['default_site_id'] ?? null;
            // Fallback site by role bila tidak dipilih
            if (! $defaultSiteId) {
                $isPusat = in_array($role->code, ['superadmin', 'general_affairs']);
                $locationCode = $isPusat ? 'PMU' : 'PTH';
                $defaultSiteId = Site::where('code', $isPusat ? 'PMU' : 'Patuha')->value('id');
            } else {
                $locationCode = Site::where('id', $defaultSiteId)->value('code') ?? 'PTH';
            }
        }

        // Unit scope — admin unit tidak boleh membuat user di unit lain.
        $scope = UnitScope::for($request->user());

        if ($mode === 'activate' && $employee) {
            $scope->ensureEmployee($employee);
        }

        if (! $scope->isGlobal()) {
            $unitId = $request->user()?->unit_id;

            // Untuk admin unit, fallback site berbasis role di atas bisa
            // menunjuk unit lain. Paksa site default ke site milik unitnya.
            if (! $defaultSiteId || ! $scope->allowsSite($defaultSiteId)) {
                $defaultSiteId = $request->user()?->default_site_id
                    ?: $scope->sites()->first()?->id;
            }

            if ($defaultSiteId) {
                $scope->ensureSite($defaultSiteId);
                $locationCode = Site::whereKey($defaultSiteId)->value('code') ?? $locationCode;
            }
        }

        if ($unitId) {
            $scope->ensureUnit($unitId);
        }

        if ($defaultSiteId) {
            $scope->ensureSite($defaultSiteId);
        }

        // Role developers bersifat global — hanya aktor global yang boleh
        // memberikannya, agar admin unit tidak bisa menaikkan akses ke seluruh unit.
        if ($role->code === 'developers' && ! $scope->isGlobal()) {
            return redirect()->back()->withInput()
                ->with('error', 'Role developers hanya dapat diberikan oleh superadmin Pusat / developer.');
        }

        // Item 4 — segmentasi role per unit: role yang dimiliki sebuah unit
        // hanya boleh diberikan kepada user di unit tersebut. Diperiksa
        // SEBELUM transaksi supaya tidak ada perubahan setengah jalan.
        if (! $role->isAssignableTo($unitId)) {
            return redirect()->back()->withInput()
                ->with('error', "Role \"{$role->name}\" khusus untuk unit lain dan tidak dapat diberikan kepada user di unit ini.");
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'nama_lengkap' => $namaLengkap,
                'email' => $validated['email'],
                'role' => $role->code,
                'jabatan' => $jabatan,
                'default_site_id' => $defaultSiteId,
                'unit_id' => $unitId,
            ]);

            $user->user_code = $rolePrefix.'.'.str_pad($user->id, 4, '0', STR_PAD_LEFT).'.'.$locationCode;
            $user->save();

            // Simpan salinan terenkripsi untuk dibagikan offline oleh admin.
            $user->setOperationalCredentials($validated['password'], $request->user());

            // Satu user = satu role RBAC (source of truth permission).
            // Lewat assignRole() agar aturan segmentasi unit tetap ditegakkan
            // dan cache otorisasi ikut dibersihkan.
            $user->assignRole($role);

            // Mode Aktivasi: link master pekerja → akun
            if ($employee) {
                $employee->user_id = $user->id;
                $employee->save();
            } elseif ($mode === 'manual' && $request->boolean('create_employee')) {
                // Mode Manual opsional: daftarkan juga ke Master Pekerja
                EmployeeDefault::create([
                    'user_id' => $user->id,
                    'employee_name' => $namaLengkap,
                    'position' => $jabatan,
                    'default_site_id' => $defaultSiteId,
                    'unit_id' => $unitId,
                    'verification_status' => 'verified',
                    'source' => 'admin',
                    'created_by' => $request->user()?->id,
                    'is_active' => true,
                ]);
            }

            DB::commit();

            $msg = $employee
                ? "Pekerja {$user->nama_lengkap} berhasil diaktivasi dengan kode: {$user->user_code}"
                : "User {$user->nama_lengkap} berhasil ditambahkan dengan kode: {$user->user_code}";

            return redirect()
                ->route('users', ['tab' => 'list'])
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', safe_exception_message($e, 'Gagal menambah user. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $scope = UnitScope::for($request->user());

        // Admin unit hanya boleh mengubah user di unitnya.
        if (! $scope->isGlobal() && (int) $user->unit_id !== (int) $request->user()?->unit_id) {
            abort(403, 'User ini berada di luar unit Anda.');
        }

        $validated = $request->validate([
            'username' => 'required|max:255|unique:users,username,'.$id,
            'nama_lengkap' => 'required|max:255',
            'email' => 'nullable|email|unique:users,email,'.$id,
            'role_code' => 'required|exists:roles,code',
            'jabatan' => 'nullable|max:255',
            'default_site_id' => 'nullable|exists:sites,id',
            'unit_id' => 'nullable|exists:units,id',
            'password' => 'nullable|min:6',
            'is_active' => 'nullable|boolean',
        ], [
            'username.required' => 'Username harus diisi',
            'username.unique' => 'Username sudah terdaftar',
            'nama_lengkap.required' => 'Nama lengkap harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'role_code.required' => 'Role harus dipilih',
            'role_code.exists' => 'Role tidak valid',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        $role = Role::where('code', $validated['role_code'])->firstOrFail();
        $newUnitId = $validated['unit_id'] ?? null;

        if (! $scope->isGlobal()) {
            $newUnitId = $request->user()?->unit_id;
        }

        if ($newUnitId) {
            $scope->ensureUnit((int) $newUnitId);
        }

        if (! empty($validated['default_site_id'])) {
            $scope->ensureSite((int) $validated['default_site_id']);
        }

        if ($role->code === 'developers' && ! $scope->isGlobal()) {
            return redirect()->back()->withInput()
                ->with('error', 'Role developers hanya dapat diberikan oleh superadmin Pusat / developer.');
        }

        // Item 4 — tolak pemindahan user ke unit lain yang tidak memiliki
        // role tersebut, atau pemberian role milik unit lain. Diperiksa
        // sebelum menulis apa pun.
        if (! $role->isAssignableTo($newUnitId)) {
            return redirect()->back()->withInput()
                ->with('error', "Role \"{$role->name}\" khusus untuk unit lain dan tidak dapat diberikan kepada user di unit ini.");
        }

        try {
            DB::beginTransaction();

            $user->update([
                'username' => $validated['username'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'email' => $validated['email'] ?: null,
                'role' => $validated['role_code'],
                'jabatan' => $validated['jabatan'] ?? null,
                'default_site_id' => $validated['default_site_id'] ?? null,
                'unit_id' => $newUnitId,
                'is_active' => $request->boolean('is_active', $user->is_active),
            ]);

            // Ganti role (bukan menumpuk) supaya permission lama tidak tertinggal.
            $user->assignRole($role);

            if (! empty($validated['password'])) {
                $user->setOperationalCredentials($validated['password'], $request->user());
            }

            DB::commit();

            return redirect()
                ->route('users', ['tab' => 'list'])
                ->with('success', 'User berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal update user. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $scope = UnitScope::for(auth()->user());

            if (! $scope->isGlobal() && (int) $user->unit_id !== (int) auth()->user()?->unit_id) {
                abort(403, 'User ini berada di luar unit Anda.');
            }

            // Prevent deleting self
            if ($user->id === auth()->id()) {
                return redirect()
                    ->back()
                    ->with('error', 'Tidak dapat menghapus akun sendiri');
            }

            // Soft delete
            $user->delete();

            return redirect()
                ->route('users', ['tab' => 'list'])
                ->with('success', 'User berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal hapus user. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'new_password' => 'required|min:6|confirmed',
        ], [
            'new_password.required' => 'Password baru harus diisi',
            'new_password.min' => 'Password minimal 6 karakter',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        try {
            $user = User::findOrFail($id);
            $scope = UnitScope::for($request->user());

            if (! $scope->isGlobal() && (int) $user->unit_id !== (int) $request->user()?->unit_id) {
                abort(403, 'User ini berada di luar unit Anda.');
            }

            $user->setOperationalCredentials($validated['new_password'], $request->user());

            return redirect()
                ->route('users', ['tab' => 'list'])
                ->with('success', 'Password berhasil direset untuk user: '.$user->username);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal reset password. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Set / ganti kredensial operasional (username tetap, password baru).
     * Password disimpan sebagai hash untuk login + salinan terenkripsi
     * untuk dibagikan offline.
     */
    public function setCredentials(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => 'required|min:6',
        ], [
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        $user = User::findOrFail($id);
        $scope = UnitScope::for($request->user());

        if (! $scope->isGlobal() && (int) $user->unit_id !== (int) $request->user()?->unit_id) {
            abort(403, 'User ini berada di luar unit Anda.');
        }

        $user->setOperationalCredentials($validated['password'], $request->user());

        return back()->with('success', 'Kredensial user '.$user->username.' berhasil disimpan.');
    }

    /**
     * Buka kredensial operasional. Setiap pembukaan dicatat di audit trail.
     */
    public function revealCredentials(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $scope = UnitScope::for($request->user());

        if (! $scope->isGlobal() && (int) $user->unit_id !== (int) $request->user()?->unit_id) {
            abort(403, 'User ini berada di luar unit Anda.');
        }

        if (! $user->hasOperationalCredentials()) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial belum dibuat. Set password terlebih dahulu.',
            ], 422);
        }

        UserCredentialReveal::create([
            'user_id'     => $user->id,
            'revealed_by' => $request->user()?->id,
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 1000),
            'revealed_at' => now(),
        ]);

        $user->increment('credentials_reveal_count');
        $user->update(['credentials_last_revealed_at' => now()]);

        return response()->json([
            'success'  => true,
            'username' => $user->username,
            'password' => $user->credentials_plain,
            'reveals'  => $user->credentials_reveal_count,
        ]);
    }

    public function restoreUser($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $scope = UnitScope::for(auth()->user());

            if (! $scope->isGlobal() && (int) $user->unit_id !== (int) auth()->user()?->unit_id) {
                abort(403, 'User ini berada di luar unit Anda.');
            }

            if (! $user->trashed()) {
                return redirect()
                    ->back()
                    ->with('error', 'User tidak dalam status terhapus');
            }

            $user->restore();

            return redirect()
                ->route('users', ['tab' => 'deleted'])
                ->with('success', 'User berhasil dipulihkan');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal memulihkan user. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // ==================== VEHICLE MANAGEMENT ====================

    public function createVehicle()
    {
        $title = 'Tambah Kendaraan / Alat';
        $sites = Site::active()->with('unit')->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        // Unit awal yang disarankan: unit user (kalau ada), kalau tidak biarkan kosong.
        $defaultUnitId = auth()->user()?->unit_id;

        return view('superadmin.vehicles.create', compact('title', 'sites', 'units', 'defaultUnitId'));
    }

    public function editVehicle($id)
    {
        $title = 'Edit Kendaraan / Alat';
        $vehicle = VehicleAndToolsConsumers::with('detail.site')->findOrFail($id);
        $detail = $vehicle->detail ?? new VehicleDetail;
        $sites = Site::active()->with('unit')->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        // Harga BBM region unit kendaraan (untuk kalkulasi benchmark otomatis)
        $regionPrices = [];
        $slug = $detail->site?->province_slug ?: 'jawa-barat';
        foreach (['PERTAMAX', 'PERTAMINA DEX'] as $fuel) {
            $price = MasterGasoline::latestPrice($slug, $fuel);
            if ($price !== null) {
                $regionPrices[$fuel] = $price;
            }
        }

        return view('superadmin.vehicles.edit', compact('title', 'vehicle', 'detail', 'sites', 'units', 'regionPrices'));
    }

    public function indexVehicles(Request $request)
    {
        $title = 'Kelola Kendaraan & Alat';

        // Get filters
        $type = $request->get('type');
        $search = $request->get('search');
        $unitFilter = $request->get('unit_id');
        $tab = $request->get('tab', 'list'); // list, add, deleted

        // Query vehicles/tools - HANYA YANG SUDAH APPROVED
        $query = VehicleAndToolsConsumers::with(['creator', 'approver', 'barcode', 'detail.site', 'unit'])
            ->where('is_approved', true) // Hanya tampilkan yang approved
            ->inUnit(UnitScope::for($request->user())->unitId());

        // ✅ Filter berdasarkan tipe (uppercase)
        if ($type) {
            $query->where('consumerial_type', strtoupper($type));
        }

        // ✅ Filter per unit bisnis (agar tiap unit bisa melihat datanya sendiri)
        // Nilai "none" = kendaraan yang unitnya belum di-assign (tidak ter-view driver).
        if ($unitFilter === 'none') {
            $query->whereNull('unit_id');
        } elseif ($unitFilter) {
            $query->where('unit_id', $unitFilter);
        }

        // ✅ Search dengan uppercase
        if ($search) {
            $searchUpper = strtoupper($search);
            $query->where(function ($q) use ($searchUpper) {
                $q->where('consumerial_code', 'like', "%{$searchUpper}%")
                    ->orWhere('consumerial_name', 'like', "%{$searchUpper}%")
                    ->orWhere('consumerial_notes', 'like', "%{$searchUpper}%");
            });
        }

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Get deleted vehicles/tools
        $deletedVehicles = VehicleAndToolsConsumers::onlyTrashed()
            ->with(['creator', 'approver'])
            ->inUnit(UnitScope::for($request->user())->unitId())
            ->orderBy('deleted_at', 'desc')
            ->paginate(15, ['*'], 'deleted_page');

        $barcode_path = VehicleBarcode::whereIn('vehicle_and_tools_consumer_id', $vehicles->pluck('id'))
            ->get()->pluck('barcode_path', 'vehicle_and_tools_consumer_id');

        // Daftar unit untuk dropdown filter — user ber-unit hanya melihat unitnya.
        $units = UnitScope::for($request->user())->isScoped()
            ? Unit::whereKey($request->user()->unit_id)->orderBy('name')->get()
            : Unit::active()->orderBy('name')->get();

        $siteIds = $request->user()?->unitSiteIds();
        $sites = $siteIds !== null
            ? Site::active()->whereIn('id', $siteIds)->orderBy('name')->get()
            : Site::active()->orderBy('name')->get();

        return view('superadmin.partials.kelola-consumerialtools', compact(
            'title',
            'vehicles',
            'deletedVehicles',
            'barcode_path',
            'sites',
            'units',
            'tab',
            'type',
            'search',
            'unitFilter'
        ));
    }

    /**
     * Aturan validasi bersama form Kendaraan/Alat (create & edit).
     *
     * Meliputi identitas teknis, identitas legal (BPKB/STNK/KIR + masa berlaku),
     * kapasitas & budget BBM, serta unit pemilik kendaraan.
     */
    private function vehicleValidationRules(?int $ignoreId = null): array
    {
        return [
            'consumerial_type' => 'required|in:KENDARAAN,ALAT',
            'consumerial_code' => 'required|max:255|unique:vehicle_and_tools_consumers,consumerial_code'.($ignoreId ? ','.$ignoreId : ''),
            'consumerial_name' => 'required|max:255',
            'consumerial_notes' => 'nullable|string|max:500',

            // Unit pemilik + site operasional
            'unit_id' => 'nullable|exists:units,id',
            'site_id' => 'nullable|exists:sites,id',

            // Identitas teknis kendaraan
            'jenis_kendaraan' => 'nullable|string|max:100',
            'merk' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'nomor_rangka' => 'nullable|string|max:100',
            'nomor_mesin' => 'nullable|string|max:100',
            'warna' => 'nullable|string|max:50',
            'kapasitas_mesin' => 'nullable|integer|min:0|max:100000',
            'odometer' => 'nullable|integer|min:0|max:10000000',

            // Identitas legal / dokumen
            'nomor_bpkb' => 'nullable|string|max:100',
            'nomor_stnk' => 'nullable|string|max:100',
            'nomor_kir' => 'nullable|string|max:100',
            'pajak_berlaku_sampai' => 'nullable|date',
            'kir_berlaku_sampai' => 'nullable|date',
            'status_kepemilikan' => 'nullable|in:'.implode(',', array_keys(VehicleDetail::statusKepemilikanOptions())),

            // Kapasitas & budget BBM
            'kapasitas_penumpang' => 'nullable|integer|min:1|max:100',
            'kapasitas_bahan_bakar' => 'nullable|numeric|min:0|max:99999',
            'volume_tangki' => 'nullable|numeric|min:0|max:99999',
            'jenis_bahan_bakar' => 'nullable|string|max:50',
            'nominal_full_tank' => 'nullable|numeric|min:0',
            'nominal_spd_default' => 'nullable|numeric|min:0',
            'kapasitas_bbm_tahunan' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Pesan validasi khusus form Kendaraan/Alat.
     */
    private function vehicleValidationMessages(): array
    {
        return [
            'consumerial_type.required' => 'Tipe konsumsi harus diisi',
            'consumerial_type.in' => 'Tipe konsumsi harus Kendaraan atau Alat',
            'consumerial_code.required' => 'Kode harus diisi',
            'consumerial_code.unique' => 'Kode sudah terdaftar',
            'consumerial_name.required' => 'Nama harus diisi',
            'unit_id.exists' => 'Unit bisnis yang dipilih tidak valid',
            'site_id.exists' => 'Site yang dipilih tidak valid',
            'status_kepemilikan.in' => 'Status kepemilikan tidak dikenal',
        ];
    }

    /**
     * Tentukan unit pemilik kendaraan.
     *
     * Urutan prioritas: unit yang dipilih di form -> unit site terpilih ->
     * unit lama kendaraan (saat edit) -> unit user yang sedang login.
     */
    private function resolveVehicleUnitId(array $validated, ?int $fallbackUnitId = null): ?int
    {
        $unitId = $validated['unit_id'] ?? null;

        if (! $unitId && ! empty($validated['site_id'])) {
            $unitId = Site::whereKey($validated['site_id'])->value('unit_id');
        }

        return $unitId ?: $fallbackUnitId ?: auth()->user()?->unit_id;
    }

    /**
     * Site dan unit harus konsisten: site wajib milik unit yang dipilih.
     * Mengembalikan pesan error, atau null bila valid.
     */
    private function siteUnitMismatchMessage(?int $siteId, ?int $unitId): ?string
    {
        if (! $siteId || ! $unitId) {
            return null;
        }

        $siteUnitId = Site::whereKey($siteId)->value('unit_id');

        if ($siteUnitId !== null && (int) $siteUnitId !== (int) $unitId) {
            return 'Site yang dipilih bukan milik unit tersebut. Pilih site lain atau ubah unit bisnisnya.';
        }

        return null;
    }

    /**
     * Susun payload vehicle_details dari input form.
     *
     * Field yang tidak dikirim form tidak diikutsertakan, sehingga update
     * parsial (mis. lewat API/test lama) tidak menghapus data yang sudah ada.
     */
    private function vehicleDetailPayload(array $validated): array
    {
        $fields = [
            'site_id',
            'jenis_kendaraan',
            'merk',
            'model',
            'tahun_pembuatan',
            'nomor_rangka',
            'nomor_mesin',
            'nomor_bpkb',
            'nomor_stnk',
            'nomor_kir',
            'pajak_berlaku_sampai',
            'kir_berlaku_sampai',
            'kapasitas_mesin',
            'odometer',
            'status_kepemilikan',
            'warna',
            'kapasitas_penumpang',
            'jenis_bahan_bakar',
            'volume_tangki',
            'nominal_full_tank',
            'nominal_spd_default',
            'kapasitas_bbm_tahunan',
            'catatan',
        ];

        $payload = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        // Kolom duplikat historis: kapasitas_bahan_bakar mengikuti volume_tangki.
        if (array_key_exists('kapasitas_bahan_bakar', $validated)
            || array_key_exists('volume_tangki', $validated)) {
            $payload['kapasitas_bahan_bakar'] = $validated['kapasitas_bahan_bakar']
                ?? ($validated['volume_tangki'] ?? null);
        }

        return $payload;
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate($this->vehicleValidationRules(), $this->vehicleValidationMessages());

        $unitId = $this->resolveVehicleUnitId($validated);

        if ($unitId === null) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Unit bisnis wajib dipilih agar kendaraan/alat bisa difilter per unit.');
        }

        if ($mismatch = $this->siteUnitMismatchMessage($validated['site_id'] ?? null, $unitId)) {
            return redirect()->back()->withInput()->with('error', $mismatch);
        }

        try {
            DB::beginTransaction();

            $vehicle = VehicleAndToolsConsumers::create([
                'consumerial_type' => $validated['consumerial_type'],
                'consumerial_code' => strtoupper($validated['consumerial_code']),
                'consumerial_name' => strtoupper($validated['consumerial_name']),
                'consumerial_notes' => $validated['consumerial_notes'] ?? null,
                'created_by' => auth()->id(),
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'is_active' => true,
                'unit_id' => $unitId,
            ]);

            // Auto-create VehicleDetail lengkap (identitas + budget & kapasitas).
            VehicleDetail::updateOrCreate(
                ['vehicle_and_tools_consumer_id' => $vehicle->id],
                $this->vehicleDetailPayload($validated)
            );

            DB::commit();

            return redirect()
                ->route('vehicles', ['tab' => 'list'])
                ->with('success', ucfirst($validated['consumerial_type']).' berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', safe_exception_message($e, 'Gagal menambahkan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function updateVehicle(Request $request, $id)
    {
        $vehicle = VehicleAndToolsConsumers::findOrFail($id);

        $validated = $request->validate(
            $this->vehicleValidationRules((int) $vehicle->id),
            $this->vehicleValidationMessages()
        );

        $unitId = $this->resolveVehicleUnitId($validated, $vehicle->unit_id ? (int) $vehicle->unit_id : null);

        if ($mismatch = $this->siteUnitMismatchMessage($validated['site_id'] ?? null, $unitId)) {
            return redirect()->back()->withInput()->with('error', $mismatch);
        }

        try {
            DB::beginTransaction();

            $vehicle->update([
                'consumerial_type' => $validated['consumerial_type'],
                'consumerial_code' => strtoupper($validated['consumerial_code']),
                'consumerial_name' => strtoupper($validated['consumerial_name']),
                'consumerial_notes' => $validated['consumerial_notes'] ?? null,
                'unit_id' => $unitId,
            ]);

            // Update Master Vehicle detail (identitas, budget & unit asal).
            VehicleDetail::updateOrCreate(
                ['vehicle_and_tools_consumer_id' => $vehicle->id],
                $this->vehicleDetailPayload($validated)
            );

            DB::commit();

            return redirect()
                ->route('vehicles', ['tab' => 'list'])
                ->with('success', ucfirst($validated['consumerial_type']).' berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal update. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function deleteVehicle($id)
    {
        try {
            $vehicle = VehicleAndToolsConsumers::withCount('permohonans')->findOrFail($id);

            // Check if vehicle has related data
            if ($vehicle->permohonans_count > 0) {
                return redirect()
                    ->back()
                    ->with('error', 'Tidak dapat dihapus karena memiliki '.
                                $vehicle->permohonans_count.' data permohonan terkait');
            }

            // Soft delete
            $vehicle->delete();

            return redirect()
                ->route('vehicles', ['tab' => 'list'])
                ->with('success', ucfirst($vehicle->consumerial_type).' berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal hapus. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function restoreVehicle($id)
    {
        try {
            $vehicle = VehicleAndToolsConsumers::withTrashed()->findOrFail($id);

            if (! $vehicle->trashed()) {
                return redirect()
                    ->back()
                    ->with('error', 'Data tidak dalam status terhapus');
            }

            $vehicle->restore();

            return redirect()
                ->route('vehicles', ['tab' => 'deleted'])
                ->with('success', ucfirst($vehicle->consumerial_type).' berhasil dipulihkan');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal memulihkan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // Tambahkan di SuperadminController.php

    // ==================== KELOLA PERMOHONAN & PELAPORAN ====================

    /**
     * Index - Kelola Permohonan & Pelaporan
     */
    public function indexPermohonan(Request $request)
    {
        $title = 'Kelola Permohonan & Pelaporan';

        // Get filters
        $status = $request->get('status');
        $search = $request->get('search');
        $tab = $request->get('tab', 'permohonan'); // permohonan, laporan

        // Query Permohonan
        $queryPermohonan = Permohonan::with(['requester', 'vehicle', 'authorizer', 'approvedRequest'])
            ->inUnit(UnitScope::for($request->user())->unitId());

        if ($status) {
            $queryPermohonan->where('status', $status);
        }

        if ($search) {
            $queryPermohonan->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($q2) use ($search) {
                        $q2->where('nama_lengkap', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($q2) use ($search) {
                        $q2->where('consumerial_code', 'like', "%{$search}%");
                    });
            });
        }

        $permohonans = $queryPermohonan->orderBy('request_date', 'desc')->paginate(20);

        // Query Laporan Transaksi
        $queryLaporan = Transaction_Proofs::with(['permohonan.requester', 'permohonan.vehicle'])
            ->inUnit(UnitScope::for($request->user())->unitId());

        if ($search) {
            $queryLaporan->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('permohonan', function ($q2) use ($search) {
                        $q2->where('request_number', 'like', "%{$search}%");
                    });
            });
        }

        $laporans = $queryLaporan->orderBy('created_at', 'desc')->paginate(20, ['*'], 'laporan_page');

        // Get all vehicles for dropdown (+ detail utk label "Plat - Merk" & budget full tank)
        $vehicles = VehicleAndToolsConsumers::with('detail')
            ->where('is_approved', true)
            ->inUnit(UnitScope::for($request->user())->unitId())
            ->orderBy('consumerial_type')
            ->orderBy('consumerial_name')
            ->get();

        // Get gasoline types
        $gasolineTypes = \App\Models\GasolineType::all();

        return view('superadmin.partials.kelola-permohonan', compact(
            'title',
            'permohonans',
            'laporans',
            'vehicles',
            'gasolineTypes',
            'tab',
            'status',
            'search'
        ));
    }

    /**
     * Update Permohonan
     */
    public function updatePermohonan(Request $request, $id)
    {
        $permohonan = Permohonan::findOrFail($id);

        // ✅ Budget dinamis dari Master Vehicle (nominal_full_tank), bukan hardcoded
        $maxNominal = null;
        if ($request->consumerial_tools_id) {
            $vehicle = VehicleAndToolsConsumers::with('detail')->find($request->consumerial_tools_id);
            $maxNominal = $vehicle?->detail?->nominal_full_tank;
        }

        $billRules = ['required', 'numeric', 'min:0'];
        $billMessages = [
            'consumerial_tools_id.required' => 'Kendaraan/Alat harus dipilih',
            'gasoline_type.required' => 'Jenis BBM harus dipilih',
            'bill_amounts.required' => 'Nominal harus diisi',
        ];
        if ($maxNominal !== null) {
            $billRules[] = 'max:'.$maxNominal;
            $billMessages['bill_amounts.max'] = 'Nominal melebihi budget full tank kendaraan (Rp '.
                number_format($maxNominal, 0, ',', '.').'). Sesuaikan di Master Vehicle bila perlu.';
        }

        $validated = $request->validate([
            'consumerial_tools_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'gasoline_type' => 'required|string',
            'bill_amounts' => $billRules,
            'requester_notes' => 'nullable|string|max:500',
            'authorizer_notes' => 'nullable|string|max:500',
        ], $billMessages);

        try {
            DB::beginTransaction();

            $permohonan->update([
                'consumerial_tools_id' => $validated['consumerial_tools_id'],
                'gasoline_type' => $validated['gasoline_type'],
                'bill_amounts' => $validated['bill_amounts'],
                'requester_notes' => $validated['requester_notes'],
                'authorizer_notes' => $validated['authorizer_notes'],
            ]);

            // Update approved_amount jika ada approved_request
            if ($permohonan->approvedRequest) {
                $permohonan->approvedRequest->update([
                    'approved_amount' => $validated['bill_amounts'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('superadmin.permohonan', ['tab' => 'permohonan'])
                ->with('success', "Permohonan {$permohonan->request_number} berhasil diupdate");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal update permohonan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Cancel Permohonan (ubah status jadi cancelled_by_driver)
     */
    public function cancelPermohonan(Request $request, $id)
    {
        $validated = $request->validate([
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $permohonan = Permohonan::findOrFail($id);

            // Update status
            $permohonan->update([
                'status' => 'cancelled_by_driver',
                'authorizer_id' => Auth::id(),
                'authorization_date' => now(),
                'authorizer_notes' => 'Dibatalkan oleh Superadmin: '.($validated['cancel_reason'] ?? 'Tidak ada alasan'),
            ]);

            // Update approved_request jika ada
            if ($permohonan->approvedRequest) {
                $permohonan->approvedRequest->update([
                    'status' => 'cancelled',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('superadmin.permohonan', ['tab' => 'permohonan'])
                ->with('success', "Permohonan {$permohonan->request_number} berhasil dibatalkan");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal membatalkan permohonan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Delete Permohonan (Soft Delete)
     */
    public function deletePermohonan($id)
    {
        try {
            DB::beginTransaction();

            $permohonan = Permohonan::findOrFail($id);

            // Jangan hapus jika sudah completed
            if ($permohonan->status === 'completed') {
                return back()->with('error', 'Tidak dapat menghapus permohonan yang sudah selesai');
            }

            // Soft delete related data
            if ($permohonan->approvedRequest) {
                $permohonan->approvedRequest->delete();
            }

            if ($permohonan->transactionProof) {
                $permohonan->transactionProof->delete();
            }

            // Soft delete permohonan
            $permohonan->delete();

            DB::commit();

            return redirect()
                ->route('superadmin.permohonan', ['tab' => 'permohonan'])
                ->with('success', "Permohonan {$permohonan->request_number} berhasil dihapus");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menghapus permohonan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Update Laporan Transaksi
     */
    public function updateLaporan(Request $request, $id)
    {        // Catatan: laporan ini adalah BBM OPERASIONAL (Transaction_Proofs), 1 permohonan = 1 struk.
        // Rincian per-item dengan gambar + kategori + item_code (struk_items) khusus SPD,
        // divisualisasikan pada validasi LPJ (SpdController::showLaporanSuperadmin → superadmin/spd/lpj/*).
        $laporan = Transaction_Proofs::findOrFail($id);

        $validated = $request->validate([
            'purchase_date' => 'required|date|before_or_equal:today',
            'fuel_volume' => 'required|numeric|min:0.01',
            'km_terakhir' => 'nullable|numeric|min:0',
            'foto_struk' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'foto_km' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'purchase_date.required' => 'Tanggal pengisian harus diisi',
            'fuel_volume.required' => 'Volume BBM harus diisi',
            'foto_struk.image' => 'File harus berupa gambar',
            'foto_struk.max' => 'Ukuran file maksimal 5MB',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'purchase_datetime' => $validated['purchase_date'].' '.now()->format('H:i:s'),
                'fuel_volume' => $validated['fuel_volume'],
                'km_terakhir' => $validated['km_terakhir'] ?? 0,
            ];

            // Upload foto struk baru jika ada
            if ($request->hasFile('foto_struk')) {
                // Hapus foto lama (privat maupun legacy publik)
                if ($laporan->struk_bbm_path) {
                    MediaStorage::delete($laporan->struk_bbm_path);
                }

                $requestNumber = $laporan->permohonan->request_number;
                $dateFormat = now()->format('dmY');

                $strukFile = $request->file('foto_struk');
                $strukExtension = $strukFile->getClientOriginalExtension();
                $strukFileName = "STRUK-{$requestNumber}-{$dateFormat}.{$strukExtension}";
                $updateData['struk_bbm_path'] = MediaStorage::storeUploaded($strukFile, 'transaction_proofs/struk', $strukFileName);
            }

            // Upload foto odometer baru jika ada
            if ($request->hasFile('foto_km')) {
                // Hapus foto lama (privat maupun legacy publik)
                if ($laporan->odometer_photo_path) {
                    MediaStorage::delete($laporan->odometer_photo_path);
                }

                $requestNumber = $laporan->permohonan->request_number;
                $dateFormat = now()->format('dmY');

                $odoFile = $request->file('foto_km');
                $odoExtension = $odoFile->getClientOriginalExtension();
                $odoFileName = "ODO-{$requestNumber}-{$dateFormat}.{$odoExtension}";
                $updateData['odometer_photo_path'] = MediaStorage::storeUploaded($odoFile, 'transaction_proofs/odometer', $odoFileName);
            }

            $laporan->update($updateData);

            DB::commit();

            return redirect()
                ->route('superadmin.permohonan', ['tab' => 'laporan'])
                ->with('success', "Laporan {$laporan->transaction_id} berhasil diupdate");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal update laporan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Generate kode voucher (format: BBMGDE-RANDOM6-DDMMYY).
     */
    private function generateVoucherCodePermohonan(): string
    {
        $date = now()->format('dmy');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        return "BBMGDE-{$random}-{$date}";
    }

    /**
     * Bulk approve/reject permohonan dengan komentar (Superadmin).
     *
     * Request body: decisions = JSON array [ { request_id, status: approve|reject, note } ]
     */
    public function bulkProcessPermohonan(Request $request)
    {
        $validated = $request->validate([
            'decisions' => 'required|string',
        ]);

        $decisions = json_decode($validated['decisions'], true);

        if (! is_array($decisions) || empty($decisions)) {
            return back()->with('error', 'Format data tidak valid');
        }

        try {
            DB::beginTransaction();

            $approvedCount = 0;
            $rejectedCount = 0;
            $skippedCount = 0;

            foreach ($decisions as $decision) {
                if (! isset($decision['request_id']) || ! isset($decision['status'])) {
                    $skippedCount++;

                    continue;
                }

                $permohonan = Permohonan::where('status', 'pending')->find($decision['request_id']);

                if (! $permohonan) {
                    $skippedCount++;

                    continue;
                }

                $newStatus = $decision['status'] === 'approve' ? 'approved' : 'rejected';

                $note = isset($decision['note']) && trim((string) $decision['note']) !== ''
                    ? trim((string) $decision['note'])
                    : null;

                $permohonan->update([
                    'status' => $newStatus,
                    'authorizer_id' => Auth::id(),
                    'authorization_date' => now(),
                    'authorizer_notes' => $newStatus === 'rejected'
                        ? ($note ? '[SUPERADMIN REJECTION] '.$note : '[SUPERADMIN REJECTION] Bulk rejection')
                        : ($note ? '[SUPERADMIN APPROVAL] '.$note : '[SUPERADMIN APPROVAL]'),
                ]);

                if ($newStatus === 'approved') {
                    Approved_Request::create([
                        'permohonan_id' => $permohonan->id,
                        'approval_date' => now(),
                        'voucher_code' => $this->generateVoucherCodePermohonan(),
                        'valid_until' => now()->addHours(50),
                        'approved_amount' => $permohonan->bill_amounts,
                        'authorizer_id' => Auth::id(),
                        'status' => 'approved',
                        // BUG-BBM-003: catatan approval harus tersimpan di
                        // voucher (kolom approval_notes), bukan dibuang.
                        'approval_notes' => $note ? '[SUPERADMIN APPROVAL] '.$note : null,
                        'unit_id' => $permohonan->unit_id,
                    ]);

                    $approvedCount++;
                } else {
                    $rejectedCount++;
                }
            }

            DB::commit();

            $message = [];
            if ($approvedCount > 0) {
                $message[] = "{$approvedCount} permohonan disetujui";
            }
            if ($rejectedCount > 0) {
                $message[] = "{$rejectedCount} permohonan ditolak";
            }
            if ($skippedCount > 0) {
                $message[] = "{$skippedCount} dilewati (tidak valid/sudah diproses)";
            }

            return redirect()->route('superadmin.permohonan', ['tab' => 'permohonan'])
                ->with('success', implode(' | ', $message));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal memproses bulk action. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Hapus Laporan Transaksi
     */
    public function deleteLaporan($id)
    {

        try {
            DB::beginTransaction();

            $laporan = Transaction_Proofs::findOrFail($id);

            // Hapus files (privat maupun legacy publik)
            if ($laporan->struk_bbm_path) {
                MediaStorage::delete($laporan->struk_bbm_path);
            }

            if ($laporan->odometer_photo_path) {
                MediaStorage::delete($laporan->odometer_photo_path);
            }

            // Update status permohonan kembali ke approved
            $permohonan = $laporan->permohonan;
            $permohonan->update(['status' => 'approved']);

            // Delete laporan
            $laporan->delete();

            DB::commit();

            return redirect()
                ->route('superadmin.permohonan', ['tab' => 'laporan'])
                ->with('success', 'Laporan berhasil dihapus. Permohonan dikembalikan ke status approved.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menghapus laporan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function generateTripAnalysis()
    {
        try {
            DB::beginTransaction();

            // Get all vehicles
            $vehicles = VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')
                ->where('is_approved', true)
                ->get();

            $totalAnalysis = 0;
            $longDistanceTrips = 0;

            foreach ($vehicles as $vehicle) {
                // Get all transaction proofs untuk kendaraan ini, sorted by date
                $proofs = Transaction_Proofs::whereHas('permohonan', function ($q) use ($vehicle) {
                    $q->where('consumerial_tools_id', $vehicle->id)
                        ->where('status', 'completed');
                })
                    ->where('km_terakhir', '>', 0)
                    ->orderBy('purchase_datetime', 'asc')
                    ->get();

                // ── BBM SPD (struk_items) sebagai sumber data pelengkap ──
                // Ambil semua struk BBM dari LPJ yang sudah approved untuk kendaraan ini.
                $spdBbms = StrukItem::where('struk_kategori', 'BBM')
                    ->whereHas('suratSpd', function ($q) use ($vehicle) {
                        $q->where('vehicle_id', $vehicle->id);
                    })
                    ->whereHas('lpj', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->whereNotNull('tanggal')
                    ->get();

                // Harga referensi per liter dari riwayat BBM operasional kendaraan ini
                // (bill_amounts / fuel_volume), dipakai untuk estimasi liter struk SPD
                // saat metadata OCR tidak membacakan volume.
                $avgPrice = null;
                $priceTotals = $proofs->map(function ($p) {
                    $nominal = (float) ($p->permohonan->bill_amounts ?? 0);
                    $volume = (float) $p->fuel_volume;

                    return $volume > 0 ? $nominal / $volume : null;
                })->filter();
                if ($priceTotals->count() > 0) {
                    $avgPrice = round($priceTotals->avg(), 2);
                }

                // Konversi struk SPD → liter BBM.
                $spdLiters = fn ($struk) => $this->estimateStrukLiters($struk, $avgPrice);

                // Skip jika kurang dari 2 transaksi
                if ($proofs->count() < 2) {
                    continue;
                }

                // Loop untuk setiap pasangan transaksi berurutan
                for ($i = 0; $i < $proofs->count() - 1; $i++) {
                    $startProof = $proofs[$i];
                    $endProof = $proofs[$i + 1];

                    // Hitung selisih KM
                    $kmDiff = $endProof->km_terakhir - $startProof->km_terakhir;

                    // Skip jika KM turun (tidak masuk akal) atau terlalu besar (> 10000 km)
                    if ($kmDiff <= 0 || $kmDiff > 10000) {
                        continue;
                    }

                    // Hitung selisih hari
                    $daysDiff = $startProof->purchase_datetime->diffInDays($endProof->purchase_datetime);

                    // BBM SPD yang jatuh di dalam rentang waktu pengukuran trip ini
                    $windowSpd = $spdBbms->filter(function ($struk) use ($startProof, $endProof) {
                        $date = $struk->tanggal ? $struk->tanggal->startOfDay() : null;

                        return $date
                            && $date->between(
                                $startProof->purchase_datetime->startOfDay(),
                                $endProof->purchase_datetime->endOfDay()
                            );
                    });
                    $spdFuel = round($windowSpd->map($spdLiters)->sum(), 2);

                    // Total BBM yang dikonsumsi pada rentang trip ini
                    $fuelConsumed = round((float) $startProof->fuel_volume + $spdFuel, 2);

                    // Hitung fuel efficiency
                    $fuelEfficiency = $fuelConsumed > 0
                        ? round($kmDiff / $fuelConsumed, 2)
                        : null;

                    // Tentukan kategori trip
                    $tripCategory = 'normal';
                    if ($kmDiff >= 400) {
                        $tripCategory = 'long_distance';
                        $longDistanceTrips++;
                    }

                    // Deteksi suspicious (efisiensi BBM tidak wajar)
                    if ($fuelEfficiency && ($fuelEfficiency < 3 || $fuelEfficiency > 25)) {
                        $tripCategory = 'suspicious';
                    }

                    // Cek apakah sudah ada analysis untuk pasangan proof ini
                    $existingAnalysis = VehicleTripAnalysis::where('transaction_proof_start_id', $startProof->id)
                        ->where('transaction_proof_end_id', $endProof->id)
                        ->first();

                    // Catatan sistem: sertakan info BBM SPD jika ada kontribusi
                    $spdNote = '';
                    if ($spdFuel > 0) {
                        $spdNote = "\n🧾 Termasuk BBM SPD (LPJ disetujui): {$spdFuel} L.";
                    }

                    if ($existingAnalysis) {
                        // Update existing
                        $existingAnalysis->update([
                            'km_difference' => $kmDiff,
                            'fuel_consumed' => $fuelConsumed,
                            'fuel_efficiency' => $fuelEfficiency,
                            'days_difference' => $daysDiff,
                            'trip_category' => $tripCategory,
                        ]);

                        $existingAnalysis->update([
                            'system_notes' => $existingAnalysis->generateSystemNotes().$spdNote,
                        ]);
                    } else {
                        // Create new
                        $analysis = VehicleTripAnalysis::create([
                            'requester_id' => $startProof->permohonan->requester_id,
                            'consumerial_tools_id' => $vehicle->id,
                            'transaction_proof_start_id' => $startProof->id,
                            'odo_start' => $startProof->km_terakhir,
                            'fuel_volume_start' => $startProof->fuel_volume,
                            'filling_date_start' => $startProof->purchase_datetime,
                            'transaction_proof_end_id' => $endProof->id,
                            'odo_end' => $endProof->km_terakhir,
                            'fuel_volume_end' => $endProof->fuel_volume,
                            'filling_date_end' => $endProof->purchase_datetime,
                            'km_difference' => $kmDiff,
                            'fuel_consumed' => $fuelConsumed,
                            'fuel_efficiency' => $fuelEfficiency,
                            'days_difference' => $daysDiff,
                            'trip_category' => $tripCategory,
                            'validation_status' => $tripCategory === 'long_distance' ? 'pending' : 'confirmed_normal',
                        ]);

                        $analysis->update([
                            'system_notes' => $analysis->generateSystemNotes().$spdNote,
                        ]);

                        $totalAnalysis++;
                    }
                }
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', "Berhasil generate {$totalAnalysis} analisis trip. {$longDistanceTrips} trip jarak jauh terdeteksi.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal generate trip analysis. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Estimasi volume (liter) sebuah struk BBM SPD.
     * Prioritas: metadata OCR (volume) → harga_per_liter → harga rata-rata BBM
     * operasional kendaraan (bill_amounts / fuel_volume). Jika tidak bisa
     * diestimasi, dikembalikan 0.
     */
    private function estimateStrukLiters(StrukItem $struk, ?float $avgPrice): float
    {
        $meta = $struk->metadata_json ?? [];

        $volume = (float) ($meta['volume'] ?? 0);
        if ($volume > 0) {
            return round($volume, 2);
        }

        $nominal = (float) $struk->nominal;
        if ($nominal <= 0) {
            return 0.0;
        }

        $price = (float) ($meta['harga_per_liter'] ?? 0);
        if ($price <= 0) {
            $price = (float) $avgPrice;
        }

        return $price > 0 ? round($nominal / $price, 2) : 0.0;
    }

    /**
     * Get trip validations data (auto-generated, only for diff_odo >= 300)
     */
    private function getAllTripsData()
    {
        // Get all vehicles
        $vehicles = VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->get();

        $allTrips = collect();

        foreach ($vehicles as $vehicle) {
            // Get transaction proofs untuk kendaraan ini
            $proofs = Transaction_Proofs::whereHas('permohonan', function ($q) use ($vehicle) {
                $q->where('consumerial_tools_id', $vehicle->id)
                    ->where('status', 'completed');
            })
                ->where('km_terakhir', '>', 0)
                ->orderBy('purchase_datetime', 'asc')
                ->get();

            // Skip jika kurang dari 2 transaksi
            if ($proofs->count() < 2) {
                continue;
            }

            // Loop untuk setiap pasangan berurutan
            for ($i = 0; $i < $proofs->count() - 1; $i++) {
                $prevProof = $proofs[$i];
                $latestProof = $proofs[$i + 1];

                $diffOdo = $latestProof->km_terakhir - $prevProof->km_terakhir;

                // Skip jika tidak masuk akal (KM turun atau terlalu besar)
                if ($diffOdo <= 0 || $diffOdo > 10000) {
                    continue;
                }

                $needsValidation = $diffOdo >= 300;

                // Cek apakah ada validasi di database (hanya untuk yang >= 300 KM)
                $validation = null;
                if ($needsValidation) {
                    $validation = TripValidation::where('transaction_proof_previous_id', $prevProof->id)
                        ->where('transaction_proof_latest_id', $latestProof->id)
                        ->first();

                    // Kalau belum ada validasi, buat entry baru
                    if (! $validation) {
                        $validation = TripValidation::create([
                            'transaction_proof_previous_id' => $prevProof->id,
                            'transaction_proof_latest_id' => $latestProof->id,
                            'previous_odo' => $prevProof->km_terakhir,
                            'latest_odo' => $latestProof->km_terakhir,
                            'diff_odo' => $diffOdo,
                            'is_long_trip' => false,
                            'long_trip_notes' => null,
                            'validated_by' => null,
                            'validated_at' => null,
                        ]);

                        $validation->calculateMetrics();
                    }
                }

                $allTrips->push([
                    'validationId' => $validation ? $validation->id : null,
                    'prevProofId' => $prevProof->id,
                    'latestProofId' => $latestProof->id,
                    'previousOdo' => $prevProof->km_terakhir,
                    'latestOdo' => $latestProof->km_terakhir,
                    'diffOdo' => $diffOdo,
                    'needsValidation' => $needsValidation,

                    // Validation status (hanya ada jika >= 300 KM)
                    'isValidated' => $validation ? ($validation->validated_at !== null) : false,
                    'isLongTrip' => $validation ? $validation->is_long_trip : null,
                    'longTripNotes' => $validation ? $validation->long_trip_notes : null,
                    'validatedBy' => $validation && $validation->validator ? $validation->validator->nama_lengkap : null,
                    'validatedAt' => $validation ? $validation->validated_at : null,
                    'statusBadge' => $validation ? $validation->status_badge : null,
                    'statusLabel' => $validation ? $validation->status_label : null,

                    // Info tambahan
                    'vehicleDisplay' => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                    'vehicleCode' => $vehicle->consumerial_code,
                    'requesterName' => $prevProof->permohonan->requester->nama_lengkap ?? 'Unknown',
                    'datePrevious' => $prevProof->purchase_datetime,
                    'dateLatest' => $latestProof->purchase_datetime,
                    'daysDiff' => $prevProof->purchase_datetime->diffInDays($latestProof->purchase_datetime),
                    'requestNumberPrev' => $prevProof->permohonan->request_number ?? '-',
                    'requestNumberLatest' => $latestProof->permohonan->request_number ?? '-',

                    // Hitung efisiensi BBM
                    'fuelVolume' => $prevProof->fuel_volume,
                    'fuelEfficiency' => $prevProof->fuel_volume > 0
                        ? round($diffOdo / $prevProof->fuel_volume, 2)
                        : 0,
                ]);
            }
        }

        return $allTrips->sortByDesc('diffOdo');
    }

    /**
     * Validate single trip
     */
    /**
     * Validate single trip dengan baseline override
     */
    public function validateTrip(Request $request, $id)
    {
        $validated = $request->validate([
            'is_long_trip' => 'required|boolean',
            'long_trip_notes' => 'nullable|string|max:500',

            // ✅ NEW: Baseline override fields
            'baseline_override' => 'nullable|numeric|min:1|max:30',
            // BUG-EFF-001: mengubah patokan efisiensi WAJIB disertai alasan
            // agar perubahan punya jejak audit (reason + siapa + kapan).
            'baseline_override_reason' => 'required_with:baseline_override|nullable|string|max:500',
        ], [
            'is_long_trip.required' => 'Jenis perjalanan harus dipilih',
            'baseline_override.numeric' => 'Patokan BBM harus berupa angka',
            'baseline_override.min' => 'Patokan BBM minimal 1 km/L',
            'baseline_override.max' => 'Patokan BBM maksimal 30 km/L',
            'baseline_override_reason.required_with' => 'Alasan perubahan patokan BBM wajib diisi.',
        ]);

        try {
            $tripValidation = TripValidation::findOrFail($id);

            // Update validation data
            $updateData = [
                'is_long_trip' => $validated['is_long_trip'],
                'long_trip_notes' => $validated['long_trip_notes'] ?? null,
                'validated_by' => Auth::id(),
                'validated_at' => now(),
            ];

            // ✅ NEW: Handle baseline override
            if ($request->filled('baseline_override')) {
                $updateData['baseline_override'] = $validated['baseline_override'];
                $updateData['baseline_override_reason'] = $validated['baseline_override_reason'];
                $updateData['baseline_overridden_by'] = Auth::id();
                $updateData['baseline_overridden_at'] = now();
            }

            $tripValidation->update($updateData);

            // Recalculate metrics dengan baseline baru
            $tripValidation->calculateMetrics();

            $message = 'Trip berhasil divalidasi';
            if ($request->filled('baseline_override')) {
                $message .= ' dengan patokan BBM disesuaikan: '.$validated['baseline_override'].' km/L.';
                $message .= ' Alasan perubahan tercatat: '.$validated['baseline_override_reason'].'.';
            }

            return redirect()
                ->back()
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal validasi trip. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Bulk validate trips
     */
    public function bulkValidateTrips(Request $request)
    {
        $validated = $request->validate([
            'validation_ids' => 'required|array',
            'validation_ids.*' => 'exists:trip_validations,id',
            'is_long_trip' => 'required|boolean',
            'long_trip_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $successCount = 0;
            foreach ($validated['validation_ids'] as $id) {
                $tripValidation = TripValidation::find($id);
                if ($tripValidation) {
                    $tripValidation->update([
                        'is_long_trip' => $validated['is_long_trip'],
                        'long_trip_notes' => $validated['long_trip_notes'] ?? null,
                        'validated_by' => Auth::id(),
                        'validated_at' => now(),
                    ]);

                    // ✅ RECALCULATE METRICS untuk setiap trip
                    $tripValidation->calculateMetrics();
                    $successCount++;
                }
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', "{$successCount} trip berhasil divalidasi dan metrics telah diupdate.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal bulk validasi. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Get trip analysis statistics
     */
    // public function getTripAnalysisStats()
    // {
    //     try {
    //         $stats = [
    //             'totalTrips' => VehicleTripAnalysis::count(),
    //             'normalTrips' => VehicleTripAnalysis::normal()->count(),
    //             'longDistanceTrips' => VehicleTripAnalysis::longDistance()->count(),
    //             'suspiciousTrips' => VehicleTripAnalysis::suspicious()->count(),
    //             'needValidation' => VehicleTripAnalysis::needValidation()->count(),
    //             'validated' => VehicleTripAnalysis::where('is_validated', true)->count(),

    //             // Average metrics
    //             'avgKmDifference' => round(VehicleTripAnalysis::avg('km_difference'), 2),
    //             'avgFuelEfficiency' => round(VehicleTripAnalysis::avg('fuel_efficiency'), 2),
    //             'avgDaysDifference' => round(VehicleTripAnalysis::avg('days_difference'), 2),

    //             // Latest long distance trips
    //             'latestLongDistance' => VehicleTripAnalysis::with(['requester', 'vehicle'])
    //                 ->longDistance()
    //                 ->where('is_validated', false)
    //                 ->orderBy('filling_date_end', 'desc')
    //                 ->limit(5)
    //                 ->get()
    //                 ->map(function($trip) {
    //                     return [
    //                         'id' => $trip->id,
    //                         'requester' => $trip->requester->nama_lengkap ?? 'Unknown',
    //                         'vehicle' => $trip->vehicle->consumerial_code ?? 'Unknown',
    //                         'kmDiff' => $trip->km_difference,
    //                         'dateRange' => $trip->filling_date_start->format('d/m/Y') . ' - ' . $trip->filling_date_end->format('d/m/Y'),
    //                     ];
    //                 }),
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'data' => $stats
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal memuat statistik trip: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    // ==================== ACTIVITY LOG ====================

    // public function getActivityLog()
    // {
    //     try {
    //         // Get recent login activities from sessions table
    //         // Alternative: buat table login_logs sendiri untuk tracking lebih detail

    //         $activities = DB::table('sessions')
    //             ->join('users', 'sessions.user_id', '=', 'users.id')
    //             ->select(
    //                 'users.id as user_id',
    //                 'users.username',
    //                 'users.fullname',
    //                 'users.role',
    //                 'sessions.last_activity',
    //                 'sessions.ip_address',
    //                 'sessions.user_agent'
    //             )
    //             ->whereNotNull('sessions.user_id')
    //             ->orderBy('sessions.last_activity', 'desc')
    //             ->limit(100)
    //             ->get();

    //         $formattedActivities = $activities->map(function ($activity) {
    //             return [
    //                 'userId' => $activity->user_id,
    //                 'username' => $activity->username,
    //                 'fullname' => $activity->fullname,
    //                 'role' => $activity->role,
    //                 'lastActivity' => Carbon::createFromTimestamp($activity->last_activity)->toISOString(),
    //                 'ipAddress' => $activity->ip_address,
    //                 'userAgent' => $activity->user_agent,
    //                 'isActive' => (time() - $activity->last_activity) < 3600, // Active jika < 1 jam
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $formattedActivities
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal memuat log aktivitas: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    // ==================== VEHICLE ACTIVATION MANAGEMENT ====================

    /**
     * Show vehicle activation management page
     */
    public function vehicleActivation(Request $request)
    {
        $title = 'Kelola Status Kendaraan & Alat';

        // Get filters
        $type = $request->get('type'); // KENDARAAN or ALAT
        $status = $request->get('status'); // active or inactive
        $search = $request->get('search');

        // Query vehicles (hanya yang sudah approved)
        $query = VehicleAndToolsConsumers::with(['creator', 'approver'])
            ->where('is_approved', true);

        // Filter by type
        if ($type) {
            $query->where('consumerial_type', strtoupper($type));
        }

        // Filter by status
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Search
        if ($search) {
            $searchUpper = strtoupper($search);
            $query->where(function ($q) use ($searchUpper) {
                $q->where('consumerial_code', 'like', "%{$searchUpper}%")
                    ->orWhere('consumerial_name', 'like', "%{$searchUpper}%")
                    ->orWhere('consumerial_notes', 'like', "%{$searchUpper}%");
            });
        }

        $vehicles = $query->orderBy('is_active', 'desc')
            ->orderBy('consumerial_type', 'asc')
            ->orderBy('consumerial_name', 'asc')
            ->paginate(20);

        // Statistics
        $stats = [
            'total' => VehicleAndToolsConsumers::where('is_approved', true)->count(),
            'active' => VehicleAndToolsConsumers::where('is_approved', true)->where('is_active', true)->count(),
            'inactive' => VehicleAndToolsConsumers::where('is_approved', true)->where('is_active', false)->count(),
            'kendaraan_active' => VehicleAndToolsConsumers::where('is_approved', true)
                ->where('is_active', true)
                ->where('consumerial_type', 'KENDARAAN')
                ->count(),
            'alat_active' => VehicleAndToolsConsumers::where('is_approved', true)
                ->where('is_active', true)
                ->where('consumerial_type', 'ALAT')
                ->count(),
        ];

        return view('superadmin.partials.vehicle-activation', compact(
            'title',
            'vehicles',
            'stats',
            'type',
            'status',
            'search'
        ));
    }

    /**
     * Activate vehicle
     */
    public function activateVehicle($id)
    {
        try {
            $vehicle = VehicleAndToolsConsumers::findOrFail($id);

            // Cek apakah sudah approved
            if (! $vehicle->is_approved) {
                return back()->with('error', 'Kendaraan/Alat harus disetujui terlebih dahulu sebelum diaktifkan.');
            }

            // Cek apakah sudah active
            if ($vehicle->is_active) {
                return back()->with('warning', 'Kendaraan/Alat sudah dalam status aktif.');
            }

            // Update status
            $vehicle->update(['is_active' => true]);

            return back()->with('success', "✅ {$vehicle->consumerial_type} {$vehicle->consumerial_code} berhasil diaktifkan.");

        } catch (\Exception $e) {
            return back()->with('error', safe_exception_message($e, 'Gagal mengaktifkan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Deactivate vehicle
     */
    public function deactivateVehicle(Request $request, $id)
    {
        $validated = $request->validate([
            'deactivation_reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $vehicle = VehicleAndToolsConsumers::findOrFail($id);

            // Cek apakah sudah inactive
            if (! $vehicle->is_active) {
                return back()->with('warning', 'Kendaraan/Alat sudah dalam status tidak aktif.');
            }

            // ✅ Cek apakah ada permohonan aktif yang menggunakan kendaraan ini
            $activeRequests = Permohonan::where('consumerial_tools_id', $vehicle->id)
                ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
                ->count();

            if ($activeRequests > 0) {
                return back()->with('error', "Tidak dapat menonaktifkan. Ada {$activeRequests} permohonan aktif yang masih menggunakan kendaraan/alat ini.");
            }

            // Update status
            $vehicle->update([
                'is_active' => false,
                'consumerial_notes' => $vehicle->consumerial_notes
                    ? $vehicle->consumerial_notes."\n[DEACTIVATED] ".($validated['deactivation_reason'] ?? 'No reason provided')
                    : '[DEACTIVATED] '.($validated['deactivation_reason'] ?? 'No reason provided'),
            ]);

            DB::commit();

            return back()->with('success', "⚠️ {$vehicle->consumerial_type} {$vehicle->consumerial_code} berhasil dinonaktifkan.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal menonaktifkan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Bulk activate vehicles
     */
    public function bulkActivateVehicles(Request $request)
    {
        $validated = $request->validate([
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'exists:vehicle_and_tools_consumers,id',
        ]);

        try {
            DB::beginTransaction();

            $activatedCount = 0;

            foreach ($validated['vehicle_ids'] as $id) {
                $vehicle = VehicleAndToolsConsumers::find($id);

                if ($vehicle && $vehicle->is_approved && ! $vehicle->is_active) {
                    $vehicle->update(['is_active' => true]);
                    $activatedCount++;
                }
            }

            DB::commit();

            return back()->with('success', "✅ {$activatedCount} kendaraan/alat berhasil diaktifkan.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal bulk activate. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Bulk deactivate vehicles
     */
    public function bulkDeactivateVehicles(Request $request)
    {
        $validated = $request->validate([
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'exists:vehicle_and_tools_consumers,id',
            'deactivation_reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $deactivatedCount = 0;
            $skippedCount = 0;

            foreach ($validated['vehicle_ids'] as $id) {
                $vehicle = VehicleAndToolsConsumers::find($id);

                if (! $vehicle || ! $vehicle->is_active) {
                    $skippedCount++;

                    continue;
                }

                // Cek active requests
                $activeRequests = Permohonan::where('consumerial_tools_id', $vehicle->id)
                    ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
                    ->count();

                if ($activeRequests > 0) {
                    $skippedCount++;

                    continue;
                }

                $vehicle->update([
                    'is_active' => false,
                    'consumerial_notes' => $vehicle->consumerial_notes
                        ? $vehicle->consumerial_notes."\n[BULK DEACTIVATED] ".($validated['deactivation_reason'] ?? 'No reason provided')
                        : '[BULK DEACTIVATED] '.($validated['deactivation_reason'] ?? 'No reason provided'),
                ]);

                $deactivatedCount++;
            }

            DB::commit();

            $message = "⚠️ {$deactivatedCount} kendaraan/alat berhasil dinonaktifkan.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} dilewati (sudah inactive atau memiliki permohonan aktif).";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', safe_exception_message($e, 'Gagal bulk deactivate. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // ==================== TRIP ANALYSIS MANAGEMENT ====================

    private function generateSystemNotes($kmDiff, $actual, $expected, $deviation, $rangeUsage, $isPremature, $daysBetween)
    {
        $notes = [];

        // ✅ NEW: Calculate daily average
        $dailyAvgKm = $daysBetween > 0 ? round($kmDiff / $daysBetween, 1) : 0;

        // 1. Distance & Usage Pattern Analysis
        if ($dailyAvgKm < 15 && $daysBetween > 3) {
            $notes[] = '⚠️ Pemakaian sangat rendah: '.$dailyAvgKm.' km/hari ('.$kmDiff.' KM dalam '.$daysBetween.' hari)';

            // ✅ Detect anomaly: rendah km/hari tapi BBM banyak
            if ($deviation < -40) {
                $notes[] = '🚨 ANOMALI TERDETEKSI: Kendaraan jarang dipakai tapi BBM boros - kemungkinan kebocoran, idle berlebihan, atau kesalahan data odometer';
            }
        } elseif ($dailyAvgKm >= 15 && $dailyAvgKm < 30 && $daysBetween > 5) {
            $notes[] = 'ℹ️ Pemakaian rendah: '.$dailyAvgKm.' km/hari - kendaraan jarang digunakan';
        } elseif ($dailyAvgKm >= 30 && $dailyAvgKm < 80) {
            $notes[] = '✅ Pemakaian normal: '.$dailyAvgKm.' km/hari';
        } elseif ($dailyAvgKm >= 80 && $dailyAvgKm < 150) {
            $notes[] = '📈 Pemakaian tinggi: '.$dailyAvgKm.' km/hari - kendaraan operasional intensif';
        } elseif ($dailyAvgKm >= 150) {
            $notes[] = '🔥 Pemakaian sangat tinggi: '.$dailyAvgKm.' km/hari - perlu validasi perjalanan dinas';
        }

        // 2. Long distance check
        if ($kmDiff >= 400) {
            $notes[] = '🚗 Jarak tempuh tinggi: '.number_format($kmDiff, 0).' KM (≥400 KM)';
        } elseif ($kmDiff >= 300) {
            $notes[] = '🚗 Jarak tempuh: '.number_format($kmDiff, 0).' KM (perlu validasi)';
        }

        // 3. Efficiency analysis
        if ($deviation < -50) {
            $notes[] = '❌ Efisiensi BBM sangat buruk: '.round($actual, 1).' km/L (patokan '.round($expected, 1).' km/L) - Selisih '.round($deviation, 1).'%';
            $notes[] = '🔧 Rekomendasi: Segera cek kondisi mesin, kebocoran BBM, atau verifikasi data odometer';
        } elseif ($deviation < -30) {
            $notes[] = '❌ Efisiensi BBM sangat rendah: '.round($actual, 1).' km/L (patokan '.round($expected, 1).' km/L) - Selisih '.round($deviation, 1).'%';
            $notes[] = '🔧 Rekomendasi: Cek kondisi kendaraan atau validasi data';
        } elseif ($deviation < -20) {
            $notes[] = '⚠️ Efisiensi BBM di bawah patokan: '.round($actual, 1).' km/L (patokan '.round($expected, 1).' km/L) - Selisih '.round($deviation, 1).'%';
        } elseif ($deviation > 10) {
            $notes[] = 'ℹ️ Efisiensi BBM di atas patokan: '.round($actual, 1).' km/L (patokan '.round($expected, 1).' km/L) - Perlu verifikasi data';
        } else {
            $notes[] = '✅ Efisiensi BBM normal: '.round($actual, 1).' km/L';
        }

        // 4. Premature refuel analysis dengan context
        if ($isPremature) {
            $expectedKm = $expected * $this->fuel_volume;
            $notes[] = '⚠️ Pengisian terlalu cepat: Baru '.round($rangeUsage, 1).'% dari jarak optimal';
            $notes[] = '📊 Detail: '.number_format($kmDiff, 0).' KM dari seharusnya '.number_format($expectedKm, 0).' KM';

            // ✅ Context: apakah ini wajar berdasarkan usage pattern?
            if ($dailyAvgKm < 20) {
                $notes[] = '💡 Catatan: Kendaraan jarang dipakai ('.$dailyAvgKm.' km/hari) - pengisian cepat tidak sesuai pola pemakaian';
            }
        } elseif ($rangeUsage > 100) {
            $notes[] = 'ℹ️ Pemakaian melebihi jarak optimal: '.round($rangeUsage, 1).'% (mungkin ada perjalanan tambahan)';
        }

        // 5. Days analysis
        if ($daysBetween <= 1) {
            $notes[] = '⚡ Pengisian dalam 1 hari - kemungkinan perjalanan intensif atau darurat';
        } elseif ($daysBetween > 30) {
            $notes[] = '📅 Gap '.$daysBetween.' hari - kendaraan jarang digunakan atau standby';
        }

        return implode("\n", $notes);
    }

    /**
     * Recalculate all trip metrics (manual trigger)
     */
    public function recalculateAllTripMetrics()
    {
        try {
            DB::beginTransaction();

            $trips = TripValidation::whereNotNull('transaction_proof_previous_id')
                ->whereNotNull('transaction_proof_latest_id')
                ->get();

            $successCount = 0;
            $failCount = 0;

            foreach ($trips as $trip) {
                if ($trip->calculateMetrics()) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', "✅ {$successCount} trip berhasil di-recalculate. {$failCount} gagal.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', safe_exception_message($e, 'Gagal recalculate metrics. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Index Trip Analysis - Dedicated view untuk analisis trip
     */
    public function indexTripAnalysis(Request $request)
    {
        $title = 'Analisis Trip & Efisiensi BBM';

        // Get filters
        $alertLevel = $request->get('alert_level');
        $vehicleId = $request->get('vehicle_id');
        $requesterId = $request->get('requester_id');
        $validated = $request->get('validated'); // true/false/null
        $search = $request->get('search');

        // Query trip validations
        $query = TripValidation::with([
            'transactionProofPrevious.permohonan.consumerialTool',
            'transactionProofPrevious.permohonan.requester',
            'validator',
        ])
            ->whereNotNull('actual_efficiency'); // Hanya yang sudah di-calculate

        // Filter by alert level
        if ($alertLevel) {
            $query->where('alert_level', $alertLevel);
        }

        // Filter by vehicle
        if ($vehicleId) {
            $query->whereHas('transactionProofPrevious.permohonan', function ($q) use ($vehicleId) {
                $q->where('consumerial_tools_id', $vehicleId);
            });
        }

        // Filter by requester
        if ($requesterId) {
            $query->whereHas('transactionProofPrevious.permohonan', function ($q) use ($requesterId) {
                $q->where('requester_id', $requesterId);
            });
        }

        // Filter by validation status
        if ($validated === 'true') {
            $query->whereNotNull('validated_at');
        } elseif ($validated === 'false') {
            $query->whereNull('validated_at');
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('transactionProofPrevious.permohonan.consumerialTool', function ($q2) use ($search) {
                    $q2->where('consumerial_code', 'like', "%{$search}%")
                        ->orWhere('consumerial_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('transactionProofPrevious.permohonan.requester', function ($q2) use ($search) {
                        $q2->where('nama_lengkap', 'like', "%{$search}%");
                    });
            });
        }

        $tripAnalyses = $query
            ->orderBy('days_between_refuel', 'asc')  // Yang paling cepat refuel
            ->orderBy('created_at', 'desc')          // Kemudian yang terbaru
            ->paginate(20);

        // Statistics
        $stats = [
            'total' => TripValidation::whereNotNull('actual_efficiency')->count(),
            'normal' => TripValidation::where('alert_level', 'normal')->count(),
            'warning' => TripValidation::where('alert_level', 'warning')->count(),
            'critical' => TripValidation::where('alert_level', 'critical')->count(),
            'needValidation' => TripValidation::whereNull('validated_at')
                ->where(function ($q) {
                    $q->where('diff_odo', '>=', 300)
                        ->orWhere('is_premature_refuel', true)
                        ->orWhere('efficiency_deviation_percent', '<', -20);
                })
                ->count(),
            'avgEfficiency' => TripValidation::whereNotNull('actual_efficiency')
                ->avg('actual_efficiency'),
            'avgDeviation' => TripValidation::whereNotNull('efficiency_deviation_percent')
                ->avg('efficiency_deviation_percent'),
        ];

        // Get vehicles and drivers for filter
        $vehicles = VehicleAndToolsConsumers::where('is_approved', true)
            ->where('consumerial_type', 'KENDARAAN')
            ->orderBy('consumerial_code')
            ->get();

        $drivers = User::where('role', 'requester')
            ->orderBy('nama_lengkap')
            ->get();

        return view('superadmin.partials.trip-analysis', compact(
            'title',
            'tripAnalyses',
            'stats',
            'vehicles',
            'drivers',
            'alertLevel',
            'vehicleId',
            'requesterId',
            'validated',
            'search'
        ));
    }

    // private function compressToJpeg(string $imagePath, int $quality = 85): ?string
    // {
    //     if (!file_exists($imagePath)) {
    //         return null;
    //     }

    //     try {
    //         $mime = mime_content_type($imagePath);

    //         $source = match($mime) {
    //             'image/jpeg' => imagecreatefromjpeg($imagePath),
    //             'image/png'  => imagecreatefrompng($imagePath),
    //             'image/webp' => imagecreatefromwebp($imagePath),
    //             default      => null,
    //         };

    //         if (!$source) return null;

    //         // Resize to max 390×300px (maintain aspect ratio)
    //         $srcW = imagesx($source);
    //         $srcH = imagesy($source);
    //         $maxW = 400;
    //         $maxH = 300;

    //         if ($srcW > $maxW || $srcH > $maxH) {
    //             $ratio  = min($maxW / $srcW, $maxH / $srcH);
    //             $newW   = (int) round($srcW * $ratio);
    //             $newH   = (int) round($srcH * $ratio);
    //             $resized = imagecreatetruecolor($newW, $newH);
    //             imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    //             imagedestroy($source);
    //             $source = $resized;
    //         }

    //         ob_start();
    //         imagejpeg($source, null, $quality);
    //         $compressed = ob_get_clean();
    //         imagedestroy($source);

    //         return 'data:image/jpeg;base64,' . base64_encode($compressed);

    //     } catch (\Exception $e) {
    //         return null;
    //     }
    // }

    private function compressToJpeg(string $imagePath, int $quality = 80): ?string
    {
        if (! file_exists($imagePath)) {
            return null;
        }

        // Skip file yang terlalu besar (> 15MB) - kemungkinan bukan foto struk
        $fileSize = filesize($imagePath);
        if ($fileSize > 15 * 1024 * 1024) {
            error_log("Skipping oversized image: {$imagePath} ({$fileSize} bytes)");

            return null;
        }

        try {
            if (! extension_loaded('imagick')) {
                return $this->compressToJpegGD($imagePath, $quality);
            }

            $imagick = new \Imagick($imagePath);

            // Konversi ke JPEG
            $imagick->setImageFormat('jpeg');

            // Optimasi dengan strip metadata
            $imagick->stripImage();

            // ✅ TANPA RESIZE — hanya turunkan kualitas (dimensi gambar dipertahankan)
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality($quality);

            // Optimasi tambahan
            $imagick->setSamplingFactors(['2x2', '1x1', '1x1']); // Chroma subsampling

            $compressed = $imagick->getImageBlob();
            $imagick->clear();

            return 'data:image/jpeg;base64,'.base64_encode($compressed);

        } catch (\Exception $e) {
            error_log('Imagick compression failed: '.$e->getMessage());

            return null;
        }
    }

    // Fallback GD dengan optimasi lebih baik — TANPA resize, hanya turunkan kualitas
    private function compressToJpegGD(string $imagePath, int $quality = 80): ?string
    {
        // Cek dimensi dulu SEBELUM load full image ke memory
        $imageInfo = getimagesize($imagePath);
        if (! $imageInfo) {
            return null;
        }

        $mimeType = $imageInfo['mime'];

        // Load image berdasarkan tipe
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($imagePath);
                break;
            case 'image/webp':
                $source = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($imagePath) : null;
                break;
            default:
                $source = imagecreatefromstring(file_get_contents($imagePath));
                break;
        }

        if (! $source) {
            return null;
        }

        // Pertahankan alpha untuk PNG transparan
        imagealphablending($source, false);
        imagesavealpha($source, true);

        // ✅ TANPA RESIZE — dimensi asli dipertahankan, hanya kualitas JPEG yang turun
        ob_start();
        imagejpeg($source, null, $quality);
        $compressed = ob_get_clean();
        imagedestroy($source);

        return 'data:image/jpeg;base64,'.base64_encode($compressed);
    }
}
