@extends('templates.main')

@section('title', 'Detail Pengecekan Mingguan')

@section('content')

<style>
    .result-ok { color: #4A7A1C; }
    .result-ng { color: #C4142A; }
    .summary-box { border-radius: 10px; padding: 16px; text-align: center; }
    .summary-box h3 { font-size: 28px; font-weight: 700; margin: 0; }
    .summary-box .label { font-size: 13px; color: #64748B; }
    .result-row { border-bottom: 1px solid #f0f0f0; padding: 10px 0; }
    .result-row:last-child { border-bottom: none; }
    .status-badge-lg {
        display: inline-flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 8px; font-weight: 700; font-size: 16px;
    }
    .status-badge-lg.ok { background: #d1e7dd; color: #4A7A1C; }
    .status-badge-lg.ng { background: #f8d7da; color: #C4142A; }
    .category-section { border-left: 4px solid #0B50A1; padding-left: 16px; margin-bottom: 8px; }
    .info-pair { margin-bottom: 6px; }
    .info-pair .info-label { font-size: 12px; color: #64748B; text-transform: uppercase; letter-spacing: .5px; }
    .info-pair .info-value { font-weight: 600; color: #093D7D; }
</style>

<div class="pagetitle">
    <h1>Detail Pengecekan Mingguan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.weekly-check.index') }}">Pengecekan Mingguan</a></li>
            <li class="breadcrumb-item active">{{ $detail->checking_detail_code }}</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('show_replacement_prompt') && $summary['needs_replacement'] > 0)
<div class="alert alert-warning">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-wrench-adjustable-circle fs-3"></i>
        <div class="flex-grow-1">
            <strong>{{ $summary['needs_replacement'] }} item perlu penggantian.</strong>
            <br><span class="small">Ajukan permohonan penggantian sparepart sekarang?</span>
        </div>
        <a href="{{ route('dispatcher.replacement.create', $detail->checking_detail_code) }}"
           class="btn btn-warning btn-sm">
            <i class="bi bi-tools me-1"></i> Ajukan Permohonan
        </a>
    </div>
</div>
@endif

<section class="section">
    <div class="row">

        <div class="col-lg-4">

            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi Pengecekan</h5>

                    <div class="info-pair">
                        <div class="info-label">Kode</div>
                        <div class="info-value"><code>{{ $detail->checking_detail_code }}</code></div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Kendaraan</div>
                        <div class="info-value">
                            {{ $detail->vehicleChecking->nopol ?? '-' }}
                            <br><small class="text-muted fw-normal">{{ $detail->vehicleChecking->vehicle->consumerial_name ?? '' }}</small>
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Jenis Pengecekan</div>
                        <div class="info-value">
                            <span class="badge bg-info text-white">Mingguan</span>
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Tanggal & Waktu</div>
                        <div class="info-value">{{ $detail->checked_at->translatedFormat('d F Y, H:i') }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Dispatcher</div>
                        <div class="info-value">{{ $detail->dispatcher->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Driver</div>
                        <div class="info-value">{{ $detail->currentDriver->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Driver Acknowledgement</div>
                        <div class="info-value">
                            @if($detail->driver_acknowledged)
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Sudah</span>
                                <br><small class="text-muted fw-normal">{{ $detail->driver_acknowledged_at?->translatedFormat('d M Y, H:i') }}</small>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Belum</span>
                            @endif
                        </div>
                    </div>
                    @if($detail->notes)
                    <div class="info-pair">
                        <div class="info-label">Catatan</div>
                        <div class="info-value fw-normal">{{ $detail->notes }}</div>
                    </div>
                    @endif

                    @if($detail->partsReplacement)
                    <hr>
                    <div class="info-pair">
                        <div class="info-label">Permohonan Penggantian</div>
                        <div class="info-value">
                            <a href="{{ route('dispatcher.replacement.show', $detail->partsReplacement->vpr_code) }}"
                               class="text-decoration-none">
                                {{ $detail->partsReplacement->vpr_code }}
                            </a>
                            <br>
                            <span class="badge {{ $detail->partsReplacement->getStatusBadgeClass() }}">
                                {{ $detail->partsReplacement->getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Ringkasan</h5>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="summary-box" style="background:#F8FAFC;">
                                <h3>{{ $summary['total'] }}</h3>
                                <div class="label">Total</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-box" style="background:#d1e7dd;">
                                <h3 class="result-ok">{{ $summary['ok'] }}</h3>
                                <div class="label">OK (V)</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-box" style="background:#f8d7da;">
                                <h3 class="result-ng">{{ $summary['ng'] }}</h3>
                                <div class="label">NG (X)</div>
                            </div>
                        </div>
                        @if($summary['needs_replacement'] > 0)
                        <div class="col-12">
                            <div class="summary-box" style="background:#fff3cd;">
                                <h3 style="color:#856404;">{{ $summary['needs_replacement'] }}</h3>
                                <div class="label">Perlu Ganti</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Hasil Pengecekan</h5>

                    @forelse($resultsByCategory as $category => $items)
                    <div class="category-section mb-3">
                        <h6 class="text-uppercase text-muted fw-semibold mb-3">{{ ucfirst($category) }}</h6>

                        @foreach($items as $result)
                        <div class="result-row">
                            <div class="row align-items-center g-2">
                                <div class="col-auto">
                                    <div class="status-badge-lg {{ $result->status }}">
                                        @if($result->status === 'ok')
                                            <i class="bi bi-check2"></i>
                                        @else
                                            <i class="bi bi-x-lg"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="col">
                                    <strong>{{ $result->vehiclePart->name ?? '-' }}</strong>
                                    @if($result->needs_replacement)
                                        <span class="badge bg-warning text-dark ms-1">
                                            <i class="bi bi-wrench me-1"></i>Perlu Ganti
                                        </span>
                                    @endif
                                    @if($result->notes)
                                        <br><small class="text-muted">{{ $result->notes }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @empty
                    <p class="text-muted">Tidak ada item hasil pengecekan.</p>
                    @endforelse

                </div>
            </div>
        </div>

    </div>
</section>

@endsection
