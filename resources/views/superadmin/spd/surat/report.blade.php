@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<x-layouts.app title="Rekap Biaya SPD" :breadcrumbs="['Rekap Biaya SPD' => null]">
    <x-ui.page-header kicker="Admin SPD" title="Rekap Biaya SPD" description="Anggaran dan realisasi seluruh surat tugas beserta status LPJ-nya." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card label="Surat Disetujui" :value="$totalApproved" icon="bi bi-file-earmark-check" hint="Surat SPD" />
        <x-ui.stat-card label="Total Budget" :value="$rp($totalBudget)" icon="bi bi-cash-stack" tone="muted" hint="Anggaran" />
        <x-ui.stat-card label="Total Realisasi" :value="$rp($totalSpending)" icon="bi bi-wallet2" :tone="$totalBudget > 0 && $totalSpending > $totalBudget ? 'danger' : 'success'" hint="LPJ disetujui" />
        <x-ui.stat-card label="LPJ Disetujui" :value="$totalLpj" icon="bi bi-receipt" tone="success" hint="Laporan" />
        <x-ui.stat-card label="Menunggu LPJ" :value="$totalMenungguLpj" icon="bi bi-hourglass-split" :tone="$totalMenungguLpjOverdue > 0 ? 'danger' : 'warning'"
            :hint="$totalMenungguLpjOverdue . ' lewat batas 3 hari'" />
    </div>

    @if ($totalMenungguLpjOverdue > 0)
        <x-ui.alert variant="destructive" :title="$totalMenungguLpjOverdue . ' surat sudah lewat batas submit LPJ (3 hari setelah SPD selesai).'">
            Form LPJ driver <strong>tidak ditutup</strong>, LPJ tetap bisa masuk kapan saja dan akan tercatat terlambat. Daftar di bawah sudah ditandai LPJ Terlambat.
        </x-ui.alert>
    @endif

    <x-ui.card>
        <x-ui.card-header title="Daftar Surat SPD" icon="bi bi-file-earmark-text" />
        <x-ui.card-content class="grid gap-4">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Driver</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th class="text-right">Budget</th>
                        <th class="text-right">Realisasi</th>
                        <th>Status</th>
                        <th class="w-20 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suratList as $surat)
                        @php
                            $budgetTotal = $surat->total_budget ?? 0;
                            $realisasi = $surat->lpjPenggantian?->total_pengeluaran ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <span class="font-medium">{{ $surat->nomor_surat }}</span>
                                @if ($surat->is_extension)<x-ui.badge variant="info" class="mt-1 flex">Perpanjangan</x-ui.badge>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ $surat->pengemudi_nama }}</td>
                            <td class="max-w-48 truncate text-muted-foreground" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</td>
                            <td class="whitespace-nowrap">{{ $surat->tanggal_berangkat->format('d/m') }} - {{ $surat->tanggal_kembali->format('d/m/y') }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($budgetTotal) }}</td>
                            <td @class(['whitespace-nowrap text-right tabular-nums', 'font-semibold text-destructive' => $budgetTotal > 0 && $realisasi > $budgetTotal])>{{ $rp($realisasi) }}</td>
                            <td>
                                <div class="grid justify-items-start gap-1">
                                    <x-spd.status-badge :status="$surat->status" />
                                    @if ($surat->status === 'approved')
                                        <x-spd.lpj-deadline :surat="$surat" compact />
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <x-ui.dropdown-menu :id="'aksi-laporan-' . $surat->id" label="Aksi laporan">
                                    <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $surat->nomor_surat }}</code></div>
                                    <x-ui.dropdown-item icon="bi bi-eye" :href="route('surat.superadmin.show', $surat)">Lihat detail</x-ui.dropdown-item>
                                    @if ($surat->status === 'approved')
                                        <x-ui.dropdown-item icon="bi bi-printer" :href="route('surat.superadmin.print', $surat)" target="_blank">Cetak surat tugas</x-ui.dropdown-item>
                                    @endif
                                    @if ($surat->lpjPenggantian)
                                        <x-ui.dropdown-separator />
                                        <x-ui.dropdown-item icon="bi bi-receipt" :href="route('lpj.superadmin.show', $surat->lpjPenggantian)">Buka LPJ</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="bi bi-file-earmark-pdf" :href="route('spd.laporan.export', $surat->lpjPenggantian)" target="_blank">Cetak SPD + LPJ</x-ui.dropdown-item>
                                    @endif
                                </x-ui.dropdown-menu>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-muted-foreground">Belum ada data SPD.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            {{ $suratList->links('vendor.pagination.shadcn') }}
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
