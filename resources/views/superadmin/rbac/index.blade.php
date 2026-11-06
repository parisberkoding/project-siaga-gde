{{--
  Role & Hak Akses (modul Pengguna & Hak Akses). Dua tampilan lewat ?view=:
  roles  = kartu role (ubah/hapus memakai endpoint yang ada),
  matrix = matriks permission x role, hanya baca. Ubah langsung dari matriks butuh endpoint sinkron massal di BE.
--}}
@php
    $view = request('view') === 'matrix' ? 'matrix' : 'roles';
    $groups = \App\Models\PermissionGroup::with(['permissions' => fn ($q) => $q->orderBy('code')])->orderBy('order')->get();
    $permTotal = $groups->sum(fn ($g) => $g->permissions->count());
    $matrixRoles = $roles->where('is_active', true)->values();
    $owned = $matrixRoles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('id')->flip()]);
    $tiles = ['from-blue-600 to-indigo-600', 'from-emerald-500 to-teal-600', 'from-orange-400 to-amber-500', 'from-violet-500 to-purple-600', 'from-cyan-500 to-sky-600', 'from-rose-500 to-pink-600'];
    $soon = '<span class="ml-1 rounded-full bg-muted px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Segera</span>';
@endphp

<x-layouts.app title="Role & Hak Akses" :breadcrumbs="[($view === 'matrix' ? 'Matriks Hak Akses' : 'Daftar Role') => null]">
    <x-ui.page-header kicker="Pengguna & Hak Akses" title="Role & Hak Akses"
        description="Atur role dan permission. Setiap pengguna mendapat gabungan permission dari role-nya.">
        <x-slot:actions>
            <x-ui.button :href="route('superadmin.rbac.create')"><i class="bi bi-plus-lg"></i> Tambah Role</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Role" :value="$roles->count()" icon="bi bi-shield-lock" hint="{{ $roles->where('is_system', true)->count() }} role sistem" />
        <x-ui.stat-card label="Role Aktif" :value="$roles->where('is_active', true)->count()" icon="bi bi-shield-check" tone="success" hint="Bisa diberikan ke pengguna" />
        <x-ui.stat-card label="Permission" :value="$permTotal" icon="bi bi-key" tone="muted" hint="{{ $groups->count() }} grup modul" />
        <x-ui.stat-card label="Role Khusus Unit" :value="$roles->reject->isGlobal()->count()" icon="bi bi-diagram-3" tone="warning" hint="Hanya untuk unit tertentu" />
    </div>

    <x-ui.tabs-list>
        <x-ui.tabs-link :href="route('superadmin.rbac.index')" :active="$view === 'roles'"><i class="bi bi-shield-lock"></i> Daftar Role</x-ui.tabs-link>
        <x-ui.tabs-link :href="route('superadmin.rbac.index', ['view' => 'matrix'])" :active="$view === 'matrix'"><i class="bi bi-grid-3x3-gap"></i> Matriks Hak Akses</x-ui.tabs-link>
    </x-ui.tabs-list>

    @if ($view === 'roles')
        @if ($roles->isEmpty())
            <x-ui.card><x-ui.card-content><x-ui.empty-state icon="bi bi-shield" title="Belum ada role" /></x-ui.card-content></x-ui.card>
        @else
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($roles as $i => $r)
                    <article @class(['flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-shadow hover:shadow-md', 'opacity-70' => ! $r->is_active])>
                        <div class="flex items-start gap-3 p-5">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br {{ $tiles[$i % count($tiles)] }} text-lg text-white shadow-md">
                                <i class="bi {{ $r->is_system ? 'bi-shield-fill-check' : 'bi-shield' }}"></i>
                            </span>
                            <div class="grid min-w-0 flex-1 gap-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <h3 class="truncate font-semibold">{{ $r->name }}</h3>
                                    @if ($r->is_system)<x-ui.badge variant="warning"><i class="bi bi-lock-fill"></i> Sistem</x-ui.badge>@endif
                                    @unless ($r->is_active)<x-ui.badge variant="danger">Nonaktif</x-ui.badge>@endunless
                                </div>
                                @if ($r->name !== $r->code)<code class="text-xs text-muted-foreground">{{ $r->code }}</code>@endif
                                <p class="line-clamp-2 text-sm text-muted-foreground">{{ $r->description ?: 'Belum ada deskripsi.' }}</p>
                            </div>
                        </div>
                        <div class="mt-auto grid grid-cols-3 divide-x border-t bg-muted/30 text-center">
                            <div class="px-2 py-3"><span class="block text-lg font-semibold tabular-nums">{{ $r->permissions->count() }}</span><span class="text-xs text-muted-foreground">Permission</span></div>
                            <div class="px-2 py-3"><span class="block text-lg font-semibold tabular-nums">{{ $r->users_count }}</span><span class="text-xs text-muted-foreground">Pengguna</span></div>
                            <div class="px-2 py-3"><span class="block truncate text-sm font-semibold">{{ $r->isGlobal() ? 'Global' : ($r->unit->code ?? '-') }}</span><span class="text-xs text-muted-foreground">Cakupan</span></div>
                        </div>
                        <div class="flex items-center justify-end gap-2 border-t px-4 py-3">
                            @if ($r->is_system)
                                <span class="mr-auto text-xs text-muted-foreground">Role sistem tidak bisa diubah atau dihapus.</span>
                            @else
                                <form action="{{ route('superadmin.rbac.delete', $r->id) }}" method="POST" class="mr-auto"
                                      data-confirm="{{ $r->users_count ? "Role ini masih dipakai {$r->users_count} pengguna, jadi penghapusan akan ditolak." : 'Role ini akan dihapus permanen.' }}" data-confirm-title="Hapus role {{ $r->name }}?" data-confirm-text="Ya, hapus" data-confirm-variant="destructive">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm" class="text-destructive hover:bg-destructive-soft"><i class="bi bi-trash3"></i> Hapus</x-ui.button>
                                </form>
                                <x-ui.button size="sm" variant="outline" :href="route('superadmin.rbac.edit', $r->id)"><i class="bi bi-sliders"></i> Atur permission</x-ui.button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @else
        <x-ui.card class="gap-0 py-0">
            <div class="flex flex-wrap items-center gap-3 border-b p-4">
                <div class="relative min-w-60 flex-1">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                    <x-ui.input type="search" data-matrix-search placeholder="Cari permission, misalnya bbm.approve" class="pl-9" />
                </div>
                <span class="text-xs text-muted-foreground">Hanya baca. Ubah per role lewat Atur permission. Ubah langsung di matriks {!! $soon !!}</span>
            </div>
            <div class="max-h-[70vh] overflow-auto">
                <table class="w-full border-separate border-spacing-0 text-sm">
                    <thead class="sticky top-0 z-10 bg-card">
                        <tr>
                            <th class="sticky left-0 z-20 min-w-64 border-b bg-card px-4 py-3 text-left font-medium">Permission</th>
                            @foreach ($matrixRoles as $r)
                                <th class="min-w-24 border-b px-2 py-3 text-center align-bottom text-xs font-medium">
                                    <a href="{{ $r->is_system ? '#' : route('superadmin.rbac.edit', $r->id) }}" class="line-clamp-2 hover:text-primary" title="{{ $r->name }}">{{ $r->name }}</a>
                                    <span class="mt-0.5 block font-normal text-muted-foreground tabular-nums">{{ $r->permissions->count() }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            <tr data-matrix-group>
                                <td colspan="{{ $matrixRoles->count() + 1 }}" class="sticky left-0 border-b bg-muted/60 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    <i class="bi {{ $group->icon ?? 'bi-shield' }}"></i> {{ $group->name }} <span class="font-normal">({{ $group->permissions->count() }})</span>
                                </td>
                            </tr>
                            @foreach ($group->permissions as $perm)
                                <tr data-matrix-row data-search="{{ strtolower($perm->code . ' ' . $perm->description) }}" class="hover:bg-muted/40">
                                    <td class="sticky left-0 border-b bg-card px-4 py-2">
                                        <code class="text-xs font-medium">{{ $perm->code }}</code>
                                        <span class="block text-xs text-muted-foreground">{{ $perm->description ?: $perm->action }}</span>
                                    </td>
                                    @foreach ($matrixRoles as $r)
                                        <td class="border-b text-center">
                                            @if (isset($owned[$r->id][$perm->id]))
                                                <span class="inline-flex size-6 items-center justify-center rounded-full bg-success-soft text-success" title="{{ $r->name }}: {{ $perm->code }}"><i class="bi bi-check-lg"></i></span>
                                            @else
                                                <span class="text-muted-foreground/40">·</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    @vite('resources/js/pages/rbac/index.js')
</x-layouts.app>
