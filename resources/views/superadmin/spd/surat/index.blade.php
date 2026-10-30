<x-layouts.app title="Validasi Surat Tugas" :breadcrumbs="['Validasi Surat Tugas' => null]">
    <x-ui.page-header kicker="Admin SPD" title="Validasi Surat Tugas" description="Periksa pengajuan surat tugas, tentukan dana, lalu setujui atau tolak.">
        @can('permission', 'spd.manage')
            <x-slot:actions>
                <x-ui.button :href="route('surat.superadmin.create-shortcut')"><i class="bi bi-plus-lg"></i> Buat Surat Tugas</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Menunggu Validasi" :value="$pending_count" icon="bi bi-hourglass-split" :tone="$pending_count ? 'warning' : 'muted'" hint="Surat pending" />
        <x-ui.stat-card label="Disetujui" :value="$approved_count" icon="bi bi-check-circle" tone="success" hint="Surat disetujui" />
        <x-ui.stat-card label="Ditolak" :value="$rejected_count" icon="bi bi-x-circle" tone="danger" hint="Surat ditolak" />
    </div>

    <x-ui.tabs default="pending">
        <x-ui.tabs-list>
            <x-ui.tabs-trigger value="pending"><i class="bi bi-clock-history"></i> Menunggu Validasi
                @if ($pending_count > 0)<x-ui.badge variant="warning" class="rounded-full">{{ $pending_count }}</x-ui.badge>@endif
            </x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="approved"><i class="bi bi-check-circle"></i> Disetujui</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="rejected"><i class="bi bi-x-circle"></i> Ditolak</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        {{-- Antrian: kartu; persetujuan butuh isian di halaman detail (pemberi tugas, kode GL, dana). --}}
        <x-ui.tabs-content value="pending">
            @if ($pending_list->isEmpty())
                <x-ui.empty-state icon="bi bi-check2-all" title="Tidak ada surat yang menunggu validasi" description="Semua pengajuan surat tugas sudah diproses." class="bg-card" />
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($pending_list->sortBy('created_at') as $surat)
                        @php
                            $info = ['nomor' => $surat->nomor_surat, 'driver' => $surat->pengemudi_nama . ' · ' . $surat->tempat_tujuan];
                        @endphp
                        <x-ui.queue-card :name="$surat->pengemudi_nama" :subtitle="$surat->nomor_surat" :since="$surat->created_at" :note="$surat->catatan"
                            figure-label="Lama perjalanan" :figure="$surat->lama_perjalanan_hari . ' hari'" :figure-hint="$surat->jumlah_pemakai . ' pemakai'">
                            <p class="flex min-w-0 items-center gap-2">
                                <i class="bi bi-geo-alt text-muted-foreground"></i>
                                <span class="truncate font-semibold" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</span>
                                @if ($surat->is_extension)<x-ui.badge variant="info">Perpanjangan</x-ui.badge>@endif
                            </p>
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-muted-foreground">
                                <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar3"></i> <span class="text-foreground">{{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}</span></span>
                                <span class="size-1 rounded-full bg-border"></span>
                                <span class="inline-flex min-w-0 items-center gap-1.5"><i class="bi bi-truck"></i> <span class="truncate">{{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</span></span>
                            </p>
                            <x-slot:actions>
                                <x-ui.button variant="outline" size="sm" class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                    data-action-dialog="rejectSuratDialog" :data-url="route('surat.superadmin.reject', $surat)" :data-info="json_encode($info)">
                                    <i class="bi bi-x-circle"></i> Tolak
                                </x-ui.button>
                                <x-ui.button size="sm" :href="route('surat.superadmin.show', $surat)"><i class="bi bi-clipboard-check"></i> Periksa & Validasi</x-ui.button>
                            </x-slot:actions>
                        </x-ui.queue-card>
                    @endforeach
                </div>
            @endif
        </x-ui.tabs-content>

        <x-ui.tabs-content value="approved">
            <x-ui.card class="py-4">
                <x-ui.card-content class="px-4">
                    @if ($approved_list->isEmpty())
                        <x-ui.empty-state icon="bi bi-inbox" title="Belum ada surat yang disetujui" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat</th><th>LPJ</th><th>Pengaju</th><th>Tujuan</th><th>Tanggal</th><th>Batas LPJ</th><th>Disetujui</th><th class="w-20 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($approved_list as $surat)
                                    @php $lpj = $surat->lpjPenggantian; @endphp
                                    <tr>
                                        <td>
                                            <span class="whitespace-nowrap font-medium">{{ $surat->nomor_surat }}</span>
                                            @if ($surat->is_extension)<x-ui.badge variant="info" class="mt-1 flex">Perpanjangan</x-ui.badge>@endif
                                        </td>
                                        <td>
                                            @if ($lpj)
                                                <x-spd.status-badge :status="$lpj->status" />
                                            @else
                                                <x-ui.badge variant="muted">Belum</x-ui.badge>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap">{{ $surat->pengemudi_nama }}</td>
                                        <td class="max-w-48 truncate text-muted-foreground" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</td>
                                        <td data-order="{{ $surat->tanggal_berangkat->timestamp }}" class="whitespace-nowrap">{{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}</td>
                                        <td data-order="{{ $surat->lpj_deadline?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                            @if ($surat->lpj_deadline)
                                                @if ($surat->isLpjDeadlineOverdue() && ! $surat->hasLpj())
                                                    <span class="text-destructive"><i class="bi bi-exclamation-triangle"></i> {{ $surat->lpj_deadline->translatedFormat('d M Y') }}</span>
                                                @else
                                                    {{ $surat->lpj_deadline->translatedFormat('d M Y') }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td data-order="{{ $surat->approved_at->timestamp }}" class="whitespace-nowrap">
                                            {{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $surat->approved_at->translatedFormat('d M Y H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <x-ui.dropdown-menu :id="'aksi-surat-' . $surat->id" label="Aksi surat">
                                                <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $surat->nomor_surat }}</code></div>
                                                <x-ui.dropdown-item icon="bi bi-eye" :href="route('surat.superadmin.show', $surat)">Lihat detail</x-ui.dropdown-item>
                                                <x-ui.dropdown-item icon="bi bi-printer" :href="route('surat.superadmin.print', $surat)" target="_blank">Cetak surat tugas</x-ui.dropdown-item>
                                                @if ($lpj)
                                                    <x-ui.dropdown-separator />
                                                    <x-ui.dropdown-item icon="bi bi-receipt" :href="route('lpj.superadmin.show', $lpj)">Buka LPJ</x-ui.dropdown-item>
                                                    <x-ui.dropdown-item icon="bi bi-file-earmark-pdf" :href="route('spd.laporan.export', $lpj)" target="_blank">Cetak SPD + LPJ</x-ui.dropdown-item>
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
        </x-ui.tabs-content>

        <x-ui.tabs-content value="rejected">
            <x-ui.card class="py-4">
                <x-ui.card-content class="px-4">
                    @if ($rejected_list->isEmpty())
                        <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada surat yang ditolak" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat</th><th>Pengaju</th><th>Tujuan</th><th>Ditolak Oleh</th><th>Alasan</th><th class="w-20 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($rejected_list as $surat)
                                    <tr>
                                        <td class="whitespace-nowrap font-medium">{{ $surat->nomor_surat }}</td>
                                        <td class="whitespace-nowrap">{{ $surat->pengemudi_nama }}</td>
                                        <td class="max-w-48 truncate text-muted-foreground" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</td>
                                        <td class="whitespace-nowrap">
                                            {{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ optional($surat->rejected_at)->translatedFormat('d M Y') ?? '-' }}</span>
                                        </td>
                                        <td class="max-w-64 truncate text-destructive" title="{{ $surat->rejection_reason }}">{{ $surat->rejection_reason }}</td>
                                        <td class="text-center">
                                            <x-ui.button variant="outline" size="icon-sm" :href="route('surat.superadmin.show', $surat)" title="Lihat detail" aria-label="Lihat detail"><i class="bi bi-eye"></i></x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </x-ui.tabs-content>
    </x-ui.tabs>

    <x-ui.dialog id="rejectSuratDialog" title="Tolak Surat Tugas" description="Surat yang ditolak dikembalikan ke pengaju beserta alasannya.">
        <form method="POST" data-loading class="grid gap-4">
            @csrf
            <div class="grid gap-0.5 rounded-lg border bg-muted/40 p-3 text-sm">
                <p class="font-medium" data-info="nomor">-</p>
                <p class="text-muted-foreground" data-info="driver">-</p>
            </div>
            <x-ui.field label="Alasan Penolakan" for="queue_rejection_reason" required hint="Minimal 10 karakter">
                <x-ui.textarea id="queue_rejection_reason" name="rejection_reason" rows="3" required minlength="10" class="h-24 resize-none" placeholder="Jelaskan alasan penolakan" />
            </x-ui.field>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" variant="destructive"><i class="bi bi-x-circle"></i> Ya, Tolak Surat</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</x-layouts.app>
