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
                <input type="date" name="date" value="{{ $date }}" class="form-control">
                <button class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

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
