@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<x-layouts.app title="Perpanjangan SPD" :breadcrumbs="['Perpanjangan (ETD)' => null]">
    <x-ui.page-header kicker="Admin SPD" title="Manajemen Perpanjangan SPD" description="Setujui pengajuan perpanjangan, lalu checklist setelah surat perpanjangan dicetak." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Perlu Approval" :value="$stats['pending']" icon="bi bi-hourglass-split" tone="warning" hint="Menunggu persetujuan" />
        <x-ui.stat-card label="Perlu Checklist" :value="$stats['acknowledged']" icon="bi bi-check2-square" hint="Perlu acknowledge" />
        <x-ui.stat-card label="Selesai" :value="$stats['done']" icon="bi bi-check-circle" tone="success" hint="Proses selesai" />
        <x-ui.stat-card label="Ditolak" :value="$stats['rejected']" icon="bi bi-x-circle" tone="danger" hint="Permohonan ditolak" />
    </div>

    <x-ui.tabs default="pending">
        <x-ui.tabs-list>
            <x-ui.tabs-trigger value="pending">Perlu Approval
                @if ($stats['pending'] > 0)<x-ui.badge variant="warning" class="rounded-full">{{ $stats['pending'] }}</x-ui.badge>@endif
            </x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="acknowledge">Perlu Checklist
                @if ($stats['need_acknowledge'] > 0)<x-ui.badge variant="info" class="rounded-full">{{ $stats['need_acknowledge'] }}</x-ui.badge>@endif
            </x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="done">Selesai</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="rejected">Ditolak</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        <x-ui.tabs-content value="pending">
            @if ($ext_pending->isEmpty())
                <x-ui.empty-state icon="bi bi-check2-all" title="Tidak ada perpanjangan yang perlu disetujui" description="Semua pengajuan perpanjangan sudah diproses." class="bg-card" />
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($ext_pending->sortBy('created_at') as $ext)
                        @php
                            $orig = $ext->originalSurat;
                            $info = ['nomor' => $orig->nomor_surat, 'driver' => 'Driver: ' . $orig->pengemudi_nama, 'durasi' => 'Tambahan: +' . $ext->durasi_tambahan_hari . ' hari'];
                        @endphp
                        <x-ui.queue-card :name="$orig->pengemudi_nama" :subtitle="$orig->nomor_surat" :since="$ext->created_at" :note="$ext->alasan_perpanjangan"
                            figure-label="Tambahan" :figure="'+' . $ext->durasi_tambahan_hari . ' hari'" :figure-hint="$rp($ext->budget_tambahan)">
                            <p class="flex min-w-0 items-center gap-2">
                                <i class="bi bi-geo-alt text-muted-foreground"></i>
                                <span class="truncate font-semibold" title="{{ $orig->tempat_tujuan }}">{{ $orig->tempat_tujuan }}</span>
                            </p>
                            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-muted-foreground">
                                <i class="bi bi-calendar3"></i> Kembali
                                <span class="line-through decoration-muted-foreground/60">{{ $ext->tanggal_kembali_original->translatedFormat('d M Y') }}</span>
                                <span>menjadi</span>
                                <span class="font-semibold text-foreground">{{ $ext->tanggal_kembali_baru->translatedFormat('d M Y') }}</span>
                            </p>
                            <x-slot:actions>
                                <x-ui.button variant="ghost" size="sm" :href="route('perpanjangan.superadmin.show', $ext)"><i class="bi bi-eye"></i> Detail</x-ui.button>
                                <x-ui.button variant="outline" size="sm" class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                    data-action-dialog="modalReject" :data-url="route('perpanjangan.superadmin.reject', $ext)" :data-info="json_encode($info)">
                                    <i class="bi bi-x-circle"></i> Tolak
                                </x-ui.button>
                                <x-ui.button variant="success" size="sm" data-action-dialog="modalApprove" :data-url="route('perpanjangan.superadmin.approve', $ext)" :data-info="json_encode($info)">
                                    <i class="bi bi-check-circle"></i> Setujui
                                </x-ui.button>
                            </x-slot:actions>
                        </x-ui.queue-card>
                    @endforeach
                </div>
            @endif
        </x-ui.tabs-content>

        <x-ui.tabs-content value="acknowledge">
            <x-ui.card class="py-4">
                <x-ui.card-content class="px-4">
                    @if ($ext_need_acknowledge->isEmpty())
                        <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada perpanjangan yang perlu di-checklist" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat Perpanjangan</th><th>Driver</th><th>Perpanjangan</th><th>Disetujui</th><th class="w-24 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($ext_need_acknowledge as $ext)
                                    @php
                                        $info = ['nomor' => $ext->nomor_surat_extension, 'driver' => 'Driver: ' . $ext->originalSurat->pengemudi_nama, 'durasi' => 'Disetujui: +' . $ext->durasi_tambahan_hari . ' hari'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="font-medium">{{ $ext->nomor_surat_extension }}</span>
                                            <span class="block text-xs text-muted-foreground">Dari {{ $ext->originalSurat->nomor_surat }}</span>
                                        </td>
                                        <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                        <td>
                                            <x-ui.badge variant="info">+{{ $ext->durasi_tambahan_hari }} hari</x-ui.badge>
                                            <span class="block text-xs text-muted-foreground">s/d {{ $ext->tanggal_kembali_baru->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td>
                                            {{ $ext->approver->nama_lengkap ?? $ext->approver->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $ext->approved_at->translatedFormat('d M Y H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <x-ui.button size="sm" data-action-dialog="modalAcknowledge" :data-url="route('perpanjangan.superadmin.acknowledge', $ext)" :data-info="json_encode($info)">
                                                <i class="bi bi-check2-square"></i> Checklist
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </x-ui.tabs-content>

        <x-ui.tabs-content value="done">
            <x-ui.card class="py-4">
                <x-ui.card-content class="px-4">
                    @if ($ext_acknowledged->isEmpty())
                        <x-ui.empty-state icon="bi bi-inbox" title="Belum ada perpanjangan yang selesai" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat Perpanjangan</th><th>Driver</th><th>Perpanjangan</th><th>Checklist Oleh</th><th class="w-24 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($ext_acknowledged as $ext)
                                    <tr>
                                        <td class="font-medium">{{ $ext->nomor_surat_extension }}</td>
                                        <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                        <td><x-ui.badge variant="success">+{{ $ext->durasi_tambahan_hari }} hari</x-ui.badge></td>
                                        <td>
                                            {{ $ext->acknowledger->nama_lengkap ?? $ext->acknowledger->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $ext->acknowledged_at->translatedFormat('d M Y H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($ext->extensionSurat)
                                                <x-ui.button variant="ghost" size="icon-sm" :href="route('surat.superadmin.show', $ext->extensionSurat)" title="Lihat surat perpanjangan" aria-label="Lihat surat"><i class="bi bi-eye"></i></x-ui.button>
                                            @else
                                                <span class="text-muted-foreground" title="Surat perpanjangan tidak ditemukan">-</span>
                                            @endif
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
                    @if ($ext_rejected->isEmpty())
                        <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada perpanjangan yang ditolak" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat Original</th><th>Driver</th><th>Ditolak Oleh</th><th>Alasan</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($ext_rejected as $ext)
                                    <tr>
                                        <td class="font-medium">{{ $ext->originalSurat->nomor_surat }}</td>
                                        <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                        <td>
                                            {{ $ext->rejector->nama_lengkap ?? $ext->rejector->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $ext->rejected_at->translatedFormat('d M Y H:i') }}</span>
                                        </td>
                                        <td class="max-w-72 truncate text-destructive" title="{{ $ext->rejection_reason }}">{{ $ext->rejection_reason }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </x-ui.tabs-content>
    </x-ui.tabs>

    {{-- Dialog aksi: action form & ringkasan diisi oleh resources/js/ui/action-dialog.js --}}
    @foreach ([
        'modalApprove' => ['title' => 'Setujui Perpanjangan', 'variant' => 'success', 'submit' => 'Ya, Setujui', 'icon' => 'bi-check-lg', 'note' => 'catatan'],
        'modalReject' => ['title' => 'Tolak Perpanjangan', 'variant' => 'destructive', 'submit' => 'Ya, Tolak', 'icon' => 'bi-x-lg', 'note' => 'alasan'],
        'modalAcknowledge' => ['title' => 'Checklist Perpanjangan', 'variant' => 'default', 'submit' => 'Checklist Selesai', 'icon' => 'bi-check2-square', 'note' => 'catatan'],
    ] as $id => $cfg)
        <x-ui.dialog :id="$id" :title="$cfg['title']" :description="$id === 'modalAcknowledge' ? 'Konfirmasi bahwa perpanjangan ini telah diproses (surat sudah dicetak).' : null">
            <form method="POST" data-loading class="grid gap-4">
                @csrf
                <div class="grid gap-0.5 rounded-lg border bg-muted/40 p-3 text-sm">
                    <p class="font-medium" data-info="nomor">-</p>
                    <p class="text-muted-foreground" data-info="driver">-</p>
                    @unless ($id === 'modalReject')<p class="text-primary" data-info="durasi">-</p>@endunless
                </div>
                @if ($cfg['note'] === 'alasan')
                    <x-ui.field label="Alasan Penolakan" required hint="Minimal 10 karakter">
                        <x-ui.textarea name="alasan" rows="3" required minlength="10" placeholder="Jelaskan alasan penolakan" />
                    </x-ui.field>
                @else
                    <x-ui.field label="Catatan (opsional)">
                        <x-ui.textarea name="catatan" rows="2" placeholder="Catatan tambahan" />
                    </x-ui.field>
                @endif
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                    <x-ui.button type="submit" :variant="$cfg['variant']"><i class="bi {{ $cfg['icon'] }}"></i> {{ $cfg['submit'] }}</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endforeach
</x-layouts.app>
