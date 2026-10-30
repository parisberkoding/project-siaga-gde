@php
    $jumlahTerlambat = $suratList->where('lpj_terlambat', true)->count();
@endphp

<x-layouts.app title="SPD Berjalan" :breadcrumbs="['SPD Berjalan' => null]">
    <x-ui.page-header kicker="Pelaporan" title="SPD Berjalan" description="Surat tugas yang sudah disetujui dan belum dibuat laporannya." />

    <x-ui.alert variant="info">
        Klik <strong>Lapor &amp; Validasi SPD</strong> pada surat yang bersangkutan untuk mengunggah struk dan menyelesaikan LPJ.
    </x-ui.alert>

    @if ($jumlahTerlambat > 0)
        <x-ui.alert variant="destructive" :title="$jumlahTerlambat . ' surat sudah melewati batas submit LPJ (3 hari setelah SPD selesai).'">
            Form LPJ <strong>tidak ditutup</strong>, silakan tetap submit. LPJ yang masuk setelah batas akan ditandai
            <em>terlambat</em> dan terlihat oleh Divisi GA &amp; pengguna yang berangkat.
        </x-ui.alert>
    @endif

    <x-ui.card>
        <x-ui.card-header title="Surat Tugas yang Harus Dilaporkan" icon="bi bi-car-front" description="{{ $suratList->count() }} surat menunggu laporan" />
        <x-ui.card-content>
            @if ($suratList->isEmpty())
                <x-ui.empty-state icon="bi bi-check2-circle" title="Semua sudah dilaporkan" description="Tidak ada surat SPD yang perlu dilaporkan." />
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Surat Tugas</th>
                            <th>Jadwal</th>
                            <th>Kendaraan</th>
                            <th>Batas Submit LPJ</th>
                            <th>Laporan Harian</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratList as $surat)
                            <tr @class(['bg-destructive-soft/60' => $surat->lpj_terlambat])>
                                <td>
                                    <span class="font-medium">{{ $surat->nomor_surat }}</span>
                                    @if ($surat->is_extension)
                                        <x-ui.badge variant="info"><i class="bi bi-arrow-repeat"></i> Perpanjangan</x-ui.badge>
                                    @endif
                                    <span class="block max-w-64 truncate text-xs text-muted-foreground" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</span>
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    {{ $surat->tanggal_berangkat->translatedFormat('l, d F Y') }} s/d<br>
                                    {{ $surat->tanggal_kembali->translatedFormat('l, d F Y') }}
                                </td>
                                <td>{{ $surat->vehicle?->consumerial_code ?? '-' }}</td>
                                <td><x-spd.lpj-deadline :surat="$surat" /></td>
                                <td>
                                    @if ($surat->daily_reports_available)
                                        <div class="grid justify-items-start gap-1">
                                            <x-ui.badge variant="success">{{ $surat->daily_submitted_count }} hari terkirim</x-ui.badge>
                                            <span class="text-xs text-muted-foreground">{{ $surat->daily_item_count }} item</span>
                                            @if ($surat->has_draft_daily)
                                                <x-ui.badge variant="warning">ada draft</x-ui.badge>
                                            @endif
                                        </div>
                                    @else
                                        <x-ui.badge variant="muted">belum ada</x-ui.badge>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <x-ui.button size="sm" :href="route('pelaporan.spd.validasi', $surat)"><i class="bi bi-calendar3"></i> Lapor &amp; Validasi SPD</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
