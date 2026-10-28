{{-- Banner kuota AI (Gemini) habis, untuk peran admin/GA. Logika sama dengan partials/gemini-status-banner. --}}
@php
    $role = Auth::user()->role ?? null;
    $visible = in_array($role, ['superadmin', 'admin', 'admin_bbm', 'general_affairs'], true)
        && \App\Services\GeminiStatusService::isActive();
    $since = $visible ? \App\Services\GeminiStatusService::since() : null;
@endphp

@if ($visible)
    <div class="border-b border-warning/30 bg-warning-soft px-4 py-2 text-sm text-warning md:px-6" role="alert">
        <i class="bi bi-exclamation-triangle-fill mr-1"></i>
        <strong>Layanan AI (Gemini) mencapai batas kuota</strong>
        @if ($since) sejak {{ $since->timezone(config('app.timezone'))->format('d M Y H:i') }}@endif.
        Analisis struk otomatis tidak tersedia sementara, pengguna akan diminta mengisi data struk secara manual.
    </div>
@endif
