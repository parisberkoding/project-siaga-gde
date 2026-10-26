@extends('templates.main')

@section('title', 'Pilih Pemeriksaan — ' . $vehicle->consumerial_code)

@section('content')

<style>
    .vehicle-header-card {
        background: linear-gradient(135deg, #093D7D 0%, #0B50A1 100%);
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
    .barcode-img svg {
        width: 100px;
        height: auto;
    }

    .check-type-card {
        border-radius: 16px;
        border: 2px solid #E4E9F0;
        padding: 24px 16px;
        text-align: center;
        transition: all 0.2s;
        cursor: pointer;
        background: #fff;
        height: 100%;
        text-decoration: none;
        display: block;
    }
    .check-type-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    .check-type-card:active {
        transform: translateY(-1px);
    }

    .check-type-card .icon-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 28px;
    }
    .check-type-card h5 {
        font-weight: 700;
        margin-bottom: 4px;
    }
    .check-type-card p {
        font-size: 13px;
        color: #64748B;
        margin-bottom: 0;
    }

    .card-daily .icon-circle   { background: #d1e7dd; color: #4A7A1C; }
    .card-daily:hover          { border-color: #4A7A1C; }
    .card-weekly .icon-circle  { background: #fff3cd; color: #997404; }
    .card-weekly:hover         { border-color: #997404; }
    .card-monthly .icon-circle { background: #f8d7da; color: #842029; }
    .card-monthly:hover        { border-color: #842029; }
    .card-body-check .icon-circle { background: #e8f4f8; color: #0B50A1; }
    .card-body-check:hover     { border-color: #0B50A1; }

    .badge-dispatcher {
        background: #71A62F;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
</style>

<div class="pagetitle">
    <h1>Pilih Pemeriksaan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('dispatcher.dispatcher-scanner') }}">Scanner Dispatcher</a>
            </li>
            <li class="breadcrumb-item active">{{ $vehicle->consumerial_code }}</li>
        </ol>
    </nav>
</div>

<section class="section">

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

            @if($barcode && file_exists($barcode->full_path))
            <div class="barcode-img text-center">
                {!! file_get_contents($barcode->full_path) !!}
                <div class="text-muted" style="font-size:10px; margin-top:4px; color:#666!important;">
                    {{ $vehicle->consumerial_code }}
                </div>
            </div>
            @endif
        </div>

        <div class="mt-3">
            <a href="{{ route('dispatcher.dispatcher-scanner') }}" class="btn btn-light btn-sm">
                <i class="bi bi-qr-code-scan me-1"></i> Scan Ulang
            </a>
            <a href="{{ route('dispatcher.scan-vehicle.result', ['code' => request('code')]) }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-clock-history me-1"></i> Lihat Riwayat Pengecekan
            </a>
        </div>
    </div>

    <div class="text-center mb-4">
        <span class="badge-dispatcher">
            <i class="bi bi-tools me-1"></i> Pilih Jenis Pemeriksaan
        </span>
    </div>

    <div class="row g-4">
        {{-- Daily Check --}}
        <div class="col-6 col-md-3">
            <a href="{{ route('dispatcher.daily-check.create', ['vehicle_id' => $vehicle->id]) }}"
               class="check-type-card card-daily">
                <div class="icon-circle">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <h5 style="color: #4A7A1C;">Harian</h5>
                <p>Pemeriksaan harian kendaraan</p>
            </a>
        </div>

        {{-- Weekly Check --}}
        <div class="col-6 col-md-3">
            <a href="{{ route('dispatcher.weekly-check.create', ['vehicle_id' => $vehicle->id]) }}"
               class="check-type-card card-weekly">
                <div class="icon-circle">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <h5 style="color: #997404;">Mingguan</h5>
                <p>Pemeriksaan mingguan kendaraan</p>
            </a>
        </div>

        {{-- Monthly Check --}}
        <div class="col-6 col-md-3">
            <a href="{{ route('dispatcher.monthly-check.create', ['vehicle_id' => $vehicle->id]) }}"
               class="check-type-card card-monthly">
                <div class="icon-circle">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <h5 style="color: #842029;">Bulanan</h5>
                <p>Pemeriksaan bulanan kendaraan</p>
            </a>
        </div>

        {{-- Body Check --}}
        <div class="col-6 col-md-3">
            <a href="{{ route('dispatcher.body-check.create', ['vehicle_id' => $vehicle->id]) }}"
               class="check-type-card card-body-check">
                <div class="icon-circle">
                    <i class="bi bi-car-front"></i>
                </div>
                <h5 style="color: #0B50A1;">Body</h5>
                <p>Pemeriksaan body kendaraan</p>
            </a>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('dispatcher.dispatcher-scanner') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Scanner
        </a>
    </div>

</section>

@endsection