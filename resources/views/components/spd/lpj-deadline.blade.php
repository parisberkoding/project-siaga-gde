{{--
    Penanda batas submit LPJ (3 hari setelah SPD selesai) untuk sebuah surat SPD.
    compact = sembunyikan baris keterangan tanggal.

    PENTING: form LPJ TIDAK pernah ditutup karena lewat deadline. Badge ini
    murni informasi agar driver, GA, dan User yang berangkat sama-sama tahu
    LPJ-nya sudah terlambat.
--}}
@props(['surat', 'compact' => false])

@php
    $lpjDeadline = $surat->lpj_deadline_efektif;
@endphp

@if ($surat->menunggu_lpj)
    <div {{ $attributes->class('grid justify-items-start gap-1') }}>
        @if ($surat->lpj_terlambat)
            <x-ui.badge variant="danger" title="Batas submit LPJ: {{ $lpjDeadline?->translatedFormat('d F Y') }}">
                <i class="bi bi-exclamation-triangle"></i> LPJ Terlambat {{ $surat->lpj_overdue_days }} hari
            </x-ui.badge>
            @unless ($compact)
                <span class="text-xs text-destructive">
                    Batas submit {{ $lpjDeadline?->translatedFormat('d F Y') }}. Form LPJ tetap terbuka, mohon segera diselesaikan.
                </span>
            @endunless
        @else
            <x-ui.badge variant="warning"><i class="bi bi-hourglass-split"></i> Menunggu LPJ</x-ui.badge>
            @if (! $compact && $lpjDeadline)
                <span class="text-xs text-muted-foreground">
                    Batas submit {{ $lpjDeadline->translatedFormat('d F Y') }} ({{ $surat->getDaysUntilLpjDeadline() }} hari lagi)
                </span>
            @endif
        @endif
    </div>
@endif
