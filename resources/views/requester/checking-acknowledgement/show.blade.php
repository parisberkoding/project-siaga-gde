@extends('templates.main')

@section('title', 'Detail Pengecekan')

@section('content')

<style>
    .info-pair { margin-bottom: 8px; }
    .info-pair .info-label {
        font-size: 11px; color: #6c757d;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .info-pair .info-value { font-weight: 600; color: #012970; }

    .item-row {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        gap: 10px;
    }
    .item-row:last-child { border-bottom: none; }

    .status-dot {
        width: 36px; height: 36px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700;
        flex-shrink: 0;
    }
    .status-dot.ok    { background: #d1e7dd; color: #198754; }
    .status-dot.ng    { background: #f8d7da; color: #dc3545; }
    .status-dot.baik  { background: #d1e7dd; color: #198754; }
    .status-dot.cukup { background: #cff4fc; color: #0dcaf0; }
    .status-dot.kurang{ background: #fff3cd; color: #997404; }
    .status-dot.tidak { background: #f8d7da; color: #dc3545; }

    .ack-banner {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
        border: 2px solid #ffc107;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .ack-banner.done {
        background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
        border-color: #198754;
    }

    .ack-btn {
        padding: 14px 32px;
        font-size: 16px;
        font-weight: 700;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .ack-btn:active { transform: scale(0.97); }

    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }
    .photo-grid .photo-item {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e9ecef;
        cursor: pointer;
    }
    .photo-grid .photo-item img {
        width: 100%; height: 120px;
        object-fit: cover;
    }
    .photo-grid .photo-item .photo-info {
        padding: 6px 8px;
        font-size: 11px;
    }

    /* Lightbox */
    .lightbox-overlay {
        display: none;
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        align-items: center; justify-content: center;
        cursor: pointer;
    }
    .lightbox-overlay.active { display: flex; }
    .lightbox-overlay img {
        max-width: 92vw; max-height: 88vh;
        border-radius: 8px;
    }
</style>

<div class="pagetitle">
    <h1>Detail Pengecekan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('requester.checking-acknowledgement.index') }}">Pengecekan</a></li>
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

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════ --}}
        {{-- LEFT: Info + Acknowledge        --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Info Card --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi</h5>

                    <div class="info-pair">
                        <div class="info-label">Kode</div>
                        <div class="info-value">{{ $detail->checking_detail_code }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Jenis</div>
                        <div class="info-value">
                            @if($detail->type === 'routine')
                                Rutin — {{ $detail->getSubTypeLabel() }}
                            @else
                                Pengecekan Body
                            @endif
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Kendaraan</div>
                        <div class="info-value">
                            {{ $detail->vehicleChecking->nopol ?? '-' }}
                            <br><small class="text-muted fw-normal">{{ $detail->vehicleChecking->vehicle->consumerial_name ?? '' }}</small>
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Diperiksa Oleh</div>
                        <div class="info-value">{{ $detail->dispatcher->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Tanggal Periksa</div>
                        <div class="info-value">{{ $detail->checked_at?->translatedFormat('d F Y, H:i') }}</div>
                    </div>
                    @if($detail->notes)
                    <div class="info-pair">
                        <div class="info-label">Catatan Dispatcher</div>
                        <div class="info-value fw-normal fst-italic">{{ $detail->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Summary Card --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Ringkasan</h5>

                    @if($detail->type === 'routine')
                        <div class="row text-center g-2">
                            <div class="col-6">
                                <div class="rounded p-2" style="background: #d1e7dd;">
                                    <div class="fw-bold fs-4" style="color: #198754;">{{ $summary['ok'] }}</div>
                                    <small style="color: #198754;">OK</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rounded p-2" style="background: #f8d7da;">
                                    <div class="fw-bold fs-4" style="color: #dc3545;">{{ $summary['ng'] }}</div>
                                    <small style="color: #dc3545;">NG</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row text-center g-2">
                            <div class="col-3">
                                <div class="rounded p-2" style="background: #d1e7dd;">
                                    <div class="fw-bold" style="color: #198754;">{{ $summary['baik'] ?? 0 }}</div>
                                    <small style="color: #198754; font-size: 10px;">Baik</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="rounded p-2" style="background: #cff4fc;">
                                    <div class="fw-bold" style="color: #0dcaf0;">{{ $summary['cukup'] ?? 0 }}</div>
                                    <small style="color: #0dcaf0; font-size: 10px;">Cukup</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="rounded p-2" style="background: #fff3cd;">
                                    <div class="fw-bold" style="color: #997404;">{{ $summary['kurang'] ?? 0 }}</div>
                                    <small style="color: #997404; font-size: 10px;">Kurang</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="rounded p-2" style="background: #f8d7da;">
                                    <div class="fw-bold" style="color: #dc3545;">{{ $summary['tidak'] ?? 0 }}</div>
                                    <small style="color: #dc3545; font-size: 10px;">Tidak</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(($summary['needs_replacement'] ?? 0) > 0)
                    <div class="alert alert-warning mt-3 mb-0 py-2 px-3">
                        <i class="bi bi-tools me-1"></i>
                        <strong>{{ $summary['needs_replacement'] }}</strong> item perlu penggantian
                    </div>
                    @endif
                </div>
            </div>

            {{-- Acknowledge Banner --}}
            <div class="ack-banner {{ $detail->driver_acknowledged ? 'done' : '' }} mb-3">
                @if($detail->driver_acknowledged)
                    <i class="bi bi-check-circle-fill fs-1 d-block mb-2" style="color: #198754;"></i>
                    <strong style="color: #198754;">Sudah Di-acknowledge</strong>
                    <br><small class="text-muted">
                        {{ $detail->driver_acknowledged_at?->translatedFormat('d F Y, H:i') }}
                    </small>
                @else
                    <i class="bi bi-exclamation-circle-fill fs-1 d-block mb-2" style="color: #997404;"></i>
                    <p class="mb-3" style="color: #997404; font-weight: 500;">
                        Anda diminta untuk menghadiri dan mengetahui hasil pengecekan kendaraan ini.
                    </p>
                    <form action="{{ route('requester.checking-acknowledgement.acknowledge', $detail->checking_detail_code) }}"
                          method="POST"
                          onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'bi bi-hourglass-split me-1\'></i> Memproses...';">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="ack-btn btn btn-warning w-100">
                            <i class="bi bi-hand-index me-1"></i>
                            Saya Menghadiri dan Mengetahui
                        </button>
                    </form>
                @endif
            </div>

            {{-- Back --}}
            <a href="{{ route('requester.checking-acknowledgement.index') }}" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

        </div>

        {{-- ═══════════════════════════════ --}}
        {{-- RIGHT: Item Results (Read-only) --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Item Results --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        Hasil Pemeriksaan
                        <span class="badge bg-secondary rounded-pill">{{ $detail->itemResults->count() }}</span>
                    </h5>

                    @foreach($detail->itemResults as $result)
                    <div class="item-row">
                        {{-- Status Dot --}}
                        <div class="status-dot {{ $result->status }}">
                            @if($result->status === 'ok')
                                ✓
                            @elseif($result->status === 'ng')
                                ✗
                            @else
                                {{ strtoupper(substr($result->status, 0, 1)) }}
                            @endif
                        </div>

                        {{-- Item Info --}}
                        <div class="flex-grow-1">
                            <strong>{{ $result->vehiclePart->name ?? 'Item #' . $loop->iteration }}</strong>
                            @if($result->vehiclePart->standard_description)
                                <br><small class="text-muted">{{ $result->vehiclePart->standard_description }}</small>
                            @endif
                            @if($result->notes)
                                <br><small class="fst-italic text-muted">
                                    <i class="bi bi-chat-left-text me-1"></i>{{ $result->notes }}
                                </small>
                            @endif
                        </div>

                        {{-- Badges --}}
                        <div class="text-end flex-shrink-0">
                            @php
                                $statusLabel = match($result->status) {
                                    'ok' => 'OK', 'ng' => 'NG',
                                    'baik' => 'Baik', 'cukup' => 'Cukup',
                                    'kurang' => 'Kurang', 'tidak' => 'Tidak',
                                    default => $result->status,
                                };
                                $statusColor = match($result->status) {
                                    'ok', 'baik' => 'bg-success',
                                    'cukup' => 'bg-info',
                                    'kurang' => 'bg-warning text-dark',
                                    'ng', 'tidak' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusColor }}">{{ $statusLabel }}</span>
                            @if($result->needs_replacement)
                                <br><span class="badge bg-danger mt-1" style="font-size: 10px;">
                                    <i class="bi bi-tools me-1"></i>Ganti
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Damage Photos (Body Check) --}}
            @if($detail->type === 'body' && isset($damagePhotos) && $damagePhotos->count() > 0)
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        Foto Kerusakan
                        <span class="badge bg-secondary rounded-pill">{{ $damagePhotos->count() }}</span>
                    </h5>

                    <div class="photo-grid">
                        @foreach($damagePhotos as $photo)
                        <div class="photo-item" onclick="openLightbox('{{ asset('storage/' . $photo->photo) }}')">
                            <img src="{{ asset('storage/' . $photo->photo) }}" alt="Kerusakan">
                            <div class="photo-info">
                                @php
                                    $zoneColors = [
                                        'depan' => '#0d6efd', 'belakang' => '#6610f2',
                                        'kiri' => '#198754', 'kanan' => '#fd7e14',
                                        'atas' => '#0dcaf0', 'bawah' => '#6c757d',
                                        'interior' => '#d63384',
                                    ];
                                @endphp
                                <span class="badge" style="background: {{ $zoneColors[$photo->zone] ?? '#6c757d' }};">
                                    {{ ucfirst($photo->zone) }}
                                </span>
                                @if($photo->description)
                                    <br><small class="text-muted">{{ Str::limit($photo->description, 40) }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Replacement Info (if exists) --}}
            @if($detail->partsReplacement)
            <div class="card border-warning">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-tools text-warning me-1"></i>
                        Permohonan Penggantian
                    </h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $detail->partsReplacement->vpr_code }}</strong>
                            <br>
                            <span class="badge {{ $detail->partsReplacement->getStatusBadgeClass() }}">
                                {{ $detail->partsReplacement->getStatusLabel() }}
                            </span>
                        </div>
                        <small class="text-muted">
                            {{ $detail->partsReplacement->created_at?->translatedFormat('d M Y') }}
                        </small>
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>
</section>

{{-- Lightbox --}}
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="Foto">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
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
