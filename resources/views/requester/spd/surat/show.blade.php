@php
    $user = Auth::user();
    $lpj = $surat->lpjPenggantian;
    $overdue = $surat->isLpjDeadlineOverdue();
@endphp

<x-layouts.app title="Detail Surat Tugas" :breadcrumbs="['Daftar Surat Tugas' => route('requester.surat.index'), $surat->nomor_surat => null]">
    <x-ui.page-header kicker="Surat Tugas" :title="$surat->nomor_surat" description="Detail surat tugas perjalanan dinas.">
        <x-slot:actions>
            <x-spd.status-badge :status="$surat->status" class="px-3 py-1 text-sm" />
            <x-ui.button variant="outline" :href="route('requester.surat.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($surat->is_extension && $surat->parentSurat)
        <x-ui.alert variant="info">
            Ini adalah <strong>Surat Perpanjangan</strong> dari
            <a href="{{ route('requester.surat.show', $surat->parentSurat) }}" class="font-medium underline underline-offset-4">{{ $surat->parentSurat->nomor_surat }}</a>.
        </x-ui.alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Pemberi Tugas & Perjalanan" icon="bi bi-geo-alt" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Pemberi tugas">
                            <span class="font-medium">{{ $surat->pemberi_tugas_nama }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $surat->pemberi_tugas_jabatan }}</span>
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Nama pengemudi"><span class="font-medium">{{ $surat->pengemudi_nama }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Tempat tujuan">{{ $surat->tempat_tujuan }}</x-ui.detail-row>
                        <x-ui.detail-row label="Melayani bidang">{{ $surat->melayani_bidang }}</x-ui.detail-row>
                        <x-ui.detail-row label="Kendaraan">
                            <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</x-ui.badge>
                            {{ $surat->vehicle->consumerial_name ?? '' }}
                        </x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            <div class="grid gap-6 md:grid-cols-2">
                <x-ui.card>
                    <x-ui.card-header title="Jadwal Perjalanan" icon="bi bi-calendar-check" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Berangkat"><span class="font-medium">{{ $surat->tanggal_berangkat->translatedFormat('d F Y') }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Kembali"><span class="font-medium">{{ $surat->tanggal_kembali->translatedFormat('d F Y') }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Lama"><x-ui.badge variant="info">{{ $surat->lama_perjalanan_hari }} hari</x-ui.badge></x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Budget" icon="bi bi-cash-stack" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Per hari" numeric><span class="tabular-nums">Rp {{ number_format($surat->budget_per_hari, 0, ',', '.') }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Total" numeric><span class="text-lg font-bold tabular-nums text-success">Rp {{ number_format($surat->total_budget, 0, ',', '.') }}</span></x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>
            </div>

            <x-ui.card>
                <x-ui.card-header title="Daftar Nama Pemakai" icon="bi bi-people" :description="$surat->jumlah_pemakai . ' orang'" />
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
                        <p class="text-sm text-muted-foreground">Tidak ada data.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            @if ($surat->catatan)
                <x-ui.card>
                    <x-ui.card-header title="Catatan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm text-muted-foreground">{{ $surat->catatan }}</p></x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($surat->status === 'rejected')
                <x-ui.alert variant="destructive" title="Surat ditolak">
                    <p>Oleh {{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }} pada {{ $surat->rejected_at->translatedFormat('d F Y H:i') }}.</p>
                    <p><strong>Alasan:</strong> {{ $surat->rejection_reason }}</p>
                </x-ui.alert>
            @elseif (in_array($surat->status, ['approved', 'completed'], true))
                <x-ui.alert variant="success" title="Surat disetujui">
                    <p>Oleh {{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }} pada {{ $surat->approved_at->translatedFormat('d F Y H:i') }}.</p>
                    @if ($surat->lpj_deadline)
                        <p>Batas LPJ: {{ $surat->lpj_deadline->translatedFormat('d M Y') }}</p>
                    @endif
                </x-ui.alert>
            @endif

            @if ($surat->childExtensions?->isNotEmpty())
                <x-ui.card>
                    <x-ui.card-header title="Surat Perpanjangan" icon="bi bi-diagram-3" />
                    <x-ui.card-content>
                        <x-ui.table>
                            <thead>
                                <tr><th>Nomor Surat</th><th>Tanggal Kembali Baru</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($surat->childExtensions as $ext)
                                    <tr>
                                        <td class="font-medium">{{ $ext->nomor_surat }}</td>
                                        <td>{{ $ext->tanggal_kembali->translatedFormat('d M Y') }}</td>
                                        <td><x-spd.status-badge :status="$ext->status" /></td>
                                        <td class="text-right"><x-ui.button variant="ghost" size="icon-sm" :href="route('requester.surat.show', $ext)" aria-label="Lihat"><i class="bi bi-eye"></i></x-ui.button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    </x-ui.card-content>
                </x-ui.card>
            @endif
        </div>

        <div class="grid gap-6">
            @if ($surat->status === 'approved')
                @php
                    $canLpj = ! $lpj && $user->hasPermission('lpj.create');
                    $canExtend = ! $surat->is_extension && $user->hasPermission('spd.create');
                @endphp
                @if ($canLpj || $canExtend)
                    <x-ui.card class="gap-4">
                        <x-ui.card-header title="Aksi Cepat" icon="bi bi-lightning" />
                        <x-ui.card-content class="grid gap-2">
                            @if ($canLpj)
                                <x-ui.button variant="success" :href="route('requester.lpj.create', $surat)"><i class="bi bi-file-earmark-plus"></i> Buat Laporan LPJ</x-ui.button>
                            @endif
                            @if ($canExtend)
                                <x-ui.button variant="outline" :href="route('requester.perpanjangan.create', ['surat_id' => $surat->id])"><i class="bi bi-clock-history"></i> Ajukan Perpanjangan</x-ui.button>
                            @endif
                        </x-ui.card-content>
                    </x-ui.card>
                @endif
            @endif

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Status LPJ" icon="bi bi-file-earmark-check" />
                <x-ui.card-content>
                    @if (! in_array($surat->status, ['approved', 'completed'], true))
                        <x-ui.alert>Surat belum disetujui.</x-ui.alert>
                    @elseif ($lpj)
                        <x-ui.alert :variant="match ($lpj->status) { 'approved' => 'success', 'rejected' => 'destructive', default => 'warning' }">
                            <p>{{ match ($lpj->status) { 'approved' => 'LPJ sudah disetujui', 'rejected' => 'LPJ ditolak', default => 'LPJ menunggu validasi' } }}</p>
                            <a href="{{ route('requester.lpj.show', $lpj) }}" class="font-medium underline underline-offset-4">Lihat LPJ</a>
                        </x-ui.alert>
                    @else
                        <x-ui.alert :variant="$overdue ? 'destructive' : 'warning'" :title="$overdue ? 'LPJ terlambat' : 'LPJ belum dibuat'">
                            @if ($surat->lpj_deadline)
                                <p>Batas: {{ $surat->lpj_deadline->translatedFormat('d M Y') }}@unless ($overdue) ({{ $surat->getDaysUntilLpjDeadline() }} hari lagi)@endunless</p>
                            @endif
                        </x-ui.alert>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Informasi Pengajuan" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Diajukan">{{ $surat->created_at->translatedFormat('d F Y H:i') }}</x-ui.detail-row>
                        <x-ui.detail-row label="Diperbarui">{{ $surat->updated_at->translatedFormat('d F Y H:i') }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
