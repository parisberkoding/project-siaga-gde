@extends('templates.main')

@section('title', $title)

@section('content')
<style>
    /* ===== INFO CARDS ===== */
    .info-card { padding-bottom: 10px; transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(13, 44, 114, 0.12) !important;
    }
    .info-card h6 {
        font-size: 28px;
        color: #093D7D;
        font-weight: 700;
        margin: 0;
        padding: 0;
    }
    .card-icon {
        font-size: 32px;
        line-height: 0;
        width: 64px;
        height: 64px;
        flex-shrink: 0;
        flex-grow: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sales-card .card-icon        { color: #0B50A1; background: #EAF1FA; }
    .revenue-card .card-icon      { color: #4A7A1C; background: #F2F9E8; }
    .customers-card .card-icon    { color: #B35B00; background: #ffecdf; }
    .transactions-card .card-icon { color: #6D28D9; background: #f3e8ff; }

    /* ===== PERIOD DROPDOWN ===== */
    .period-menu {
        border-radius: 4px;
        padding: 10px 0;
        border: 0;
        box-shadow: 0 5px 30px 0 rgba(82, 63, 105, 0.2);
    }
    .period-menu .dropdown-item {
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 14px;
    }
    .period-menu .dropdown-item:hover { background-color: #F4F7FB; }

    /* ===== FILTER PANEL ===== */
    #advancedFilter .form-control:focus,
    #advancedFilter .form-select:focus {
        border-color: #0B50A1;
        box-shadow: 0 0 0 0.2rem rgba(11, 80, 161, 0.15);
    }

    /* ===== CHARTS ===== */
    .chart-card { border-radius: 10px; }
    .chart-card canvas { max-height: 340px; }

    /* ===== TABLES ===== */
    .card { border-radius: var(--gde-r-lg); }
    .card-header { border-radius: 10px 10px 0 0 !important; }
    .table-hover tbody tr:hover { background-color: rgba(0,123,255,0.05); }

    /* ===== MISC ===== */
    .section-title {
        font-size: 15px;
        font-weight: 700;
        color: #093D7D;
        letter-spacing: 0.3px;
    }
    .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

    /* ===== MODAL SCROLLABLE ===== */
    .modal-dialog-scrollable .modal-body { max-height: calc(100vh - 200px); }

    /* ===== PDF LOADING OVERLAY ===== */
    @keyframes pdfIconPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%       { transform: scale(1.08); opacity: 0.85; }
    }
</style>

<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Home</li>
            <li class="breadcrumb-item active">Dashboard Bisnis</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="d-flex justify-content-end flex-wrap gap-2 mb-3">
    <a href="{{ route('superadmin.trip-analysis.index') }}" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-signpost-2 me-2"></i>Trip Analysis
    </a>
    @if(Auth::user()->role === 'superadmin')
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#printReportModal">
        <i class="bi bi-printer-fill me-2"></i>Cetak Laporan Transaksi BBM
    </button>
    @endif
</div>

{{-- ============================================================
     FILTER PANEL (realtime — auto-submit saat berubah)
     ============================================================ --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">
            <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Data Dashboard
            <span id="filterBadge" class="badge bg-primary ms-2 d-none"></span>
        </h5>
        <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#advancedFilter"
                aria-expanded="true" aria-controls="advancedFilter">
            <i class="bi bi-chevron-up me-1"></i> Sembunyikan Filter
        </button>
    </div>

    <div class="collapse show" id="advancedFilter">
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('superadmin.dashboard.bisnis') }}">
                <div class="row g-3">

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-range text-primary"></i> Tanggal Mulai
                        </label>
                        <input type="date" class="form-control" name="start_date"
                               id="filter_start_date" value="{{ request('start_date') }}"
                               max="{{ date('Y-m-d') }}" onchange="dashboardFilter.submit()">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check text-success"></i> Tanggal Akhir
                        </label>
                        <input type="date" class="form-control" name="end_date"
                               id="filter_end_date" value="{{ request('end_date') }}"
                               max="{{ date('Y-m-d') }}" onchange="dashboardFilter.submit()">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-flag text-warning"></i> Status Transaksi
                        </label>
                        <select class="form-select" name="status" id="filter_status"
                                onchange="dashboardFilter.submit()">
                            <option value="">Semua Status</option>
                            @foreach(['pending', 'approved', 'completed', 'rejected'] as $st)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>
                                {{ ucfirst($st) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-fuel-pump text-danger"></i> Jenis BBM
                        </label>
                        <select class="form-select" name="gasoline_type" id="filter_gasoline"
                                onchange="dashboardFilter.submit()">
                            <option value="">Semua Jenis BBM</option>
                            @foreach(['Pertamax', 'Pertamina Dex', 'Pertalite'] as $gt)
                            <option value="{{ $gt }}" {{ request('gasoline_type') == $gt ? 'selected' : '' }}>
                                {{ $gt }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-truck text-info"></i> Kendaraan
                        </label>
                        <select class="form-select" name="vehicle_id" id="filter_vehicle"
                                onchange="dashboardFilter.submit()">
                            <option value="">Semua Kendaraan</option>
                            @foreach($vehicles ?? [] as $vehicle)
                            <option value="{{ $vehicle->id }}"
                                    {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-badge text-secondary"></i> Driver/Pemohon
                        </label>
                        <select class="form-select" name="requester_id" id="filter_requester"
                                onchange="dashboardFilter.submit()">
                            <option value="">Semua Driver</option>
                            @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}"
                                    {{ request('requester_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->nama_lengkap }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-cash-stack text-success"></i> Rentang Nominal
                        </label>
                        <select class="form-select" name="nominal_range" id="filter_nominal"
                                onchange="dashboardFilter.submit()">
                            <option value="">Semua Nominal</option>
                            <option value="0-100000"      {{ request('nominal_range') == '0-100000'      ? 'selected' : '' }}>&lt; Rp 100.000</option>
                            <option value="100000-300000" {{ request('nominal_range') == '100000-300000' ? 'selected' : '' }}>Rp 100.000 – Rp 300.000</option>
                            <option value="300000-500000" {{ request('nominal_range') == '300000-500000' ? 'selected' : '' }}>Rp 300.000 – Rp 500.000</option>
                            <option value="500000+"       {{ request('nominal_range') == '500000+'       ? 'selected' : '' }}>&gt; Rp 500.000</option>
                        </select>
                    </div>

                </div><!-- /.row -->

                <div class="row mt-4">
                    <div class="col-12 d-flex justify-content-end gap-2 align-items-center">
                        <span id="filterLoading" class="d-none align-items-center text-muted small">
                            <span class="spinner-border spinner-border-sm me-2 text-primary" role="status"></span>Memuat data...
                        </span>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div><!-- End Filter Panel -->

{{-- ============================================================
     RINGKASAN (STAT CARDS)
     ============================================================ --}}
<div class="row g-3 mb-4">

    <div class="col-lg-3 col-md-6">
        <div class="card info-card revenue-card h-100">
            <div class="card-body">
                <h5 class="card-title">Total Transaksi
                    <span>| {{ $stats['periodLabel'] }}</span>
                </h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="ps-3">
                        <h6>{{ $stats['totalTransactions'] ?? $stats['total'] ?? 0 }}</h6>
                        <span class="text-muted small pt-2 ps-1">Semua Status</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card info-card sales-card h-100">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between align-items-start">
                    Total Pengeluaran
                    <span>| {{ $stats['periodLabel'] }}</span>
                    <a class="text-muted" href="#" data-bs-toggle="dropdown" aria-expanded="false"
                       title="Ganti periode">
                        <i class="bi bi-three-dots"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end period-menu">
                        <li><h6 class="dropdown-header">Periode</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('day')">Hari Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('month')">Bulan Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('year')">Tahun Ini</a></li>
                    </ul>
                </h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Rp {{ number_format($stats['totalExpense'], 0, ',', '.') }}</h6>
                        <span class="text-muted small pt-2 ps-1">Approved + Completed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card info-card customers-card h-100">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between align-items-start">
                    Rata-rata Nilai Permohonan
                    <span>| {{ $stats['periodLabel'] }}</span>
                    <a class="text-muted" href="#" data-bs-toggle="dropdown" aria-expanded="false"
                       title="Ganti periode">
                        <i class="bi bi-three-dots"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end period-menu">
                        <li><h6 class="dropdown-header">Periode</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('day')">Hari Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('month')">Bulan Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('year')">Tahun Ini</a></li>
                    </ul>
                </h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Rp {{ number_format($stats['avgRequestValue'] ?? 0, 0, ',', '.') }}</h6>
                        <span class="text-success small pt-1 fw-bold">Per Permohonan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card info-card transactions-card h-100">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between align-items-start">
                    Rata-rata Frekuensi
                    <span>| {{ $stats['periodLabel'] }}</span>
                    <a class="text-muted" href="#" data-bs-toggle="dropdown" aria-expanded="false"
                       title="Ganti periode">
                        <i class="bi bi-three-dots"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end period-menu">
                        <li><h6 class="dropdown-header">Periode</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('day')">Hari Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('month')">Bulan Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updatePeriod('year')">Tahun Ini</a></li>
                    </ul>
                </h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="ps-3">
                        <h6>{{ $stats['avgRequestFrequency']['value'] }}</h6>
                        <span class="text-muted small pt-2 ps-1">{{ $stats['avgRequestFrequency']['label'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.row stat cards -->

{{-- ============================================================
     TREN & DISTRIBUSI
     ============================================================ --}}
<div class="d-flex align-items-center gap-2 mb-2">
    <h6 class="section-title mb-0"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Analisis Pengeluaran</h6>
    <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-calendar3 me-1"></i>{{ $stats['periodLabel'] }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end period-menu">
            <li><a class="dropdown-item" href="#" onclick="updatePeriod('day')">Hari Ini</a></li>
            <li><a class="dropdown-item" href="#" onclick="updatePeriod('month')">Bulan Ini</a></li>
            <li><a class="dropdown-item" href="#" onclick="updatePeriod('year')">Tahun Ini</a></li>
        </ul>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card chart-card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">
                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                    Tren Pengeluaran
                </h5>
                <div class="flex-grow-1" style="min-height: 300px; position: relative;">
                    <canvas id="expenseLineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">
                    <i class="bi bi-pie-chart-fill text-success me-2"></i>
                    Distribusi Jenis BBM
                </h5>
                <div style="min-height: 240px; position: relative;">
                    <canvas id="gasolinePieChart"></canvas>
                </div>
                <div class="mt-3">
                    @php
                        $gasolineData = $stats['gasolineExpenseDistribution'] ?? [];
                        $gasolineTotal = array_sum($gasolineData);
                    @endphp
                    @foreach($gasolineData as $type => $amount)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">{{ $type }}</span>
                        <span class="badge bg-primary">
                            Rp {{ number_format($amount, 0, ',', '.') }}
                            ({{ $gasolineTotal > 0 ? round(($amount / $gasolineTotal) * 100, 1) : 0 }}%)
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div><!-- /.row chart 1 -->

{{-- ============================================================
     RANKING TOP 10
     ============================================================ --}}
<div class="card chart-card h-100 shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong><i class="bi bi-trophy-fill me-2 text-warning"></i>Top 10 Konsumsi BBM</strong>
        <div class="btn-group btn-group-sm" role="group" aria-label="Mode ranking">
            <button type="button" class="btn btn-outline-primary active" id="rankingVehicleBtn"
                    onclick="switchRanking('vehicle')">
                <i class="bi bi-truck me-1"></i>Per Kendaraan
            </button>
            <button type="button" class="btn btn-outline-primary" id="rankingDriverBtn"
                    onclick="switchRanking('driver')">
                <i class="bi bi-person-badge me-1"></i>Per Driver
            </button>
        </div>
    </div>
    <div class="card-body" style="min-height: 320px; position: relative;">
        <canvas id="rankingChart"></canvas>
    </div>
</div>

{{-- ============================================================
     TABEL PIVOT: DATA LENGKAP
     ============================================================ --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong><i class="bi bi-table me-2"></i>Tabel Pivot: Data Lengkap</strong>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" class="form-control form-control-sm" id="pivotSearch"
                   placeholder="Cari..." style="width: 200px;">
            <button class="btn btn-success btn-sm" onclick="exportTableToCSV('pivotTable', 'pivot_data.csv')">
                <i class="bi bi-file-excel me-1"></i>Export CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="pivotTable">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Driver</th>
                        <th>Kendaraan</th>
                        <th>Tgl Pengajuan</th>
                        <th class="text-end">Nominal</th>
                        <th class="text-center">Status</th>
                        <th>Tgl Pengisian</th>
                        <th class="text-center">Volume</th>
                    </tr>
                </thead>
                <tbody id="pivotTableBody">
                    @foreach($pivotData as $row)
                    <tr>
                        <td><small class="text-primary">{{ $row['requestNumber'] }}</small></td>
                        <td>{{ $row['driverName'] }}</td>
                        <td><small>{{ $row['vehicleDisplay'] }}</small></td>
                        <td><small>{{ $row['requestAt']->format('d/m/Y') }}</small></td>
                        <td class="text-end"><strong>Rp {{ number_format($row['nominal'], 0, ',', '.') }}</strong></td>
                        <td class="text-center">
                            @if($row['status'] == 'completed')
                                <span class="badge bg-primary">{{ $row['status'] }}</span>
                            @elseif($row['status'] == 'approved')
                                <span class="badge bg-success">{{ $row['status'] }}</span>
                            @elseif($row['status'] == 'rejected')
                                <span class="badge bg-danger">{{ $row['status'] }}</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ $row['status'] }}</span>
                            @endif
                        </td>
                        <td><small>{{ $row['tanggalPengisian'] ? $row['tanggalPengisian']->format('d/m/Y') : '-' }}</small></td>
                        <td class="text-center">{{ $row['volumePengisian'] > 0 ? $row['volumePengisian'] . ' L' : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2">
            <span class="text-muted small">
                Menampilkan {{ $pivotData->firstItem() ?? 0 }} – {{ $pivotData->lastItem() ?? 0 }}
                dari {{ $pivotData->total() }} transaksi
            </span>
            <div>{{ $pivotData->links() }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     STATISTIK PER DRIVER
     ============================================================ --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong><i class="bi bi-person-check me-2"></i>Statistik Detail per Driver</strong>
        <input type="text" class="form-control form-control-sm" id="searchDriver"
               placeholder="Cari driver..." style="max-width: 250px;">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="driverStatsTable">
                <thead class="table-light">
                    <tr>
                        <th>Driver</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Rejected</th>
                        <th class="text-center">Completed</th>
                        <th class="text-center">Approved</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">% Pelaporan</th>
                        <th class="text-end">Rata-rata Nominal</th>
                    </tr>
                </thead>
                <tbody id="driverStatsBody">
                    @foreach($driverStats as $driver)
                    <tr>
                        <td><strong>{{ $driver['driver'] }}</strong></td>
                        <td class="text-center"><span class="badge bg-primary">{{ $driver['total'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger">{{ $driver['rejected'] }}</span></td>
                        <td class="text-center"><span class="badge bg-success">{{ $driver['completed'] }}</span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark">{{ $driver['approved'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info text-dark">{{ $driver['pending'] }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $driver['reportRate'] }}%</span></td>
                        <td class="text-end">
                            <span class="text-success fw-bold"
                                  title="Max: Rp {{ number_format($driver['maxNominal'], 0, ',', '.') }} | Min: Rp {{ number_format($driver['minNominal'], 0, ',', '.') }}"
                                  style="cursor: help;">
                                Rp {{ number_format($driver['avgNominal'], 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2">
            <span class="text-muted small">
                Menampilkan {{ $driverStats->firstItem() ?? 0 }} – {{ $driverStats->lastItem() ?? 0 }}
                dari {{ $driverStats->total() }} driver
            </span>
            <div>{{ $driverStats->links() }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     PERFORMA KM KENDARAAN
     ============================================================ --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <strong><i class="bi bi-speedometer2 me-2"></i>Performa KM Kendaraan</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kendaraan</th>
                        <th class="text-center">Rata-rata KM</th>
                        <th class="text-center">Total Trip</th>
                        <th class="text-center">Max KM</th>
                        <th class="text-center">Min KM</th>
                        <th class="text-end">Odometer Terakhir</th>
                        <th class="text-end">Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kmPerformance as $vehicle)
                    <tr>
                        <td><strong>{{ $vehicle['vehicleDisplay'] }}</strong></td>
                        <td class="text-center"><span class="badge bg-primary">{{ $vehicle['avgKmDiff'] }} km</span></td>
                        <td class="text-center">{{ $vehicle['totalTrips'] }}</td>
                        <td class="text-center">{{ $vehicle['maxKm'] }} km</td>
                        <td class="text-center">{{ $vehicle['minKm'] }} km</td>
                        <td class="text-end"><strong>{{ number_format($vehicle['latestOdo'], 0, ',', '.') }}</strong> km</td>
                        <td class="text-end text-muted small">{{ $vehicle['lastUpdate']->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2">
            <span class="text-muted small">
                Menampilkan {{ $kmPerformance->firstItem() ?? 0 }} – {{ $kmPerformance->lastItem() ?? 0 }}
                dari {{ $kmPerformance->total() }} kendaraan
            </span>
            <div>{{ $kmPerformance->links() }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     PDF LOADING OVERLAY
     ============================================================ --}}
<div id="pdfLoadingOverlay" style="
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    flex-direction: column;
">
    <div style="
        background: #fff;
        border-radius: 16px;
        padding: 40px 48px;
        text-align: center;
        box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        min-width: 320px;
    ">
        <div style="margin-bottom: 20px;">
            <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"
                 style="animation: pdfIconPulse 1.5s ease-in-out infinite;">
                <rect width="56" height="56" rx="12" fill="#EEF2FF"/>
                <path d="M14 12h20l10 10v24H14V12z" stroke="#1a56db" stroke-width="2" fill="#fff"/>
                <path d="M34 12v10h10" stroke="#1a56db" stroke-width="2" fill="none"/>
                <rect x="19" y="26" width="18" height="2" rx="1" fill="#1a56db" opacity="0.5"/>
                <rect x="19" y="31" width="14" height="2" rx="1" fill="#1a56db" opacity="0.5"/>
                <rect x="19" y="36" width="10" height="2" rx="1" fill="#1a56db" opacity="0.3"/>
            </svg>
        </div>
        <div style="font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 6px; font-family: Arial, sans-serif;">
            Memuat Dokumen Laporan<span id="pdfLoadingDots"></span>
        </div>
        <div style="font-size: 13px; color: #64748b; margin-bottom: 24px; font-family: Arial, sans-serif;">
            Sedang memproses data transaksi BBM...
        </div>
        <div style="width:100%;height:8px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:14px;">
            <div id="pdfProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#1a56db,#60a5fa);border-radius:99px;transition:width 0.3s ease;"></div>
        </div>
        <div id="pdfProgressLabel" style="font-size: 12px; color: #94a3b8; font-family: Arial, sans-serif;">0%</div>
    </div>
</div>

{{-- ============================================================
     MODAL: CETAK LAPORAN TRANSAKSI BBM
     ============================================================ --}}
<div class="modal fade" id="printReportModal" tabindex="-1" aria-labelledby="printReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="printReportModalLabel">
                    <i class="bi bi-printer-fill me-2"></i>Cetak Laporan Transaksi BBM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('superadmin.print-report') }}" method="GET" id="formPrintReport">
                <div class="modal-body">

                    <div class="alert alert-info border-0 shadow-sm">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <small>Pilih rentang tanggal dan status transaksi yang ingin dicetak dalam laporan.</small>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="print_start_date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-event text-primary me-2"></i>Tanggal Mulai
                            </label>
                            <input type="date" class="form-control form-control-lg"
                                   id="print_start_date" name="start_date" required max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="print_end_date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-check text-success me-2"></i>Tanggal Akhir
                            </label>
                            <input type="date" class="form-control form-control-lg"
                                   id="print_end_date" name="end_date" required max="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">
                            <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Berdasarkan Status Transaksi
                        </label>
                        <div class="alert alert-warning border-0 mb-3">
                            <small>
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Catatan:</strong> Minimal pilih satu status. Jika tidak ada yang dipilih,
                                sistem akan otomatis mencetak <strong>Approved</strong> dan <strong>Completed</strong>.
                            </small>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check form-check-inline border rounded p-3 w-100">
                                    <input class="form-check-input me-2" type="checkbox" name="status[]"
                                           value="approved" id="statusApproved" checked>
                                    <label class="form-check-label w-100" for="statusApproved">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div><span class="badge bg-success me-2">✓</span><strong>Approved</strong></div>
                                            <small class="text-muted">Disetujui Admin</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline border rounded p-3 w-100">
                                    <input class="form-check-input me-2" type="checkbox" name="status[]"
                                           value="completed" id="statusCompleted" checked>
                                    <label class="form-check-label w-100" for="statusCompleted">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div><span class="badge bg-primary me-2">✓</span><strong>Completed</strong></div>
                                            <small class="text-muted">Sudah Lapor</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline border rounded p-3 w-100">
                                    <input class="form-check-input me-2" type="checkbox" name="status[]"
                                           value="pending" id="statusPending">
                                    <label class="form-check-label w-100" for="statusPending">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div><span class="badge bg-warning text-dark me-2">⏳</span><strong>Pending</strong></div>
                                            <small class="text-muted">Menunggu Approval</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline border rounded p-3 w-100">
                                    <input class="form-check-input me-2" type="checkbox" name="status[]"
                                           value="rejected" id="statusRejected">
                                    <label class="form-check-label w-100" for="statusRejected">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div><span class="badge bg-danger me-2">✗</span><strong>Rejected</strong></div>
                                            <small class="text-muted">Ditolak</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllStatus(true)">
                                <i class="bi bi-check-all"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllStatus(false)">
                                <i class="bi bi-x-circle"></i> Hapus Semua
                            </button>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-fuel-pump text-danger me-2"></i>Filter Jenis BBM (Opsional)
                        </label>
                        <select class="form-select" name="gasoline_type">
                            <option value="">Semua Jenis BBM</option>
                            @foreach(['Pertamax', 'Pertamina Dex', 'Pertalite'] as $gt)
                            <option value="{{ $gt }}">{{ $gt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-check text-info me-2"></i>Diapprove Oleh (Opsional)
                        </label>
                        <select class="form-select" name="authorizer_id">
                            <option value="">-- Semua Approver --</option>
                            @foreach($approvers as $approver)
                            <option value="{{ $approver->id }}">
                                {{ $approver->nama_lengkap }} ({{ ucfirst($approver->role) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-warning border-0 shadow-sm mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <small><strong>Perhatian:</strong> Laporan akan langsung dihasilkan sebagai file PDF dan dibuka di tab baru.</small>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary shadow-sm" name="export_type" value="pdf">
                        <i class="bi bi-file-pdf me-1"></i>Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================
     SCRIPTS
     ============================================================ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"
     integrity="sha384-9MhbyIRcBVQiiC7FSd7T38oJNj2Zh+EfxS7/vjhBi4OOT78NlHSnzM31EZRWR1LZ"
     crossorigin="anonymous"></script>

<script>
(function () {
    'use strict';

    // ── Data dari server ──
    const statsData      = @json($stats);
    const driverStatsData = @json($driverStats);

    // ── Chart instances ──
    let expenseLineChart = null;
    let gasolinePieChart = null;
    let rankingChart     = null;

    // ================================================================
    // INIT
    // ================================================================
    document.addEventListener('DOMContentLoaded', function () {
        renderExpenseLineChart();
        renderGasolinePieChart();
        renderRankingChart('vehicle');
        initializeSearch();
        updateFilterBadge();
    });

    // ================================================================
    // LINE CHART: TREN PENGELUARAN
    // ================================================================
    function renderExpenseLineChart() {
        const ctx = document.getElementById('expenseLineChart');
        if (!ctx) return;

        const data = statsData.expenseOverTime || {};
        if (Object.keys(data).length === 0) return;

        if (expenseLineChart) expenseLineChart.destroy();

        expenseLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Pengeluaran (Rp)',
                    data: Object.values(data),
                    fill: true,
                    backgroundColor: 'rgba(54, 162, 235, 0.15)',
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: val => 'Rp ' + (val / 1000000).toFixed(1) + 'Jt' }
                    }
                }
            }
        });
    }

    // ================================================================
    // PIE CHART: DISTRIBUSI JENIS BBM
    // ================================================================
    function renderGasolinePieChart() {
        const ctx = document.getElementById('gasolinePieChart');
        if (!ctx) return;

        const data = statsData.gasolineExpenseDistribution || {};
        if (Object.keys(data).length === 0) return;

        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)'
        ];

        if (gasolinePieChart) gasolinePieChart.destroy();

        gasolinePieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    data: Object.values(data),
                    backgroundColor: colors.slice(0, Object.keys(data).length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val   = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct   = ((val / total) * 100).toFixed(1);
                                return `${context.label}: Rp ${val.toLocaleString('id-ID')} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ================================================================
    // BAR CHART: TOP 10 KONSUMSI (Kendaraan / Driver)
    // ================================================================
    function renderRankingChart(mode) {
        const vehicleBtn = document.getElementById('rankingVehicleBtn');
        const driverBtn  = document.getElementById('rankingDriverBtn');
        if (vehicleBtn) vehicleBtn.classList.toggle('active', mode === 'vehicle');
        if (driverBtn)  driverBtn.classList.toggle('active', mode === 'driver');

        const ctx = document.getElementById('rankingChart');
        if (!ctx) return;

        const raw = mode === 'vehicle'
            ? (statsData.vehicleExpense || {})
            : (statsData.driverExpense || {});

        if (Object.keys(raw).length === 0) {
            if (rankingChart) { rankingChart.destroy(); rankingChart = null; }
            ctx.parentElement.innerHTML = '<p class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data</p>';
            return;
        }

        const sorted = Object.entries(raw).sort((a, b) => b[1] - a[1]).slice(0, 10);
        const color  = mode === 'vehicle'
            ? { bg: 'rgba(255, 159, 64, 0.8)',  border: 'rgba(255, 159, 64, 1)' }
            : { bg: 'rgba(153, 102, 255, 0.8)', border: 'rgba(153, 102, 255, 1)' };

        if (rankingChart) rankingChart.destroy();

        rankingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(i => i[0]),
                datasets: [{
                    label: 'Pengeluaran (Rp)',
                    data: sorted.map(i => i[1]),
                    backgroundColor: color.bg,
                    borderColor: color.border,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.parsed.x.toLocaleString('id-ID') } }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: val => 'Rp ' + (val / 1000000).toFixed(1) + 'Jt' }
                    }
                }
            }
        });
    }

    // ================================================================
    // SEARCH (client-side)
    // ================================================================
    function initializeSearch() {
        const driverSearch = document.getElementById('searchDriver');
        if (driverSearch) {
            driverSearch.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#driverStatsBody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }

        const pivotSearch = document.getElementById('pivotSearch');
        if (pivotSearch) {
            pivotSearch.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#pivotTableBody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }
    }

    // ================================================================
    // FILTER BADGE
    // ================================================================
    function updateFilterBadge() {
        const params = new URLSearchParams(window.location.search);
        let active = 0;
        params.forEach((val, key) => {
            if (key !== 'period' && val !== '') active++;
        });

        const badge = document.getElementById('filterBadge');
        if (badge) {
            if (active > 0) {
                badge.textContent = `${active} filter aktif`;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }
    }

    // ================================================================
    // PDF OVERLAY
    // ================================================================
    function initPdfOverlay() {
        var dotsEl, dotsInterval, dotsPhase = 0;
        var dotStates = ['.', '..', '...', '..', '.'];

        function startDots() {
            dotsEl = document.getElementById('pdfLoadingDots');
            dotsPhase = 0;
            dotsInterval = setInterval(function () {
                dotsPhase = (dotsPhase + 1) % dotStates.length;
                if (dotsEl) dotsEl.textContent = dotStates[dotsPhase];
            }, 400);
        }
        function stopDots() { clearInterval(dotsInterval); }

        var progressEl, progressLabelEl, currentProgress = 0, slowCrawlInterval;

        function setProgress(val) {
            val = Math.round(val);
            if (progressEl)      progressEl.style.width  = val + '%';
            if (progressLabelEl) progressLabelEl.textContent = val + '%';
        }

        function animateProgress() {
            progressEl      = document.getElementById('pdfProgressBar');
            progressLabelEl = document.getElementById('pdfProgressLabel');
            currentProgress = 0;

            var checkpoints = [
                { target: 15, delay: 300  },
                { target: 30, delay: 700  },
                { target: 55, delay: 1400 },
                { target: 72, delay: 2200 },
                { target: 85, delay: 3200 },
            ];
            var step = 0;

            function runStep() {
                if (step >= checkpoints.length) {
                    slowCrawlInterval = setInterval(function () {
                        if (currentProgress < 94) {
                            currentProgress += 0.4;
                            setProgress(Math.min(currentProgress, 94));
                        } else {
                            clearInterval(slowCrawlInterval);
                        }
                    }, 400);
                    return;
                }
                var cp = checkpoints[step];
                setTimeout(function () {
                    currentProgress = cp.target;
                    setProgress(currentProgress);
                    step++;
                    runStep();
                }, cp.delay);
            }
            runStep();
        }

        function completeProgress() {
            clearInterval(slowCrawlInterval);
            setProgress(100);
        }

        function showOverlay() {
            var el = document.getElementById('pdfLoadingOverlay');
            if (el) el.style.display = 'flex';
            startDots();
            animateProgress();
        }

        function hideOverlay() {
            completeProgress();
            stopDots();
            setTimeout(function () {
                var el = document.getElementById('pdfLoadingOverlay');
                if (el) el.style.display = 'none';
                currentProgress = 0;
                setProgress(0);
            }, 800);
        }

        function getCookie(name) {
            var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
        }
        function deleteCookie(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        }

        function startPolling(token) {
            var cookieName = 'download_ready_' + token;
            var poll = setInterval(function () {
                if (getCookie(cookieName) !== null) {
                    clearInterval(poll);
                    deleteCookie(cookieName);
                    hideOverlay();
                }
            }, 500);
            setTimeout(function () { clearInterval(poll); hideOverlay(); }, 60000);
        }

        var form = document.getElementById('formPrintReport');
        if (!form) return;
        form.addEventListener('submit', function () {
            var token = Math.random().toString(36).substring(2, 11) + Date.now().toString(36);
            var tokenInput = form.querySelector('input[name="download_token"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'download_token';
                form.appendChild(tokenInput);
            }
            tokenInput.value = token;

            var modalEl = document.getElementById('printReportModal');
            if (modalEl && window.bootstrap) {
                var bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
            }
            showOverlay();
            startPolling(token);
        });
    }
    document.addEventListener('DOMContentLoaded', initPdfOverlay);

    // ── Expose global helpers ──
    window.switchRanking    = mode => renderRankingChart(mode);
    window.updatePeriod     = period => window.location.href = `{{ route('superadmin.dashboard.bisnis') }}?period=${period}`;
    window.resetFilters     = () => {
        const period = new URLSearchParams(window.location.search).get('period');
        const qs = period && /^[a-z]+$/.test(period) ? `?period=${period}` : '';
        const form = document.getElementById('filterForm');
        if (form) form.reset();
        window.location.href = '{{ route('superadmin.dashboard.bisnis') }}' + qs;
    };
    window.toggleAllStatus  = checked => document.querySelectorAll('input[name="status[]"]').forEach(cb => cb.checked = checked);

    // ================================================================
    // REALTIME FILTER (auto-submit saat filter berubah, tanpa tombol)
    // ================================================================
    window.dashboardFilter = {
        _timer: null,

        submit() {
            const form = document.getElementById('filterForm');
            if (!form) return;

            clearTimeout(this._timer);
            this._timer = setTimeout(() => {
                const loading = document.getElementById('filterLoading');
                if (loading) {
                    loading.classList.remove('d-none');
                    loading.classList.add('d-inline-flex');
                }
                form.submit();
            }, 350);
        }
    };

    window.exportTableToCSV  = function (tableId, filename) {
        const table = document.getElementById(tableId);
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = [];
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.style.display !== 'none') {
                rows.push(Array.from(row.querySelectorAll('td')).map(td => '"' + td.textContent.trim().replace(/"/g, '""') + '"').join(','));
            }
        });
        const blob = new Blob([[headers.join(','), ...rows].join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href     = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    };

    // Toggle label tombol filter
    const collapseEl = document.getElementById('advancedFilter');
    const toggleBtn  = document.querySelector('button[data-bs-target="#advancedFilter"]');
    if (collapseEl && toggleBtn) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            toggleBtn.innerHTML = '<i class="bi bi-chevron-up me-1"></i> Sembunyikan Filter';
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            toggleBtn.innerHTML = '<i class="bi bi-chevron-down me-1"></i> Tampilkan Filter';
        });
    }
})();
</script>

@endsection