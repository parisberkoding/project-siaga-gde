@extends('templates.main')

@section('title', 'Daftar Pengecekan Rutin')

@section('content')

<div class="pagetitle">
    <h1>Pengecekan Rutin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengecekan Rutin</li>
        </ol>
    </nav>
</div>

{{-- Alert --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- Header: Title + Tombol Tambah --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2">
                        <h5 class="card-title mb-0 pb-0">Daftar Pengecekan Rutin</h5>
                        <a href="{{ route('dispatcher.routine-check.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Pengecekan Baru
                        </a>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('dispatcher.routine-check.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua Kendaraan</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} — {{ $vehicle->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Jenis</label>
                                <select name="sub_type" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="daily" {{ request('sub_type') == 'daily' ? 'selected' : '' }}>Harian</option>
                                    <option value="weekly" {{ request('sub_type') == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                    <option value="monthly" {{ request('sub_type') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Dari Tanggal</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Sampai Tanggal</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('dispatcher.routine-check.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Kendaraan</th>
                                    <th>Jenis</th>
                                    <th>Dispatcher</th>
                                    <th>Driver</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Hasil</th>
                                    <th class="text-center">Driver Ack</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkings as $checking)
                                <tr>
                                    <td>
                                        <a href="{{ route('dispatcher.routine-check.show', $checking->checking_detail_code) }}"
                                           class="fw-semibold text-primary text-decoration-none">
                                            {{ $checking->checking_detail_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>{{ $checking->vehicleChecking->nopol ?? '-' }}</strong>
                                        <br><small class="text-muted">{{ $checking->vehicleChecking->vehicle->consumerial_name ?? '' }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $subTypeColors = ['daily' => 'bg-primary', 'weekly' => 'bg-info', 'monthly' => 'bg-secondary'];
                                            $subTypeLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
                                        @endphp
                                        <span class="badge {{ $subTypeColors[$checking->sub_type] ?? 'bg-secondary' }}">
                                            {{ $subTypeLabels[$checking->sub_type] ?? $checking->sub_type }}
                                        </span>
                                    </td>
                                    <td>{{ $checking->dispatcher->name ?? '-' }}</td>
                                    <td>{{ $checking->currentDriver->name ?? '-' }}</td>
                                    <td>
                                        {{ $checking->checked_at->format('d/m/Y') }}
                                        <br><small class="text-muted">{{ $checking->checked_at->format('H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $okCount = $checking->itemResults->whereIn('status', ['ok'])->count();
                                            $ngCount = $checking->itemResults->where('status', 'ng')->count();
                                            $total = $checking->itemResults->count();
                                        @endphp
                                        <span class="text-success fw-bold">{{ $okCount }}</span>
                                        <span class="text-muted">/</span>
                                        <span class="text-danger fw-bold">{{ $ngCount }}</span>
                                        <br><small class="text-muted">dari {{ $total }} item</small>
                                    </td>
                                    <td class="text-center">
                                        @if($checking->driver_acknowledged)
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('dispatcher.routine-check.show', $checking->checking_detail_code) }}"
                                           class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted fs-1"></i>
                                        <p class="text-muted mt-2 mb-0">Belum ada data pengecekan rutin</p>
                                        <a href="{{ route('dispatcher.routine-check.create') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-plus-lg me-1"></i> Mulai Pengecekan
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($checkings->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $checkings->links() }}
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
