{{--
  Panel ringkasan form surat tugas (driver & admin). Angka & teks diisi resources/js/pages/spd/surat-create.js
  lewat id summaryLama, summaryTanggal, summaryPemakai, summaryKendaraan, summaryJenis.
  Slot `rows` = baris tambahan di daftar, slot utama = info + tombol.
--}}
@props(['title' => 'Ringkasan Pengajuan', 'badge' => null, 'badgeIcon' => 'bi bi-hourglass-split'])

<x-ui.card class="gap-0 overflow-hidden pt-0">
    <div class="bg-sidebar-gradient ornament-corner px-6 py-5 text-white" style="--ornament: #fff; --ornament-dot: #fff">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-white/70">{{ $title }}</p>
            @if ($badge)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-medium ring-1 ring-white/20"><i class="{{ $badgeIcon }}"></i> {{ $badge }}</span>
            @endif
        </div>
        <p class="mt-3 flex items-baseline gap-2">
            <span id="summaryLama" class="text-4xl font-bold leading-none tabular-nums tracking-tight">-</span>
            <span class="text-sm text-white/80">hari perjalanan</span>
        </p>
        <p id="summaryTanggal" class="mt-2 text-sm text-white/80">Pilih tanggal berangkat dan kembali</p>
    </div>

    <x-ui.card-content class="grid gap-4 pt-5">
        <dl class="grid gap-3 text-sm">
            @foreach ([['bi-people', 'Pemakai', 'summaryPemakai', '1 orang'], ['bi-truck', 'Kendaraan', 'summaryKendaraan', '-'], ['bi-signpost-split', 'Jenis SPD', 'summaryJenis', '-']] as [$icon, $label, $id, $default])
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-2 text-muted-foreground">
                        <span class="flex size-7 items-center justify-center rounded-md bg-muted"><i class="bi {{ $icon }}"></i></span>{{ $label }}
                    </dt>
                    <dd id="{{ $id }}" class="truncate text-right font-medium">{{ $default }}</dd>
                </div>
            @endforeach
            {{ $rows ?? '' }}
        </dl>
        {{ $slot }}
    </x-ui.card-content>
</x-ui.card>
