<x-layouts.app title="Riwayat Permohonan BBM" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Riwayat' => null]">
    <x-ui.page-header kicker="Permohonan BBM" title="Riwayat Permohonan" description="100 permohonan terbaru tampil langsung. Pilih bulan pengajuan untuk melihat data bulan tertentu." />

    <x-ui.card class="gap-4">
        <x-ui.card-content class="grid gap-4">
            {{-- Filter bulan: tiap pilihan adalah tautan ?bulan=YYYY-MM, bulan tanpa data nonaktif. --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-muted-foreground">Bulan pengajuan</span>
                    <x-ui.month-picker id="history-month" :months="$months" :selected="$selectedMonth" />
                </div>
                <p class="text-sm text-muted-foreground">
                    <span class="font-medium text-foreground tabular-nums">{{ $history->total() }}</span> permohonan
                    {{ $selectedMonth ? 'di ' . $selectedMonth->translatedFormat('F Y') : 'di semua bulan' }}
                </p>
            </div>

            {{-- Pengguna HP: navigasi halaman juga di atas supaya tidak perlu menggulir ke bawah. --}}
            <div class="md:hidden">{{ $history->links('vendor.pagination.shadcn') }}</div>

            <x-ui.table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Tanggal</th>
                        <th>Kendaraan/Alat</th>
                        <th>Jenis BBM</th>
                        <th class="text-right">Nominal</th>
                        <th>Status</th>
                        <th class="text-right"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td><code class="text-xs">{{ $item->request_number }}</code></td>
                            <td class="whitespace-nowrap">{{ $item->request_date->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                {{ $item->vehicle->consumerial_code ?? 'N/A' }}
                                <span class="block text-xs text-muted-foreground">{{ $item->vehicle->consumerial_name ?? '' }} ({{ $item->vehicle->consumerial_type ?? '-' }})</span>
                            </td>
                            <td><x-ui.badge variant="secondary">{{ $item->gasoline_type }}</x-ui.badge></td>
                            <td class="whitespace-nowrap text-right font-medium tabular-nums">Rp {{ number_format($item->bill_amounts, 0, ',', '.') }}</td>
                            <td><x-bbm.status-badge :status="$item->status" /></td>
                            <td class="text-right">
                                <x-ui.button variant="outline" size="sm" :href="route('requester-show', $item->id)">
                                    <i class="bi bi-eye"></i> Detail
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-muted-foreground">
                                {{ $selectedMonth ? 'Tidak ada permohonan di bulan ini. Pilih bulan lain atau Semua bulan.' : 'Belum ada riwayat permohonan.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            {{ $history->links('vendor.pagination.shadcn') }}
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
