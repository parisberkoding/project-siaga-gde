@php
    $jumlahTerlambat = $suratList->where('lpj_terlambat', true)->count();
@endphp

<x-layouts.app title="SPD Berjalan" :breadcrumbs="['SPD Berjalan' => null]">
    <x-ui.page-header kicker="Pelaporan" title="SPD Berjalan" description="Surat tugas yang sudah disetujui dan belum dibuat LPJ-nya. Pilih Lapor & Validasi untuk mengunggah struk." />

    {{-- Satu pengingat saja: terlambat (merah) bila ada, selain itu tidak ada kotak tambahan. --}}
    @if ($jumlahTerlambat > 0)
        <x-ui.alert variant="destructive" :title="$jumlahTerlambat . ' surat melewati batas submit LPJ (3 hari setelah kembali).'">
            Form LPJ tetap terbuka, silakan tetap kirim. LPJ yang masuk setelah batas ditandai terlambat.
        </x-ui.alert>
    @endif

    @if ($suratList->isEmpty())
        <x-ui.card>
            <x-ui.card-content>
                <x-ui.empty-state icon="bi bi-check2-circle" title="Semua sudah dilaporkan" description="Tidak ada surat SPD yang perlu dilaporkan." />
            </x-ui.card-content>
        </x-ui.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            {{-- Yang sudah terlambat paling atas. --}}
            @foreach ($suratList->sortByDesc(fn ($s) => (int) $s->lpj_terlambat) as $surat)
                <article class="flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-shadow hover:shadow-md">
                    <div @class(['relative px-5 py-4 text-white', 'bg-linear-to-br from-rose-600 to-red-700' => $surat->lpj_terlambat, 'bg-brand-gradient' => ! $surat->lpj_terlambat])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="grid min-w-0 gap-1">
                                <span class="font-mono text-xs text-white/80">{{ $surat->nomor_surat }}</span>
                                <span class="truncate text-base font-semibold" title="{{ $surat->tempat_tujuan }}"><i class="bi bi-geo-alt"></i> {{ $surat->tempat_tujuan }}</span>
                            </div>
                            @if ($surat->is_extension)
                                <span class="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium backdrop-blur"><i class="bi bi-arrow-repeat"></i> Perpanjangan</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid flex-1 gap-3 px-5 py-4 text-sm">
                        <div class="grid grid-cols-[auto_1fr] items-center gap-x-3 gap-y-2">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-info-soft text-info"><i class="bi bi-calendar-range"></i></span>
                            <span>
                                {{ $surat->tanggal_berangkat->translatedFormat('d M') }} sampai {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}
                                <span class="block text-xs text-muted-foreground">{{ (int) $surat->tanggal_berangkat->diffInDays($surat->tanggal_kembali) + 1 }} hari perjalanan</span>
                            </span>
                            <span class="flex size-8 items-center justify-center rounded-lg bg-warning-soft text-warning"><i class="bi bi-car-front"></i></span>
                            <span>{{ $surat->vehicle?->consumerial_code ?? '-' }} <span class="text-xs text-muted-foreground">{{ $surat->vehicle?->consumerial_name }}</span></span>
                            <span class="flex size-8 items-center justify-center rounded-lg bg-success-soft text-success"><i class="bi bi-receipt"></i></span>
                            <span class="flex flex-wrap items-center gap-1.5">
                                @if ($surat->daily_reports_available)
                                    <x-ui.badge variant="success">{{ $surat->daily_submitted_count }} hari terkirim</x-ui.badge>
                                    <span class="text-xs text-muted-foreground">{{ $surat->daily_item_count }} struk</span>
                                    @if ($surat->has_draft_daily)<x-ui.badge variant="warning">ada draft</x-ui.badge>@endif
                                @else
                                    <span class="text-muted-foreground">Belum ada struk</span>
                                @endif
                            </span>
                        </div>
                        <x-spd.lpj-deadline :surat="$surat" />
                    </div>

                    <div class="border-t bg-muted/30 px-5 py-3">
                        <x-ui.button class="w-full" :href="route('pelaporan.spd.validasi', $surat)"><i class="bi bi-cloud-arrow-up"></i> Lapor &amp; Validasi SPD</x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
