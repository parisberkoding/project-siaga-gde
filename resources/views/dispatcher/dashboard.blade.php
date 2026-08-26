@extends('templates.main')

@section('title', 'Dashboard Dispatcher')

@section('content')

<div class="pagetitle">
    <h1>Dashboard Dispatcher</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

{{-- Alert Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('info'))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- SUMMARY CARDS                                      --}}
{{-- ══════════════════════════════════════════════════ --}}
<section class="section">
    <div class="row">

        {{-- Overdue --}}
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Overdue</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                             style="background: #ffe2e5; color: #fa5a7d;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="color: #fa5a7d;">{{ $badges['overdue_count'] }}</h6>
                            <span class="text-muted small">Pengecekan terlambat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Due Today --}}
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Due Today</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                             style="background: #fff4de; color: #ffbb2c;">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="color: #ffbb2c;">{{ $badges['due_today_count'] }}</h6>
                            <span class="text-muted small">Harus dicek hari ini</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending Replacement --}}
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Permohonan Aktif</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                             style="background: #e0f8e9; color: #2eca6a;">
                            <i class="bi bi-wrench-adjustable"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="color: #2eca6a;">{{ $badges['pending_replacements'] }}</h6>
                            <span class="text-muted small">Menunggu approval</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recently Serviced --}}
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai Service</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                             style="background: #f6f6fe; color: #4154f1;">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="color: #4154f1;">{{ $badges['recently_serviced'] }}</h6>
                            <span class="text-muted small">Dalam 24 jam terakhir</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════════════ --}}
{{-- GENERATE PERIODE & QUICK ACTIONS                   --}}
{{-- ══════════════════════════════════════════════════ --}}
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                        {{-- Generate Periode
                        <div class="d-flex align-items-center gap-2">
                            @if($currentPeriodExists)
                                <span class="badge bg-success fs-6 fw-normal py-2 px-3">
                                    <i class="bi bi-check-lg me-1"></i>
                                    Periode {{ now()->translatedFormat('F Y') }} — {{ $currentPeriodCount }}/{{ $totalActiveVehicles }} kendaraan
                                </span>
                            @else
                                <form action="{{ route('dispatcher.generate-period') }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Generate periode {{ now()->translatedFormat('F Y') }} untuk {{ $totalActiveVehicles }} kendaraan aktif?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-calendar-plus me-1"></i> Generate Periode {{ now()->translatedFormat('F Y') }}
                                    </button>
                                </form>
                                <span class="text-muted small">{{ $totalActiveVehicles }} kendaraan aktif</span>
                            @endif
                        </div> --}}

                        {{-- Quick Actions --}}
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('dispatcher.daily-check.create') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-calendar-day me-1"></i> Cek Harian
                            </a>
                            <a href="{{ route('dispatcher.weekly-check.create') }}" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-calendar-week me-1"></i> Cek Mingguan
                            </a>
                            <a href="{{ route('dispatcher.monthly-check.create') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-calendar-month me-1"></i> Cek Bulanan
                            </a>
                            <a href="{{ route('dispatcher.body-check.create') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-car-front me-1"></i> Cek Body
                            </a>
                            <a href="{{ route('dispatcher.replacement.index') }}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-tools me-1"></i> Permohonan
                            </a>
                            <a href="{{ route('dispatcher.export-checking.index') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════ --}}
{{-- BADGE: KENDARAAN BARU SELESAI SERVICE (24 JAM)     --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($recentlyServiced->count() > 0)
<section class="section">
    <div class="row">
        <div class="col-12">
            @foreach($recentlyServiced as $serviced)
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-car-front-fill fs-4 me-3"></i>
                <div>
                    <strong>{{ $serviced->replacement->nopol ?? '-' }}</strong> sudah selesai di service
                    <span class="text-muted ms-2">
                        — {{ $serviced->reported_at->diffForHumans() }}
                        oleh {{ $serviced->driver->name ?? '-' }}
                        di {{ $serviced->service_location }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- JADWAL PENGECEKAN                                  --}}
{{-- ══════════════════════════════════════════════════ --}}
<section class="section">
    <div class="row">

        {{-- OVERDUE --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                        Overdue
                        @if($overdueSchedules->count() > 0)
                            <span class="badge bg-danger rounded-pill">{{ $overdueSchedules->count() }}</span>
                        @endif
                    </h5>

                    @if($overdueSchedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kendaraan</th>
                                    <th>Jenis</th>
                                    <th>Harusnya</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overdueSchedules as $schedule)
                                <tr>
                                    <td>
                                        <strong>{{ $schedule->vehicle->consumerial_code ?? '-' }}</strong>
                                        @if($schedule->assignedDispatcher)
                                            <br><small class="text-muted">PIC: {{ $schedule->assignedDispatcher->name }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $schedule->getStatusBadgeClass() }}">{{ $schedule->getFrequencyLabel() }}</span></td>
                                    <td>
                                        <span class="text-danger">{{ $schedule->next_due_date?->format('d/m/Y') }}</span>
                                        <br><small class="text-danger">{{ abs($schedule->days_until_due) }} hari lalu</small>
                                    </td>
                                    {{-- <td>
                                        @if(in_array($schedule->check_frequency, ['daily','weekly','monthly']))
                                            <a href="{{ route('dispatcher.routine-check.create', ['vehicle_id' => $schedule->vehicle_id]) }}"
                                               class="btn btn-sm btn-danger">
                                                <i class="bi bi-clipboard-check"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('dispatcher.body-check.create') }}"
                                               class="btn btn-sm btn-danger">
                                                <i class="bi bi-car-front"></i>
                                            </a>
                                        @endif
                                    </td> --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada pengecekan yang terlambat</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- DUE TODAY --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-clock-fill text-warning me-1"></i>
                        Due Today
                        @if($dueTodaySchedules->count() > 0)
                            <span class="badge bg-warning text-dark rounded-pill">{{ $dueTodaySchedules->count() }}</span>
                        @endif
                    </h5>

                    @if($dueTodaySchedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kendaraan</th>
                                    <th>Jenis</th>
                                    <th>PIC</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dueTodaySchedules as $schedule)
                                <tr>
                                    <td><strong>{{ $schedule->vehicle->consumerial_code ?? '-' }}</strong></td>
                                    <td><span class="badge bg-warning text-dark">{{ $schedule->getFrequencyLabel() }}</span></td>
                                    <td>{{ $schedule->assignedDispatcher->name ?? '-' }}</td>
                                    {{-- <td>
                                        @if(in_array($schedule->check_frequency, ['daily','weekly','monthly']))
                                            <a href="{{ route('dispatcher.routine-check.create', ['vehicle_id' => $schedule->vehicle_id]) }}"
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-clipboard-check"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('dispatcher.body-check.create') }}"
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-car-front"></i>
                                            </a>
                                        @endif
                                    </td> --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-emoji-smile text-success fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Semua pengecekan hari ini sudah selesai</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════════════ --}}
{{-- UPCOMING (7 HARI KE DEPAN)                         --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($upcomingSchedules->count() > 0)
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-event me-1"></i>
                        Upcoming (7 Hari ke Depan)
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kendaraan</th>
                                    <th>Jenis</th>
                                    <th>PIC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingSchedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->next_due_date?->translatedFormat('d M') }}</td>
                                    <td><strong>{{ $schedule->vehicle->consumerial_code ?? '-' }}</strong></td>
                                    <td><span class="badge bg-secondary">{{ $schedule->getFrequencyLabel() }}</span></td>
                                    <td>{{ $schedule->assignedDispatcher->name ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

@endsection
