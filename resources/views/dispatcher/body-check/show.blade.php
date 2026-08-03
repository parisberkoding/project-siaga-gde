@extends('templates.main')

@section('title', 'Detail Pengecekan Body')

@section('content')

<style>
    .summary-box {
        border-radius: 10px;
        padding: 14px;
        text-align: center;
    }
    .summary-box h3 {
        font-size: 26px;
        font-weight: 700;
        margin: 0;
    }
    .summary-box .label {
        font-size: 12px;
        color: #6c757d;
    }

    .result-row {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 0;
    }
    .result-row:last-child { border-bottom: none; }

    .status-badge-lg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        height: 32px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
    }
    .status-badge-lg.baik   { background: #d1e7dd; color: #198754; }
    .status-badge-lg.cukup  { background: #cff4fc; color: #0dcaf0; }
    .status-badge-lg.kurang { background: #fff3cd; color: #997404; }
    .status-badge-lg.tidak  { background: #f8d7da; color: #dc3545; }

    .info-pair {
        margin-bottom: 6px;
    }
    .info-pair .info-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-pair .info-value {
        font-weight: 600;
        color: #012970;
    }

    /* BBM Gauge Display */
    .bbm-bar {
        height: 16px;
        border-radius: 8px;
        background: #e9ecef;
        overflow: hidden;
        position: relative;
    }
    .bbm-bar .fill {
        height: 100%;
        border-radius: 8px;
        transition: width 0.5s;
    }

    /* Photo Gallery */
    .damage-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .damage-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .damage-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .damage-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        cursor: pointer;
    }
    .damage-card .damage-info {
        padding: 10px;
    }
    .damage-card .zone-badge {
        font-size: 11px;
    }

    /* Lightbox */
    .lightbox-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .lightbox-overlay.active { display: flex; }
    .lightbox-overlay img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 8px;
    }

    @media (max-width: 576px) {
        .damage-gallery {
            grid-template-columns: 1fr 1fr;
        }
        .damage-card img {
            height: 120px;
        }
    }
</style>

<div class="pagetitle">
    <h1>Detail Pengecekan Body</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.body-check.index') }}">Pengecekan Body</a></li>
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

{{-- Prompt VPR --}}
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
        {{-- LEFT: Info + Summary                        --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Info Card --}}
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

                    {{-- Keperluan --}}
                    @if($detail->keperluan)
                    <div class="info-pair">
                        <div class="info-label">Keperluan</div>
                        <div class="info-value fw-normal">{{ $detail->keperluan }}</div>
                    </div>
                    @endif

                    {{-- BBM --}}
                    @if($detail->bbm_level !== null)
                    <div class="info-pair">
                        <div class="info-label">Level BBM</div>
                        <div class="info-value">
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <small class="text-muted">E</small>
                                <div class="bbm-bar flex-grow-1">
                                    @php
                                        $bbmColor = $detail->bbm_level > 50 ? '#198754' : ($detail->bbm_level > 25 ? '#ffc107' : '#dc3545');
                                    @endphp
                                    <div class="fill" style="width: {{ $detail->bbm_level }}%; background: {{ $bbmColor }};"></div>
                                </div>
                                <small class="text-muted">F</small>
                                <strong>{{ $detail->bbm_level }}%</strong>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Driver Ack --}}
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

                    {{-- VPR Link --}}
                    @if($detail->partsReplacement)
                    <hr>
                    <div class="info-pair">
                        <div class="info-label">Permohonan Penggantian</div>
                        <div class="info-value">
                            <a href="{{ route('dispatcher.replacement.show', $detail->partsReplacement->vpr_code) }}" class="text-decoration-none">
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

            {{-- Summary Card --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Ringkasan</h5>

                    <div class="row g-2">
                        <div class="col-6">
                            <div class="summary-box" style="background: #d1e7dd;">
                                <h3 style="color: #198754;">{{ $summary['baik'] }}</h3>
                                <div class="label">Baik</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="summary-box" style="background: #cff4fc;">
                                <h3 style="color: #0dcaf0;">{{ $summary['cukup'] }}</h3>
                                <div class="label">Cukup</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="summary-box" style="background: #fff3cd;">
                                <h3 style="color: #997404;">{{ $summary['kurang'] }}</h3>
                                <div class="label">Kurang</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="summary-box" style="background: #f8d7da;">
                                <h3 style="color: #dc3545;">{{ $summary['tidak'] }}</h3>
                                <div class="label">Tidak</div>
                            </div>
                        </div>
                    </div>

                    {{-- Needs replacement --}}
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

                    {{-- Condition Score --}}
                    @if($summary['total'] > 0)
                    @php
                        // Score: Baik=3, Cukup=2, Kurang=1, Tidak=0
                        $score = ($summary['baik']*3 + $summary['cukup']*2 + $summary['kurang']*1) / ($summary['total'] * 3) * 100;
                        $scoreColor = $score >= 70 ? '#198754' : ($score >= 40 ? '#ffc107' : '#dc3545');
                    @endphp
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Skor Kondisi Body</span>
                            <span style="color: {{ $scoreColor }}; font-weight: 700;">{{ round($score) }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar" style="width: {{ $score }}%; background: {{ $scoreColor }};"></div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-grid gap-2">
                        @if($summary['needs_replacement'] > 0 && !$detail->partsReplacement)
                            <a href="{{ route('dispatcher.replacement.create', $detail->checking_detail_code) }}" class="btn btn-warning">
                                <i class="bi bi-tools me-1"></i> Ajukan Permohonan Penggantian
                            </a>
                        @endif
                        <a href="{{ route('dispatcher.body-check.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT: Item Results + Photo Gallery          --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Item Results --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Hasil Pengecekan Kelengkapan</h5>

                    @forelse($detail->itemResults as $item)
                    <div class="result-row">
                        <div class="row align-items-center g-2">
                            {{-- Status Badge --}}
                            <div class="col-auto">
                                <div class="status-badge-lg {{ $item->status }}">
                                    {{ ucfirst($item->status) }}
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
                    @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada data hasil pengecekan</p>
                    </div>
                    @endforelse

                </div>
            </div>

            {{-- Damage Photos Gallery --}}
            @if(isset($detail->damagePhotos) && $detail->damagePhotos->count() > 0)
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-camera me-1"></i> Dokumentasi Kerusakan
                        <span class="badge bg-secondary rounded-pill">{{ $detail->damagePhotos->count() }}</span>
                    </h5>

                    <div class="damage-gallery">
                        @foreach($detail->damagePhotos as $photo)
                        <div class="damage-card">
                            @php
                                $photoUrl = asset('storage/' . $photo->photo);
                            @endphp
                            <img src="{{ $photoUrl }}" alt="Kerusakan"
                                 onclick="openLightbox('{{ $photoUrl }}')"
                                 loading="lazy">
                            <div class="damage-info">
                                @php
                                    $zoneLabels = [
                                        'depan' => 'Tampak Depan', 'belakang' => 'Tampak Belakang',
                                        'kiri' => 'Tampak Kiri', 'kanan' => 'Tampak Kanan',
                                        'atas' => 'Tampak Atas', 'bawah' => 'Tampak Bawah',
                                        'interior' => 'Interior',
                                    ];
                                    $zoneColors = [
                                        'depan' => 'bg-primary', 'belakang' => 'bg-info',
                                        'kiri' => 'bg-warning text-dark', 'kanan' => 'bg-success',
                                        'atas' => 'bg-secondary', 'bawah' => 'bg-dark',
                                        'interior' => 'bg-danger',
                                    ];
                                @endphp
                                <span class="badge zone-badge {{ $zoneColors[$photo->zone] ?? 'bg-secondary' }}">
                                    {{ $zoneLabels[$photo->zone] ?? $photo->zone }}
                                </span>
                                @if($photo->description)
                                    <p class="small text-muted mt-1 mb-0">{{ $photo->description }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>
</section>

{{-- Lightbox --}}
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <img src="" id="lightboxImg" alt="Photo">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

@endsection
