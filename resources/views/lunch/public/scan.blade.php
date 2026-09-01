@extends('templates.public', ['title' => 'Scan QR Lunch'])

@push('head')
<style>
  body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    min-height: 100vh;
  }
  .scan-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    background: #ffffff;
    overflow: hidden;
  }
  .scanner-container {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    background: #111;
    border: 3px solid #0d6efd;
    box-shadow: 0 0 20px rgba(13, 110, 253, 0.2);
  }
  #reader {
    width: 100% !important;
    border: none !important;
  }
  #reader video {
    object-fit: cover !important;
    border-radius: 12px;
  }
  /* Scanner Laser Effect */
  .scanner-laser {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(to right, transparent, #0d6efd, transparent);
    animation: scanning 2.5s linear infinite;
    z-index: 10;
    pointer-events: none;
  }
  @keyframes scanning {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
  }
  .btn-manual {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
  }
  .btn-manual:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
  }
  .footer-hint {
    font-size: 0.85rem;
    color: #6c757d;
  }
  .header-logo {
    transition: transform 0.3s ease;
  }
  .header-logo:hover {
    transform: scale(1.05);
  }
</style>
@endpush

@section('content')
<div class="text-center mb-4 pt-4">
  <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="Logo Geodipa" height="56" class="mb-3 header-logo">
  <h3 class="fw-bold mb-1 text-dark">Lunch Attendance</h3>
  <p class="text-muted px-3">Scan QR Code di lokasi kantin atau meja katering untuk mencatat kehadiran makan siang Anda.</p>
</div>

<div class="card scan-card mb-4">
  <div class="card-body p-4">
    <div class="scanner-container">
      <div class="scanner-laser"></div>
      <div id="reader"></div>
    </div>

    <div id="scan-error" class="alert alert-danger d-none mt-3 mb-0 border-0 rounded-3">
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
        <div>
          <strong class="d-block">Akses Kamera Gagal</strong>
          <span id="scan-error-text" class="small">Kamera tidak dapat diakses atau izin ditolak.</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="text-center px-2">
  <p class="footer-hint mb-3">
    <i class="bi bi-info-circle me-1"></i> QR tidak terbaca? Anda bisa membuka halaman kehadiran langsung dari daftar lokasi di bawah ini.
  </p>
  
  <div class="d-grid gap-2">
    <a href="{{ url('lunch-attendance/site/PMU') }}" class="btn btn-outline-primary btn-manual py-2-5">
      <i class="bi bi-geo-alt me-1"></i>Absen di PMU (Jakarta)
    </a>
    <a href="{{ url('lunch-attendance/site/Patuha') }}" class="btn btn-outline-success btn-manual py-2-5">
      <i class="bi bi-geo-alt me-1"></i>Absen di Patuha
    </a>
    <a href="{{ url('lunch-attendance/site/BCMP') }}" class="btn btn-outline-info btn-manual py-2-5">
      <i class="bi bi-geo-alt me-1"></i>Absen di Dieng / BCMP
    </a>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
<script nonce="{{ csp_nonce() }}">
  const errorBox  = document.getElementById('scan-error');
  const errorText = document.getElementById('scan-error-text');

  function goTo(decodedText) {
    try {
      const url = new URL(decodedText);
      if (url.pathname.includes('/lunch-attendance/site/')) {
        window.location.href = url.href;
        return;
      }
    } catch (e) { /* Buka URL langsung jika bukan format valid */ }

    window.location.href = '{{ url('lunch-attendance/site') }}/' + encodeURIComponent(decodedText.trim());
  }

  const scanner = new Html5Qrcode('reader');

  scanner.start(
    { facingMode: 'environment' },
    {
      fps: 15,
      qrbox: function(width, height) {
        const size = Math.min(width, height) * 0.7;
        return { width: size, height: size };
      },
      formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
    },
    (decodedText) => {
      scanner.stop().catch(() => {});
      goTo(decodedText);
    },
    () => { /* frame terlewati - scan terus berjalan */ }
  ).catch((err) => {
    errorBox.classList.remove('d-none');
    errorText.textContent = 'Pastikan izin kamera diberikan: ' + err;
  });
</script>
@endpush
