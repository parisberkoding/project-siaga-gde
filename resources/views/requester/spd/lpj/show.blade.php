@php
    $surat = $laporan->suratSpd;
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<x-layouts.app title="Detail LPJ" :breadcrumbs="['Laporan LPJ' => route('requester.lpj.index'), 'Detail' => null]">
    <x-ui.page-header kicker="Laporan Pertanggungjawaban" :title="$surat->nomor_surat ?? 'Detail LPJ'" description="Rincian pengeluaran dan status validasi LPJ.">
        <x-slot:actions>
            <x-spd.status-badge :status="$laporan->status" class="px-3 py-1 text-sm" />
            @if ($laporan->is_late)
                <x-ui.badge variant="danger" class="px-3 py-1 text-sm"><i class="bi bi-clock"></i> Terlambat {{ $laporan->days_late }} hari</x-ui.badge>
            @endif
            @if ($laporan->is_over_budget)
                <x-ui.badge variant="warning" class="px-3 py-1 text-sm"><i class="bi bi-exclamation-triangle"></i> Over Budget</x-ui.badge>
            @endif
            <x-ui.button variant="outline" :href="route('requester.lpj.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <div class="grid gap-6 md:grid-cols-2">
                <x-ui.card>
                    <x-ui.card-header title="Surat Tugas" icon="bi bi-file-earmark-text" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Nomor">
                                <span class="font-medium">{{ $surat->nomor_surat ?? '-' }}</span>
                                @if ($surat?->is_extension)
                                    <x-ui.badge variant="info">Perpanjangan</x-ui.badge>
                                @endif
                            </x-ui.detail-row>
                            <x-ui.detail-row label="Tujuan">{{ $surat->tempat_tujuan ?? '-' }}</x-ui.detail-row>
                            <x-ui.detail-row label="Tanggal">
                                @if ($surat)
                                    {{ $surat->tanggal_berangkat->translatedFormat('d M Y') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-muted-foreground">{{ $surat->lama_perjalanan_hari }} hari</span>
                                @else
                                    -
                                @endif
                            </x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Pelapor" icon="bi bi-person" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Nama"><span class="font-medium">{{ $laporan->nama }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Jabatan">{{ $laporan->jabatan }}</x-ui.detail-row>
                            <x-ui.detail-row label="Kendaraan">
                                <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $laporan->nomor_polisi }}</x-ui.badge>
                                <span class="block text-xs text-muted-foreground">{{ $laporan->vehicle->consumerial_name ?? '' }}</span>
                            </x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>
            </div>

            @if ($laporan->uraian)
                <x-ui.card>
                    <x-ui.card-header title="Uraian Perjalanan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm">{{ $laporan->uraian }}</p></x-ui.card-content>
                </x-ui.card>
            @endif

            <x-ui.card>
                <x-ui.card-header title="Rincian Pengeluaran" icon="bi bi-list-check" :description="$laporan->details->count() . ' item'" />
                <x-ui.card-content>
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th class="w-12">No</th>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th class="text-right">Nominal</th>
                                <th>Struk</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($laporan->details as $index => $detail)
                                <tr>
                                    <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                    <td class="whitespace-nowrap">{{ $detail->tanggal?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            <x-spd.tipe-badge :tipe="$detail->tipe_penggunaan" />
                                            @if ($detail->strukItem?->kategori_source === 'ai')
                                                <x-ui.badge variant="info" title="Dianalisis AI/OCR"><i class="bi bi-stars"></i> AI</x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($detail->nominal) }}</td>
                                    <td>
                                        @if ($detail->strukItem?->path_image)
                                            <x-ui.button variant="ghost" size="icon-sm" :href="asset('storage/' . $detail->strukItem->path_image)" target="_blank" title="Lihat struk" aria-label="Lihat struk"><i class="bi bi-image"></i></x-ui.button>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted-foreground">{{ $detail->keterangan ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-muted-foreground">Tidak ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/50 font-semibold">
                                <td colspan="3" class="text-right">Total Pengeluaran</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($laporan->total_pengeluaran) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Ringkasan Keuangan" icon="bi bi-calculator" />
                <x-ui.card-content class="grid gap-6 md:grid-cols-2">
                    <dl class="divide-y">
                        <x-ui.detail-row label="Biaya BBM" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_bbm) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Tol" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_tol) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Portir" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_portir) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Lain-lain" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_lain) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Total" numeric><span class="font-bold tabular-nums">{{ $rp($laporan->total_pengeluaran) }}</span></x-ui.detail-row>
                    </dl>
                    <div class="grid content-start gap-4">
                        <dl class="divide-y">
                            <x-ui.detail-row label="Uang dari" numeric>{{ $laporan->uang_dari ?? 'KAS' }}</x-ui.detail-row>
                            <x-ui.detail-row label="Jumlah diberikan" numeric><span class="tabular-nums">{{ $rp($laporan->jumlah_diberikan) }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Pengembalian" numeric><span @class(['tabular-nums', 'font-semibold text-success' => $laporan->pengembalian > 0])>{{ $rp($laporan->pengembalian) }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Kekurangan" numeric><span @class(['tabular-nums', 'font-semibold text-warning' => $laporan->kekurangan > 0])>{{ $rp($laporan->kekurangan) }}</span></x-ui.detail-row>
                        </dl>
                        @if ($surat)
                            <x-ui.alert :variant="$laporan->is_over_budget ? 'warning' : 'info'">
                                <p>Budget <strong>{{ $rp($surat->total_budget) }}</strong>, terpakai <strong>{{ $rp($laporan->total_pengeluaran) }}</strong>@if ($laporan->is_over_budget) (over budget)@endif.</p>
                            </x-ui.alert>
                        @endif
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            @if ($laporan->status !== 'pending')
                @if ($laporan->status === 'approved')
                    <x-ui.alert variant="success" title="Laporan disetujui">
                        Oleh {{ $laporan->approverPengguna->nama_lengkap ?? $laporan->approverPengguna->name ?? '-' }}
                        pada {{ $laporan->approved_pengguna_at?->translatedFormat('d F Y H:i') ?? '-' }}.
                    </x-ui.alert>
                @else
                    <x-ui.alert variant="destructive" title="Laporan ditolak">
                        <p>Oleh {{ $laporan->rejector->nama_lengkap ?? $laporan->rejector->name ?? '-' }} pada {{ $laporan->rejected_at?->translatedFormat('d F Y H:i') ?? '-' }}.</p>
                        <p><strong>Alasan:</strong> {{ $laporan->rejection_reason }}</p>
                    </x-ui.alert>
                @endif
            @endif
        </div>

        <div class="grid gap-6">
            <x-ui.card class="gap-4">
                <x-ui.card-header title="Status Laporan" icon="bi bi-info-circle" />
                <x-ui.card-content class="grid gap-3">
                    @if ($laporan->status === 'pending')
                        <x-ui.alert variant="warning">Menunggu validasi.</x-ui.alert>
                    @elseif ($laporan->status === 'approved')
                        <x-ui.alert variant="success">Laporan telah disetujui.</x-ui.alert>
                        <x-ui.button :href="route('spd.laporan.export', $laporan)" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Cetak SPD + LPJ</x-ui.button>
                    @else
                        <x-ui.alert variant="destructive" title="Laporan ditolak">{{ $laporan->rejection_reason }}</x-ui.alert>
                        @can('permission', 'lpj.update')
                            <x-ui.button :href="route('requester.lpj.edit', $laporan)"><i class="bi bi-pencil"></i> Revisi LPJ</x-ui.button>
                        @endcan
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Informasi Pengajuan" icon="bi bi-calendar" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Tanggal LPJ">{{ $laporan->tanggal_lpj?->translatedFormat('d M Y') ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Batas">
                            <span @class(['text-destructive font-medium' => $laporan->is_late])>{{ $laporan->due_date?->translatedFormat('d M Y') ?? '-' }}</span>
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Diajukan">{{ $laporan->created_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                        <x-ui.detail-row label="Diperbarui">{{ $laporan->updated_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            @if ($laporan->catatan)
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Catatan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm">{{ $laporan->catatan }}</p></x-ui.card-content>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
