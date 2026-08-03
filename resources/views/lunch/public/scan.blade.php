@extends('templates.public', ['title' => 'Scan QR Lunch'])

@section('content')
<div class="text-center mb-4 pt-3">
  <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="Logo" height="48" class="mb-2">
  <h4 class="fw-bold mb-1">Lunch Attendance</h4>
  <p class="text-muted mb-0">Arahkan kamera ke QR lokasi makan siang</p>
</div>

<div class="card shadow-sm">
  <div class="card-body p-3">
    <div id="reader" class="rounded overflow-hidden"></div>

    <div id="scan-error" class="alert alert-warning d-none mt-3 mb-0">
      <i class="bi bi-exclamation-triangle me-1"></i>
      <span id="scan-error-text">Kamera tidak dapat diakses.</span>
    </div>
  </div>
</div>

<p class="text-center text-muted small mt-3 mb-0">
  QR tidak terbaca? Buka langsung halaman lokasi kamu — tempelan QR juga
  memuat tautan langsung.
</p>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
  const errorBox  = document.getElementById('scan-error');
  const errorText = document.getElementById('scan-error-text');

  function goTo(decodedText) {
    // QR berisi URL halaman attendance (…/lunch-attendance/site/{CODE})
    // atau cukup kode site polos (PMU / Patuha / BCMP).
    try {
      const url = new URL(decodedText);
      if (url.pathname.includes('/lunch-attendance/site/')) {
        window.location.href = url.href;
        return;
      }
    } catch (e) { /* bukan URL — perlakukan sebagai kode site */ }

    window.location.href = '{{ url('lunch-attendance/site') }}/' + encodeURIComponent(decodedText.trim());
  }

  const scanner = new Html5Qrcode('reader');

  scanner.start(
    { facingMode: 'environment' },
    {
      fps: 10,
      qrbox: { width: 230, height: 230 },
      formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
    },
    (decodedText) => {
      scanner.stop().catch(() => {});
      goTo(decodedText);
    },
    () => { /* frame tanpa QR — abaikan */ }
  ).catch((err) => {
    errorBox.classList.remove('d-none');
    errorText.textContent = 'Kamera tidak dapat diakses: ' + err;
  });
</script>
@endpush
