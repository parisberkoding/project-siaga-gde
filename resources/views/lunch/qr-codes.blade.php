@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>QR Lokasi — Lunch Attendance</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">QR Lokasi</li>
        </ol>
    </nav>
</div>

<section class="section">

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Cetak dan tempel QR di area makan masing-masing site. Karyawan cukup scan
        dengan kamera HP — halaman attendance terbuka <strong>tanpa login</strong>.
    </div>

    <div class="row g-3">
        @foreach($sites as $site)
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h4 class="fw-bold mt-2">{{ $site->name }}</h4>
                    <span class="badge bg-primary mb-3">{{ $site->code }}</span>
                    <div>
                        <img src="{{ route('lunch.qr-image', $site) }}" alt="QR {{ $site->code }}"
                             class="img-fluid border rounded" style="max-width: 260px;">
                    </div>
                    <p class="text-muted small mt-3 mb-1">
                        {{ route('lunch.attendance.form', $site->code) }}
                    </p>
                    <button class="btn btn-outline-primary btn-sm" onclick="printQr('{{ $site->code }}', this)">
                        <i class="bi bi-printer me-1"></i>Cetak
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</section>
@endsection

@push('scripts')
<script>
function printQr(code, btn) {
    const card = btn.closest('.card');
    const win  = window.open('', '_blank');
    win.document.write('<html><head><title>QR ' + code + '</title></head>' +
        '<body style="text-align:center;font-family:sans-serif;padding-top:40px;">' +
        card.querySelector('h4').outerHTML + card.querySelector('img').outerHTML +
        '<p>Scan untuk absen makan siang</p></body></html>');
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 400);
}
</script>
@endpush
