@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Dashboard Lunch Management</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" action="{{ route('lunch.dashboard') }}" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    {{-- ── Panduan Alur Lunch (step by step, seamless) ── --}}
    @if(!empty($stepStatus))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong><i class="bi bi-list-ol me-2 text-primary"></i>Alur Lunch Management — Status Langkah</strong>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- Step 1: Master Pekerja --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-secondary">1</span>
                            @if($stepStatus['employee_count'] > 0)
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-exclamation-circle-fill text-danger"></i>
                            @endif
                        </div>
                        <h6 class="mt-2 mb-1">Master Pekerja</h6>
                        <small class="text-muted d-block mb-2">{{ $stepStatus['employee_count'] }} pekerja terdaftar</small>
                        @can('permission', 'lunch.manage')
                        <a href="{{ route('lunch.employee-defaults.index') }}" class="btn btn-sm btn-outline-primary w-100">Kelola Pekerja</a>
                        @endcan
                    </div>
                </div>
                {{-- Step 2: Forecast H+1 --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-secondary">2</span>
                            @if($stepStatus['forecast_tomorrow'])
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-clock text-warning"></i>
                            @endif
                        </div>
                        <h6 class="mt-2 mb-1">Forecast Besok</h6>
                        <small class="text-muted d-block mb-2">
                            {{ $stepStatus['forecast_tomorrow'] ? 'Sudah digenerate' : 'Belum digenerate' }}
                        </small>
                        @can('permission', 'lunch.manage')
                        <form method="POST" action="{{ route('lunch.forecasts.generate') }}" class="d-inline w-100">
                            @csrf
                            <button class="btn btn-sm btn-outline-success w-100" onclick="return confirm('Generate forecast untuk besok?')">
                                <i class="bi bi-magic"></i> Generate
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                {{-- Step 3: Distribusi --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-secondary">3</span>
                            @if($stepStatus['distribution_today_locked'])
                                <i class="bi bi-lock-fill text-success"></i>
                            @else
                                <i class="bi bi-pencil text-warning"></i>
                            @endif
                        </div>
                        <h6 class="mt-2 mb-1">Distribusi Hari Ini</h6>
                        <small class="text-muted d-block mb-2">
                            {{ $stepStatus['distribution_today']->count() }} site ·
                            {{ $stepStatus['distribution_today_locked'] ? 'terkunci' : 'draft' }}
                        </small>
                        <a href="{{ route('lunch.distributions.index') }}" class="btn btn-sm btn-outline-primary w-100">Kelola Distribusi</a>
                    </div>
                </div>
                {{-- Step 4: Kehadiran & Export --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-secondary">4</span>
                            <i class="bi bi-qr-code-scan text-primary"></i>
                        </div>
                        <h6 class="mt-2 mb-1">Kehadiran & Export</h6>
                        <small class="text-muted d-block mb-2">{{ $stepStatus['attendance_today'] }} sudah scan hari ini</small>
                        <div class="d-grid gap-1">
                            <a href="{{ route('vendor.lunch.today') }}" class="btn btn-sm btn-outline-info">Siapa Makan</a>
                            <a href="{{ route('lunch.export-form') }}" class="btn btn-sm btn-outline-success">Export Vendor</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Summary Cards per site --}}
    <div class="row g-3 mb-3">
        @foreach($sites as $site)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title pb-0">{{ $site->name }} <span class="badge bg-light text-dark">{{ $site->code }}</span></h5>
                    <div class="d-flex justify-content-between text-center">
                        <div>
                            <h4 class="fw-bold text-primary mb-0">{{ $forecastBySite[$site->id] ?? 0 }}</h4>
                            <small class="text-muted">Forecast</small>
                        </div>
                        <div>
                            <h4 class="fw-bold text-success mb-0">{{ $attendanceBySite[$site->id] ?? 0 }}</h4>
                            <small class="text-muted">Hadir (QR)</small>
                        </div>
                        <div>
                            <h4 class="fw-bold text-info mb-0">{{ $guestBySite[$site->id] ?? 0 }}</h4>
                            <small class="text-muted">Tamu</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ ($accuracyYesterday['accuracy'] ?? 0) >= 80 ? 'text-success' : 'text-warning' }}">
                        {{ $accuracyYesterday['accuracy'] !== null ? $accuracyYesterday['accuracy'] . '%' : '—' }}
                    </h3>
                    <p class="mb-0">Akurasi Forecast Kemarin</p>
                    <small class="text-muted">{{ $accuracyYesterday['correct'] }}/{{ $accuracyYesterday['evaluated'] }} tepat</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ $unresolvedToday > 0 ? 'text-danger' : 'text-success' }}">{{ $unresolvedToday }}</h3>
                    <p class="mb-0">Nama Belum Dikenali</p>
                    <small class="text-muted">
                        <a href="{{ route('lunch.transactions.index', ['status' => 'unresolved']) }}">lihat transaksi</a>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ $pendingGuests > 0 ? 'text-warning' : 'text-success' }}">{{ $pendingGuests }}</h3>
                    <p class="mb-0">Tamu Butuh Konfirmasi</p>
                    <small class="text-muted"><a href="{{ route('lunch.guests.calendar') }}">buka kalender tamu</a></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary">{{ $distributions->sum('total_count') }}</h3>
                    <p class="mb-0">Total Porsi Distribusi</p>
                    <small class="text-muted"><a href="{{ route('lunch.distributions.index', ['date' => $date]) }}">detail distribusi</a></small>
                </div>
            </div>
        </div>
    </div>

    {{-- Distribusi hari ini --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Distribusi {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h5>
            @if($distributions->isEmpty())
                <p class="text-muted mb-0">Belum ada distribusi untuk tanggal ini.
                    <a href="{{ route('lunch.distributions.index', ['date' => $date]) }}">Generate di halaman Distribution</a>.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th class="text-center">Karyawan</th>
                                <th class="text-center">Tamu</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($distributions as $d)
                            <tr>
                                <td>{{ $d->site->name }}</td>
                                <td class="text-center">{{ $d->employee_count }}</td>
                                <td class="text-center">{{ $d->guest_count }}</td>
                                <td class="text-center fw-bold">{{ $d->total_count }}</td>
                                <td class="text-center">
                                    @if($d->is_locked)
                                        <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</section>
@endsection
