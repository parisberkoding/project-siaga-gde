@php
    $surat = $laporan->suratSpd;
    $total = 'Rp ' . number_format((float) $laporan->total_pengeluaran, 0, ',', '.');
@endphp

<x-layouts.app title="Konfirmasi Perjalanan Dinas" :breadcrumbs="['Konfirmasi Perjalanan' => route('lpj.pengguna.index'), 'Detail' => null]">
    <x-ui.page-header kicker="Konfirmasi Perjalanan" :title="$surat->nomor_surat ?? 'Konfirmasi Perjalanan Dinas'" description="Periksa rincian perjalanan lalu validasi sebagai pengguna yang berangkat.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('lpj.pengguna.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Rincian Perjalanan Dinas" icon="bi bi-file-earmark-text" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Nomor surat tugas"><span class="font-medium">{{ $surat->nomor_surat ?? '-' }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Nomor LPJ">{{ $laporan->lpj_number ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Tempat tujuan">{{ $surat->tempat_tujuan ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Tanggal perjalanan">
                            @if ($surat)
                                {{ $surat->tanggal_berangkat->translatedFormat('d F Y') }} s/d {{ $surat->tanggal_kembali->translatedFormat('d F Y') }}
                            @else
                                -
                            @endif
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Pengemudi">{{ $laporan->nama ?? $surat->pengemudi_nama ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Daftar pemakai">
                            @if ($surat?->details->isNotEmpty())
                                <ol class="grid gap-1.5">
                                    @foreach ($surat->details as $d)
                                        <li class="flex flex-wrap items-center gap-2">
                                            <span class="flex size-5 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">{{ $loop->iteration }}</span>
                                            {{ $d->nama_lengkap }}
                                            @if ($d->user_id == auth()->id())
                                                <x-ui.badge>Anda</x-ui.badge>
                                            @endif
                                            @unless ($d->user_id)
                                                <span class="text-xs text-muted-foreground">(tanpa akun)</span>
                                            @endunless
                                        </li>
                                    @endforeach
                                </ol>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    <i class="bi bi-info-circle"></i> Cukup <strong>satu</strong> penumpang yang memvalidasi, penumpang lain tidak perlu memvalidasi lagi.
                                </p>
                            @else
                                -
                            @endif
                        </x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Rincian Pengeluaran" icon="bi bi-list-check" />
                <x-ui.card-content>
                    <x-ui.table>
                        <thead>
                            <tr><th>Tanggal</th><th>Jenis</th><th class="text-right">Nominal</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($laporan->details as $d)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $d->tanggal?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td><x-spd.tipe-badge :tipe="$d->tipe_penggunaan" /></td>
                                    <td class="whitespace-nowrap text-right tabular-nums">Rp {{ number_format((float) $d->nominal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-muted-foreground">Tidak ada rincian pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/50 font-semibold">
                                <td colspan="2" class="text-right">Total Pengeluaran</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ $total }}</td>
                            </tr>
                        </tfoot>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card class="overflow-hidden pt-0 lg:sticky lg:top-20">
            <div class="bg-primary px-6 py-4 text-primary-foreground">
                <p class="flex items-center gap-2 font-semibold"><i class="bi bi-shield-check"></i> Validasi Kebenaran Perjalanan</p>
            </div>
            <x-ui.card-content class="grid gap-4">
                @if ($laporan->approved_by_pengguna)
                    <x-ui.alert variant="success" title="Sudah divalidasi">{{ optional($laporan->approved_pengguna_at)->translatedFormat('d F Y H:i') }}</x-ui.alert>

                    @if ($qrSvg)
                        <div class="grid justify-items-center gap-2 rounded-lg border p-4">
                            <div class="size-[150px] [&_svg]:size-full">{!! $qrSvg !!}</div>
                            <span class="text-xs text-muted-foreground">QR Tanda Tangan Validasi</span>
                        </div>
                    @endif

                    <p class="text-sm text-muted-foreground">
                        Divalidasi oleh <strong class="text-foreground">{{ $laporan->approverPengguna->nama_lengkap ?? '-' }}</strong>. Menunggu validasi Divisi GA.
                    </p>
                @else
                    <p class="text-sm text-muted-foreground">
                        Sebagai <strong class="text-foreground">pengguna yang berangkat</strong>, nyatakan bahwa perjalanan dinas ini benar adanya beserta rincian pengeluarannya.
                    </p>
                    <x-ui.button variant="success" data-dialog-open="validasiModal"><i class="bi bi-pen"></i> Validasi &amp; Tanda Tangan</x-ui.button>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </div>

    @unless ($laporan->approved_by_pengguna)
        <x-ui.dialog id="validasiModal" title="Validasi Perjalanan Dinas">
            <form action="{{ route('lpj.pengguna.approve', $laporan) }}" method="POST" data-loading class="grid gap-4">
                @csrf
                <div class="rounded-lg border bg-muted/40 p-3 text-sm">
                    <p class="font-medium">{{ $surat->nomor_surat ?? '-' }}</p>
                    <p class="text-muted-foreground">{{ $surat->tempat_tujuan ?? '-' }} &middot; {{ $total }}</p>
                </div>
                <label class="flex items-start gap-3 text-sm">
                    <x-ui.checkbox name="pernyataan" value="1" required class="mt-0.5" />
                    <span><strong>Saya mengetahui bahwa perjalanan dinas ini benar adanya</strong> dengan rincian sebagaimana terlampir pada laporan pertanggungjawaban ini.</span>
                </label>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                    <x-ui.button type="submit" variant="success"><i class="bi bi-check-circle"></i> Ya, Validasi &amp; Tanda Tangan</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endunless
</x-layouts.app>
