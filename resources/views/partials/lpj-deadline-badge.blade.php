{{--
    Penanda batas submit LPJ (3 hari setelah SPD selesai) untuk sebuah surat SPD.

    Parameter:
      - $surat   : App\Models\SuratSpd (wajib)
      - $compact : bool (opsional) — sembunyikan baris keterangan tanggal

    PENTING: form LPJ TIDAK pernah ditutup karena lewat deadline. Badge ini
    murni informasi agar driver, GA, dan User yang berangkat sama-sama tahu
    LPJ-nya sudah terlambat.
--}}
@php
    $compact = $compact ?? false;
    $lpjDeadline = $surat->lpj_deadline_efektif;
@endphp

@if($surat->menunggu_lpj)
    @if($surat->lpj_terlambat)
        <span class="badge bg-danger"
              title="Batas submit LPJ: {{ $lpjDeadline?->translatedFormat('d F Y') }}">
            <i class="bi bi-exclamation-triangle"></i> LPJ Terlambat {{ $surat->lpj_overdue_days }} hari
        </span>
        @unless($compact)
            <div class="small text-danger mt-1">
                Batas submit {{ $lpjDeadline?->translatedFormat('d F Y') }} — form LPJ tetap terbuka, mohon segera diselesaikan.
            </div>
        @endunless
    @else
        <span class="badge bg-warning text-dark">Menunggu LPJ</span>
        @unless($compact)
            @if($lpjDeadline)
                <div class="small text-muted mt-1">
                    Batas submit {{ $lpjDeadline->translatedFormat('d F Y') }}
                    ({{ $surat->getDaysUntilLpjDeadline() }} hari lagi)
                </div>
            @endif
        @endunless
    @endif
@endif
