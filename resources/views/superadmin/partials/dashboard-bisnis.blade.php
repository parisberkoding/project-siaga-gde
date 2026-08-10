@extends('templates.main')

@section('title', $title)

@section('content')
<style>
    /* ===== INFO CARDS ===== */
    .info-card { padding-bottom: 10px; }
    .info-card h6 {
        font-size: 28px;
        color: #012970;
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
    }
    .sales-card .card-icon    { color: #4154f1; background: #f6f6fe; }
    .revenue-card .card-icon  { color: #2eca6a; background: #e0f8e9; }
    .customers-card .card-icon { color: #ff771d; background: #ffecdf; }

    /* ===== DROPDOWN FILTER (3-dot) ===== */
    .filter {
        position: absolute;
        right: 0px;
        top: 15px;
    }
    .filter .icon {
        color: #aab7cf;
        padding-right: 20px;
        padding-bottom: 5px;
        transition: 0.3s;
        font-size: 16px;
    }
    .filter .icon:hover,
    .filter .icon:focus { color: #4154f1; }
    .filter .dropdown-menu {
        border-radius: 4px;
        padding: 10px 0;
        animation-name: dropdown-animate;
        animation-duration: 0.2s;
        animation-fill-mode: both;
        border: 0;
        box-shadow: 0 5px 30px 0 rgba(82, 63, 105, 0.2);
    }
    .filter .dropdown-menu li { padding: 0 10px; }
    .filter .dropdown-menu li a {
        padding: 10px 15px;
        border-radius: 4px;
        color: #212529;
        font-size: 14px;
    }
    .filter .dropdown-menu li a:hover { background-color: #f6f9ff; }
    @keyframes dropdown-animate {
        0%   { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* ===== ADVANCED FILTER PANEL ===== */
    #advancedFilter .form-control:focus,
    #advancedFilter .form-select:focus {
        border-color: #4154f1;
        box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.15);
    }
    .form-check-inline { transition: all 0.3s ease; cursor: pointer; }
    .form-check-inline:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .form-check-inline input:checked + label { color: #4154f1; font-weight: 600; }

    /* Toggle chevron animation */
    .toggle-filter-btn .bi-chevron-down {
        transition: transform 0.3s ease;
        display: inline-block;
    }
    .toggle-filter-btn[aria-expanded="false"] .bi-chevron-down {
        transform: rotate(-90deg);
    }

    /* ===== BADGE ANIMATION ===== */
    #filterBadge { animation: fadeInScale 0.3s ease; }
    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.8); }
        to   { opacity: 1; transform: scale(1); }
    }

    /* ===== TABS ===== */
    .dashboard-tabs .nav-link {
        color: #6c757d;
        font-weight: 500;
        padding: 0.6rem 1.4rem;
        border-radius: 6px 6px 0 0;
    }
    .dashboard-tabs .nav-link.active {
        color: #4154f1;
        font-weight: 700;
        border-color: #dee2e6 #dee2e6 #fff;
        background: #fff;
    }
    .dashboard-tabs .nav-link:hover:not(.active) { color: #4154f1; }

    /* ===== CHARTS SECTION ===== */
    .chart-card { border-radius: 10px; }
    .chart-card canvas { max-height: 340px; }

    /* ===== TABLES ===== */
    .card { border-radius: 10px; }
    .card-header { border-radius: 10px 10px 0 0 !important; }
    .table-hover tbody tr:hover { background-color: rgba(0,123,255,0.05); }

    /* ===== PRINT ===== */
    @media print {
        .btn, input, select, .dashboard-tabs { display: none !important; }
        .tab-pane { display: block !important; opacity: 1 !important; }
    }

    /* ===== MISC ===== */
    .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .trip-checkbox:checked { background-color: #0d6efd; border-color: #0d6efd; }

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

{{-- ============================================================
     TAB NAVIGATION
     ============================================================ --}}
<ul class="nav nav-tabs dashboard-tabs mb-3" id="dashboardMainTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab != 'tabel' ? 'active' : '' }}" id="tab-grafik" data-bs-toggle="tab"
                data-bs-target="#pane-grafik" type="button" role="tab">
            <i class="bi bi-bar-chart-line-fill me-2"></i>Grafik & Ringkasan
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab == 'tabel' ? 'active' : '' }}" id="tab-tabel" data-bs-toggle="tab"
                data-bs-target="#pane-tabel" type="button" role="tab">
            <i class="bi bi-table me-2"></i>Tabel Detail
        </button>
    </li>
</ul>

<div class="tab-content" id="dashboardMainTabContent">

    {{-- ============================================================
         TAB 1 — GRAFIK & RINGKASAN
         ============================================================ --}}
    <div class="tab-pane fade {{ $tab != 'tabel' ? 'show active' : '' }}" id="pane-grafik" role="tabpanel">

        {{-- Filter Panel --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">
                    <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Data Dashboard
                    <span id="filterBadge" class="badge bg-primary ms-2 d-none"></span>
                </h5>
                <button class="btn btn-sm btn-outline-secondary toggle-filter-btn" type="button"
                        data-bs-toggle="collapse" data-bs-target="#advancedFilter"
                        aria-expanded="true" aria-controls="advancedFilter">
                    <i class="bi bi-chevron-down me-1"></i> Toggle Filter
                </button>
            </div>

            <div class="collapse show" id="advancedFilter">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('superadmin.dashboard.bisnis') }}">
                        <input type="hidden" name="tab" id="activeTabInput" value="{{ $tab }}">
                        <div class="row g-3">

                            {{-- Tanggal Mulai --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar-range text-primary"></i> Tanggal Mulai
                                </label>
                                <input type="date" class="form-control" name="start_date"
                                       id="filter_start_date"
                                       value="{{ request('start_date') }}" max="{{ date('Y-m-d') }}">
                            </div>

                            {{-- Tanggal Akhir --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar-check text-success"></i> Tanggal Akhir
                                </label>
                                <input type="date" class="form-control" name="end_date"
                                       id="filter_end_date"
                                       value="{{ request('end_date') }}" max="{{ date('Y-m-d') }}">
                            </div>

                            {{-- Status Transaksi --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-flag text-warning"></i> Status Transaksi
                                </label>
                                <select class="form-select" name="status" id="filter_status">
                                    <option value="">Semua Status</option>
                                    <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                                    <option value="approved"  {{ request('status') == 'approved'  ? 'selected' : '' }}>Approved</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="rejected"  {{ request('status') == 'rejected'  ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>

                            {{-- Jenis BBM --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-fuel-pump text-danger"></i> Jenis BBM
                                </label>
                                <select class="form-select" name="gasoline_type" id="filter_gasoline">
                                    <option value="">Semua Jenis BBM</option>
                                    <option value="Pertamax"      {{ request('gasoline_type') == 'Pertamax'      ? 'selected' : '' }}>Pertamax</option>
                                    <option value="Pertamina Dex" {{ request('gasoline_type') == 'Pertamina Dex' ? 'selected' : '' }}>Pertamina Dex</option>
                                    <option value="Pertalite"     {{ request('gasoline_type') == 'Pertalite'     ? 'selected' : '' }}>Pertalite</option>
                                </select>
                            </div>

                            {{-- Kendaraan --}}
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-truck text-info"></i> Kendaraan
                                </label>
                                <select class="form-select" name="vehicle_id" id="filter_vehicle">
                                    <option value="">Semua Kendaraan</option>
                                    @foreach($vehicles ?? [] as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                            {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Driver / Pemohon --}}
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-person-badge text-secondary"></i> Driver/Pemohon
                                </label>
                                <select class="form-select" name="requester_id" id="filter_requester">
                                    <option value="">Semua Driver</option>
                                    @foreach($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}"
                                            {{ request('requester_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->nama_lengkap }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Rentang Nominal --}}
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-cash-stack text-success"></i> Rentang Nominal
                                </label>
                                <select class="form-select" name="nominal_range" id="filter_nominal">
                                    <option value="">Semua Nominal</option>
                                    <option value="0-100000"       {{ request('nominal_range') == '0-100000'       ? 'selected' : '' }}>&lt; Rp 100.000</option>
                                    <option value="100000-300000"  {{ request('nominal_range') == '100000-300000'  ? 'selected' : '' }}>Rp 100.000 – Rp 300.000</option>
                                    <option value="300000-500000"  {{ request('nominal_range') == '300000-500000'  ? 'selected' : '' }}>Rp 300.000 – Rp 500.000</option>
                                    <option value="500000+"        {{ request('nominal_range') == '500000+'        ? 'selected' : '' }}>&gt; Rp 500.000</option>
                                </select>
                            </div>

                        </div><!-- /.row -->

                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div><!-- End Filter Panel -->

        {{-- Tombol Cetak (superadmin only) --}}
        @if(Auth::user()->role === 'superadmin')
        <div class="text-center mb-4">
            <button class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#printReportModal">
                <i class="bi bi-printer-fill me-2"></i>Cetak Laporan Transaksi BBM
            </button>
        </div>
        @endif

        {{-- ---- STAT CARDS ---- --}}
        <div class="row g-3 mb-4">

            {{-- Rata-rata Frekuensi --}}
            <div class="col-lg-3 col-md-6">
                <div class="card info-card sales-card h-100">
                    <div class="filter">
                        <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <li class="dropdown-header text-start"><h6>Filter</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('day',   'freq')">Hari Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('month', 'freq')">Bulan Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('year',  'freq')">Tahun Ini</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Rata-rata Frekuensi
                            <span id="freqPeriodLabel">| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="ps-3">
                                <h6 id="avgFrequencyValue">{{ $stats['avgRequestFrequency']['value'] }}</h6>
                                <span class="text-muted small pt-2 ps-1" id="avgFrequencyLabel">{{ $stats['avgRequestFrequency']['label'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rata-rata Nilai Permohonan --}}
            <div class="col-lg-3 col-md-6">
                <div class="card info-card revenue-card h-100">
                    <div class="filter">
                        <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <li class="dropdown-header text-start"><h6>Filter</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('day',   'avg')">Hari Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('month', 'avg')">Bulan Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('year',  'avg')">Tahun Ini</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Rata-rata Nilai Permohonan
                            <span id="avgPeriodLabel">| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="ps-3">
                                <h6 id="avgValueAmount">Rp {{ number_format($stats['avgRequestValue'] ?? 0, 0, ',', '.') }}</h6>
                                <span class="text-success small pt-1 fw-bold">Per Permohonan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Pengeluaran --}}
            <div class="col-lg-3 col-md-6">
                <div class="card info-card customers-card h-100">
                    <div class="filter">
                        <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <li class="dropdown-header text-start"><h6>Filter</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('day',   'total')">Hari Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('month', 'total')">Bulan Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updatePeriod('year',  'total')">Tahun Ini</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Total Pengeluaran
                            <span id="totalPeriodLabel">| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="ps-3">
                                <h6 id="totalExpenseAmount">Rp {{ number_format($stats['totalExpense'], 0, ',', '.') }}</h6>
                                <span class="text-muted small pt-2 ps-1">Approved + Completed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="col-lg-3 col-md-6">
                <div class="card info-card warning-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Transaksi
                            <span>| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
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

        </div><!-- /.row stat cards -->

        {{-- ---- CHART ROW 1: Tren Pengeluaran + Distribusi BBM ---- --}}
        <div class="row g-3 mb-4">

            {{-- Line Chart: Tren Pengeluaran --}}
            <div class="col-lg-8">
                <div class="card chart-card h-100">
                    <div class="filter">
                        <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <li class="dropdown-header text-start"><h6>Filter</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="updateChartPeriod('day')">Hari Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateChartPeriod('month')">Bulan Ini</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateChartPeriod('year')">Tahun Ini</a></li>
                        </ul>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                            Tren Pengeluaran
                            <span id="chartPeriodLabel">| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div class="flex-grow-1" style="min-height: 300px; position: relative;">
                            <canvas id="expenseLineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pie Chart: Distribusi Jenis BBM --}}
            <div class="col-lg-4">
                <div class="card chart-card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="bi bi-pie-chart-fill text-success me-2"></i>
                            Distribusi Jenis BBM
                            <span>| {{ $stats['periodLabel'] }}</span>
                        </h5>
                        <div style="min-height: 260px; position: relative;">
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

        {{-- ---- CHART ROW 2: Top 10 Kendaraan + Top 10 Driver ---- --}}
        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <div class="card chart-card h-100">
                    <div class="card-header bg-light">
                        <strong><i class="bi bi-truck me-2 text-warning"></i>Top 10 Konsumsi BBM per Kendaraan</strong>
                    </div>
                    <div class="card-body" style="min-height: 300px; position: relative;">
                        <canvas id="vehicleExpenseChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card chart-card h-100">
                    <div class="card-header bg-light">
                        <strong><i class="bi bi-person-badge me-2 text-info"></i>Top 10 Pengajuan Driver</strong>
                    </div>
                    <div class="card-body" style="min-height: 300px; position: relative;">
                        <canvas id="driverExpenseChart"></canvas>
                    </div>
                </div>
            </div>

        </div><!-- /.row chart 2 -->

    </div><!-- /#pane-grafik -->


    {{-- ============================================================
         TAB 2 — TABEL DETAIL
         ============================================================ --}}
    <div class="tab-pane fade {{ $tab == 'tabel' ? 'show active' : '' }}" id="pane-tabel" role="tabpanel">

        {{-- Statistik Detail per Driver --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong><i class="bi bi-table me-2"></i>Statistik Detail per Driver</strong>
                <input type="text" class="form-control form-control-sm" id="searchDriver"
                       placeholder="🔍 Cari driver..." style="max-width: 250px;">
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

        {{-- Performa KM Kendaraan --}}
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

        {{-- Tabel Pivot: Data Lengkap --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong><i class="bi bi-table me-2"></i>Tabel Pivot: Data Lengkap</strong>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" class="form-control form-control-sm" id="pivotSearch"
                           placeholder="🔍 Cari..." style="width: 200px;">
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

    </div><!-- /#pane-tabel -->

</div><!-- /.tab-content -->


{{-- ============================================================
     MODAL: Validasi Trip (single copy)
     ============================================================ --}}
<div class="modal fade" id="validateTripModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle-fill me-2"></i>Validasi Trip Analysis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formValidateTrip" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong id="modalTripInfo"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Apakah ada perjalanan jarak jauh?</label>
                        <select class="form-select" name="validation_status" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="confirmed_long_distance">✅ Ya, ada perjalanan jarak jauh (ke luar kota)</option>
                            <option value="confirmed_normal">✅ Tidak, hanya perjalanan normal (dalam kota)</option>
                            <option value="flagged">⚠️ Perlu investigasi lebih lanjut</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Admin (Opsional)</label>
                        <textarea class="form-control" name="admin_notes" rows="3"
                                  placeholder="Contoh: Trip ke Jakarta untuk pengiriman barang..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Simpan Validasi
                    </button>
                </div>
            </form>
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
     MODAL: Cetak Laporan Transaksi BBM
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
                            <small class="text-muted">Pilih tanggal awal periode laporan</small>
                        </div>
                        <div class="col-md-6">
                            <label for="print_end_date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-check text-success me-2"></i>Tanggal Akhir
                            </label>
                            <input type="date" class="form-control form-control-lg"
                                   id="print_end_date" name="end_date" required max="{{ date('Y-m-d') }}">
                            <small class="text-muted">Pilih tanggal akhir periode laporan</small>
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
                            <option value="Pertamax">Pertamax</option>
                            <option value="Pertamina Dex">Pertamina Dex</option>
                            <option value="Pertalite">Pertalite</option>
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
                        <small class="text-muted">Filter laporan berdasarkan siapa yang meng-approve</small>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
(function () {
    'use strict';

    // ── Data dari server ──
    const statsData      = @json($stats);
    const driverStatsData = @json($driverStats);

    // ── Chart instances ──
    let expenseLineChart   = null;
    let gasolinePieChart   = null;
    let vehicleExpenseChart = null;
    let driverExpenseChart  = null;

    // ================================================================
    // INIT
    // ================================================================
    document.addEventListener('DOMContentLoaded', function () {
        renderExpenseLineChart();
        renderGasolinePieChart();
        renderVehicleExpenseChart();
        renderDriverExpenseChart();
        initializeSearch();
        updateFilterBadge();
        initToggleFilterChevron();
        initPdfOverlay();
        initTabPersistence();
    });

    // ================================================================
    // TAB PERSISTENCE + CHART RESIZE
    // ================================================================
    function initTabPersistence() {
        const tabs = document.getElementById('dashboardMainTabs');
        const hiddenInput = document.getElementById('activeTabInput');
        if (!tabs || !hiddenInput) return;

        const charts = () => [expenseLineChart, gasolinePieChart, vehicleExpenseChart, driverExpenseChart].filter(Boolean);

        tabs.addEventListener('shown.bs.tab', function (event) {
            // Simpan tab aktif ke hidden input (ikut saat filter disubmit)
            if (hiddenInput) hiddenInput.value = event.target.dataset.bsTarget === '#pane-tabel' ? 'tabel' : 'grafik';
            // Chart di pane yang baru tampil perlu resize ulang agar tidak blank/pecah
            charts().forEach(function (chart) {
                if (typeof chart.resize === 'function') chart.resize();
            });
        });
    }

    // ================================================================
    // TOGGLE FILTER CHEVRON
    // ================================================================
    function initToggleFilterChevron() {
        const collapseEl = document.getElementById('advancedFilter');
        const btn = document.querySelector('.toggle-filter-btn');
        if (!collapseEl || !btn) return;

        collapseEl.addEventListener('show.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'true');
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

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
                        ticks: {
                            callback: val => 'Rp ' + (val / 1000000).toFixed(1) + 'Jt'
                        }
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
                    legend: { position: 'bottom' },
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
    // BAR CHART: TOP 10 KENDARAAN
    // ================================================================
    function renderVehicleExpenseChart() {
        const ctx = document.getElementById('vehicleExpenseChart');
        if (!ctx) return;

        const data = statsData.vehicleExpense || {};
        if (Object.keys(data).length === 0) {
            ctx.parentElement.innerHTML = '<p class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data</p>';
            return;
        }

        const sorted = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 10);

        if (vehicleExpenseChart) vehicleExpenseChart.destroy();

        vehicleExpenseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(i => i[0]),
                datasets: [{
                    label: 'Pengeluaran (Rp)',
                    data: sorted.map(i => i[1]),
                    backgroundColor: 'rgba(255, 159, 64, 0.8)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
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
    // BAR CHART: TOP 10 DRIVER
    // ================================================================
    function renderDriverExpenseChart() {
        const ctx = document.getElementById('driverExpenseChart');
        if (!ctx) return;

        const data = statsData.driverExpense || {};
        if (Object.keys(data).length === 0) {
            ctx.parentElement.innerHTML = '<p class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data</p>';
            return;
        }

        const sorted = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 10);

        if (driverExpenseChart) driverExpenseChart.destroy();

        driverExpenseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(i => i[0]),
                datasets: [{
                    label: 'Total Pengajuan (Rp)',
                    data: sorted.map(i => i[1]),
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
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
    // SEARCH
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
        params.forEach((val, key) => { if (key !== 'period' && val !== '') active++; });

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

        function setProgress(val) {
            val = Math.round(val);
            if (progressEl)      progressEl.style.width  = val + '%';
            if (progressLabelEl) progressLabelEl.textContent = val + '%';
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

    // ── Expose global helpers ──
    window.currentTab = () => {
        const input = document.getElementById('activeTabInput');
        return input ? input.value : 'grafik';
    };
    window.updatePeriod      = period => window.location.href = `{{ route('superadmin.dashboard.bisnis') }}?period=${period}&tab=${window.currentTab()}`;
    window.updateChartPeriod = period => window.location.href = `{{ route('superadmin.dashboard.bisnis') }}?period=${period}&tab=${window.currentTab()}`;
    window.resetFilters      = () => { document.getElementById('filterForm').reset(); window.location.href = '{{ route("superadmin.dashboard.bisnis") }}?tab=' + window.currentTab(); };
    window.toggleAllStatus   = checked => document.querySelectorAll('input[name="status[]"]').forEach(cb => cb.checked = checked);

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

    window.showValidationModal = function (validationId, driver, vehicle, distance) {
        const modal = new bootstrap.Modal(document.getElementById('validateTripModal'));
        document.getElementById('formValidateTrip').action = `/trip-validations/${validationId}/validate`;
        document.getElementById('modalTripInfo').textContent = `${driver} - ${vehicle}: ${distance.toLocaleString('id-ID')} KM`;
        modal.show();
    };

    window.filterTripTable = function () {
        const validation = document.getElementById('filterValidation')?.value || '';
        const distance   = document.getElementById('filterDistance')?.value   || '';
        const efficiency = document.getElementById('filterEfficiency')?.value || '';
        const search     = document.getElementById('searchTrip')?.value.toLowerCase() || '';

        document.querySelectorAll('#allTripsBody tr').forEach(row => {
            if (row.cells.length === 1) return;
            const needsValidation = row.getAttribute('data-needs-validation') === 'true';
            const isValidated     = row.getAttribute('data-validated') === 'validated';
            const rowDistance     = parseInt(row.getAttribute('data-distance'))    || 0;
            const rowEfficiency   = parseFloat(row.getAttribute('data-efficiency')) || 0;
            const rowText         = row.textContent.toLowerCase();
            let show = true;

            if (validation === 'not_required'    && needsValidation)              show = false;
            if (validation === 'needs_validation' && (!needsValidation || isValidated)) show = false;
            if (validation === 'validated'        && !isValidated)                show = false;
            if (distance === '0-100'   && (rowDistance < 0   || rowDistance > 100)) show = false;
            if (distance === '100-300' && (rowDistance < 100 || rowDistance > 300)) show = false;
            if (distance === '300-500' && (rowDistance < 300 || rowDistance > 500)) show = false;
            if (distance === '500+'    && rowDistance < 500)                        show = false;
            if (efficiency === 'good'  && (rowEfficiency < 8 || rowEfficiency > 15)) show = false;
            if (efficiency === 'low'   && rowEfficiency >= 8)                      show = false;
            if (efficiency === 'high'  && rowEfficiency <= 15)                     show = false;
            if (search && !rowText.includes(search))                               show = false;

            row.style.display = show ? '' : 'none';
        });
    };

    window.toggleCheckAll = function () {
        const checkAll = document.getElementById('checkAll');
        document.querySelectorAll('.trip-checkbox').forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') cb.checked = checkAll.checked;
        });
        window.updateBulkActions();
    };

    window.updateBulkActions = function () {
        const checked   = document.querySelectorAll('.trip-checkbox:checked');
        const container = document.getElementById('bulkActionsContainer');
        const countEl   = document.getElementById('selectedCount');
        if (countEl) countEl.textContent = checked.length;
        if (container) container.style.display = checked.length > 0 ? 'block' : 'none';
    };

    window.bulkValidate = function (isLongTrip) {
        const checked = Array.from(document.querySelectorAll('.trip-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) { alert('Pilih trip yang ingin divalidasi'); return; }
        const msg = isLongTrip ? 'Konfirmasi sebagai Perjalanan Dinas Jarak Jauh?' : 'Konfirmasi sebagai Perjalanan Normal?';
        if (!confirm(`${msg}\n\n${checked.length} trip akan divalidasi.`)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("superadmin.trip-validations.bulk-validate") }}';

        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        checked.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'validation_ids[]'; inp.value = id;
            form.appendChild(inp);
        });

        const ltInp = document.createElement('input');
        ltInp.type = 'hidden'; ltInp.name = 'is_long_trip'; ltInp.value = isLongTrip ? '1' : '0';
        form.appendChild(ltInp);

        document.body.appendChild(form);
        form.submit();
    };

})();
</script>

@endsection
