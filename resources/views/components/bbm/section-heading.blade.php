{{--
  Judul seksi halaman Permohonan Aktif: ikon bernada + judul + jumlah (badge di samping judul, tetap dekat
  di HP) + keterangan singkat per poin. Warna mengikuti bar kuota: success = voucher aktif, warning = menunggu.
  points: [['teks', 'ikon bootstrap' | null], ...]; numbered = poin diberi nomor langkah, bukan ikon.
--}}
@props(['id', 'title', 'count' => 0, 'icon', 'tone' => 'success', 'points' => [], 'numbered' => false])

@php
    [$tile, $badge, $dot] = $tone === 'warning'
        ? ['from-amber-400 to-orange-500 shadow-orange-500/25', 'bg-amber-100 text-amber-800 ring-amber-200', 'bg-amber-100 text-amber-800']
        : ['from-emerald-500 to-teal-600 shadow-emerald-500/25', 'bg-emerald-100 text-emerald-700 ring-emerald-200', 'bg-emerald-100 text-emerald-700'];
@endphp

<div class="flex items-start gap-3">
    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-lg text-white shadow-md {{ $tile }}" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
    </span>
    <div class="grid min-w-0 gap-1.5">
        <div class="flex flex-wrap items-center gap-2">
            <h2 id="{{ $id }}" class="text-base font-semibold leading-tight sm:text-lg">{{ $title }}</h2>
            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums ring-1 {{ $badge }}"
                  aria-label="{{ $count }} item">{{ $count }}</span>
        </div>
        @if ($points)
            <ol class="flex flex-col gap-1 text-xs text-muted-foreground sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:text-sm">
                @foreach ($points as $i => [$text, $pointIcon])
                    <li class="flex items-center gap-1.5">
                        @if ($numbered)
                            <span class="flex size-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $dot }}">{{ $i + 1 }}</span>
                        @elseif ($pointIcon)
                            <i class="bi {{ $pointIcon }} shrink-0"></i>
                        @endif
                        {{ $text }}
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
