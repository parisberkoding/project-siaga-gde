@extends('templates.main')

@section('title', 'Jadwal Service Kendaraan')

@section('content')

<style>
    .schedule-card {
        border: 1px solid #E4E9F0;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }
    .schedule-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .schedule-card.overdue { border-left: 4px solid #ED1B2F; }
    .schedule-card.today { border-left: 4px solid #D8CE3A; }
    .schedule-card.upcoming { border-left: 4px solid #0B50A1; }
    .schedule-card.completed { border-left: 4px solid #71A62F; opacity: 0.8; }

    .calendar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .month-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .month-nav h5 { margin: 0; color: #093D7D; font-weight: 700; }
</style>

<div class="pagetitle">
    <h1>Jadwal Service Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Jadwal Service</li>
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
                            <span class="text-muted small pt-2">Jadwal terlewat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Hari Ini</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['today'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Jadwal hari ini</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Terjadwal</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $counts['upcoming'] ?? 0 }}</h6>
                            <span class="text-muted small pt-2">Akan datang</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
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
    </div>
</section>

<section class="section dashboard">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- Month Nav + Filter --}}
                    <div class="calendar-header pt-3">
                        <div class="month-nav">
                            <a href="{{ route('admin.service-scheduling.index', ['month' => $prevMonth]) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <h5>{{ $currentMonthLabel }}</h5>
                            <a href="{{ route('admin.service-scheduling.index', ['month' => $nextMonth]) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="GET" action="{{ route('admin.service-scheduling.index') }}" class="d-flex gap-2">
                                <input type="hidden" name="month" value="{{ request('month', now()->format('Y-m')) }}">
                                <select name="vehicle_id" class="form-select form-select-sm" style="width: auto;"
                                        onchange="this.form.submit()">
                                    <option value="">Semua Kendaraan</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    {{-- Schedule List --}}
                    @if($schedules->count() > 0)

                        {{-- Overdue --}}
                        @php $overdueItems = $schedules->where('group', 'overdue'); @endphp
                        @if($overdueItems->count() > 0)
                        <h6 class="fw-bold text-danger mt-3 mb-2">
                            <i class="bi bi-exclamation-triangle me-1"></i> Overdue
                        </h6>
                        @foreach($overdueItems as $item)
                        <div class="schedule-card overdue">
                            <div class="row align-items-center g-2">
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Tanggal Service</small>
                                    <br><strong class="text-danger">{{ $item->scheduled_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Kendaraan</small>
                                    <br><strong>{{ $item->nopol ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Driver</small>
                                    <br><strong>{{ optional($item->desiredDriver)->nama_lengkap ?? optional($item->lastDriver)->nama_lengkap ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Lokasi</small>
                                    <br>{{ Str::limit($item->service_location ?? '-', 25) }}
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Deadline</small>
                                    <br><span class="text-danger fw-bold">{{ $item->scheduled_date?->format('d/m/Y') }}</span>
                                    <br><small class="text-danger">{{ abs($item->scheduled_date?->diffInDays(now()) ?? 0) }} hari lalu</small>
                                </div>
                                <div class="col-md-2 col-6 text-end">
                                    @if($item->vpr_code)
                                        <a href="{{ route('admin.replacement-approval.show', $item->vpr_code) }}"
                                           class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                        {{-- Today --}}
                        @php $todayItems = $schedules->where('group', 'today'); @endphp
                        @if($todayItems->count() > 0)
                        <h6 class="fw-bold text-warning mt-3 mb-2">
                            <i class="bi bi-clock me-1"></i> Hari Ini
                        </h6>
                        @foreach($todayItems as $item)
                        <div class="schedule-card today">
                            <div class="row align-items-center g-2">
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Tanggal Service</small>
                                    <br><strong>{{ $item->scheduled_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Kendaraan</small>
                                    <br><strong>{{ $item->nopol ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Driver</small>
                                    <br><strong>{{ optional($item->desiredDriver)->nama_lengkap ?? optional($item->lastDriver)->nama_lengkap ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Lokasi</small>
                                    <br>{{ Str::limit($item->service_location ?? '-', 25) }}
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Status</small>
                                    <br><span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                </div>
                                <div class="col-md-2 col-6 text-end">
                                    <a href="{{ route('admin.replacement-approval.show', $item->vpr_code ?? '') }}"
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                        {{-- Upcoming --}}
                        @php $upcomingItems = $schedules->where('group', 'upcoming'); @endphp
                        @if($upcomingItems->count() > 0)
                        <h6 class="fw-bold mt-3 mb-2" style="color: #0B50A1;">
                            <i class="bi bi-calendar-event me-1"></i> Terjadwal
                        </h6>
                        @foreach($upcomingItems as $item)
                        <div class="schedule-card upcoming">
                            <div class="row align-items-center g-2">
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Tanggal Service</small>
                                    <br><strong>{{ $item->scheduled_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Kendaraan</small>
                                    <br><strong>{{ $item->nopol ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Driver</small>
                                    <br><strong>{{ optional($item->desiredDriver)->nama_lengkap ?? optional($item->lastDriver)->nama_lengkap ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Lokasi</small>
                                    <br>{{ Str::limit($item->service_location ?? '-', 25) }}
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Deadline</small>
                                    <br>{{ $item->transaction?->deadline_date?->format('d/m/Y') ?? '-' }}
                                </div>
                                <div class="col-md-2 col-6 text-end">
                                    <a href="{{ route('admin.replacement-approval.show', $item->vpr_code ?? '') }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                        {{-- Completed --}}
                        @php $completedItems = $schedules->where('group', 'completed'); @endphp
                        @if($completedItems->count() > 0)
                        <h6 class="fw-bold text-success mt-3 mb-2">
                            <i class="bi bi-check2-all me-1"></i> Selesai
                        </h6>
                        @foreach($completedItems as $item)
                        <div class="schedule-card completed">
                            <div class="row align-items-center g-2">
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Tanggal Service</small>
                                    <br><strong>{{ $item->scheduled_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Kendaraan</small>
                                    <br><strong>{{ $item->nopol ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Driver</small>
                                    <br>{{ optional($item->desiredDriver)->nama_lengkap ?? optional($item->lastDriver)->nama_lengkap ?? '-' }}
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Lokasi</small>
                                    <br>{{ Str::limit($item->service_location ?? '-', 25) }}
                                </div>
                                <div class="col-md-2 col-6">
                                    <small class="text-muted">Biaya</small>
                                    <br><strong>Rp {{ number_format($item->transaction?->total_amount ?? 0, 0, ',', '.') }}</strong>
                                </div>
                                <div class="col-md-2 col-6 text-end">
                                    <a href="{{ route('admin.replacement-approval.show', $item->vpr_code ?? '') }}"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada jadwal service untuk bulan ini</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
