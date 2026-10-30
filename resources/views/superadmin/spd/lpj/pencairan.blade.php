{{--
    Lampiran Pemberian Dana Perjalanan Dinas: kelola baris pencairan. Khusus Divisi GA (permission lpj.pencairan).
    Setiap perubahan otomatis menyinkronkan jumlah_diberikan pada LPJ dengan total pencairan.
--}}
@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $balanceAkhir = $total - (float) $laporan->total_pengeluaran;
@endphp

<x-layouts.app title="Pemberian Dana" :breadcrumbs="['Validasi Laporan' => route('lpj.superadmin.index'), $laporan->lpj_number => route('lpj.superadmin.show', $laporan), 'Pemberian Dana' => null]">
    <x-ui.page-header kicker="Lampiran Pemberian Dana" title="Pemberian Dana Perjalanan Dinas" :description="$surat->nomor_surat ?? '-'">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('lpj.superadmin.show', $laporan)"><i class="bi bi-arrow-left"></i> Detail LPJ</x-ui.button>
            <x-ui.button :href="route('spd.laporan.pemberian-dana', $laporan)" target="_blank"><i class="bi bi-printer"></i> Cetak Lembar Pemberian Dana</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Balance Awal" :value="$rp($total)" icon="bi bi-cash-stack" hint="Total pencairan" />
        <x-ui.stat-card label="Pengeluaran" :value="$rp($laporan->total_pengeluaran)" icon="bi bi-receipt" tone="muted" hint="Total LPJ" />
        <x-ui.stat-card label="Balance Akhir" :value="$rp($balanceAkhir)" icon="bi bi-wallet2" :tone="$balanceAkhir < 0 ? 'warning' : 'success'" :hint="$balanceAkhir < 0 ? 'Kekurangan' : 'Pengembalian'" />
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Ringkasan" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="No. Surat Dinas"><span class="font-medium">{{ $surat->nomor_surat ?? '-' }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Kendaraan">{{ $laporan->kendaraan_label }}</x-ui.detail-row>
                        <x-ui.detail-row label="Uang Dari">{{ $laporan->uang_dari ?? 'KAS' }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Rincian Pencairan" icon="bi bi-list-ul" />
                <x-ui.card-content>
                    <x-ui.table>
                        <thead>
                            <tr><th class="w-12">No</th><th>Tanggal</th><th>No. Referensi Perbankan</th><th class="text-right">Nominal</th><th>Bentuk</th><th>Rek. Tujuan</th><th class="text-right">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($pencairans as $index => $pencairan)
                                <tr>
                                    <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                    <td class="whitespace-nowrap">{{ $pencairan->tanggal?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>{{ $pencairan->bank_reference ?: '-' }}</td>
                                    <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($pencairan->nominal) }}</td>
                                    <td><x-ui.badge :variant="$pencairan->isTransfer() ? 'default' : 'secondary'">{{ $pencairan->bentuk_pencairan_label }}</x-ui.badge></td>
                                    <td class="text-muted-foreground">{{ $pencairan->isTransfer() ? ($pencairan->rek_tujuan ?: '-') : '-' }}</td>
                                    <td>
                                        <div class="flex justify-end gap-1">
                                            <x-ui.button variant="ghost" size="icon-sm" data-dialog-open="editPencairan{{ $pencairan->id }}" title="Ubah" aria-label="Ubah"><i class="bi bi-pencil"></i></x-ui.button>
                                            <form action="{{ route('lpj.superadmin.pencairan.destroy', [$laporan, $pencairan]) }}" method="POST"
                                                  data-confirm="Baris pencairan ini akan dihapus dan total disinkronkan ulang." data-confirm-title="Hapus pencairan?" data-confirm-text="Hapus" data-confirm-variant="destructive">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button type="submit" variant="ghost" size="icon-sm" class="text-destructive hover:bg-destructive-soft hover:text-destructive" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></x-ui.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-muted-foreground">Belum ada data pencairan.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($pencairans->isNotEmpty())
                            <tfoot>
                                <tr class="border-t bg-muted/50 font-semibold">
                                    <td colspan="3" class="text-right">Grand Total</td>
                                    <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($total) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card class="overflow-hidden pt-0 lg:sticky lg:top-20">
            <div class="bg-primary px-6 py-4 text-primary-foreground">
                <p class="flex items-center gap-2 font-semibold"><i class="bi bi-plus-circle"></i> Tambah Pencairan</p>
            </div>
            <x-ui.card-content>
                <form action="{{ route('lpj.superadmin.pencairan.store', $laporan) }}" method="POST" data-loading class="grid gap-3">
                    @csrf
                    @include('superadmin.spd.lpj.partials.pencairan-fields', ['values' => [
                        'tanggal' => old('tanggal', now()->toDateString()), 'nominal' => old('nominal'), 'bentuk' => old('bentuk_pencairan', 'Tunai'),
                        'bank_reference' => old('bank_reference'), 'rek_tujuan' => old('rek_tujuan'), 'catatan' => old('catatan'),
                    ]])
                    <x-ui.button type="submit"><i class="bi bi-save"></i> Simpan Pencairan</x-ui.button>
                    <p class="text-xs text-muted-foreground">Total pencairan akan disinkronkan ke <em>Jumlah Diberikan</em> pada LPJ.</p>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    @foreach ($pencairans as $pencairan)
        <x-ui.dialog id="editPencairan{{ $pencairan->id }}" title="Ubah Pencairan">
            <form action="{{ route('lpj.superadmin.pencairan.update', [$laporan, $pencairan]) }}" method="POST" data-loading class="grid gap-3">
                @csrf
                @method('PUT')
                @include('superadmin.spd.lpj.partials.pencairan-fields', ['values' => [
                    'tanggal' => $pencairan->tanggal?->format('Y-m-d'), 'nominal' => (float) $pencairan->nominal, 'bentuk' => $pencairan->bentuk_pencairan_label,
                    'bank_reference' => $pencairan->bank_reference, 'rek_tujuan' => $pencairan->rek_tujuan, 'catatan' => $pencairan->catatan,
                ]])
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                    <x-ui.button type="submit"><i class="bi bi-save"></i> Simpan</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endforeach

    @push('scripts')
        @vite('resources/js/pages/spd/pencairan.js')
    @endpush
</x-layouts.app>
