@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Dashboard Lunch Management</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Check-in Hari Ini</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <form method="GET" action="{{ route('lunch.dashboard') }}" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
                <a href="{{ route('lunch.forecasts.index') }}" class="btn btn-primary text-nowrap">
                    <i class="bi bi-calendar2-week me-1"></i>Data Forecast
                </a>
            </form>
        </div>
        <div class="col-md-7 text-md-end">
            <a href="{{ route('lunch.qr-codes') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-qr-code me-1"></i>Cetak QR
            </a>
            <a href="{{ route('lunch.transactions.index', ['date' => $date]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-check me-1"></i>Semua Check-in
            </a>
            @can('permission', 'lunch.manage')
            <a href="{{ route('lunch.forecasts.index', ['date' => $date]) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i>Kelola Forecast
            </a>
            @endcan
        </div>
    </div>

    {{-- ── KPI utama ───────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0">{{ $attendanceBySite->sum() }}</h3>
                    <p class="mb-0 small">Sudah Check-in</p>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0">{{ $forecastBySite->sum() }}</h3>
                    <p class="mb-0 small">Forecast Hari Ini</p>
                    <small class="text-muted">{{ $tomorrowForecastCount }} sudah terjadwal besok</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ $unresolvedToday > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ $unresolvedToday }}</h3>
                    <p class="mb-0 small">Nama Belum Dikenali</p>
                    <a href="{{ route('lunch.transactions.index', ['date' => $date, 'status' => 'unresolved']) }}" class="small">periksa</a>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ $pendingGuests > 0 ? 'text-warning' : 'text-success' }} mb-0">{{ $pendingGuests }}</h3>
                    <p class="mb-0 small">Tamu Perlu Konfirmasi</p>
                    <a href="{{ route('lunch.guests.calendar') }}" class="small">buka kalender</a>
                </div>
            </div>
        </div>
    </div>

    @if($pendingEmployees > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div><i class="bi bi-person-exclamation me-1"></i>{{ $pendingEmployees }} karyawan hasil scan belum diverifikasi.</div>
            @can('permission', 'lunch.manage')
            <a href="{{ route('lunch.employee-defaults.index', ['verification' => 'pending']) }}" class="btn btn-sm btn-outline-dark">Verifikasi</a>
            @endcan
        </div>
    @endif

    {{-- ── Check-in per site ───────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        @forelse($sites as $site)
            @php
                $forecast = (int) ($forecastBySite[$site->id] ?? 0);
                $hadir = (int) ($attendanceBySite[$site->id] ?? 0);
                $guest = (int) ($guestBySite[$site->id] ?? 0);
                $dist = $distributions->firstWhere('site_id', $site->id);
                $custom = $customPurchases->firstWhere('site_id', $site->id);
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 {{ $custom ? 'border-warning' : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0">{{ $site->name }}</h6>
                                <small class="text-muted">{{ $site->unit?->name }} · {{ $site->code }}</small>
                            </div>
                            @if($dist?->is_locked)
                                <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i>Vendor</span>
                            @else
                                <span class="badge bg-secondary">Open</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between text-center mt-3">
                            <div>
                                <h5 class="fw-bold text-success mb-0">{{ $hadir }}</h5>
                                <small class="text-muted">Check-in</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-primary mb-0">{{ $forecast }}</h5>
                                <small class="text-muted">Forecast</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-info mb-0">{{ $guest }}</h5>
                                <small class="text-muted">Tamu</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">{{ $dist?->total_count ?? 0 }}</h5>
                                <small class="text-muted">Porsi</small>
                            </div>
                        </div>

                        <div class="progress mt-3" style="height: 6px;">
                            @php($percent = $forecast > 0 ? min(100, round($hadir / $forecast * 100)) : 0)
                            <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                        </div>
                        <small class="text-muted">{{ $percent }}% dari forecast sudah check-in</small>

                        @if($custom)
                            <div class="alert alert-warning py-1 px-2 small mt-2 mb-0">
                                <i class="bi bi-bag-heart me-1"></i>Katering diganti: {{ $custom->title }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info mb-0">Tidak ada site dalam kewenangan Anda.</div></div>
        @endforelse
    </div>

    <div class="row g-3">
        {{-- ── Check-in terbaru ────────────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-clock-history me-1"></i>Check-in Terbaru</strong>
                    <a href="{{ route('lunch.transactions.index', ['date' => $date]) }}" class="small">lihat semua</a>
                </div>
                <div class="card-body pt-2">
                    @if($recentTransactions->isEmpty())
                        <p class="text-muted small mb-0">Belum ada yang check-in pada tanggal ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Nama</th><th>Site</th><th>Jam</th></tr></thead>
                                <tbody>
                                    @foreach($recentTransactions as $trx)
                                        <tr>
                                            <td>
                                                {{ $trx->employee_name ?? $trx->raw_alias }}
                                                @unless($trx->isResolved())
                                                    <span class="badge bg-danger ms-1">belum dikenali</span>
                                                @endunless
                                            </td>
                                            <td><small>{{ $trx->site?->name }}</small></td>
                                            <td><small>{{ $trx->scanned_at?->format('H:i') ?? '—' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Akurasi & pintasan ──────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ ($accuracyYesterday['accuracy'] ?? 0) >= 80 ? 'text-success' : 'text-warning' }} mb-1">
                        {{ $accuracyYesterday['accuracy'] !== null ? $accuracyYesterday['accuracy'] . '%' : '—' }}
                    </h3>
                    <p class="mb-0 small">Akurasi Forecast Kemarin</p>
                    <small class="text-muted">{{ $accuracyYesterday['correct'] }}/{{ $accuracyYesterday['evaluated'] }} tepat</small>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><strong>Pintasan</strong></div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('lunch.forecasts.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar2-week me-2 text-primary"></i>Data Forecast & override
                    </a>
                    @can('permission', 'lunch.manage')
                    <a href="{{ route('lunch.employee-defaults.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-people me-2 text-primary"></i>Master Karyawan
                    </a>
                    @endcan
                    <a href="{{ route('lunch.guests.calendar') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-check me-2 text-primary"></i>Reservasi Tamu
                    </a>
                    @can('permission', 'lunch.manage')
                    <a href="{{ route('lunch.custom-purchases.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-bag-heart me-2 text-primary"></i>Custom Purchase
                    </a>
                    <a href="{{ route('lunch.vendor-payments.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-cash-coin me-2 text-primary"></i>Pengeluaran & Pembayaran Vendor
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

</section>
@endsection
