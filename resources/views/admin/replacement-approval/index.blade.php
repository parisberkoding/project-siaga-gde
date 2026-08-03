@extends('templates.main')

@section('title', 'Approval Permohonan Penggantian')

@section('content')

<style>
    .status-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }
    .status-tab {
        padding: 6px 14px;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        color: #6c757d;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-tab:hover { border-color: #4154f1; color: #4154f1; }
    .status-tab.active { background: #4154f1; border-color: #4154f1; color: #fff; }
    .status-tab .count {
        border-radius: 10px;
        padding: 1px 8px;
        font-size: 11px;
    }
    .status-tab.active .count { background: rgba(255,255,255,0.3); }
    .status-tab:not(.active) .count { background: #e9ecef; color: #6c757d; }

    .pending-highlight {
        border-left: 4px solid #ffc107;
    }
</style>

<div class="pagetitle">
    <h1>Approval Permohonan Penggantian</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Approval Penggantian</li>
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
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Summary Cards --}}
<section class="section dashboard">
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Menunggu Approval</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $statusCounts['pending'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Menunggu persetujuan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Approved</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $statusCounts['approved'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Telah disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">In Service</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-wrench"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ ($statusCounts['scheduled'] ?? 0) + ($statusCounts['in_service'] ?? 0) }}</h6>
                            <span class="text-muted small pt-2">Dijadwalkan / proses</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai (Bulan Ini)</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $statusCounts['completed'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Selesai bulan ini</span>
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
                <div class="card-body">

                    <h5 class="card-title">Daftar Permohonan</h5>

                    {{-- Status Tabs --}}
                    <div class="status-tabs">
                        <a href="{{ route('admin.replacement-approval.index') }}"
                           class="status-tab {{ !request('status') ? 'active' : '' }}">
                            Semua
                        </a>
                        @php
                            $statusConfig = [
                                'pending'    => ['label' => 'Pending',    'icon' => 'bi-hourglass-split'],
                                'approved'   => ['label' => 'Approved',   'icon' => 'bi-check-circle'],
                                'rejected'   => ['label' => 'Rejected',   'icon' => 'bi-x-circle'],
                                'scheduled'  => ['label' => 'Scheduled',  'icon' => 'bi-calendar-check'],
                                'in_service' => ['label' => 'In Service', 'icon' => 'bi-wrench'],
                                'completed'  => ['label' => 'Completed',  'icon' => 'bi-check2-all'],
                            ];
                        @endphp
                        @foreach($statusConfig as $statusKey => $cfg)
                            <a href="{{ route('admin.replacement-approval.index', ['status' => $statusKey]) }}"
                               class="status-tab {{ request('status') == $statusKey ? 'active' : '' }}">
                                <i class="bi {{ $cfg['icon'] }}"></i>
                                {{ $cfg['label'] }}
                                @if(($statusCounts[$statusKey] ?? 0) > 0)
                                    <span class="count">{{ $statusCounts[$statusKey] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('admin.replacement-approval.index') }}" class="mb-3">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Dispatcher</label>
                                <select name="dispatcher_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($dispatchers as $dispatcher)
                                        <option value="{{ $dispatcher->id }}" {{ request('dispatcher_id') == $dispatcher->id ? 'selected' : '' }}>
                                            {{ $dispatcher->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Dari</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Sampai</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2 col-12">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('admin.replacement-approval.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
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
                                    <th>Kode VPR</th>
                                    <th>Kendaraan</th>
                                    <th>Dispatcher</th>
                                    <th class="text-center">Item</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($replacements as $vpr)
                                <tr class="{{ $vpr->status === 'pending' ? 'pending-highlight' : '' }}">
                                    <td>
                                        <a href="{{ route('admin.replacement-approval.show', $vpr->vpr_code) }}"
                                           class="fw-semibold text-primary text-decoration-none">
                                            {{ $vpr->vpr_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>{{ $vpr->nopol }}</strong>
                                        <br><small class="text-muted">{{ $vpr->vehicle->consumerial_name ?? '' }}</small>
                                    </td>
                                    <td>{{ $vpr->dispatcher->nama_lengkap ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary rounded-pill">{{ $vpr->replacementDetails->count() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $vpr->getStatusBadgeClass() }}">
                                            {{ $vpr->getStatusLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $vpr->created_at->format('d/m/Y') }}
                                        <br><small class="text-muted">{{ $vpr->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.replacement-approval.show', $vpr->vpr_code) }}"
                                           class="btn btn-sm {{ $vpr->status === 'pending' ? 'btn-warning' : 'btn-outline-primary' }}">
                                            @if($vpr->status === 'pending')
                                                <i class="bi bi-check-circle me-1"></i> Review
                                            @else
                                                <i class="bi bi-eye"></i>
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted fs-1"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data permohonan</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($replacements->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $replacements->links() }}
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
