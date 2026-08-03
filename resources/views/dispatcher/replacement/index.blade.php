@extends('templates.main')

@section('title', 'Permohonan Penggantian Sparepart')

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
</style>

<div class="pagetitle">
    <h1>Permohonan Penggantian Sparepart</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Permohonan Penggantian</li>
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

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h5 class="card-title">Daftar Permohonan</h5>

                    {{-- Status Tabs --}}
                    <div class="status-tabs">
                        <a href="{{ route('dispatcher.replacement.index') }}"
                           class="status-tab {{ !request('status') ? 'active' : '' }}">
                            Semua
                        </a>
                        @php
                            $statusConfig = [
                                'pending'    => ['label' => 'Pending',    'icon' => 'bi-hourglass-split'],
                                'approved'   => ['label' => 'Approved',   'icon' => 'bi-check-circle'],
                                'scheduled'  => ['label' => 'Scheduled',  'icon' => 'bi-calendar-check'],
                                'in_service' => ['label' => 'In Service', 'icon' => 'bi-wrench'],
                                'completed'  => ['label' => 'Completed',  'icon' => 'bi-check2-all'],
                            ];
                        @endphp
                        @foreach($statusConfig as $statusKey => $cfg)
                            <a href="{{ route('dispatcher.replacement.index', ['status' => $statusKey]) }}"
                               class="status-tab {{ request('status') == $statusKey ? 'active' : '' }}">
                                <i class="bi {{ $cfg['icon'] }}"></i>
                                {{ $cfg['label'] }}
                                @if(($statusCounts[$statusKey] ?? 0) > 0)
                                    <span class="count">{{ $statusCounts[$statusKey] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    {{-- Filter Kendaraan --}}
                    <form method="GET" action="{{ route('dispatcher.replacement.index') }}" class="mb-3">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4 col-8">
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua Kendaraan</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} — {{ $vehicle->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
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
                                    <th>Driver Terakhir</th>
                                    <th class="text-center">Item</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($replacements as $vpr)
                                <tr>
                                    <td>
                                        <a href="{{ route('dispatcher.replacement.show', $vpr->vpr_code) }}"
                                           class="fw-semibold text-primary text-decoration-none">
                                            {{ $vpr->vpr_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>{{ $vpr->nopol }}</strong>
                                        <br><small class="text-muted">{{ $vpr->vehicle->consumerial_name ?? '' }}</small>
                                    </td>
                                    <td>{{ $vpr->dispatcher->name ?? '-' }}</td>
                                    <td>{{ $vpr->lastDriver->name ?? '-' }}</td>
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
                                        <br><small class="text-muted">{{ $vpr->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('dispatcher.replacement.show', $vpr->vpr_code) }}"
                                           class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted fs-1"></i>
                                        <p class="text-muted mt-2 mb-0">
                                            @if(request('status'))
                                                Tidak ada permohonan dengan status "{{ $statusConfig[request('status')]['label'] ?? request('status') }}"
                                            @else
                                                Belum ada permohonan penggantian sparepart
                                            @endif
                                        </p>
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
