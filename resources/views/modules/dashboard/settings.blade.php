@php
    $user = Auth::user();
    $canManage = $user->hasPermission('settings.manage');
    $canView = $user->hasPermission('settings.view');
@endphp

<x-layouts.app title="Dashboard Pengaturan" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-gear'" title="Dashboard Pengaturan & Data Master"
        description="Kondisi unit bisnis, modul yang aktif, tahap fitur, dan data master yang dipakai aplikasi.">
        <x-slot:actions>
            @if ($canView)
                <x-ui.button variant="outline" :href="route('master-gasoline.index')"><i class="bi bi-fuel-pump"></i> Harga BBM</x-ui.button>
            @endif
            @if ($canManage)
                <x-ui.button variant="outline" :href="route('superadmin.sites.index')"><i class="bi bi-geo-alt"></i> Site / Lokasi</x-ui.button>
                <x-ui.button :href="route('superadmin.units.index')"><i class="bi bi-diagram-3"></i> Unit Bisnis</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Unit Bisnis" :value="number_format($totalUnit)" icon="bi bi-diagram-3"
            :hint="number_format($totalSite) . ' site terdaftar'" :href="$canManage ? route('superadmin.units.index') : null" />
        <x-ui.stat-card label="Modul Aktif" :value="number_format($totalModul)" icon="bi bi-grid-3x3-gap" tone="success" hint="Terdaftar di aplikasi" />
        <x-ui.stat-card label="Fitur Production" :value="number_format($fiturProduction)" icon="bi bi-toggle2-on" tone="warning"
            :hint="number_format($fiturDevelopment) . ' masih development'" />
        <x-ui.stat-card label="Pemetaan SSO" :value="number_format($totalSsoMapping)" icon="bi bi-person-badge" hint="Jabatan ke role (aktif)" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.card class="gap-4 xl:col-span-2">
            <x-ui.card-header title="Modul & Akses Unit" icon="bi bi-grid-3x3-gap" description="Jumlah unit yang mengaktifkan tiap modul" />
            <x-ui.card-content>
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Modul</th>
                            <th class="text-right">Permission</th>
                            <th class="text-right">Unit Aktif</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modules as $row)
                            <tr>
                                <td class="font-medium"><i class="{{ $row->icon ?: 'bi bi-grid' }} mr-1.5 text-muted-foreground"></i>{{ $row->name }}</td>
                                <td class="text-right tabular-nums">{{ number_format($row->permissions_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format($row->units_count) }}</td>
                                <td>
                                    @if ($row->is_always_on)
                                        <x-ui.badge variant="info">Selalu aktif</x-ui.badge>
                                    @elseif ($row->is_active)
                                        <x-ui.badge variant="success">Aktif</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="muted">Nonaktif</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>

        <div class="grid content-start gap-4">
            <x-ui.card class="gap-4">
                <x-ui.card-header title="Unit & Site" icon="bi bi-diagram-3" description="Jumlah site dan pengguna per unit">
                    @if ($canManage)
                        <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('superadmin.units.index')">Kelola <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                    @endif
                </x-ui.card-header>
                <x-ui.card-content>
                    <ul class="divide-y">
                        @forelse ($units as $unit)
                            <li class="flex items-center gap-2 py-2.5 first:pt-0 last:pb-0">
                                <div class="grid min-w-0 flex-1">
                                    <span class="truncate text-sm font-medium">{{ $unit->name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $unit->code }}{{ $unit->is_central ? ' · Pusat' : '' }}</span>
                                </div>
                                <x-ui.badge variant="info">{{ number_format($unit->sites_count) }} site</x-ui.badge>
                                <x-ui.badge variant="muted">{{ number_format($unit->users_count) }} user</x-ui.badge>
                            </li>
                        @empty
                            <li><x-ui.empty-state icon="bi bi-diagram-3" title="Belum ada unit" /></li>
                        @endforelse
                    </ul>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Master Harga BBM" icon="bi bi-fuel-pump" description="Data harga bahan bakar terakhir yang tersinkron">
                    @if ($canView)
                        <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('master-gasoline.index')">Lihat <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                    @endif
                </x-ui.card-header>
                <x-ui.card-content class="grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-muted-foreground"><i class="bi bi-database"></i> Baris harga</span>
                        <span class="font-semibold tabular-nums">{{ number_format($totalHargaBbm) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-muted-foreground"><i class="bi bi-clock-history"></i> Update terakhir</span>
                        <span class="font-medium">{{ $hargaTerakhir?->updated_at?->translatedFormat('d M Y H:i') ?? 'Belum ada' }}</span>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
