@php
    $canUpdate = Auth::user()->hasPermission('lpj.update');
@endphp

<x-layouts.app title="Laporan SPD (LPJ)" :breadcrumbs="['Laporan LPJ' => null]">
    <x-ui.page-header kicker="Pelaporan" title="Laporan Pertanggungjawaban" description="LPJ perjalanan dinas yang sudah Anda kirim beserta status validasinya." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total LPJ" :value="$stats['total']" icon="bi bi-file-earmark-text" hint="Semua laporan" />
        <x-ui.stat-card label="Menunggu" :value="$stats['pending']" icon="bi bi-hourglass-split" tone="warning" hint="Menunggu validasi" />
        <x-ui.stat-card label="Disetujui" :value="$stats['approved']" icon="bi bi-check-circle" tone="success" hint="Laporan disetujui" />
        <x-ui.stat-card label="Ditolak" :value="$stats['rejected']" icon="bi bi-x-circle" :tone="$stats['rejected'] ? 'danger' : 'muted'" hint="Perlu direvisi" />
    </div>

    <x-ui.card>
        <x-ui.card-header title="Daftar Laporan Pertanggungjawaban" icon="bi bi-file-earmark-check" />
        <x-ui.card-content>
            @if ($laporanList->isEmpty())
                <x-ui.empty-state icon="bi bi-file-earmark-x" title="Belum ada laporan pertanggungjawaban" description="LPJ dibuat dari surat tugas yang sudah disetujui.">
                    <x-ui.button class="mt-2" :href="route('requester.surat.index')"><i class="bi bi-plus-lg"></i> Buat Laporan Pertama</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table :datatable="['perPage' => 10]">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Surat Tugas</th>
                            <th>Tujuan</th>
                            <th>Kendaraan</th>
                            <th class="text-right">Total Pengeluaran</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($laporanList as $index => $lpj)
                            <tr>
                                <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                <td>
                                    <span class="font-medium">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</span>
                                    @if ($lpj->suratSpd?->is_extension)
                                        <x-ui.badge variant="info" class="mt-1 flex"><i class="bi bi-arrow-repeat"></i> Perpanjangan</x-ui.badge>
                                    @endif
                                </td>
                                <td class="text-muted-foreground">{{ Str::limit($lpj->suratSpd->tempat_tujuan ?? '-', 25) }}</td>
                                <td><x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $lpj->nomor_polisi }}</x-ui.badge></td>
                                <td data-order="{{ $lpj->total_pengeluaran }}" class="whitespace-nowrap text-right">
                                    <span class="font-semibold tabular-nums">Rp {{ number_format($lpj->total_pengeluaran, 0, ',', '.') }}</span>
                                    @if ($lpj->is_over_budget)
                                        <x-ui.badge variant="warning" class="mt-1 ml-auto flex">Over Budget</x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="grid justify-items-start gap-1">
                                        <x-spd.status-badge :status="$lpj->status" />
                                        @if ($lpj->is_late)
                                            <x-ui.badge variant="danger"><i class="bi bi-alarm"></i> Terlambat</x-ui.badge>
                                        @endif
                                    </div>
                                </td>
                                <td data-order="{{ $lpj->created_at->timestamp }}" class="whitespace-nowrap">
                                    {{ $lpj->created_at->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-muted-foreground">{{ $lpj->created_at->format('H:i') }}</span>
                                </td>
                                <td class="text-center">
                                    <x-ui.dropdown-menu :id="'aksi-lpj-' . $lpj->id" label="Aksi LPJ" @class(['border-destructive/50 text-destructive' => $lpj->status === 'rejected'])>
                                        <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $lpj->suratSpd->nomor_surat ?? '-' }}</code></div>
                                        <x-ui.dropdown-item icon="bi bi-eye" :href="route('requester.lpj.show', $lpj)">Lihat detail</x-ui.dropdown-item>
                                        {{-- BUG-RBAC-003: export butuh lpj.export; pemilik tanpa izin itu tetap bisa mencetak LPJ yang sudah disetujui. --}}
                                        @if (Auth::user()->hasPermission('lpj.export'))
                                            <x-ui.dropdown-item icon="bi bi-file-earmark-pdf" :href="route('spd.laporan.export', $lpj)" target="_blank">Export SPD + LPJ</x-ui.dropdown-item>
                                        @elseif ($lpj->status === 'approved')
                                            <x-ui.dropdown-item icon="bi bi-printer" :href="route('spd.laporan.print', $lpj)" target="_blank">Cetak SPD + LPJ</x-ui.dropdown-item>
                                        @endif
                                        @if ($lpj->status === 'rejected' && $canUpdate)
                                            <x-ui.dropdown-separator />
                                            <x-ui.dropdown-item icon="bi bi-pencil" variant="destructive" :href="route('requester.lpj.edit', $lpj)">Revisi LPJ</x-ui.dropdown-item>
                                        @endif
                                    </x-ui.dropdown-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
