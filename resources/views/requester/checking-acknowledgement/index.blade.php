@extends('templates.main')

@section('title', 'Hasil Pengecekan Kendaraan')

@section('content')

<style>
    .ack-badge {
        position: relative;
    }
    .ack-badge .pulse-dot {
        width: 10px; height: 10px;
        background: #ED1B2F;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%   { box-shadow: 0 0 0 0 rgba(220,53,69,0.5); }
        70%  { box-shadow: 0 0 0 8px rgba(220,53,69,0); }
        100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); }
    }

    .check-card {
        border: 1px solid #E4E9F0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        transition: all 0.15s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .check-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); color: inherit; }
    .check-card.unread { border-left: 4px solid #ED1B2F; background: #fffbfb; }
    .check-card.read { border-left: 4px solid #71A62F; opacity: 0.75; }

    .result-mini-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .tab-toggle {
        display: flex;
        gap: 0;
        margin-bottom: 16px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #E4E9F0;
        width: fit-content;
    }
    .tab-toggle a {
        padding: 8px 20px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        color: #64748B;
        background: #fff;
        border-right: 1px solid #E4E9F0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .tab-toggle a:last-child { border-right: none; }
    .tab-toggle a.active { background: #0B50A1; color: #fff; }
    .tab-toggle a .badge-count {
        padding: 1px 7px;
        border-radius: 10px;
        font-size: 10px;
    }
    .tab-toggle a.active .badge-count { background: rgba(255,255,255,0.3); }
    .tab-toggle a:not(.active) .badge-count { background: #E4E9F0; color: #64748B; }
</style>

<div class="pagetitle">
    <h1>Hasil Pengecekan Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item active">Pengecekan</li>
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
                    <h5 class="card-title">Belum Acknowledge</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pendingCount ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Perlu ditindaklanjuti</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Sudah Acknowledge</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $acknowledgedCount ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Telah dikonfirmasi</span>
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

                    {{-- Toggle Tabs --}}
                    <div class="tab-toggle">
                        <a href="{{ route('requester.checking-acknowledgement.index', ['tab' => 'pending']) }}"
                           class="{{ (request('tab', 'pending') == 'pending') ? 'active' : '' }}">
                            <span class="pulse-dot" style="{{ (request('tab', 'pending') == 'pending') ? 'background:#fff' : '' }}"></span>
                            Belum Ack
                            @if(($pendingCount ?? 0) > 0)
                                <span class="badge-count">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('requester.checking-acknowledgement.index', ['tab' => 'done']) }}"
                           class="{{ request('tab') == 'done' ? 'active' : '' }}">
                            <i class="bi bi-check2-all"></i>
                            Sudah Ack
                        </a>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('requester.checking-acknowledgement.index') }}" class="mb-3">
                        <input type="hidden" name="tab" value="{{ request('tab', 'pending') }}">
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
                                <label class="form-label small text-muted">Jenis</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="routine" {{ request('type') == 'routine' ? 'selected' : '' }}>Rutin</option>
                                    <option value="body" {{ request('type') == 'body' ? 'selected' : '' }}>Body</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Dari</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('requester.checking-acknowledgement.index', ['tab' => request('tab', 'pending')]) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Card List --}}
                    @forelse($checkings as $check)
                    <a href="{{ route('requester.checking-acknowledgement.show', $check->checking_detail_code) }}"
                       class="check-card {{ $check->driver_acknowledged ? 'read' : 'unread' }}">
                        <div class="row align-items-center g-2">
                            <div class="col">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    @if(!$check->driver_acknowledged)
                                        <span class="pulse-dot"></span>
                                    @else
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 12px;"></i>
                                    @endif
                                    <strong style="font-size: 14px;">{{ $check->checking_detail_code }}</strong>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-1">
                                    {{-- Type Badge --}}
                                    @if($check->type === 'routine')
                                        @php
                                            $subBadge = match($check->sub_type) {
                                                'daily' => 'bg-primary',
                                                'weekly' => 'bg-info',
                                                'monthly' => 'bg-secondary',
                                                default => 'bg-secondary',
                                            };
                                            $subLabel = match($check->sub_type) {
                                                'daily' => 'Harian',
                                                'weekly' => 'Mingguan',
                                                'monthly' => 'Bulanan',
                                                default => '-',
                                            };
                                        @endphp
                                        <span class="badge {{ $subBadge }}">Rutin {{ $subLabel }}</span>
                                    @else
                                        <span class="badge bg-dark">Body</span>
                                    @endif

                                    {{-- Vehicle --}}
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-truck me-1"></i>{{ $check->vehicleChecking->nopol ?? '-' }}
                                    </span>
                                </div>

                                {{-- Results Mini --}}
                                <div class="d-flex flex-wrap gap-1">
                                    @if($check->type === 'routine')
                                        <span class="result-mini-badge" style="background: #d1e7dd; color: #4A7A1C;">
                                            OK {{ $check->ok_count }}
                                        </span>
                                        <span class="result-mini-badge" style="background: #f8d7da; color: #C4142A;">
                                            NG {{ $check->ng_count }}
                                        </span>
                                    @else
                                        @php
                                            $baikC  = $check->itemResults->where('status','baik')->count();
                                            $cukupC = $check->itemResults->where('status','cukup')->count();
                                            $kurangC= $check->itemResults->where('status','kurang')->count();
                                            $tidakC = $check->itemResults->where('status','tidak')->count();
                                        @endphp
                                        @if($baikC)  <span class="result-mini-badge" style="background:#d1e7dd;color:#4A7A1C;">B{{ $baikC }}</span> @endif
                                        @if($cukupC) <span class="result-mini-badge" style="background:#cff4fc;color:#0B50A1;">C{{ $cukupC }}</span> @endif
                                        @if($kurangC)<span class="result-mini-badge" style="background:#fff3cd;color:#997404;">K{{ $kurangC }}</span> @endif
                                        @if($tidakC) <span class="result-mini-badge" style="background:#f8d7da;color:#C4142A;">T{{ $tidakC }}</span> @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto text-end">
                                <div class="text-muted" style="font-size: 12px;">
                                    {{ $check->checked_at?->format('d/m/Y') }}
                                    <br>{{ $check->checked_at?->format('H:i') }}
                                </div>
                                <small class="text-muted">{{ $check->dispatcher->name ?? '-' }}</small>
                                <br>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="text-center py-5">
                        @if(request('tab') == 'done')
                            <i class="bi bi-clipboard-check text-muted fs-1"></i>
                            <p class="text-muted mt-2 mb-0">Belum ada pengecekan yang di-acknowledge</p>
                        @else
                            <i class="bi bi-check-circle text-success fs-1"></i>
                            <p class="text-muted mt-2 mb-0">Semua pengecekan sudah di-acknowledge!</p>
                        @endif
                    </div>
                    @endforelse

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
