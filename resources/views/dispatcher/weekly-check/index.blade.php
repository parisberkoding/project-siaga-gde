@extends('templates.main')

@section('title', 'Daftar Pengecekan Mingguan')

@section('content')

<div class="pagetitle">
    <h1>Pengecekan Mingguan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengecekan Mingguan</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-week me-1 text-info"></i> Daftar Pengecekan Mingguan
                        </h5>
                        <a href="{{ route('dispatcher.weekly-check.create') }}" class="btn btn-info btn-sm text-white">
                            <i class="bi bi-plus-lg me-1"></i> Pengecekan Mingguan Baru
                        </a>
                    </div>

                    <form method="GET" action="{{ route('dispatcher.weekly-check.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua Kendaraan</option>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->consumerial_code }} — {{ $v->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Dari Tanggal</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                       value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Sampai Tanggal</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                       value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-info btn-sm w-100">
                                    <i class="bi bi-funnel me-1"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('dispatcher.weekly-check.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Kendaraan</th>
                                    <th>Driver</th>
                                    <th>Dispatcher</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">OK</th>
                                    <th class="text-center">NG</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkings as $check)
                                <tr>
                                    <td><code class="small">{{ $check->checking_detail_code }}</code></td>
                                    <td>
                                        <strong>{{ $check->vehicleChecking->nopol ?? '-' }}</strong>
                                        <br><small class="text-muted">{{ $check->vehicleChecking->vehicle->consumerial_name ?? '' }}</small>
                                    </td>
                                    <td>{{ $check->currentDriver->nama_lengkap ?? '-' }}</td>
                                    <td>{{ $check->dispatcher->nama_lengkap ?? '-' }}</td>
                                    <td>
                                        {{ $check->checked_at->translatedFormat('d M Y') }}
                                        <br><small class="text-muted">{{ $check->checked_at->format('H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $check->ok_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($check->ng_count > 0)
                                            <span class="badge bg-danger">{{ $check->ng_count }}</span>
                                        @else
                                            <span class="badge bg-light text-dark">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('dispatcher.weekly-check.show', $check->checking_detail_code) }}"
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3"></i>
                                        <p class="mt-2">Belum ada data pengecekan mingguan.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $checkings->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
