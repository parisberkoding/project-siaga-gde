@php
    $user = Auth::user();
    $canSparepart = $user->hasPermission('vehicle.sparepart');
    $totalVpr = max(1, collect($statusBars)->sum('total'));
    $barColor = fn ($key) => match ($key) {
        'rejected' => 'bg-destructive',
        'pending' => 'bg-warning',
        'completed' => 'bg-success',
        default => 'bg-primary',
    };
@endphp

<x-layouts.app title="Dashboard Kendaraan" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-truck'" title="Dashboard Kendaraan & Alat"
        description="Kesehatan armada, jadwal pengecekan, dan permohonan penggantian sparepart dalam satu ringkasan.">
        <x-slot:actions>
            @if ($canSparepart)
                <x-ui.button variant="outline" :href="route('admin.replacement-approval.index')"><i class="bi bi-tools"></i> Permohonan VPR</x-ui.button>
            @endif
            @if ($user->hasPermission('vehicle.checking'))
                <x-ui.button variant="outline" :href="route('dispatcher.daily-check.index')"><i class="bi bi-clipboard-check"></i> Mulai Pemeriksaan</x-ui.button>
            @endif
            @if ($user->hasPermission('vehicle.view'))
                <x-ui.button :href="route('vehicles')"><i class="bi bi-truck"></i> Daftar Kendaraan</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Kendaraan & Alat" :value="number_format($totalKendaraan)" icon="bi bi-truck"
            :hint="number_format($kendaraanAktif) . ' aktif'" :href="$user->hasPermission('vehicle.view') ? route('vehicles') : null" />
        <x-ui.stat-card label="Jadwal Terlambat" :value="number_format($jadwalOverdue)" icon="bi bi-calendar-x" :tone="$jadwalOverdue ? 'warning' : 'muted'"
            :hint="number_format($jadwalHariIni) . ' jatuh tempo hari ini'" />
        <x-ui.stat-card label="VPR Menunggu" :value="number_format($vprMenunggu)" icon="bi bi-wrench-adjustable" :tone="$vprMenunggu ? 'danger' : 'muted'"
            :hint="number_format($vprBerjalan) . ' sedang diproses'" :href="$canSparepart ? route('admin.replacement-approval.index') : null" />
        <x-ui.stat-card label="Pengecekan Bulan Ini" :value="number_format($cekBulanIni)" icon="bi bi-clipboard2-check" tone="success"
            :hint="number_format($cekHariIni) . ' hari ini · ' . number_format($temuanNg) . ' temuan NG'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card class="gap-4 lg:col-span-2">
            <x-ui.card-header title="Tren Pengecekan" icon="bi bi-bar-chart-line" description="Jumlah pengecekan kendaraan 6 bulan terakhir" />
            <x-ui.card-content class="flex flex-1 flex-col">
                <div class="relative min-h-64 flex-1"><canvas id="vehicleTrendChart" role="img" aria-label="Grafik tren pengecekan kendaraan 6 bulan"></canvas></div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="Status VPR" icon="bi bi-pie-chart" description="Permohonan penggantian sparepart" />
            <x-ui.card-content>
                <ul class="grid gap-3">
                    @foreach ($statusBars as $bar)
                        <x-ui.bar-row :label="$bar['label']" :value="$bar['total']" :max="$totalVpr" :color="$barColor($bar['key'])" />
                    @endforeach
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card class="gap-4">
            <x-ui.card-header title="Jadwal Terdekat" icon="bi bi-calendar-event" description="Jatuh tempo dalam 7 hari ke depan" />
            <x-ui.card-content>
                <ul class="divide-y">
                    @forelse ($jadwalTerdekat as $schedule)
                        @php $overdue = $schedule->next_due_date?->isPast(); @endphp
                        <li class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-foreground"><i class="bi bi-truck"></i></span>
                            <div class="grid min-w-0 flex-1">
                                <span class="truncate text-sm font-medium">{{ $schedule->vehicle?->consumerial_code ?? $schedule->schedule_code }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ $schedule->vehicle?->consumerial_name ?? 'Kendaraan' }} · {{ ucfirst($schedule->check_frequency) }}</span>
                            </div>
                            <x-ui.badge :variant="$overdue ? 'danger' : 'muted'">{{ $schedule->next_due_date?->translatedFormat('d M Y') ?? '-' }}</x-ui.badge>
                        </li>
                    @empty
                        <li><x-ui.empty-state icon="bi bi-calendar-check" title="Tidak ada jadwal" description="Tidak ada pengecekan yang jatuh tempo dalam 7 hari ke depan." /></li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="VPR Terbaru" icon="bi bi-tools" description="Permohonan penggantian terakhir">
                @if ($canSparepart)
                    <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('admin.replacement-approval.index')">Lihat semua <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                @endif
            </x-ui.card-header>
            <x-ui.card-content>
                <ul class="divide-y">
                    @forelse ($vprTerbaru as $vpr)
                        @php
                            $vprVariant = match ($vpr->status) {
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                'completed' => 'success',
                                default => 'info',
                            };
                        @endphp
                        <li class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-foreground"><i class="bi bi-wrench-adjustable"></i></span>
                            <div class="grid min-w-0 flex-1">
                                <span class="truncate text-sm font-medium">{{ $vpr->vpr_code }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ $vpr->vehicle?->consumerial_code ?? 'Kendaraan' }} · {{ $vpr->created_at?->translatedFormat('d M Y') }}</span>
                            </div>
                            <x-ui.badge :variant="$vprVariant">{{ $vpr->status_label ?? ucfirst(str_replace('_', ' ', $vpr->status)) }}</x-ui.badge>
                            @if ($canSparepart)
                                <x-ui.button variant="ghost" size="icon-sm" :href="route('admin.replacement-approval.show', $vpr)" data-tooltip="Detail" aria-label="Detail {{ $vpr->vpr_code }}"><i class="bi bi-eye"></i></x-ui.button>
                            @endif
                        </li>
                    @empty
                        <li><x-ui.empty-state icon="bi bi-tools" title="Belum ada permohonan penggantian" /></li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    @push('scripts')
        <script type="application/json" id="page-data">@json(['trend' => $trend])</script>
        @vite('resources/js/pages/vehicle/dashboard.js')
    @endpush
</x-layouts.app>
