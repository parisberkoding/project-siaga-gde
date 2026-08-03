@extends('templates.main')

@section('title', 'Riwayat Kendaraan — ' . $vehicle->consumerial_code)

@section('content')

<style>
    .vehicle-header-card {
        background: linear-gradient(135deg, #012970 0%, #4154f1 100%);
        border-radius: 16px;
        color: #fff;
        padding: 24px;
    }
    .barcode-img {
        background: #fff;
        border-radius: 8px;
        padding: 10px 16px;
        max-width: 220px;
    }

    .check-timeline-item {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 10px;
        transition: box-shadow 0.15s;
    }
    .check-timeline-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    .type-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .type-body    { background: #e8f4f8; color: #0d6efd; }
    .type-daily   { background: #d1e7dd; color: #198754; }
    .type-weekly  { background: #fff3cd; color: #997404; }
    .type-monthly { background: #f8d7da; color: #842029; }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .stat-ok  { background: #d1e7dd; color: #198754; }
    .stat-ng  { background: #f8d7da; color: #842029; }

    .empty-state {
        text-align: center;
        padding: 32px;
        color: #adb5bd;
    }

    .nav-pills .nav-link.active {
        background-color: #4154f1;
    }
    .nav-pills .nav-link {
        color: #4154f1;
    }
    .tab-count-badge {
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 20px;
        background: #e9ecef;
        color: #495057;
        margin-left: 4px;
    }
    .nav-link.active .tab-count-badge {
        background: rgba(255,255,255,0.3);
        color: #fff;
    }

    .barcode-img svg {
        width: 100px;
        height: auto;
    }
</style>

<div class="pagetitle">
    <h1>Riwayat Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                @if(in_array(auth()->user()->role, ['dispatcher']))
                    <a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a>
                @endif
            </li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.scan-vehicle') }}">Scan QR Code</a></li>
            <li class="breadcrumb-item active">{{ $vehicle->consumerial_code }}</li>
        </ol>
    </nav>
</div>

<section class="section">

    {{-- ═══════════════════════════════ --}}
    {{-- HEADER KENDARAAN               --}}
    {{-- ═══════════════════════════════ --}}
    <div class="vehicle-header-card mb-4">
        <div class="d-flex flex-wrap align-items-start gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-car-front-fill fs-4"></i>
                    <h4 class="mb-0 fw-bold">{{ $vehicle->consumerial_code }}</h4>
                </div>
                <div class="opacity-85 mb-1">{{ $vehicle->consumerial_name ?? '—' }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-white text-dark fw-semibold">{{ ucfirst($vehicle->consumerial_type ?? '—') }}</span>
                    @if($vehicle->is_active)
                        <span class="badge" style="background:rgba(255,255,255,0.25);">Aktif</span>
                    @else
                        <span class="badge bg-danger">Non-aktif</span>
                    @endif
                </div>
            </div>

            {{-- {{ dd($barcode->full_path, $barcode->url, file_exists($barcode->full_path)) }} --}}

            {{-- QR code image --}}
          @if($barcode && file_exists($barcode->full_path))
            <div class="barcode-img text-center">
                {!! file_get_contents($barcode->full_path) !!}
                <div class="text-muted" style="font-size:10px; margin-top:4px; color:#666!important;">
                    {{ $vehicle->consumerial_code }}
                </div>
            </div>
        @endif
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('dispatcher.scan-vehicle') }}" class="btn btn-light btn-sm">
                <i class="bi bi-qr-code-scan me-1"></i> Scan Ulang
            </a>
            @if(in_array(auth()->user()->role, ['superadmin', 'dispatcher']) && $barcode)
                <a href="{{ route('dispatcher.barcode.download', $vehicle->id) }}"
                   class="btn btn-outline-light btn-sm">
                    <i class="bi bi-download me-1"></i> Download QR Code
                </a>
            @endif
            @if(auth()->user()->role === 'superadmin')
                <a href="{{ route('superadmin.vehicle-health.show', $vehicle->consumerial_code) }}"
                   class="btn btn-outline-light btn-sm">
                    <i class="bi bi-heart-pulse me-1"></i> Lihat Vehicle Health
                </a>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════ --}}
    {{-- RINGKASAN STATISTIK            --}}
    {{-- ═══════════════════════════════ --}}
    @php
        $totalAll = $checkingsByType['body']->count()
                  + $checkingsByType['daily']->count()
                  + $checkingsByType['weekly']->count()
                  + $checkingsByType['monthly']->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center py-3">
                <div style="font-size:22px; font-weight:800; color:#4154f1;">{{ $totalAll }}</div>
                <div class="text-muted" style="font-size:12px;">Total Pengecekan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center py-3">
                <div style="font-size:22px; font-weight:800; color:#0d6efd;">{{ $checkingsByType['body']->count() }}</div>
                <div class="text-muted" style="font-size:12px;">Body Check</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center py-3">
                <div style="font-size:22px; font-weight:800; color:#198754;">{{ $checkingsByType['daily']->count() + $checkingsByType['weekly']->count() + $checkingsByType['monthly']->count() }}</div>
                <div class="text-muted" style="font-size:12px;">Routine Check</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center py-3">
                @php
                    $allCheckings = $checkingsByType['body']->merge($checkingsByType['daily'])
                        ->merge($checkingsByType['weekly'])->merge($checkingsByType['monthly']);
                    $lastCheck = $allCheckings->sortByDesc('checked_at')->first();
                @endphp
                <div style="font-size:14px; font-weight:700; color:#012970;">
                    {{ $lastCheck ? $lastCheck->checked_at->format('d M Y') : '—' }}
                </div>
                <div class="text-muted" style="font-size:12px;">Terakhir Diperiksa</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ --}}
    {{-- TABS RIWAYAT                   --}}
    {{-- ═══════════════════════════════ --}}
    <div class="card">
        <div class="card-body">

            <ul class="nav nav-pills mb-4 flex-wrap gap-1" id="checkingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-all" data-bs-toggle="pill" data-bs-target="#pane-all" type="button">
                        Semua
                        <span class="tab-count-badge">{{ $totalAll }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-body" data-bs-toggle="pill" data-bs-target="#pane-body" type="button">
                        <i class="bi bi-car-front me-1"></i>Body Check
                        <span class="tab-count-badge">{{ $checkingsByType['body']->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-daily" data-bs-toggle="pill" data-bs-target="#pane-daily" type="button">
                        <i class="bi bi-calendar-day me-1"></i>Harian
                        <span class="tab-count-badge">{{ $checkingsByType['daily']->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-weekly" data-bs-toggle="pill" data-bs-target="#pane-weekly" type="button">
                        <i class="bi bi-calendar-week me-1"></i>Mingguan
                        <span class="tab-count-badge">{{ $checkingsByType['weekly']->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-monthly" data-bs-toggle="pill" data-bs-target="#pane-monthly" type="button">
                        <i class="bi bi-calendar-month me-1"></i>Bulanan
                        <span class="tab-count-badge">{{ $checkingsByType['monthly']->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- ALL --}}
                <div class="tab-pane fade show active" id="pane-all" role="tabpanel">
                    @php
                        $allSorted = $allCheckings->sortByDesc('checked_at');
                    @endphp
                    @forelse($allSorted as $detail)
                        @include('dispatcher.barcode-scan._checking-card', ['detail' => $detail])
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-clipboard-x fs-1 mb-2 d-block"></i>
                            Belum ada riwayat pengecekan untuk kendaraan ini.
                        </div>
                    @endforelse
                </div>

                {{-- BODY --}}
                <div class="tab-pane fade" id="pane-body" role="tabpanel">
                    @forelse($checkingsByType['body'] as $detail)
                        @include('dispatcher.barcode-scan._checking-card', ['detail' => $detail])
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-car-front fs-1 mb-2 d-block"></i>
                            Belum ada riwayat body check.
                        </div>
                    @endforelse
                </div>

                {{-- DAILY --}}
                <div class="tab-pane fade" id="pane-daily" role="tabpanel">
                    @forelse($checkingsByType['daily'] as $detail)
                        @include('dispatcher.barcode-scan._checking-card', ['detail' => $detail])
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-calendar-day fs-1 mb-2 d-block"></i>
                            Belum ada riwayat pengecekan harian.
                        </div>
                    @endforelse
                </div>

                {{-- WEEKLY --}}
                <div class="tab-pane fade" id="pane-weekly" role="tabpanel">
                    @forelse($checkingsByType['weekly'] as $detail)
                        @include('dispatcher.barcode-scan._checking-card', ['detail' => $detail])
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-calendar-week fs-1 mb-2 d-block"></i>
                            Belum ada riwayat pengecekan mingguan.
                        </div>
                    @endforelse
                </div>

                {{-- MONTHLY --}}
                <div class="tab-pane fade" id="pane-monthly" role="tabpanel">
                    @forelse($checkingsByType['monthly'] as $detail)
                        @include('dispatcher.barcode-scan._checking-card', ['detail' => $detail])
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-calendar-month fs-1 mb-2 d-block"></i>
                            Belum ada riwayat pengecekan bulanan.
                        </div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>

</section>

@endsection
