{{-- Detail perpanjangan: menampilkan surat terkait dan aksi validasi (dana perpanjangan diinput Admin GA). --}}
@php
    $isApproved = in_array($surat->status, ['approved', 'completed'], true);
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<x-layouts.app title="Detail Perpanjangan" :breadcrumbs="['Persetujuan Perpanjangan' => route('perpanjangan.superadmin.index'), $surat->nomor_surat => null]">
    <x-ui.page-header kicker="Detail & Validasi" :title="$surat->nomor_surat" :description="$surat->pengemudi_nama . ' · ' . $surat->tempat_tujuan">
        <x-slot:actions>
            <x-spd.status-badge :status="$surat->status" class="px-3 py-1 text-sm" />
            <x-ui.button variant="outline" :href="route('perpanjangan.superadmin.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($surat->is_extension && $surat->parentSurat)
        <x-ui.alert variant="info">
            Ini adalah <strong>Surat Perpanjangan</strong> dari
            <a href="{{ route('surat.superadmin.show', $surat->parentSurat) }}" class="font-medium underline underline-offset-4">{{ $surat->parentSurat->nomor_surat }}</a>.
        </x-ui.alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Surat Tugas" icon="bi bi-file-earmark-text" />
                <x-ui.card-content class="grid gap-6 md:grid-cols-2">
                    <dl class="divide-y">
                        <x-ui.detail-row label="Pemberi tugas">
                            <span class="font-medium">{{ $surat->pemberi_tugas_nama }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $surat->pemberi_tugas_jabatan }}</span>
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Pengemudi"><span class="font-medium">{{ $surat->pengemudi_nama }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Tujuan">{{ $surat->tempat_tujuan }}</x-ui.detail-row>
                        <x-ui.detail-row label="Melayani bidang">{{ $surat->melayani_bidang }}</x-ui.detail-row>
                        <x-ui.detail-row label="Kendaraan">
                            <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</x-ui.badge>
                            <span class="block text-xs text-muted-foreground">{{ $surat->vehicle->consumerial_name ?? '' }}</span>
                        </x-ui.detail-row>
                    </dl>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Berangkat"><span class="font-medium">{{ $surat->tanggal_berangkat->translatedFormat('d F Y') }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Kembali"><span class="font-medium">{{ $surat->tanggal_kembali->translatedFormat('d F Y') }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Lama"><x-ui.badge variant="info">{{ $surat->lama_perjalanan_hari }} hari</x-ui.badge></x-ui.detail-row>
                        <x-ui.detail-row label="Pemakai">{{ $surat->jumlah_pemakai }} orang</x-ui.detail-row>
                        @if ($surat->budget_per_hari > 0)
                            <x-ui.detail-row label="Budget per hari" numeric><span class="tabular-nums">{{ $rp($surat->budget_per_hari) }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Total budget" numeric><span class="font-bold tabular-nums text-success">{{ $rp($surat->total_budget) }}</span></x-ui.detail-row>
                        @endif
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Daftar Nama Pemakai" icon="bi bi-people" />
                <x-ui.card-content>
                    @if ($surat->details->isNotEmpty())
                        <ol class="grid gap-2 sm:grid-cols-2">
                            @foreach ($surat->details as $detail)
                                <li class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">{{ $loop->iteration }}</span>
                                    {{ $detail->nama_lengkap }}
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm text-muted-foreground">Tidak ada data penumpang.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            @if ($surat->catatan)
                <x-ui.card>
                    <x-ui.card-header title="Catatan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm text-muted-foreground">{{ $surat->catatan }}</p></x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($isApproved)
                <x-ui.alert variant="success" title="Disetujui oleh {{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }}">
                    <p>{{ $surat->approved_at->translatedFormat('d F Y H:i') }}</p>
                    @if ($surat->lpj_deadline)<p>Batas LPJ: {{ $surat->lpj_deadline->translatedFormat('d M Y') }}</p>@endif
                </x-ui.alert>
            @elseif ($surat->status === 'rejected')
                <x-ui.alert variant="destructive" title="Ditolak oleh {{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }}">
                    <p>{{ $surat->rejected_at->translatedFormat('d F Y H:i') }}</p>
                    <p><strong>Alasan:</strong> {{ $surat->rejection_reason }}</p>
                </x-ui.alert>
            @endif

            @if ($surat->childExtensions?->isNotEmpty())
                <x-ui.card>
                    <x-ui.card-header title="Surat Perpanjangan" icon="bi bi-diagram-3" />
                    <x-ui.card-content>
                        <x-ui.table>
                            <thead><tr><th>Nomor Surat</th><th>Tanggal Kembali Baru</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($surat->childExtensions as $ext)
                                    <tr>
                                        <td class="font-medium">{{ $ext->nomor_surat }}</td>
                                        <td>{{ $ext->tanggal_kembali->translatedFormat('d M Y') }}</td>
                                        <td><x-spd.status-badge :status="$ext->status" /></td>
                                        <td class="text-right"><x-ui.button variant="ghost" size="icon-sm" :href="route('surat.superadmin.show', $ext)" aria-label="Lihat"><i class="bi bi-eye"></i></x-ui.button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    </x-ui.card-content>
                </x-ui.card>
            @endif
        </div>

        <div class="grid gap-6">
            @if ($surat->status === 'pending')
                <x-ui.card class="overflow-hidden pt-0">
                    <div class="bg-primary px-6 py-4 text-primary-foreground">
                        <p class="flex items-center gap-2 font-semibold"><i class="bi bi-shield-check"></i> Aksi Validasi</p>
                    </div>
                    <x-ui.card-content>
                        {{-- Driver tidak mengajukan nominal; dana perpanjangan dicatat Admin GA saat approve
                             (modal di daftar perpanjangan) atau setelahnya lewat detail surat perpanjangan. --}}
                        <form action="{{ route('perpanjangan.superadmin.approve', $extension) }}" method="POST" id="approveForm" class="grid gap-4">
                            @csrf
                            <p class="text-sm text-muted-foreground">Periksa detail surat dengan teliti sebelum memvalidasi.</p>
                            <x-ui.alert variant="info">Menyetujui perpanjangan hanya memindahkan tanggal kembali dan tenggat LPJ. Nominal dana perpanjangan diinput Admin GA.</x-ui.alert>
                            <x-ui.button type="submit" variant="success"><i class="bi bi-check-circle"></i> Setujui Perpanjangan</x-ui.button>
                            <x-ui.button variant="outline" class="text-destructive hover:bg-destructive-soft hover:text-destructive" data-dialog-open="rejectModal"><i class="bi bi-x-circle"></i> Tolak Surat Tugas</x-ui.button>
                        </form>
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($isApproved)
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Dokumen & LPJ" icon="bi bi-printer" />
                    <x-ui.card-content class="grid gap-4">
                        <x-ui.button :href="route('surat.superadmin.print', $surat)" target="_blank"><i class="bi bi-printer"></i> Cetak Surat Tugas</x-ui.button>
                        @if ($surat->lpjPenggantian)
                            <div class="flex items-center justify-between gap-2 text-sm"><span>LPJ sudah disubmit</span><x-spd.status-badge :status="$surat->lpjPenggantian->status" /></div>
                        @else
                            <x-ui.alert :variant="$surat->isLpjDeadlineOverdue() ? 'destructive' : 'warning'" :title="$surat->isLpjDeadlineOverdue() ? 'LPJ belum disubmit (terlambat)' : 'LPJ belum disubmit'">
                                @if ($surat->lpj_deadline) Batas: {{ $surat->lpj_deadline->translatedFormat('d M Y') }} @endif
                            </x-ui.alert>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Informasi Pengajuan" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Diajukan oleh">{{ $surat->user->nama_lengkap ?? $surat->user->name }}</x-ui.detail-row>
                        <x-ui.detail-row label="Tanggal">{{ $surat->created_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                        <x-ui.detail-row label="Diperbarui">{{ $surat->updated_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>

    <x-ui.dialog id="rejectModal" title="Tolak Surat Tugas">
        <form action="{{ route('surat.superadmin.reject', $surat) }}" method="POST" data-loading class="grid gap-4">
            @csrf
            <div class="rounded-lg border bg-muted/40 p-3 text-sm">
                <p class="font-medium">{{ $surat->nomor_surat }}</p>
                <p class="text-muted-foreground">{{ $surat->pengemudi_nama }} &middot; {{ $surat->tempat_tujuan }}</p>
            </div>
            <x-ui.field label="Alasan Penolakan" for="rejection_reason" required hint="Minimal 10 karakter">
                <x-ui.textarea id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Jelaskan alasan penolakan" />
            </x-ui.field>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" variant="destructive"><i class="bi bi-x-circle"></i> Ya, Tolak Surat</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>

    @if ($surat->status === 'pending')
        @push('scripts')
            @vite('resources/js/pages/spd/perpanjangan-detail.js')
        @endpush
    @endif
</x-layouts.app>
