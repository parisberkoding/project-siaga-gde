@php
    $canCreate = Auth::user()->hasPermission('spd.create');
    $canLpj = Auth::user()->hasPermission('lpj.create');
@endphp

<x-layouts.app title="Daftar Surat Tugas" :breadcrumbs="['Daftar Surat Tugas' => null]">
    <x-ui.page-header kicker="Surat Tugas" title="Daftar Surat Tugas" description="Surat tugas perjalanan dinas yang Anda ajukan beserta status LPJ-nya.">
        @if ($canCreate)
            <x-slot:actions>
                <x-ui.button :href="route('requester.surat.create')"><i class="bi bi-plus-lg"></i> Buat Surat Baru</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Surat" :value="$stats['total']" icon="bi bi-file-earmark-text" hint="Semua surat tugas" />
        <x-ui.stat-card label="Menunggu" :value="$stats['pending']" icon="bi bi-hourglass-split" tone="warning" hint="Menunggu validasi" />
        <x-ui.stat-card label="Disetujui" :value="$stats['approved']" icon="bi bi-check-circle" tone="success" hint="Surat disetujui" />
        <x-ui.stat-card label="Perlu LPJ" :value="$stats['need_lpj']" icon="bi bi-file-earmark-check" :tone="$stats['need_lpj'] ? 'danger' : 'muted'" hint="Belum dilaporkan" />
    </div>

    <x-ui.card>
        <x-ui.card-header title="Surat Tugas Saya" icon="bi bi-file-earmark-text" description="{{ $suratList->count() }} surat, terbaru di atas" />
        <x-ui.card-content>
            @if ($suratList->isEmpty())
                <x-ui.empty-state icon="bi bi-file-earmark-x" title="Belum ada surat tugas" description="Surat yang Anda ajukan akan tampil di sini.">
                    @if ($canCreate)
                        <x-ui.button class="mt-2" :href="route('requester.surat.create')"><i class="bi bi-plus-lg"></i> Buat Surat Pertama</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <x-ui.table :datatable="['perPage' => 10]">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Tujuan</th>
                            <th>Tanggal</th>
                            <th>Kendaraan</th>
                            <th>Status</th>
                            <th>LPJ</th>
                            <th class="w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratList as $surat)
                            <tr>
                                <td>
                                    <span class="font-medium">{{ $surat->nomor_surat }}</span>
                                    @if ($surat->is_extension)
                                        <x-ui.badge variant="info" class="mt-1 flex"><i class="bi bi-arrow-repeat"></i> Perpanjangan</x-ui.badge>
                                    @endif
                                </td>
                                <td class="max-w-56 truncate text-muted-foreground" title="{{ $surat->tempat_tujuan }}">{{ $surat->tempat_tujuan }}</td>
                                <td data-order="{{ $surat->tanggal_berangkat->timestamp }}" class="whitespace-nowrap">
                                    {{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-muted-foreground">{{ $surat->lama_perjalanan_hari }} hari</span>
                                </td>
                                <td><x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</x-ui.badge></td>
                                <td><x-spd.status-badge :status="$surat->status" /></td>
                                <td>
                                    @if ($surat->status === 'approved')
                                        @if ($surat->lpjPenggantian)
                                            <x-spd.status-badge :status="$surat->lpjPenggantian->status" />
                                        @else
                                            <x-spd.lpj-deadline :surat="$surat" />
                                        @endif
                                    @else
                                        <span class="text-muted-foreground">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <x-ui.dropdown-menu :id="'aksi-surat-' . $surat->id" label="Aksi surat">
                                        <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $surat->nomor_surat }}</code></div>
                                        <x-ui.dropdown-item icon="bi bi-eye" :href="route('requester.surat.show', $surat)">Lihat detail</x-ui.dropdown-item>
                                        @if ($surat->status === 'approved' && ! $surat->lpjPenggantian && $canLpj)
                                            <x-ui.dropdown-item icon="bi bi-file-earmark-plus" variant="success" :href="route('requester.lpj.create', $surat)">Buat LPJ</x-ui.dropdown-item>
                                        @endif
                                        @if ($surat->status === 'approved' && ! $surat->is_extension && $canCreate)
                                            <x-ui.dropdown-item icon="bi bi-clock-history" :href="route('requester.perpanjangan.create', ['surat_id' => $surat->id])">Ajukan perpanjangan</x-ui.dropdown-item>
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
