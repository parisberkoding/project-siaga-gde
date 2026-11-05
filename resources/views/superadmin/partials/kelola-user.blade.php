{{--
  Manajemen Pengguna (modul Pengguna & Hak Akses). Semua aksi memakai endpoint yang sudah ada:
  filter search/role, ubah, reset password, kredensial (+ reveal JSON), hapus, pulihkan.
  Bagian bertanda "Segera" (filter unit/status, aksi massal, ekspor, riwayat aktivitas) baru UI:
  endpoint-nya belum ada di BE. Interaksi halaman: resources/js/pages/users/index.js.
--}}
@php
    $authUser = Auth::user();
    $scope = \App\Services\UnitScope::for($authUser);
    $scoped = fn () => \App\Models\User::query()->when(! $scope->isGlobal(), fn ($q) => $q->where('unit_id', $authUser->unit_id));
    $stats = [
        'total' => $scoped()->count(),
        'aktif' => $scoped()->where('is_active', true)->count(),
        'nonaktif' => $scoped()->where('is_active', false)->count(),
        'sso' => $scoped()->where('auth_provider', 'sso')->count(),
    ];
    $roleCounts = \Illuminate\Support\Facades\DB::table('user_roles')
        ->join('users', 'users.id', '=', 'user_roles.user_id')
        ->whereNull('users.deleted_at')
        ->when(! $scope->isGlobal(), fn ($q) => $q->where('users.unit_id', $authUser->unit_id))
        ->selectRaw('role_id, count(*) as total')->groupBy('role_id')->pluck('total', 'role_id');

    // Hak akses efektif per role untuk dialog detail (dibaca JS dari atribut data).
    $rolePerms = \App\Models\Role::with('permissions.group')->get()->mapWithKeys(fn ($r) => [$r->code => [
        'name' => $r->name,
        'groups' => $r->permissions->groupBy(fn ($p) => $p->group?->name ?? 'Lainnya')
            ->map(fn ($perms) => $perms->map(fn ($p) => ['code' => $p->code, 'label' => $p->description ?: $p->action])->values())->all(),
    ]]);

    $tones = [
        'superadmin' => 'bg-rose-100 text-rose-700 ring-rose-200', 'developers' => 'bg-slate-200 text-slate-700 ring-slate-300',
        'general_affairs' => 'bg-emerald-100 text-emerald-700 ring-emerald-200', 'manager' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'dispatcher' => 'bg-cyan-100 text-cyan-700 ring-cyan-200', 'requester' => 'bg-blue-100 text-blue-700 ring-blue-200',
        'pegawai' => 'bg-violet-100 text-violet-700 ring-violet-200', 'pengguna_spd' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
        'view_only' => 'bg-zinc-100 text-zinc-600 ring-zinc-200', 'vendor' => 'bg-orange-100 text-orange-700 ring-orange-200',
    ];
    $avatarTones = ['from-blue-500 to-indigo-600', 'from-emerald-500 to-teal-600', 'from-orange-400 to-rose-500', 'from-violet-500 to-fuchsia-600', 'from-cyan-500 to-sky-600'];
    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
    $canUpdate = $authUser->hasPermission('users.update');
    $canDelete = $authUser->hasPermission('users.delete');
    $canCreate = $authUser->hasPermission('users.create');
    $gateEnabled = (bool) config('gate.enabled');
    $gateResult = session('gate_sync');
    $soon = '<span class="ml-1 rounded-full bg-muted px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Segera</span>';
@endphp

<x-layouts.app title="Manajemen Pengguna" :breadcrumbs="['Daftar Pengguna' => null]">
    <x-ui.page-header kicker="Pengguna & Hak Akses" title="Manajemen Pengguna"
        description="Kelola akun, peran, unit, dan akses login setiap pengguna SIAGA.">
        <x-slot:actions>
            <x-ui.button variant="outline" disabled title="Ekspor daftar pengguna belum tersedia"><i class="bi bi-file-earmark-spreadsheet"></i> Ekspor {!! $soon !!}</x-ui.button>
            {{-- Sinkron GATE HCIS: hanya aktif bila GATE_SYNC_ENABLED=true (lokal: nonaktif, tanpa request ke GATE). --}}
            @if ($canCreate)
                @if ($gateEnabled)
                    <x-ui.button variant="outline" data-dialog-open="gateSyncDialog"><i class="bi bi-arrow-repeat"></i> Sync dari GATE</x-ui.button>
                @else
                    <x-ui.button variant="outline" disabled title="Sinkron GATE dinonaktifkan di lingkungan ini (GATE_SYNC_ENABLED=false)"><i class="bi bi-arrow-repeat"></i> Sync dari GATE <span class="ml-1 rounded-full bg-muted px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Nonaktif</span></x-ui.button>
                @endif
            @endif
            @if ($canCreate)
                <x-ui.button :href="route('users.create')"><i class="bi bi-person-plus"></i> Tambah Pengguna</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Pengguna" :value="$stats['total']" icon="bi bi-people" hint="Dalam cakupan unit Anda" />
        <x-ui.stat-card label="Akun Aktif" :value="$stats['aktif']" icon="bi bi-person-check" tone="success" hint="Bisa login" />
        <x-ui.stat-card label="Akun Nonaktif" :value="$stats['nonaktif']" icon="bi bi-person-slash" tone="warning" hint="Login diblokir" />
        <x-ui.stat-card label="Terhapus" :value="$deletedUsers->total()" icon="bi bi-trash3" tone="danger" hint="Bisa dipulihkan" :href="$canDelete ? route('users', ['tab' => 'deleted']) : null" />
    </div>

    {{-- Hasil sinkron GATE (pratinjau = belum disimpan, bisa langsung dijalankan dari sini). --}}
    @if ($gateResult)
        <x-ui.card @class(['gap-4', 'border-warning/40' => $gateResult['dry_run']])>
            <x-ui.card-header :title="$gateResult['dry_run'] ? 'Pratinjau sync GATE (belum disimpan)' : 'Sync GATE selesai'"
                :icon="$gateResult['dry_run'] ? 'bi bi-eye' : 'bi bi-check2-circle'"
                :description="$gateResult['total'] . ' pegawai dari GATE · ' . number_format($gateResult['duration_ms'] / 1000, 1, ',', '.') . ' detik'">
                @if ($gateResult['dry_run'] && $canCreate)
                    <x-slot:action>
                        <form method="POST" action="{{ route('users.sync-gate') }}" data-loading data-confirm="Simpan hasil sinkron GATE ke data Pengguna?" data-confirm-title="Jalankan sync GATE">
                            @csrf
                            <x-ui.button type="submit" size="sm" data-loading-text="Menyinkronkan…"><i class="bi bi-arrow-repeat"></i> Jalankan sync</x-ui.button>
                        </form>
                    </x-slot:action>
                @endif
            </x-ui.card-header>
            <x-ui.card-content class="grid gap-4">
                <dl class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach ([[$gateResult['dry_run'] ? 'Akan dibuat' : 'Akun dibuat', $gateResult['created']], ['Diperbarui', $gateResult['updated']], ['Dinonaktifkan', $gateResult['deactivated']], ['Tidak berubah', $gateResult['unchanged']], ['Dilewati', count($gateResult['skipped'])]] as [$label, $count])
                        <div class="rounded-lg border bg-muted/40 p-3">
                            <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                            <dd class="text-xl font-semibold tabular-nums">{{ $count }}</dd>
                        </div>
                    @endforeach
                </dl>
                @foreach (['skipped' => 'Dilewati', 'warnings' => 'Catatan'] as $key => $heading)
                    @if (count($gateResult[$key]))
                        <details class="rounded-lg border text-sm">
                            <summary class="cursor-pointer px-3 py-2 font-medium">{{ $heading }} ({{ count($gateResult[$key]) }})</summary>
                            <ul class="grid gap-1 border-t px-3 py-2 text-muted-foreground">
                                @foreach ($gateResult[$key] as [$who, $why])
                                    <li><span class="font-medium text-foreground">{{ $who }}</span>: {{ $why }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                @endforeach
            </x-ui.card-content>
        </x-ui.card>
    @endif

    <x-ui.tabs-list>
        <x-ui.tabs-link :href="route('users', ['tab' => 'list'])" :active="$tab !== 'deleted'"><i class="bi bi-people"></i> Daftar Pengguna</x-ui.tabs-link>
        @if ($canDelete)
            <x-ui.tabs-link :href="route('users', ['tab' => 'deleted'])" :active="$tab === 'deleted'"><i class="bi bi-trash3"></i> Terhapus
                @if ($deletedUsers->total())<x-ui.badge variant="danger" class="rounded-full">{{ $deletedUsers->total() }}</x-ui.badge>@endif
            </x-ui.tabs-link>
        @endif
    </x-ui.tabs-list>

    @if ($tab !== 'deleted')
        {{-- Filter cepat per role (dengan jumlah), lalu pencarian. --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('users', ['tab' => 'list', 'search' => $search]) }}"
               @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ring-1 transition-colors', 'bg-primary text-primary-foreground ring-primary' => ! $role, 'bg-card ring-border hover:bg-accent' => $role])>
                Semua <span class="tabular-nums opacity-75">{{ $stats['total'] }}</span>
            </a>
            @foreach ($roles as $r)
                <a href="{{ route('users', ['tab' => 'list', 'role' => $r->code, 'search' => $search]) }}"
                   @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ring-1 transition-colors', 'bg-primary text-primary-foreground ring-primary' => $role === $r->code, 'bg-card ring-border hover:bg-accent' => $role !== $r->code])>
                    {{ $r->name }} <span class="tabular-nums opacity-75">{{ $roleCounts[$r->id] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <x-ui.card class="gap-0 py-0">
            <form method="GET" action="{{ route('users') }}" data-autofilter class="flex flex-wrap items-center gap-2 border-b p-4">
                <input type="hidden" name="tab" value="list">
                @if ($role)<input type="hidden" name="role" value="{{ $role }}">@endif
                <div class="relative min-w-60 flex-1">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                    <x-ui.input name="search" :value="$search" placeholder="Cari nama, username, atau email" class="pl-9" />
                </div>
                <x-ui.select inline disabled title="Filter unit belum tersedia di server"><option>Semua unit</option></x-ui.select>
                <x-ui.select inline disabled title="Filter status belum tersedia di server"><option>Semua status</option></x-ui.select>
                <span class="text-xs text-muted-foreground">Filter unit dan status {!! $soon !!}</span>
            </form>

            {{-- Aksi massal: muncul saat ada baris yang dipilih. Endpoint belum ada, jadi tombolnya nonaktif. --}}
            <div data-bulk-bar hidden class="flex flex-wrap items-center gap-2 border-b bg-primary/5 px-4 py-2.5 text-sm">
                <span class="font-medium"><span data-bulk-count>0</span> pengguna dipilih</span>
                <span class="mx-1 h-4 w-px bg-border"></span>
                <x-ui.button size="sm" variant="outline" disabled><i class="bi bi-person-check"></i> Aktifkan</x-ui.button>
                <x-ui.button size="sm" variant="outline" disabled><i class="bi bi-person-slash"></i> Nonaktifkan</x-ui.button>
                <x-ui.button size="sm" variant="outline" disabled><i class="bi bi-shield"></i> Ubah role</x-ui.button>
                {!! $soon !!}
                <button type="button" data-bulk-clear class="ml-auto text-xs text-muted-foreground hover:text-foreground">Batal pilih</button>
            </div>

            @if ($users->isEmpty())
                <x-ui.empty-state icon="bi bi-people" title="Tidak ada pengguna" description="Ubah kata kunci atau filter role." class="py-12" />
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <th class="w-10 pl-4"><x-ui.checkbox data-bulk-all aria-label="Pilih semua di halaman ini" /></th>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Unit & Site</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="w-16 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $userRole = $user->roles->first();
                                $roleCode = $userRole->code ?? $user->role;
                                $payload = [
                                    'id' => $user->id, 'username' => $user->username, 'nama_lengkap' => $user->nama_lengkap, 'email' => $user->email,
                                    'jabatan' => $user->jabatan, 'role' => $roleCode, 'roles' => $user->roles->pluck('code')->all(),
                                    'site' => $user->default_site_id, 'unit' => $user->unit_id, 'is_active' => (bool) $user->is_active,
                                    'has_credentials' => $user->hasOperationalCredentials(),
                                    'unit_label' => $user->unit ? "{$user->unit->code} · {$user->unit->name}" : 'Global (semua unit)',
                                    'site_label' => $user->defaultSite ? "{$user->defaultSite->code} · {$user->defaultSite->name}" : '-',
                                    'provider' => $user->auth_provider === 'sso' ? 'SSO' : 'Akun lokal',
                                    'source' => $user->registration_source ?: '-',
                                    'created' => $user->created_at?->translatedFormat('d M Y H:i'),
                                    'deactivated' => $user->deactivated_at?->translatedFormat('d M Y H:i'),
                                    'deactivation_reason' => $user->deactivation_reason,
                                    'employee' => (bool) $user->employeeDefault,
                                ];
                            @endphp
                            <tr @class(['opacity-60' => ! $user->is_active])>
                                <td class="pl-4"><x-ui.checkbox data-bulk-item :value="$user->id" :aria-label="'Pilih ' . $user->username" /></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br {{ $avatarTones[$user->id % count($avatarTones)] }} text-xs font-semibold text-white shadow-sm">{{ $initials($user->nama_lengkap ?: $user->username) }}</span>
                                        <span class="grid min-w-0 leading-tight">
                                            <span class="truncate font-medium">{{ $user->nama_lengkap ?: $user->username }}</span>
                                            <span class="truncate text-xs text-muted-foreground">{{ '@' . $user->username }}{{ $user->email ? ' · ' . $user->email : '' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $tones[$roleCode] ?? 'bg-muted text-muted-foreground ring-border' }}">
                                        <i class="bi bi-shield-fill-check text-[10px]"></i> {{ $userRole->name ?? $user->role }}
                                    </span>
                                    @if ($user->roles->count() > 1)<span class="ml-1 text-xs text-muted-foreground">+{{ $user->roles->count() - 1 }}</span>@endif
                                    @if ($user->jabatan)<span class="mt-0.5 block max-w-44 truncate text-xs text-muted-foreground" title="{{ $user->jabatan }}">{{ $user->jabatan }}</span>@endif
                                </td>
                                <td>
                                    <span class="block text-sm">{{ $user->unit->code ?? 'Global' }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $user->defaultSite->code ?? 'Tanpa site' }}</span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @if ($user->is_active)
                                            <x-ui.badge variant="success"><span class="size-1.5 rounded-full bg-current"></span> Aktif</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger" :title="$user->deactivation_reason"><span class="size-1.5 rounded-full bg-current"></span> Nonaktif</x-ui.badge>
                                        @endif
                                        @if ($user->auth_provider === 'sso')<x-ui.badge variant="info">SSO</x-ui.badge>@endif
                                        @if ($user->isPendingCredentials())<x-ui.badge variant="warning">Kredensial pending</x-ui.badge>@endif
                                        @if ($user->employeeDefault)<x-ui.badge variant="muted" title="Terhubung ke Master Pekerja"><i class="bi bi-link-45deg"></i> Pekerja</x-ui.badge>@endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-xs text-muted-foreground">{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                                <td class="pr-4 text-right">
                                    <x-ui.dropdown-menu :id="'aksi-user-' . $user->id" label="Aksi pengguna">
                                        <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground">{{ '@' . $user->username }}</div>
                                        <x-ui.dropdown-item icon="bi bi-person-vcard" data-user-detail="{{ json_encode($payload) }}">Detail & hak akses</x-ui.dropdown-item>
                                        @if ($canUpdate)
                                            <x-ui.dropdown-item icon="bi bi-pencil-square" data-user-edit="{{ json_encode($payload) }}">Ubah data & role</x-ui.dropdown-item>
                                            <x-ui.dropdown-item icon="bi bi-key" data-user-reset="{{ json_encode($payload) }}">Reset password</x-ui.dropdown-item>
                                            <x-ui.dropdown-item icon="bi bi-person-badge" data-user-credentials="{{ json_encode($payload) }}">Kredensial akun</x-ui.dropdown-item>
                                        @endif
                                        @if ($canDelete && $user->id !== $authUser->id)
                                            <x-ui.dropdown-separator />
                                            <form action="{{ route('users.delete', $user->id) }}" method="POST"
                                                  data-confirm="Akun {{ $user->username }} dipindah ke tab Terhapus dan masih bisa dipulihkan." data-confirm-title="Hapus pengguna?" data-confirm-text="Ya, hapus" data-confirm-variant="destructive">
                                                @csrf
                                                <x-ui.dropdown-item type="submit" icon="bi bi-trash3" variant="destructive">Hapus pengguna</x-ui.dropdown-item>
                                            </form>
                                        @endif
                                    </x-ui.dropdown-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
                <div class="border-t p-4">{{ $users->appends(['tab' => 'list', 'role' => $role, 'search' => $search])->links('vendor.pagination.shadcn') }}</div>
            @endif
        </x-ui.card>
    @else
        <x-ui.alert variant="info">Pengguna yang dihapus masih bisa dipulihkan beserta role-nya.</x-ui.alert>
        <x-ui.card class="gap-0 py-0">
            @if ($deletedUsers->isEmpty())
                <x-ui.empty-state icon="bi bi-check2-circle" title="Tidak ada pengguna terhapus" class="py-12" />
            @else
                <x-ui.table>
                    <thead><tr><th class="pl-4">Pengguna</th><th>Role</th><th>Dihapus</th><th class="w-32 pr-4 text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($deletedUsers as $user)
                            <tr>
                                <td class="pl-4">
                                    <span class="block font-medium">{{ $user->nama_lengkap ?: $user->username }}</span>
                                    <span class="text-xs text-muted-foreground">{{ '@' . $user->username }}{{ $user->email ? ' · ' . $user->email : '' }}</span>
                                </td>
                                <td><x-ui.badge variant="muted">{{ $user->roles->first()->name ?? $user->role }}</x-ui.badge></td>
                                <td class="whitespace-nowrap text-xs text-muted-foreground">{{ $user->deleted_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="pr-4 text-right">
                                    <form action="{{ route('users.restore', $user->id) }}" method="POST" data-confirm="Akun {{ $user->username }} akan aktif kembali di daftar pengguna." data-confirm-title="Pulihkan pengguna?" data-confirm-text="Ya, pulihkan" data-confirm-variant="success">
                                        @csrf
                                        <x-ui.button type="submit" size="sm" variant="outline"><i class="bi bi-arrow-counterclockwise"></i> Pulihkan</x-ui.button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
                <div class="border-t p-4">{{ $deletedUsers->appends(['tab' => 'deleted'])->links('vendor.pagination.shadcn') }}</div>
            @endif
        </x-ui.card>
    @endif

    {{-- Detail & hak akses efektif (gabungan permission dari semua role pengguna). --}}
    @if ($canCreate && $gateEnabled)
        @php $lastGateSync = \Illuminate\Support\Facades\Cache::get(\App\Services\Gate\GateEmployeeSync::CACHE_KEY); @endphp
        <x-ui.dialog id="gateSyncDialog" title="Sync pengguna dari GATE" description="Data pegawai diambil dari GATE HCIS dan dicocokkan berdasarkan username SSO.">
            <ul class="grid gap-2.5 text-sm">
                <li class="flex gap-2"><i class="bi bi-person-badge text-primary"></i><span><b>Yang disimpan hanya</b> nama lengkap, jabatan, unit bisnis (dari lokasi kerja), dan username SSO.</span></li>
                <li class="flex gap-2"><i class="bi bi-person-plus text-primary"></i><span>Pegawai yang belum punya akun dibuat dengan role <b>Pegawai</b>; role akun lama tidak diubah.</span></li>
                <li class="flex gap-2"><i class="bi bi-person-dash text-primary"></i><span>Pegawai nonaktif di GATE akan <b>dinonaktifkan</b> (tidak dihapus).</span></li>
            </ul>
            <p class="text-xs text-muted-foreground">
                @if ($lastGateSync)
                    Sync terakhir: {{ \Illuminate\Support\Carbon::parse($lastGateSync['finished_at'])->translatedFormat('d M Y H:i') }}, {{ $lastGateSync['created'] }} dibuat, {{ $lastGateSync['updated'] }} diperbarui.
                @else
                    Belum pernah disinkronkan. Mulai dengan Pratinjau untuk melihat hasilnya tanpa menyimpan.
                @endif
            </p>
            <x-slot:footer>
                <form method="POST" action="{{ route('users.sync-gate') }}" data-loading>
                    @csrf
                    <input type="hidden" name="dry_run" value="1">
                    <x-ui.button type="submit" variant="outline" data-loading-text="Memuat…"><i class="bi bi-eye"></i> Pratinjau</x-ui.button>
                </form>
                <form method="POST" action="{{ route('users.sync-gate') }}" data-loading data-confirm="Simpan hasil sinkron GATE ke data Pengguna?" data-confirm-title="Jalankan sync GATE">
                    @csrf
                    <x-ui.button type="submit" data-loading-text="Menyinkronkan…"><i class="bi bi-arrow-repeat"></i> Jalankan sync</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    <x-ui.dialog id="userDetailDialog" title="Detail pengguna" size="lg">
        <div class="grid gap-5" data-role-perms="{{ $rolePerms->toJson() }}">
            <div class="flex items-center gap-3">
                <span data-detail="avatar" class="flex size-12 items-center justify-center rounded-full bg-linear-to-br from-blue-500 to-indigo-600 text-base font-semibold text-white"></span>
                <div class="grid leading-tight">
                    <span data-detail="nama_lengkap" class="text-lg font-semibold"></span>
                    <span data-detail="handle" class="text-sm text-muted-foreground"></span>
                </div>
                <span data-detail="status" class="ml-auto"></span>
            </div>
            <dl class="grid gap-x-6 gap-y-3 rounded-lg bg-muted/40 p-4 text-sm sm:grid-cols-2">
                @foreach (['jabatan' => 'Jabatan', 'unit_label' => 'Unit bisnis', 'site_label' => 'Site', 'provider' => 'Metode login', 'source' => 'Sumber pendaftaran', 'created' => 'Dibuat'] as $key => $label)
                    <div><dt class="text-xs text-muted-foreground">{{ $label }}</dt><dd data-detail="{{ $key }}" class="font-medium"></dd></div>
                @endforeach
            </dl>
            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Hak akses efektif</h3>
                    <span data-detail="perm_count" class="text-xs text-muted-foreground"></span>
                </div>
                <p class="text-xs text-muted-foreground">Gabungan permission dari role: <span data-detail="role_names" class="font-medium text-foreground"></span></p>
                <div data-detail="perms" class="grid max-h-72 gap-3 overflow-y-auto pr-1"></div>
            </div>
            <div class="grid gap-1 rounded-lg border border-dashed p-3 text-sm">
                <span class="font-medium">Riwayat aktivitas & login terakhir {!! $soon !!}</span>
                <span class="text-xs text-muted-foreground">Server belum mencatat waktu login dan riwayat perubahan akun.</span>
            </div>
        </div>
    </x-ui.dialog>

    @if ($canUpdate)
        <x-ui.dialog id="userEditDialog" title="Ubah data & role" description="Perubahan role langsung mengubah hak akses pengguna." size="lg" static>
            <form id="userEditForm" method="POST" class="grid gap-4" data-loading>
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Username" required><x-ui.input name="username" data-edit="username" required /></x-ui.field>
                    <x-ui.field label="Nama lengkap" required><x-ui.input name="nama_lengkap" data-edit="nama_lengkap" required /></x-ui.field>
                    <x-ui.field label="Email" hint="Boleh kosong untuk akun hasil scan."><x-ui.input type="email" name="email" data-edit="email" /></x-ui.field>
                    <x-ui.field label="Jabatan"><x-ui.input name="jabatan" data-edit="jabatan" /></x-ui.field>
                </div>
                <x-ui.field label="Role / hak akses" required>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($roles as $r)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary/5">
                                <input type="radio" name="role_code" value="{{ $r->code }}" required class="mt-1 accent-primary">
                                <span class="grid gap-0.5">
                                    <span class="text-sm font-medium">{{ $r->name }}</span>
                                    <span class="line-clamp-2 text-xs text-muted-foreground">{{ $r->description ?: $r->code }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Unit bisnis" hint="Kosong berarti akses global (pusat).">
                        <x-ui.select name="unit_id" data-edit="unit">
                            <option value="">Global / Pusat</option>
                            @foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->code }} · {{ $unit->name }}</option>@endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Site">
                        <x-ui.select name="default_site_id" data-edit="site">
                            <option value="">Tidak ditentukan</option>
                            @foreach ($sites as $site)<option value="{{ $site->id }}">{{ $site->code }} · {{ $site->name }}</option>@endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>
                <label class="flex items-center justify-between gap-4 rounded-lg border p-3">
                    <span class="grid">
                        <span class="text-sm font-medium">Akun aktif</span>
                        <span class="text-xs text-muted-foreground">Matikan untuk memblokir login tanpa menghapus akun.</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" data-edit="is_active" class="peer sr-only">
                    <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-input transition-colors after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-success peer-checked:after:translate-x-5 peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50"></span>
                </label>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" form="userEditForm"><i class="bi bi-check2"></i> Simpan perubahan</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>

        <x-ui.dialog id="userResetDialog" title="Reset password" size="sm" static>
            <form id="userResetForm" method="POST" class="grid gap-4" data-loading>
                @csrf
                <x-ui.alert variant="warning">Password baru untuk <strong data-reset="username"></strong>. Sampaikan ke pengguna secara langsung.</x-ui.alert>
                <x-ui.field label="Password baru" hint="Minimal 6 karakter." required>
                    <div class="relative"><x-ui.input type="password" name="new_password" minlength="6" required class="pr-10" /><button type="button" data-toggle-password class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
                </x-ui.field>
                <x-ui.field label="Konfirmasi password" required>
                    <div class="relative"><x-ui.input type="password" name="new_password_confirmation" minlength="6" required class="pr-10" /><button type="button" data-toggle-password class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" form="userResetForm"><i class="bi bi-key"></i> Reset password</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>

        <x-ui.dialog id="userCredentialsDialog" title="Kredensial akun" description="Untuk dibagikan ke pegawai secara offline. Setiap kali password dibuka, akses tercatat." size="sm" static>
            <form id="userCredentialsForm" method="POST" class="grid gap-4" data-loading>
                @csrf
                <x-ui.field label="Username"><x-ui.input data-credentials="username" readonly /></x-ui.field>
                <x-ui.field label="Password baru / ganti" hint="Kosongkan bila hanya ingin melihat password tersimpan.">
                    <div class="relative"><x-ui.input type="password" name="password" minlength="6" class="pr-10" /><button type="button" data-toggle-password class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
                </x-ui.field>
                <div data-credentials="revealed" hidden class="rounded-lg bg-success-soft p-3">
                    <span class="text-xs text-muted-foreground">Password tersimpan</span>
                    <span data-credentials="password" class="block font-mono text-lg font-semibold"></span>
                    <span data-credentials="count" class="text-xs text-muted-foreground"></span>
                </div>
                <x-ui.button type="button" variant="outline" size="sm" data-credentials="reveal"><i class="bi bi-eye"></i> Lihat password tersimpan</x-ui.button>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Tutup</x-ui.button>
                <x-ui.button type="submit" form="userCredentialsForm"><i class="bi bi-check2"></i> Simpan kredensial</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @vite('resources/js/pages/users/index.js')
</x-layouts.app>
