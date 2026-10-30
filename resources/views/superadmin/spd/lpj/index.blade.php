@php
    // Dipakai tiga rute (GA, management, pengguna). Route detail mengikuti KONTEKS halaman, bukan hanya
    // permission, agar user dengan banyak peran tidak nyasar ke tahap validasi yang lain.
    $isPengguna = request()->routeIs('lpj.pengguna.*');
    $showRoute = $isPengguna ? 'lpj.pengguna.show' : (request()->routeIs('lpj.management.*') ? 'lpj.management.show' : 'lpj.superadmin.show');
    $pageTitle = $isPengguna ? 'Validasi Perjalanan' : 'Validasi Laporan';
    $menungguOverdue = $suratMenungguOverdue ?? collect();
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $canPencairan = Auth::user()->hasPermission('lpj.pencairan');
@endphp

<x-layouts.app :title="$pageTitle" :breadcrumbs="[$pageTitle => null]">
    <x-ui.page-header :kicker="$isPengguna ? 'Validasi Pengguna' : 'Admin SPD'" title="Validasi Laporan Pertanggungjawaban"
        :description="$isPengguna ? 'LPJ perjalanan yang Anda ikuti dan perlu dikonfirmasi kebenarannya.' : 'Periksa LPJ dari driver lalu setujui atau tolak.'" />

    {{-- LPJ belum disubmit & lewat batas 3 hari: pengingat untuk mengejar, form LPJ driver tetap terbuka. --}}
    @if ($menungguOverdue->isNotEmpty())
        <x-ui.alert variant="destructive" :title="$menungguOverdue->count() . ' surat tugas belum dibuatkan LPJ dan sudah lewat batas 3 hari.'">
            <p>Batas submit = tanggal kembali SPD + 3 hari. Form LPJ driver <strong>tidak ditutup</strong>, LPJ tetap bisa masuk kapan saja dan akan tercatat terlambat.</p>
            <ul class="mt-2 grid gap-1.5">
                @foreach ($menungguOverdue as $suratOverdue)
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md bg-card/70 px-2.5 py-1.5 text-foreground">
                        <span class="font-medium">{{ $suratOverdue->nomor_surat }}</span>
                        <span class="text-xs text-muted-foreground">{{ $suratOverdue->pengemudi_nama }} &middot; {{ $suratOverdue->tempat_tujuan }} &middot; kembali {{ $suratOverdue->tanggal_kembali?->translatedFormat('d M Y') }}</span>
                        <x-spd.lpj-deadline :surat="$suratOverdue" compact class="ml-auto" />
                    </li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Menunggu Validasi" :value="$stats['pending']" icon="bi bi-hourglass-split" tone="warning" hint="Perlu divalidasi" />
        <x-ui.stat-card label="Disetujui" :value="$stats['approved']" icon="bi bi-check-circle" tone="success" hint="Total disetujui" />
        <x-ui.stat-card label="Terlambat" :value="$stats['late']" icon="bi bi-clock" tone="danger" hint="Submission terlambat" />
        <x-ui.stat-card label="Total Bulan Ini" :value="$rp($total_approved_amount)" icon="bi bi-cash-stack" tone="muted" hint="Total pengeluaran" />
    </div>

    <x-ui.tabs default="pending">
        <x-ui.tabs-list>
            <x-ui.tabs-trigger value="pending">Menunggu Validasi
                @if ($stats['pending'] > 0)<x-ui.badge variant="warning" class="rounded-full">{{ $stats['pending'] }}</x-ui.badge>@endif
            </x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="approved">Disetujui</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="rejected">Ditolak</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        {{-- Antrian: kartu; validasi dua tahap (User & GA) dilakukan di halaman detail. --}}
        <x-ui.tabs-content value="pending">
            @if ($pending_list->isEmpty())
                <x-ui.empty-state icon="bi bi-check2-all" title="Tidak ada LPJ yang menunggu validasi" description="Semua laporan sudah diproses." class="bg-card" />
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($pending_list->sortBy('created_at') as $lpj)
                        @php
                            $surat = $lpj->suratSpd;
                            $steps = [['User', $lpj->approved_by_pengguna], ['GA', $lpj->approved_by_divisi_ga]];
                        @endphp
                        <x-ui.queue-card :name="$lpj->nama" :subtitle="$surat->nomor_surat ?? '-'" :since="$lpj->created_at"
                            :status="$lpj->is_late ? 'Terlambat ' . $lpj->days_late . ' hari' : 'Menunggu'" :tone="$lpj->is_late ? 'danger' : 'warning'"
                            figure-label="Total biaya" :figure="$rp($lpj->total_pengeluaran)" :figure-hint="$lpj->is_over_budget ? 'Melebihi budget' : null">
                            <p class="flex min-w-0 items-center gap-2">
                                <i class="bi bi-geo-alt text-muted-foreground"></i>
                                <span class="truncate font-semibold" title="{{ $surat->tempat_tujuan ?? '' }}">{{ $surat->tempat_tujuan ?? '-' }}</span>
                                @if ($surat?->is_extension)<x-ui.badge variant="info">Perpanjangan</x-ui.badge>@endif
                            </p>
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-muted-foreground">
                                <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar3"></i> <span class="text-foreground">{{ $lpj->tanggal_lpj?->translatedFormat('d M Y') ?? '-' }}</span></span>
                                <span class="size-1 rounded-full bg-border"></span>
                                {{-- Progres dua tanda tangan --}}
                                @foreach ($steps as [$label, $done])
                                    <span @class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium', 'bg-success-soft text-success' => $done, 'bg-muted text-muted-foreground' => ! $done])>
                                        <i class="bi {{ $done ? 'bi-check-circle-fill' : 'bi-circle' }}"></i> {{ $label }}
                                    </span>
                                @endforeach
                            </p>
                            <x-slot:actions>
                                <x-ui.button variant="ghost" size="sm" :href="route('spd.laporan.export', $lpj)" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</x-ui.button>
                                <x-ui.button size="sm" :href="route($showRoute, $lpj)"><i class="bi bi-clipboard-check"></i> Periksa & Validasi</x-ui.button>
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
                        <x-ui.empty-state icon="bi bi-inbox" title="Belum ada LPJ yang disetujui" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat Tugas</th><th>Driver</th><th>Tujuan</th><th class="text-right">Total Biaya</th><th>Disetujui</th><th class="w-20 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($approved_list as $lpj)
                                    <tr>
                                        <td>
                                            <span class="whitespace-nowrap font-medium">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</span>
                                            @if ($lpj->suratSpd?->is_extension)<x-ui.badge variant="info" class="mt-1 flex">Perpanjangan</x-ui.badge>@endif
                                        </td>
                                        <td class="whitespace-nowrap">{{ $lpj->nama }}</td>
                                        <td class="max-w-48 truncate text-muted-foreground" title="{{ $lpj->suratSpd->tempat_tujuan ?? '' }}">{{ $lpj->suratSpd->tempat_tujuan ?? '-' }}</td>
                                        <td data-order="{{ $lpj->total_pengeluaran }}" class="whitespace-nowrap text-right font-semibold tabular-nums">{{ $rp($lpj->total_pengeluaran) }}</td>
                                        <td data-order="{{ $lpj->approved_pengguna_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                            {{ $lpj->approverPengguna->nama_lengkap ?? $lpj->approverPengguna->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $lpj->approved_pengguna_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <x-ui.dropdown-menu :id="'aksi-lpj-' . $lpj->id" label="Aksi LPJ">
                                                <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $lpj->suratSpd->nomor_surat ?? '-' }}</code></div>
                                                <x-ui.dropdown-item icon="bi bi-eye" :href="route($showRoute, $lpj)">Lihat detail</x-ui.dropdown-item>
                                                <x-ui.dropdown-item icon="bi bi-printer" :href="route('spd.laporan.export', $lpj)" target="_blank">Cetak SPD + LPJ</x-ui.dropdown-item>
                                                @if ($canPencairan)
                                                    <x-ui.dropdown-separator />
                                                    <x-ui.dropdown-item icon="bi bi-cash-stack" :href="route('lpj.superadmin.pencairan.index', $lpj)">Kelola pencairan</x-ui.dropdown-item>
                                                    <x-ui.dropdown-item icon="bi bi-file-earmark-text" :href="route('spd.laporan.pemberian-dana', $lpj)" target="_blank">Cetak lembar pemberian dana</x-ui.dropdown-item>
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
                        <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada LPJ yang ditolak" />
                    @else
                        <x-ui.table :datatable="['perPage' => 10]">
                            <thead>
                                <tr><th>No. Surat Tugas</th><th>Driver</th><th class="text-right">Total Biaya</th><th>Ditolak Oleh</th><th>Alasan</th><th class="w-20 text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($rejected_list as $lpj)
                                    <tr>
                                        <td class="whitespace-nowrap font-medium">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</td>
                                        <td class="whitespace-nowrap">{{ $lpj->nama }}</td>
                                        <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($lpj->total_pengeluaran) }}</td>
                                        <td class="whitespace-nowrap">
                                            {{ $lpj->rejector->nama_lengkap ?? $lpj->rejector->name ?? '-' }}
                                            <span class="block text-xs text-muted-foreground">{{ $lpj->rejected_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                        </td>
                                        <td class="max-w-64 truncate text-destructive" title="{{ $lpj->rejection_reason }}">{{ $lpj->rejection_reason }}</td>
                                        <td class="text-center"><x-ui.button variant="outline" size="icon-sm" :href="route($showRoute, $lpj)" title="Lihat detail" aria-label="Lihat detail"><i class="bi bi-eye"></i></x-ui.button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </x-ui.tabs-content>
    </x-ui.tabs>
</x-layouts.app>
