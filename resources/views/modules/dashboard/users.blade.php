@php
    $user = Auth::user();
    $canView = $user->hasPermission('users.view');
    $maxRole = max(1, $roleCounts->max('users_count') ?? 1);
    $maxUnit = max(1, $unitCounts->max('users_count') ?? 1);
@endphp

<x-layouts.app title="Dashboard Pengguna" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-people'" title="Dashboard Manajemen Pengguna"
        description="Jumlah akun, sebaran role, dan pengguna terbaru pada unit bisnis Anda.">
        <x-slot:actions>
            @if ($canView)
                <x-ui.button variant="outline" :href="route('users')"><i class="bi bi-people"></i> Daftar User</x-ui.button>
            @endif
            @if ($user->hasPermission('users.create'))
                <x-ui.button :href="route('users.create')"><i class="bi bi-person-plus"></i> Tambah User</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Pengguna" :value="number_format($totalUser)" icon="bi bi-people" hint="Akun terdaftar" :href="$canView ? route('users') : null" />
        <x-ui.stat-card label="Akun Aktif" :value="number_format($userAktif)" icon="bi bi-person-check" tone="success" hint="Boleh login" />
        <x-ui.stat-card label="Akun Nonaktif" :value="number_format($userNonaktif)" icon="bi bi-person-dash" :tone="$userNonaktif ? 'danger' : 'muted'" hint="Dinonaktifkan sementara" />
        <x-ui.stat-card label="Pengguna Baru" :value="number_format($userBaru)" icon="bi bi-person-plus" tone="warning" hint="Dibuat bulan ini" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card class="gap-4">
            <x-ui.card-header title="Sebaran Role" icon="bi bi-shield-check" description="Jumlah pengguna per role" />
            <x-ui.card-content>
                @if ($roleCounts->isEmpty())
                    <x-ui.empty-state icon="bi bi-shield" title="Belum ada role" />
                @else
                    <ul class="grid gap-3">
                        @foreach ($roleCounts as $role)
                            <x-ui.bar-row :label="$role->name" :value="$role->users_count" :max="$maxRole" />
                        @endforeach
                    </ul>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="Sebaran Unit" icon="bi bi-diagram-3" description="Jumlah pengguna per unit bisnis" />
            <x-ui.card-content>
                @if ($unitCounts->isEmpty())
                    <x-ui.empty-state icon="bi bi-diagram-3" title="Belum ada unit" />
                @else
                    <ul class="grid gap-3">
                        @foreach ($unitCounts as $unit)
                            <x-ui.bar-row :label="$unit->name . ($unit->is_central ? ' (Pusat)' : '')" :value="$unit->users_count" :max="$maxUnit" color="bg-success" />
                        @endforeach
                    </ul>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Pengguna Terbaru" icon="bi bi-person-lines-fill" description="6 akun terakhir yang dibuat">
            @if ($canView)
                <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('users')">Lihat semua <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
            @endif
        </x-ui.card-header>
        <x-ui.card-content>
            @if ($userTerbaru->isEmpty())
                <x-ui.empty-state icon="bi bi-people" title="Belum ada pengguna" />
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Unit</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($userTerbaru as $row)
                            <tr>
                                <td class="font-medium">{{ $row->nama_lengkap }}</td>
                                <td class="text-muted-foreground">{{ $row->username }}</td>
                                <td>{{ $row->unit?->name ?? 'Belum ada unit' }}</td>
                                <td>{{ $row->roles->pluck('name')->join(', ') ?: $row->role }}</td>
                                <td><x-ui.badge :variant="$row->is_active ? 'success' : 'danger'">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                                <td class="whitespace-nowrap text-muted-foreground">{{ $row->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
