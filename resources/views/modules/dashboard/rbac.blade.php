@php
    $canManage = Auth::user()->hasPermission('rbac.roles');
@endphp

<x-layouts.app title="Dashboard RBAC" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-shield-lock'" title="Dashboard RBAC & Hak Akses"
        description="Kesehatan konfigurasi role, permission, dan akun yang belum memiliki role.">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button variant="outline" :href="route('superadmin.rbac.index')"><i class="bi bi-shield-lock"></i> Kelola Role</x-ui.button>
                <x-ui.button :href="route('superadmin.rbac.create')"><i class="bi bi-plus-lg"></i> Tambah Role</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Role" :value="number_format($totalRole)" icon="bi bi-shield-lock" hint="Role aktif & sistem" :href="$canManage ? route('superadmin.rbac.index') : null" />
        <x-ui.stat-card label="Total Permission" :value="number_format($totalPermission)" icon="bi bi-key" tone="success" hint="Hak akses terdaftar" />
        <x-ui.stat-card label="Grup Permission" :value="number_format($totalGroup)" icon="bi bi-collection" tone="warning" hint="Pengelompokan modul" />
        <x-ui.stat-card label="User Tanpa Role" :value="number_format($userTanpaRole)" icon="bi bi-person-exclamation" :tone="$userTanpaRole ? 'danger' : 'muted'" hint="Perlu ditinjau" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.card class="gap-4 xl:col-span-2">
            <x-ui.card-header title="Ringkasan Role" icon="bi bi-table" description="Jumlah pengguna dan permission pada setiap role">
                @if ($canManage)
                    <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('superadmin.rbac.index')">Kelola Role <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
                @endif
            </x-ui.card-header>
            <x-ui.card-content>
                @if ($roles->isEmpty())
                    <x-ui.empty-state icon="bi bi-shield" title="Belum ada role" />
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Kode</th>
                                <th class="text-right">Pengguna</th>
                                <th class="text-right">Permission</th>
                                <th>Unit</th>
                                @if ($canManage)<th><span class="sr-only">Aksi</span></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="font-medium">{{ $role->name }}</td>
                                    <td><code class="rounded bg-muted px-1.5 py-0.5 text-xs">{{ $role->code }}</code></td>
                                    <td class="text-right tabular-nums">{{ number_format($role->users_count) }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($role->permissions_count) }}</td>
                                    <td>
                                        @if ($role->unit)
                                            {{ $role->unit->name }}
                                        @else
                                            <x-ui.badge variant="muted">Global</x-ui.badge>
                                        @endif
                                    </td>
                                    @if ($canManage)
                                        <td class="text-right">
                                            <x-ui.button variant="ghost" size="icon-sm" :href="route('superadmin.rbac.edit', $role->id)" data-tooltip="Ubah" aria-label="Ubah role {{ $role->name }}"><i class="bi bi-pencil"></i></x-ui.button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="Role Terbaru" icon="bi bi-clock-history" description="5 role yang terakhir dibuat" />
            <x-ui.card-content>
                <ul class="divide-y">
                    @forelse ($roleTerbaru as $role)
                        <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                            <div class="grid min-w-0">
                                <span class="truncate text-sm font-medium">{{ $role->name }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ $role->description ?: 'Tanpa deskripsi' }}</span>
                            </div>
                            <span class="shrink-0 text-xs text-muted-foreground">{{ $role->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                        </li>
                    @empty
                        <li><x-ui.empty-state icon="bi bi-shield" title="Belum ada role" /></li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</x-layouts.app>
