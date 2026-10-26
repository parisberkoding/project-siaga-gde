{{-- Banner status "Gemini Reach Limit" — tampil untuk superadmin/admin/GA
     bila provider OCR sedang kena rate-limit (flag cache dari GeminiStatusService).
     Flag otomatis hilang saat ada analisis yang berhasil lagi atau setelah TTL. --}}
@php
    $geminiRole = Auth::user()->role ?? null;
    $geminiBannerVisible = $geminiRole !== null
        && in_array($geminiRole, ['superadmin', 'admin', 'admin_bbm', 'general_affairs'], true)
        && \App\Services\GeminiStatusService::isActive();
    $geminiSince = $geminiBannerVisible ? \App\Services\GeminiStatusService::since() : null;
@endphp
@if ($geminiBannerVisible)
<div class="alert alert-warning alert-dismissible fade show mb-0 rounded-0 d-flex align-items-center gap-2"
     role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="small">
        <strong>Layanan AI (Gemini) mencapai batas kuota</strong>
        @if ($geminiSince)
            sejak {{ $geminiSince->timezone(config('app.timezone'))->format('d M Y H:i') }}.
        @else
            .
        @endif
        Analisis struk otomatis tidak tersedia sementara — pengguna akan diminta <strong>mengisi data struk secara manual</strong>.
        Mohon informasikan kepada driver/staf terkait.
    </div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
@endif
