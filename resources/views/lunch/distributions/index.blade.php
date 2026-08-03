@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Lunch Distribution</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Distribution</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-3 g-2 align-items-center">
        <div class="col-md-4">
            <form method="GET" action="{{ route('lunch.distributions.index') }}" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control">
                <button class="btn btn-primary">Filter</button>
            </form>
        </div>
        <div class="col-md-8 text-md-end">
            <a href="{{ route('lunch.export-form') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Vendor
            </a>
            <form method="POST" action="{{ route('lunch.distributions.generate') }}" class="d-inline">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button class="btn btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Hitung dari Forecast</button>
            </form>
            <form method="POST" action="{{ route('lunch.distributions.lock') }}" class="d-inline"
                  onsubmit="return confirm('Kunci distribusi {{ $date }}? Angka tidak akan berubah otomatis setelah dikirim ke vendor.')">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button class="btn btn-warning"><i class="bi bi-lock me-1"></i>Kunci Angka Vendor</button>
            </form>
        </div>
    </div>

    {{-- Distribusi per site --}}
    <div class="row g-3 mb-3">
        @forelse($distributions as $d)
        <div class="col-md-4">
            <div class="card h-100 {{ $d->is_locked ? 'border-success' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title pb-0 mb-0">{{ $d->site->name }}</h5>
                        @if($d->is_locked)
                            <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </div>
                    <h2 class="fw-bold text-primary my-2">{{ $d->total_count }} <small class="fs-6 text-muted">porsi</small></h2>
                    <div class="d-flex gap-3 text-muted small">
                        <span><i class="bi bi-person me-1"></i>{{ $d->employee_count }} karyawan</span>
                        <span><i class="bi bi-people me-1"></i>{{ $d->guest_count }} tamu</span>
                    </div>
                    @if($d->is_locked)
                        <small class="text-muted d-block mt-2">
                            Dikunci {{ $d->locked_at?->format('d/m/Y H:i') }} oleh {{ $d->lockedBy?->nama_lengkap ?? '-' }}
                        </small>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                Belum ada distribusi untuk tanggal ini. Klik <strong>Hitung dari Forecast</strong>
                setelah forecast digenerate.
            </div>
        </div>
        @endforelse
    </div>

    {{-- Reservasi tamu tanggal ini --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Reservasi Tamu — {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h5>
            @if($guests->isEmpty())
                <p class="text-muted mb-0">Tidak ada reservasi tamu.
                    <a href="{{ route('lunch.guests.calendar') }}">Kelola di kalender tamu</a>.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>Instansi</th><th>Site</th><th class="text-center">Jumlah</th><th>Status</th><th>Catatan</th></tr>
                        </thead>
                        <tbody>
                            @foreach($guests as $guest)
                            <tr>
                                <td>{{ $guest->institution }}</td>
                                <td><span class="badge bg-primary">{{ $guest->site->code }}</span></td>
                                <td class="text-center">{{ $guest->guest_count }}</td>
                                <td><span class="badge bg-{{ $guest->statusBadgeClass() }}">{{ $guest->statusLabel() }}</span></td>
                                <td class="text-muted">{{ $guest->notes ?? '-' }}</td>
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
