@php
    $canCreate = Auth::user()->hasPermission('spd.create');
@endphp

<x-layouts.app title="Pengajuan Perpanjangan" :breadcrumbs="['Perpanjangan' => null]">
    <x-ui.page-header kicker="Surat Tugas" title="Pengajuan Perpanjangan SPD" description="Riwayat pengajuan perpanjangan waktu perjalanan dinas Anda.">
        @if ($canCreate)
            <x-slot:actions>
                <x-ui.button :href="route('requester.perpanjangan.create')"><i class="bi bi-plus-lg"></i> Ajukan Perpanjangan</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.card-header title="Daftar Pengajuan Perpanjangan" icon="bi bi-clock-history" />
        <x-ui.card-content>
            @if ($extensionList->isEmpty())
                <x-ui.empty-state icon="bi bi-clock" title="Belum ada pengajuan perpanjangan">
                    @if ($canCreate)
                        <x-ui.button class="mt-2" :href="route('requester.perpanjangan.create')"><i class="bi bi-plus-lg"></i> Ajukan Perpanjangan</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <x-ui.table :datatable="['perPage' => 10]">
                    <thead>
                        <tr>
                            <th>Surat Original</th>
                            <th>Surat Perpanjangan</th>
                            <th>Perpanjangan</th>
                            <th class="text-right">Dana Perpanjangan</th>
                            <th>Status</th>
                            <th>Tanggal Ajukan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($extensionList as $ext)
                            <tr>
                                <td>
                                    <span class="font-medium">{{ $ext->originalSurat->nomor_surat }}</span>
                                    <span class="block text-xs text-muted-foreground">{{ Str::limit($ext->originalSurat->tempat_tujuan, 25) }}</span>
                                </td>
                                <td @class(['font-medium text-success' => $ext->status === 'approved', 'text-muted-foreground' => $ext->status !== 'approved'])>{{ $ext->nomor_surat_extension }}</td>
                                <td class="whitespace-nowrap">
                                    <span class="text-xs text-muted-foreground">{{ $ext->tanggal_kembali_original->translatedFormat('d M') }}</span>
                                    <i class="bi bi-arrow-right text-xs text-muted-foreground"></i>
                                    <span class="font-medium">{{ $ext->tanggal_kembali_baru->translatedFormat('d M Y') }}</span>
                                    <x-ui.badge variant="info" class="mt-1 flex">+{{ $ext->durasi_tambahan_hari }} hari</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums">@if ((float) $ext->budget_tambahan > 0)Rp {{ number_format($ext->budget_tambahan, 0, ',', '.') }}@else<span class="text-xs text-muted-foreground">Menunggu input Admin GA</span>@endif</td>
                                <td>
                                    <x-spd.status-badge :status="$ext->status" />
                                    @if ($ext->status === 'approved' && ! $ext->is_acknowledged)
                                        <span class="mt-1 block text-xs text-muted-foreground">Belum di-checklist</span>
                                    @elseif ($ext->status === 'rejected' && $ext->rejection_reason)
                                        <span class="mt-1 block text-xs text-destructive" title="{{ $ext->rejection_reason }}">{{ Str::limit($ext->rejection_reason, 30) }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ $ext->created_at->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-muted-foreground">{{ $ext->created_at->format('H:i') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
