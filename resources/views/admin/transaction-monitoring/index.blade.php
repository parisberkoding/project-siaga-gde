@extends('templates.main')

@section('title', 'Monitoring Transaksi Service')

@section('content')

<div class="pagetitle">
    <h1>Monitoring Transaksi Service</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Monitoring Transaksi</li>
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

{{-- Summary --}}
<section class="section dashboard">
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Overdue</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['overdue'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Transaksi terlambat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Dalam Proses</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-tools"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['in_progress'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Sedang dikerjakan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai (Bulan Ini)</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['completed'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Selesai bulan ini</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Biaya (Bulan Ini)</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 0.85rem;">Rp {{ number_format($counts['total_cost'] ?? 0, 0, ',', '.') }}</h6>
                            <span class="text-muted small pt-2">Total pengeluaran</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section dashboard">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('admin.transaction-monitoring.index') }}"
                          class="row g-2 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm mb-1">Kendaraan</label>
                            <select name="vehicle_id" class="form-select form-select-sm">
                                <option value="">Semua Kendaraan</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                        {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->consumerial_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="scheduled"   {{ request('status') === 'scheduled'   ? 'selected' : '' }}>Terjadwal</option>
                                <option value="in_service"  {{ request('status') === 'in_service'  ? 'selected' : '' }}>Dalam Proses</option>
                                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Selesai</option>
                                <option value="overdue"     {{ request('status') === 'overdue'     ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                   value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('admin.transaction-monitoring.index') }}"
                               class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </form>

                    {{-- Table --}}
                    @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Tgl Service</th>
                                    <th>Kendaraan</th>
                                    <th>Driver</th>
                                    <th>Lokasi</th>
                                    <th>Total</th>
                                    <th>Status VPR</th>
                                    <th>Deadline</th>
                                    <th style="width:80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $tx)
                                @php
                                    $status = optional($tx->replacement)->status ?? '-';
                                    $statusBadge = match($status) {
                                        'scheduled'  => 'primary',
                                        'in_service' => 'warning',
                                        'completed'  => 'success',
                                        'rejected'   => 'secondary',
                                        default      => 'secondary',
                                    };
                                    $statusLabel = match($status) {
                                        'scheduled'  => 'Terjadwal',
                                        'in_service' => 'Dalam Proses',
                                        'completed'  => 'Selesai',
                                        'rejected'   => 'Ditolak',
                                        default      => ucfirst(str_replace('_', ' ', $status)),
                                    };
                                @endphp
                                <tr class="{{ $tx->is_overdue ? 'table-danger' : '' }}">
                                    <td>
                                        <code class="small">{{ $tx->vprt_code }}</code>
                                        @if($tx->is_overdue)
                                            <br><span class="badge bg-danger">Overdue</span>
                                        @endif
                                    </td>
                                    <td>{{ $tx->service_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        <strong>{{ optional(optional($tx->replacement)->vehicle)->consumerial_code ?? '-' }}</strong>
                                    </td>
                                    <td>{{ optional($tx->driver)->nama_lengkap ?? '-' }}</td>
                                    <td>{{ Str::limit($tx->service_location ?? '-', 25) }}</td>
                                    <td>
                                        <strong>Rp {{ number_format($tx->total_amount ?? 0, 0, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>
                                        @if($tx->deadline_date)
                                            <span class="{{ $tx->is_overdue ? 'text-danger fw-bold' : '' }}">
                                                {{ $tx->deadline_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($tx->replacement)
                                            <a href="{{ route('admin.replacement-approval.show', $tx->replacement->vpr_code) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ $transactions->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-receipt text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada transaksi yang sesuai filter</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
