{{--
  Tambah Pengguna. Field sama dengan versi lama (users.store): mode (manual | activate), employee_default_id,
  nama_lengkap, jabatan, default_site_id, unit_id, create_employee, username, password, email, role_code.
  Versi lama menaruh pilihan mode di luar <form> sehingga mode aktivasi tidak pernah terkirim; di sini sudah di dalam.
  Interaksi (mode, pratinjau, buat password): resources/js/pages/users/create.js.
--}}
@php
    $mode = old('mode', 'manual');
    $roleIcons = ['superadmin' => 'bi-shield-lock', 'developers' => 'bi-code-slash', 'general_affairs' => 'bi-briefcase', 'manager' => 'bi-person-badge',
        'dispatcher' => 'bi-truck', 'requester' => 'bi-car-front', 'pegawai' => 'bi-person', 'pengguna_spd' => 'bi-person-walking',
        'view_only' => 'bi-eye', 'vendor' => 'bi-shop'];
@endphp

<x-layouts.app title="Tambah Pengguna" :breadcrumbs="['Daftar Pengguna' => route('users', ['tab' => 'list']), 'Tambah' => null]">
    <x-ui.page-header kicker="Pengguna & Hak Akses" title="Tambah Pengguna"
        description="Buat akun baru dari biodata baru, atau aktifkan pekerja yang sudah ada di Master Pekerja." />

    <form action="{{ route('users.store') }}" method="POST" data-loading data-user-create class="grid items-start gap-6 lg:grid-cols-3">
        @csrf
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Cara membuat akun" step="1" />
                <x-ui.card-content class="grid gap-3 sm:grid-cols-2">
                    @foreach (['manual' => ['bi-pencil-square', 'Input sendiri', 'Isi biodata pekerja baru sekaligus akun login.'], 'activate' => ['bi-person-check', 'Aktivasi pekerja', 'Pilih dari Master Pekerja yang belum punya akun.']] as $value => [$icon, $label, $hint])
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary/5 has-checked:ring-1 has-checked:ring-primary/30">
                            <input type="radio" name="mode" value="{{ $value }}" @checked($mode === $value) class="sr-only" data-mode>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-linear-to-br {{ $value === 'manual' ? 'from-blue-600 to-indigo-600' : 'from-emerald-500 to-teal-600' }} text-white shadow-sm"><i class="bi {{ $icon }}"></i></span>
                            <span class="grid gap-0.5"><span class="font-medium">{{ $label }}</span><span class="text-sm text-muted-foreground">{{ $hint }}</span></span>
                        </label>
                    @endforeach
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Data pekerja" step="2" />
                <x-ui.card-content class="grid gap-4">
                    <div data-mode-section="activate" @if ($mode !== 'activate') hidden @endif class="grid gap-3">
                        <x-ui.field label="Pekerja dari Master Pekerja" for="employeeSelect" required error="employee_default_id" hint="Hanya pekerja aktif yang belum punya akun.">
                            <x-ui.select id="employeeSelect" name="employee_default_id">
                                <option value="">Pilih pekerja</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" data-name="{{ $emp->employee_name }}" data-position="{{ $emp->position }}" data-site-name="{{ $emp->defaultSite?->name }}" @selected(old('employee_default_id') == $emp->id)>
                                        {{ $emp->employee_name }} · {{ $emp->position ?? 'Tanpa jabatan' }} ({{ $emp->defaultSite?->name ?? 'Tanpa site' }})
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <dl class="grid gap-3 rounded-lg bg-muted/40 p-4 text-sm sm:grid-cols-3">
                            <div><dt class="text-xs text-muted-foreground">Nama</dt><dd data-employee="name" class="font-medium">-</dd></div>
                            <div><dt class="text-xs text-muted-foreground">Jabatan</dt><dd data-employee="position" class="font-medium">-</dd></div>
                            <div><dt class="text-xs text-muted-foreground">Site</dt><dd data-employee="site" class="font-medium">-</dd></div>
                        </dl>
                    </div>

                    <div data-mode-section="manual" @if ($mode === 'activate') hidden @endif class="grid gap-4">
                        <x-ui.field label="Nama lengkap" for="namaLengkap" required error="nama_lengkap">
                            <x-ui.input id="namaLengkap" name="nama_lengkap" :value="old('nama_lengkap')" placeholder="Nama lengkap sesuai identitas" data-preview="name" />
                        </x-ui.field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.field label="Jabatan" for="jabatan" error="jabatan"><x-ui.input id="jabatan" name="jabatan" :value="old('jabatan')" placeholder="Contoh: Staff IT, Driver" /></x-ui.field>
                            <x-ui.field label="Site" for="site" error="default_site_id">
                                <x-ui.select id="site" name="default_site_id">
                                    <option value="">Otomatis sesuai role</option>
                                    @foreach ($sites as $site)<option value="{{ $site->id }}" @selected(old('default_site_id') == $site->id)>{{ $site->code }} · {{ $site->name }}</option>@endforeach
                                </x-ui.select>
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Unit bisnis" for="unit" error="unit_id" hint="Data pengguna dibatasi ke unit yang dipilih. Kosongkan untuk akses global (pusat).">
                            <x-ui.select id="unit" name="unit_id" data-preview="unit">
                                <option value="">Global / Pusat</option>
                                @foreach ($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->code }} · {{ $unit->name }}</option>@endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <label class="flex items-start gap-3 rounded-lg border p-3">
                            <x-ui.checkbox name="create_employee" value="1" :checked="(bool) old('create_employee')" class="mt-0.5" />
                            <span class="grid"><span class="text-sm font-medium">Daftarkan juga ke Master Pekerja</span><span class="text-xs text-muted-foreground">Supaya muncul di daftar pekerja SPD dan Lunch.</span></span>
                        </label>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Akun login" step="3" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Username" for="username" required error="username">
                            <x-ui.input id="username" name="username" :value="old('username')" placeholder="Contoh: john.doe" required autocomplete="off" data-preview="username" />
                        </x-ui.field>
                        <x-ui.field label="Email" for="email" required error="email">
                            <x-ui.input type="email" id="email" name="email" :value="old('email')" placeholder="email@perusahaan.com" required />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Password" for="password" required error="password" hint="Minimal 6 karakter. Bisa direset kapan saja dari daftar pengguna.">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <x-ui.input type="password" id="password" name="password" minlength="6" required autocomplete="new-password" class="pr-10" />
                                <button type="button" data-toggle-password class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                            </div>
                            <x-ui.button type="button" variant="outline" data-generate-password><i class="bi bi-magic"></i> Buatkan</x-ui.button>
                        </div>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Role / hak akses" step="4" />
                <x-ui.card-content class="grid gap-2 sm:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary/5">
                            <input type="radio" name="role_code" value="{{ $role->code }}" required @checked(old('role_code') === $role->code) class="sr-only" data-preview="role" data-label="{{ $role->name }}">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="bi {{ $roleIcons[$role->code] ?? 'bi-shield' }}"></i></span>
                            <span class="grid gap-0.5"><span class="text-sm font-medium">{{ $role->name }}</span><span class="line-clamp-2 text-xs text-muted-foreground">{{ $role->description ?: $role->code }}</span></span>
                        </label>
                    @endforeach
                    @error('role_code')<p class="text-xs text-destructive sm:col-span-2">{{ $message }}</p>@enderror
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <aside class="grid gap-4 lg:sticky lg:top-20">
            <x-ui.card class="overflow-hidden py-0">
                <div class="bg-brand-gradient px-5 py-5 text-white">
                    <span class="text-xs uppercase tracking-wider text-white/75">Pratinjau akun</span>
                    <div class="mt-3 flex items-center gap-3">
                        <span data-preview-out="initials" class="flex size-12 items-center justify-center rounded-full bg-white/20 text-lg font-semibold ring-2 ring-white/30">?</span>
                        <span class="grid min-w-0 leading-tight">
                            <span data-preview-out="name" class="truncate text-lg font-semibold">Nama pengguna</span>
                            <span data-preview-out="username" class="truncate text-sm text-white/75">@username</span>
                        </span>
                    </div>
                </div>
                <x-ui.card-content class="grid gap-3 py-4 text-sm">
                    <div class="flex justify-between gap-2"><span class="text-muted-foreground">Role</span><span data-preview-out="role" class="font-medium">Belum dipilih</span></div>
                    <div class="flex justify-between gap-2"><span class="text-muted-foreground">Unit</span><span data-preview-out="unit" class="font-medium">Global / Pusat</span></div>
                    <x-ui.button type="submit" class="mt-1 w-full"><i class="bi bi-person-plus"></i> Simpan pengguna</x-ui.button>
                    <x-ui.button variant="outline" class="w-full" :href="route('users', ['tab' => 'list'])">Batal</x-ui.button>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.alert variant="info" title="Dua cara membuat akun">
                Input sendiri untuk pekerja baru. Aktivasi pekerja untuk pegawai yang sudah ada di Master Pekerja, nama, jabatan, dan site-nya ikut otomatis.
            </x-ui.alert>
        </aside>
    </form>

    @vite('resources/js/pages/users/create.js')
</x-layouts.app>
