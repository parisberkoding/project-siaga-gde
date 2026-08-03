@extends('templates.main')

@section('title', 'Jadwal Penggantian Suku Cadang')

@section('content')

<style>
    .schedule-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 12px;
        transition: all 0.15s;
    }
    .schedule-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .schedule-card.urgent {
        border-left: 5px solid #dc3545;
        animation: urgentGlow 2s ease-in-out infinite;
    }
    .schedule-card.active-service { border-left: 5px solid #ffc107; }
    .schedule-card.upcoming { border-left: 5px solid #4154f1; }
    .schedule-card.completed { border-left: 5px solid #198754; opacity: 0.8; }

    @keyframes urgentGlow {
        0%,100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); }
        50% { box-shadow: 0 0 8px 2px rgba(220,53,69,0.15); }
    }

    .schedule-header {
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .schedule-body {
        padding: 0 16px 14px;
    }

    .item-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        font-size: 12px;
        margin: 2px;
    }

    .countdown-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .countdown-badge.overdue { background: #f8d7da; color: #dc3545; }
    .countdown-badge.today { background: #fff3cd; color: #997404; }
    .countdown-badge.soon { background: #cff4fc; color: #0dcaf0; }
    .countdown-badge.ok { background: #e9ecef; color: #6c757d; }
</style>

<div class="pagetitle">
    <h1>Jadwal Penggantian Suku Cadang</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item active">Jadwal Penggantian</li>
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
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Overdue</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['overdue'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Jadwal terlewat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Aktif</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-wrench"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['active'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Sedang berjalan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['completed'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Penggantian selesai</span>
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

                    <h5 class="card-title">Tugas Service Anda</h5>

                    {{-- Active / Upcoming --}}
                    @php
                        $activeSchedules = $schedules->whereIn('group', ['overdue', 'active', 'upcoming']);
                        $completedSchedules = $schedules->where('group', 'completed');
                    @endphp

                    @if($activeSchedules->count() > 0)
                    @foreach($activeSchedules as $schedule)
                    @php
                        $vpr = $schedule->partsReplacement;
                        $cardClass = match($schedule->group) {
                            'overdue' => 'urgent',
                            'active' => 'active-service',
                            'upcoming' => 'upcoming',
                            default => '',
                        };
                    @endphp
                    <div class="schedule-card {{ $cardClass }}">
                        <div class="schedule-header">
                            <div>
                                <strong>{{ $vpr->vpr_code ?? '-' }}</strong>
                                <br>
                                <span class="badge bg-light text-dark mt-1">
                                    <i class="bi bi-truck me-1"></i>{{ $vpr->nopol ?? '-' }}
                                </span>
                                <span class="badge {{ $vpr->getStatusBadgeClass() }} mt-1">
                                    {{ $vpr->getStatusLabel() }}
                                </span>
                            </div>
                            <div class="text-end">
                                {{-- Countdown --}}
                                @if($schedule->deadline_date)
                                    @php
                                        $daysLeft = now()->startOfDay()->diffInDays($schedule->deadline_date, false);
                                    @endphp
                                    @if($daysLeft < 0)
                                        <span class="countdown-badge overdue">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Overdue {{ abs($daysLeft) }} hari
                                        </span>
                                    @elseif($daysLeft == 0)
                                        <span class="countdown-badge today">
                                            <i class="bi bi-clock"></i>
                                            Deadline Hari Ini
                                        </span>
                                    @elseif($daysLeft <= 2)
                                        <span class="countdown-badge soon">
                                            <i class="bi bi-clock"></i>
                                            {{ $daysLeft }} hari lagi
                                        </span>
                                    @else
                                        <span class="countdown-badge ok">
                                            {{ $daysLeft }} hari lagi
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="schedule-body">
                            {{-- Service Info --}}
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Lokasi Service</small>
                                    <strong style="font-size: 13px;">{{ $schedule->service_location ?? 'Belum ditentukan' }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Tgl Service</small>
                                    <strong style="font-size: 13px;">{{ $schedule->service_date?->format('d/m/Y') ?? '-' }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Deadline</small>
                                    <strong style="font-size: 13px; {{ ($daysLeft ?? 99) < 0 ? 'color:#dc3545' : '' }}">
                                        {{ $schedule->deadline_date?->format('d/m/Y') ?? '-' }}
                                    </strong>
                                </div>
                            </div>

                            {{-- Items to Replace --}}
                            <small class="text-muted d-block mb-1">Item yang diganti:</small>
                            <div class="d-flex flex-wrap">
                                @foreach($vpr->replacementDetails ?? [] as $item)
                                <span class="item-chip">
                                    <i class="bi bi-wrench" style="font-size: 10px;"></i>
                                    {{ $item->item_name }}
                                </span>
                                @endforeach
                            </div>

                            {{-- Action --}}
                            <div class="mt-3 d-flex gap-2">
                                @if(in_array($vpr->status ?? '', ['scheduled', 'in_service']) && !($schedule->transaction))
                                    <a href="{{ route('requester.service-transaction.create', $vpr->vpr_code) }}"
                                       class="btn btn-sm btn-warning flex-grow-1">
                                        <i class="bi bi-plus-circle me-1"></i> Lapor Transaksi Service
                                    </a>
                                @elseif($schedule->transaction)
                                    <a href="{{ route('requester.service-transaction.index') }}"
                                       class="btn btn-sm btn-outline-success flex-grow-1">
                                        <i class="bi bi-check-circle me-1"></i> Sudah Dilaporkan
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-check text-success fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada jadwal service aktif saat ini</p>
                    </div>
                    @endif

                    {{-- Completed (collapsed) --}}
                    @if($completedSchedules->count() > 0)
                    <hr class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">
                            <i class="bi bi-check2-all me-1"></i> Selesai ({{ $completedSchedules->count() }})
                        </h6>
                        <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#completedList">
                            Tampilkan
                        </button>
                    </div>
                    <div class="collapse" id="completedList">
                        @foreach($completedSchedules as $schedule)
                        @php $vpr = $schedule->partsReplacement; @endphp
                        <div class="schedule-card completed">
                            <div class="schedule-header">
                                <div>
                                    <strong>{{ $vpr->vpr_code ?? '-' }}</strong>
                                    <span class="badge bg-light text-dark ms-1">{{ $vpr->nopol ?? '-' }}</span>
                                    <span class="badge bg-success ms-1">Selesai</span>
                                </div>
                                <small class="text-muted">
                                    {{ $schedule->service_date?->format('d/m/Y') }}
                                </small>
                            </div>
                            <div class="schedule-body">
                                <div class="d-flex flex-wrap">
                                    @foreach($vpr->replacementDetails ?? [] as $item)
                                    <span class="item-chip">{{ $item->item_name }}</span>
                                    @endforeach
                                </div>
                                @if($schedule->transaction)
                                <small class="text-muted mt-1 d-block">
                                    Total: <strong>Rp {{ number_format($schedule->transaction->total_amount ?? 0, 0, ',', '.') }}</strong>
                                </small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
