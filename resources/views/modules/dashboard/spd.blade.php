@php
    $user = Auth::user();
    $canManage = $user->hasPermission('spd.manage');
    $canValidateLpj = $user->hasPermission('lpj.validate');
    $canExtend = $user->hasPermission('spd.update');

    // Warna tetap per status (dipakai donut & legenda).
    $statusColors = ['pending' => '#9a6b00', 'approved' => '#4a7a1c', 'completed' => '#4154f1', 'rejected' => '#c4142a'];
    $statusColor = fn ($key) => $statusColors[$key] ?? '#5b6b80';
    $statusTotal = collect($statusBars)->sum('total');

    // Ringkasan tren 6 bulan.
    $trendTotal = array_sum($trend['values']);
    $peakIndex = $trendTotal ? array_search(max($trend['values']), $trend['values']) : null;

    $pageData = [
        'trend' => $trend,
        'status' => [
            'labels' => array_column($statusBars, 'label'),
            'values' => array_column($statusBars, 'total'),
            'colors' => array_map(fn ($bar) => $statusColor($bar['key']), $statusBars),
        ],
    ];
@endphp

<x-layouts.app title="Dashboard SPD" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-envelope-paper'" title="Dashboard SPD & LPJ"
        description="Ringkasan surat tugas, perjalanan yang sedang berjalan, dan LPJ yang menunggu tindakan.">
        <x-slot:actions>
            @if ($canValidateLpj)
                <x-ui.button variant="outline" :href="route('lpj.superadmin.index')"><i class="bi bi-receipt"></i> Validasi LPJ</x-ui.button>
            @endif
            @if ($canManage)
                <x-ui.button variant="outline" :href="route('surat.superadmin.index')"><i class="bi bi-check2-square"></i> Validasi Surat</x-ui.button>
                <x-ui.button :href="route('surat.superadmin.create-shortcut')"><i class="bi bi-plus-lg"></i> Buat Surat</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($lpjTerlambat > 0 && $canValidateLpj)
        <x-ui.alert variant="destructive" :title="$lpjTerlambat . ' LPJ tercatat terlambat.'">
            LPJ yang masuk setelah batas 3 hari tetap bisa divalidasi, statusnya tercatat terlambat.
            <a href="{{ route('lpj.superadmin.index') }}" class="font-medium underline underline-offset-4">Buka Validasi Laporan</a>
        </x-ui.alert>
    @endif

    {{-- Kartu bisa diklik menuju halaman tindak lanjutnya (bila pengguna berhak). --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Surat Bulan Ini" :value="number_format($suratBulanIni)" icon="bi bi-envelope-paper"
            :hint="'Dibuat sejak ' . now()->startOfMonth()->translatedFormat('d M Y')" :href="$canManage ? route('surat.superadmin.report') : null" />
        <x-ui.stat-card label="Menunggu Validasi" :value="number_format($menungguValidasi)" icon="bi bi-hourglass-split" :tone="$menungguValidasi ? 'warning' : 'muted'"
            hint="Surat tugas belum disetujui" :href="$canManage ? route('surat.superadmin.index') : null" />
        <x-ui.stat-card label="Perjalanan Berjalan" :value="number_format($perjalananBerjalan)" icon="bi bi-signpost-2" tone="success"
            hint="Sedang berlangsung hari ini" />
        <x-ui.stat-card label="LPJ Menunggu" :value="number_format($lpjMenunggu)" icon="bi bi-receipt-cutoff" :tone="$lpjMenunggu ? 'danger' : 'muted'"
            :hint="$lpjTerlambat ? number_format($lpjTerlambat) . ' di antaranya terlambat' : 'Belum divalidasi GA'" :href="$canValidateLpj ? route('lpj.superadmin.index') : null" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card class="gap-4 lg:col-span-2">
            <x-ui.card-header title="Tren Surat Tugas" icon="bi bi-bar-chart-line" description="Jumlah surat tugas dibuat per bulan, 6 bulan terakhir" />
            <x-ui.card-content class="flex flex-1 flex-col gap-4">
                <div class="flex flex-wrap gap-x-8 gap-y-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Total 6 bulan</p>
                        <p class="text-xl font-bold tabular-nums tracking-tight">{{ number_format($trendTotal) }} <span class="text-sm font-normal text-muted-foreground">surat</span></p>
                    </div>
                    @if ($peakIndex !== null)
                        <div>
                            <p class="text-xs text-muted-foreground">Bulan tersibuk</p>
                            <p class="text-xl font-bold tabular-nums tracking-tight">{{ $trend['labels'][$peakIndex] }} <span class="text-sm font-normal text-muted-foreground">{{ $trend['values'][$peakIndex] }} surat</span></p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-muted-foreground">Budget disetujui</p>
                        <p class="text-xl font-bold tabular-nums tracking-tight">Rp {{ number_format((float) $totalBudget, 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="relative min-h-64 flex-1"><canvas id="spdTrendChart" role="img" aria-label="Grafik tren surat tugas 6 bulan"></canvas></div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="Status Surat" icon="bi bi-pie-chart" description="Seluruh surat tugas pada unit Anda" />
            <x-ui.card-content class="grid gap-5">
                <div class="relative mx-auto size-44">
                    <canvas id="spdStatusChart" role="img" aria-label="Grafik status surat tugas"></canvas>
                    @if ($statusTotal)
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-xs text-muted-foreground">Total</span>
                            <span class="text-xl font-bold tabular-nums">{{ number_format($statusTotal) }}</span>
                        </div>
                    @endif
                </div>
                <ul class="grid gap-3 text-sm">
                    @foreach ($statusBars as $bar)
                        @php $percent = $statusTotal ? round($bar['total'] / $statusTotal * 100) : 0; @endphp
                        <li class="grid gap-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2"><span class="size-2.5 rounded-full" style="background: {{ $statusColor($bar['key']) }}"></span>{{ $bar['label'] }}</span>
                                <span class="font-semibold tabular-nums">{{ number_format($bar['total']) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full" style="width: {{ $percent }}%; background: {{ $statusColor($bar['key']) }}"></div></div>
                                <span class="w-9 text-right text-xs tabular-nums text-muted-foreground">{{ $percent }}%</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($canExtend)
                    <a href="{{ route('perpanjangan.superadmin.index') }}" class="flex items-center justify-between gap-2 rounded-lg border p-3 text-sm transition-colors hover:bg-accent">
                @else
                    <div class="flex items-center justify-between gap-2 rounded-lg border p-3 text-sm">
                @endif
                    <span class="flex items-center gap-2 text-muted-foreground"><i class="bi bi-arrow-repeat"></i> Perpanjangan pending</span>
                    <x-ui.badge :variant="$perpanjanganMenunggu ? 'warning' : 'muted'">{{ number_format($perpanjanganMenunggu) }}</x-ui.badge>
                @if ($canExtend)
                    </a>
                @else
                    </div>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <x-ui.card-header title="Surat Terbaru" icon="bi bi-clock-history" description="6 surat tugas terakhir">
                @if ($canManage)
                    <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('surat.superadmin.index')">Lihat semua <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                @endif
            </x-ui.card-header>
            <x-ui.card-content>
                <ul class="divide-y">
                    @forelse ($suratTerbaru as $surat)
                        @php
                            $showUrl = $canManage ? route('surat.superadmin.show', $surat) : ($user->hasPermission('spd.view') ? route('requester.surat.show', $surat) : null);
                        @endphp
                        <li class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-module-spd/10 text-module-spd"><i class="bi bi-file-earmark-text"></i></span>
                            <div class="grid min-w-0 flex-1">
                                <span class="truncate text-sm font-medium">{{ $surat->nomor_surat }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ $surat->pengemudi_nama }} &middot; {{ $surat->tanggal_berangkat?->translatedFormat('d M Y') }}</span>
                            </div>
                            <x-spd.status-badge :status="$surat->status" />
                            @if ($showUrl)
                                <x-ui.button variant="ghost" size="icon-sm" :href="$showUrl" aria-label="Lihat surat"><i class="bi bi-chevron-right"></i></x-ui.button>
                            @endif
                        </li>
                    @empty
                        <li><x-ui.empty-state icon="bi bi-envelope" title="Belum ada surat tugas" /></li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header title="LPJ Perlu Perhatian" icon="bi bi-exclamation-triangle" description="Menunggu validasi atau melewati tenggat">
                @if ($canValidateLpj)
                    <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('lpj.superadmin.index')">Lihat semua <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                @endif
            </x-ui.card-header>
            <x-ui.card-content>
                <ul class="divide-y">
                    @forelse ($lpjPerluPerhatian as $lpj)
                        @php
                            $showUrl = $canValidateLpj ? route('lpj.superadmin.show', $lpj) : ($user->hasPermission('lpj.view') ? route('requester.lpj.show', $lpj) : null);
                        @endphp
                        <li class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span @class([
                                'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                'bg-destructive-soft text-destructive' => $lpj->is_late,
                                'bg-warning-soft text-warning' => ! $lpj->is_late,
                            ])><i class="bi bi-receipt"></i></span>
                            <div class="grid min-w-0 flex-1">
                                <span class="truncate text-sm font-medium">{{ $lpj->lpj_number }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ $lpj->nama }} &middot; {{ $lpj->tanggal_lpj?->translatedFormat('d M Y') }}</span>
                            </div>
                            @if ($lpj->is_late)
                                <x-ui.badge variant="danger"><i class="bi bi-alarm"></i> Terlambat {{ $lpj->days_late }} hari</x-ui.badge>
                            @else
                                <x-spd.status-badge :status="$lpj->status" />
                            @endif
                            @if ($showUrl)
                                <x-ui.button variant="ghost" size="icon-sm" :href="$showUrl" aria-label="Lihat LPJ"><i class="bi bi-chevron-right"></i></x-ui.button>
                            @endif
                        </li>
                    @empty
                        <li><x-ui.empty-state icon="bi bi-check2-circle" title="Semua beres" description="Tidak ada LPJ yang perlu ditindaklanjuti." /></li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    @if ($perjalananAktif->isNotEmpty())
        <x-ui.card>
            <x-ui.card-header title="Perjalanan Sedang Berjalan" icon="bi bi-geo-alt" description="Pemantauan surat yang sedang aktif hari ini" />
            <x-ui.card-content>
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Pengemudi</th>
                            <th>Tujuan</th>
                            <th>Berangkat</th>
                            <th>Kembali</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjalananAktif as $surat)
                            <tr>
                                <td class="font-medium">{{ $surat->nomor_surat }}</td>
                                <td>{{ $surat->pengemudi_nama }}</td>
                                <td class="text-muted-foreground">{{ \Illuminate\Support\Str::limit($surat->tempat_tujuan, 42) }}</td>
                                <td class="whitespace-nowrap">{{ $surat->tanggal_berangkat?->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">{{ $surat->tanggal_kembali?->translatedFormat('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>
    @endif

    @push('scripts')
        <script type="application/json" id="page-data">@json($pageData)</script>
        @vite('resources/js/pages/spd/dashboard.js')
    @endpush
</x-layouts.app>
