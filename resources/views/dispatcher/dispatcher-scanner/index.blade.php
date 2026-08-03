@extends('templates.main')

@section('title', 'Scanner Dispatcher — Pilih Pemeriksaan')

@section('content')

<style>
    #reader {
        width: 100%;
        max-width: 520px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #198754;
        background: #000;
        transform: scaleX(-1);
    }
    .scan-card {
        border-radius: 16px;
        border: 1px solid #e9ecef;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .scan-status {
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 600;
    }
    .pulse-ring {
        width: 14px; height: 14px;
        border-radius: 50%;
        background: #198754;
        animation: pulse 1.2s infinite;
        flex-shrink: 0;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.3); }
    }
    .divider-text {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #adb5bd;
        font-size: 13px;
    }
    .divider-text::before,
    .divider-text::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #dee2e6;
    }
    .badge-dispatcher {
        background: #198754;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
</style>

<div class="pagetitle">
    <h1>Scanner Dispatcher</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Scanner Dispatcher</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">

            <div class="text-center mb-3">
                <span class="badge-dispatcher">
                    <i class="bi bi-tools me-1"></i> Scanner Pemeriksaan
                </span>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card scan-card">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-qr-code-scan" style="font-size: 2.5rem; color: #198754;"></i>
                        <h5 class="fw-bold mt-2 mb-1">Scan Barcode Kendaraan</h5>
                        <p class="text-muted small mb-0">Scan QR untuk memulai pemeriksaan kendaraan</p>
                    </div>

                    <div class="scan-status mb-3" id="scanStatus">
                        <div class="pulse-ring"></div>
                        <span style="color: #198754;">Memulai kamera...</span>
                    </div>

                    <div id="reader"></div>

                    <div class="divider-text my-4">atau masukkan kode manual</div>

                    <form action="{{ route('dispatcher.dispatcher-scanner.result') }}" method="GET" id="manualForm">
                        <div class="input-group">
                            <input
                                type="text"
                                name="code"
                                id="manualCode"
                                class="form-control"
                                placeholder="Contoh: D 1077 ALV"
                                autocomplete="off"
                                required
                            >
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                        </div>
                        <div class="form-text text-center mt-2">Masukkan kode kendaraan (plat nomor) secara manual</div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const resultUrl = "{{ route('dispatcher.dispatcher-scanner.result') }}";
    const statusEl  = document.getElementById('scanStatus');

    function setStatus(msg, color = '#198754', showPulse = true) {
        statusEl.innerHTML = (showPulse ? '<div class="pulse-ring" style="background:' + color + '"></div>' : '') +
            '<span style="color:' + color + '">' + msg + '</span>';
    }

    let scanned = false;

    const html5QrCode = new Html5Qrcode("reader");

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        aspectRatio: 1.0,
    };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            if (scanned) return;
            scanned = true;

            setStatus('QR Code ditemukan! Mengalihkan...', '#198754', true);

            html5QrCode.stop().catch(() => {});

            const url = resultUrl + '?code=' + encodeURIComponent(decodedText);
            window.location.href = url;
        },
        (errorMsg) => {
            // Diabaikan
        }
    ).then(() => {
        setStatus('Kamera aktif — scan QR kendaraan untuk memulai pemeriksaan', '#198754', true);
    }).catch((err) => {
        setStatus('Kamera tidak dapat diakses. Gunakan input manual di bawah.', '#dc3545', false);
        console.error('Kamera error:', err);
    });
})();
</script>
@endpush

@endsection