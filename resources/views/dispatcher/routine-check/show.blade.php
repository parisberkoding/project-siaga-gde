@extends('templates.main')

@section('title', 'Detail Pengecekan Rutin')

@section('content')

<style>
    .result-ok { color: #4A7A1C; }
    .result-ng { color: #C4142A; }

    .summary-box {
        border-radius: 10px;
        padding: 16px;
        text-align: center;
    }
    .summary-box h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .summary-box .label {
        font-size: 13px;
        color: #64748B;
    }

    .result-row {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 0;
    }
    .result-row:last-child {
        border-bottom: none;
    }

    .status-badge-lg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 16px;
    }
    .status-badge-lg.ok {
        background: #d1e7dd;
        color: #4A7A1C;
    }
    .status-badge-lg.ng {
        background: #f8d7da;
        color: #C4142A;
    }

    .category-section {
        border-left: 4px solid #093D7D;
        padding-left: 16px;
        margin-bottom: 8px;
    }
    .category-section.cat-kesiapan { border-color: #0B50A1; }
    .category-section.cat-keselamatan { border-color: #C4142A; }
    .category-section.cat-kelengkapan { border-color: #D8CE3A; }

    .info-pair {
        margin-bottom: 6px;
    }
    .info-pair .info-label {
        font-size: 12px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-pair .info-value {
        font-weight: 600;
        color: #093D7D;
    }
</style>

<div class="pagetitle">
    <h1>Detail Pengecekan Rutin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">Pengecekan Rutin</li>
            <li class="breadcrumb-item active">{{ $detail->checking_detail_code }}</li>
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

{{-- Prompt: ada item perlu diganti --}}
@if(session('show_replacement_prompt') && $summary['needs_replacement'] > 0)
<div class="alert alert-warning" role="alert">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-wrench-adjustable-circle fs-3"></i>
        <div class="flex-grow-1">
            <strong>{{ $summary['needs_replacement'] }} item perlu penggantian.</strong>
            <br><span class="small">Ajukan permohonan penggantian sparepart sekarang?</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dispatcher.replacement.create', $detail->checking_detail_code) }}" class="btn btn-outline-warning btn-sm flex-fill">
                <i class="bi bi-tools me-1"></i> Ajukan Manual
            </a>
            <form action="{{ route('dispatcher.replacement.auto-create', $detail->checking_detail_code) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-magic me-1"></i> Auto-Ajukan
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- INFO HEADER + SUMMARY                       --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Detail Info --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi Pengecekan</h5>

                    <div class="info-pair">
                        <div class="info-label">Kode</div>
                        <div class="info-value">{{ $detail->checking_detail_code }}</div>
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
                            @php
                                $subTypeLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
                                $subTypeColors = ['daily' => 'bg-primary', 'weekly' => 'bg-info', 'monthly' => 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $subTypeColors[$detail->sub_type] ?? 'bg-secondary' }}">
                                {{ $subTypeLabels[$detail->sub_type] ?? $detail->sub_type }}
                            </span>
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
                                <span class="badge bg-success">
                                    <i class="bi bi-check-lg me-1"></i> Sudah
                                </span>
                                <br><small class="text-muted fw-normal">{{ $detail->driver_acknowledged_at?->translatedFormat('d M Y, H:i') }}</small>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-hourglass-split me-1"></i> Belum
                                </span>
                            @endif
                        </div>
                    </div>
                    @if($detail->notes)
                    <div class="info-pair">
                        <div class="info-label">Catatan</div>
                        <div class="info-value fw-normal">{{ $detail->notes }}</div>
                    </div>
                    @endif

                    {{-- VPR Status --}}
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

            {{-- Summary --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Ringkasan</h5>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="summary-box" style="background: #F8FAFC;">
                                <h3>{{ $summary['total'] }}</h3>
                                <div class="label">Total Item</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-box" style="background: #d1e7dd;">
                                <h3 class="result-ok">{{ $summary['ok'] }}</h3>
                                <div class="label">OK (V)</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-box" style="background: #f8d7da;">
                                <h3 class="result-ng">{{ $summary['ng'] }}</h3>
                                <div class="label">NG (X)</div>
                            </div>
                        </div>
                    </div>

                    @if($summary['needs_replacement'] > 0)
                    <div class="mt-2">
                        <div class="summary-box" style="background: #fff3cd;">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-wrench text-warning"></i>
                                <strong>{{ $summary['needs_replacement'] }}</strong>
                                <span class="label mb-0">perlu penggantian</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Progress bar --}}
                    @if($summary['total'] > 0)
                    <div class="mt-3">
                        @php $okPercent = round(($summary['ok'] / $summary['total']) * 100); @endphp
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Kondisi Kendaraan</span>
                            <span>{{ $okPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $okPercent }}%"></div>
                            <div class="progress-bar bg-danger" style="width: {{ 100 - $okPercent }}%"></div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-grid gap-2">
                        @if($summary['needs_replacement'] > 0 && !$detail->partsReplacement)
                            <a href="{{ route('dispatcher.replacement.create', $detail->checking_detail_code) }}"
                               class="btn btn-warning">
                                <i class="bi bi-tools me-1"></i> Ajukan Permohonan Penggantian
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- HASIL PER ITEM — GROUPED BY CATEGORY        --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Hasil Pengecekan</h5>

                    @forelse($resultsByCategory as $category => $items)
                    @php
                        $catLabels = [
                            'kesiapan' => 'Kesiapan',
                            'keselamatan' => 'Keselamatan',
                            'kelengkapan' => 'Kelengkapan',
                        ];
                        $catIcons = [
                            'kesiapan' => 'bi-shield-check',
                            'keselamatan' => 'bi-heart-pulse',
                            'kelengkapan' => 'bi-gear',
                        ];
                    @endphp

                    <div class="category-section cat-{{ $category }} mb-4">
                        <h6 class="fw-bold text-uppercase mb-3">
                            <i class="bi {{ $catIcons[$category] ?? 'bi-list-check' }} me-1"></i>
                            {{ $catLabels[$category] ?? ucfirst($category) }}
                            <span class="badge bg-light text-dark ms-1">{{ $items->count() }} item</span>
                        </h6>

                        @foreach($items as $item)
                        <div class="result-row">
                            <div class="row align-items-center g-2">
                                {{-- Status Badge --}}
                                <div class="col-auto">
                                    <div class="status-badge-lg {{ $item->status }}">
                                        @if($item->status === 'ok')
                                            <i class="bi bi-check2-circle"></i>
                                        @else
                                            <i class="bi bi-x-circle"></i>
                                        @endif
                                    </div>
                                </div>

                                {{-- Item Name --}}
                                <div class="col">
                                    <strong>{{ $item->vehiclePart->name }}</strong>
                                    @if($item->vehiclePart->standard_description)
                                        <br><small class="text-muted">Standar: {{ $item->vehiclePart->standard_description }}</small>
                                    @endif
                                    @if($item->notes)
                                        <br><small class="fst-italic"><i class="bi bi-chat-left-text me-1"></i>{{ $item->notes }}</small>
                                    @endif
                                </div>

                                {{-- Badges --}}
                                <div class="col-auto">
                                    @if($item->needs_replacement)
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-wrench me-1"></i> Perlu Ganti
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada data hasil pengecekan</p>
                    </div>
                    @endforelse

                </div>
            </div>
        </div>

    </div>
</section>

@endsection
