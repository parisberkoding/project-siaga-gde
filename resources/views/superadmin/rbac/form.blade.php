{{--
  Form role (buat/ubah). Field sama dengan versi lama: code, name, unit_id, description, is_active, permissions[].
  Pilih permission per grup dengan pencarian dan "pilih semua" per grup: resources/js/pages/rbac/form.js.
--}}
@php
    $editing = isset($role);
    $selected = collect(old('permissions', $rolePermissionIds ?? []))->map(fn ($id) => (int) $id)->flip();
    $total = $groups->sum(fn ($g) => $g->permissions->count());
@endphp

<x-layouts.app :title="$title" :breadcrumbs="['Daftar Role' => route('superadmin.rbac.index'), ($editing ? $role->name : 'Tambah Role') => null]">
    <x-ui.page-header kicker="Pengguna & Hak Akses" :title="$editing ? 'Atur Role ' . $role->name : 'Tambah Role'"
        description="Isi identitas role, lalu centang permission yang diberikan. Permission bersifat gabungan antar role.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('superadmin.rbac.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ $editing ? route('superadmin.rbac.update', $role->id) : route('superadmin.rbac.store') }}" method="POST" data-loading data-role-form
          class="grid items-start gap-6 lg:grid-cols-3">
        @csrf
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Identitas role" step="1" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Kode role" for="code" required error="code" hint="Huruf kecil, angka, garis bawah. Tidak bisa diubah setelah dibuat.">
                            <x-ui.input id="code" name="code" :value="old('code', $role->code ?? '')" placeholder="Contoh: bbm_officer" :readonly="$editing" required pattern="[a-z0-9_]+" />
                        </x-ui.field>
                        <x-ui.field label="Nama role" for="name" required error="name" hint="Huruf kecil semua; spasi otomatis menjadi underscore.">
                            <x-ui.input id="name" name="name" :value="old('name', $role->name ?? '')" placeholder="Contoh: bbm_officer" required />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Cakupan unit" for="unit_id" error="unit_id" hint="Global berlaku di semua unit. Pilih unit untuk role yang hanya bisa diberikan ke pengguna unit itu.">
                        <x-ui.select id="unit_id" name="unit_id">
                            <option value="">Global, semua unit</option>
                            @foreach ($units ?? [] as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $role->unit_id ?? '') === (string) $unit->id)>{{ $unit->code }} · {{ $unit->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Deskripsi" for="description">
                        <x-ui.textarea id="description" name="description" rows="2" placeholder="Jelaskan fungsi role ini">{{ old('description', $role->description ?? '') }}</x-ui.textarea>
                    </x-ui.field>
                    @if ($editing)
                        <label class="flex items-center justify-between gap-4 rounded-lg border p-3">
                            <span class="grid">
                                <span class="text-sm font-medium">Role aktif</span>
                                <span class="text-xs text-muted-foreground">Nonaktifkan untuk mencabut akses semua pengguna dengan role ini.</span>
                            </span>
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked(old('is_active', $role->is_active))>
                            <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-input transition-colors after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-success peer-checked:after:translate-x-5 peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50"></span>
                        </label>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-0 py-0">
                <div class="grid gap-3 border-b p-5">
                    <div class="flex items-center gap-2 font-semibold leading-none">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-linear-to-br from-orange-500 to-amber-500 text-xs font-bold text-white shadow-sm">2</span>
                        Permission
                        <span class="ml-auto text-sm font-normal text-muted-foreground"><span data-perm-count class="font-semibold text-foreground">0</span> dari {{ $total }} dipilih</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative min-w-56 flex-1">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                            <x-ui.input type="search" data-perm-search placeholder="Cari permission" class="pl-9" />
                        </div>
                        <x-ui.button type="button" size="sm" variant="outline" data-perm-all="1"><i class="bi bi-check2-all"></i> Pilih semua</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="ghost" data-perm-all="0">Kosongkan</x-ui.button>
                    </div>
                </div>

                <div class="grid divide-y">
                    @forelse ($groups as $group)
                        <details open data-perm-group class="group/perm">
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-3 hover:bg-muted/40 [&::-webkit-details-marker]:hidden">
                                <x-ui.checkbox data-group-toggle aria-label="Pilih semua {{ $group->name }}" />
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="bi {{ $group->icon ?? 'bi-shield' }}"></i></span>
                                <span class="flex-1 font-medium">{{ $group->name }}</span>
                                <span class="text-xs text-muted-foreground"><span data-group-count>0</span>/{{ $group->permissions->count() }}</span>
                                <i class="bi bi-chevron-down text-muted-foreground transition-transform group-open/perm:rotate-180"></i>
                            </summary>
                            <div class="grid gap-2 px-5 pb-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($group->permissions as $perm)
                                    <label data-perm-item data-search="{{ strtolower($perm->code . ' ' . $perm->description . ' ' . $perm->action) }}"
                                           class="flex cursor-pointer items-start gap-2.5 rounded-lg border p-3 transition-colors hover:border-primary/40 has-checked:border-success/50 has-checked:bg-success-soft">
                                        <x-ui.checkbox name="permissions[]" :value="$perm->id" :checked="isset($selected[$perm->id])" class="mt-0.5" />
                                        <span class="grid min-w-0 gap-0.5">
                                            <span class="text-sm font-medium">{{ $perm->action }}</span>
                                            @if ($perm->description)<span class="text-xs text-muted-foreground">{{ $perm->description }}</span>@endif
                                            <code class="truncate text-[11px] text-muted-foreground">{{ $perm->code }}</code>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <x-ui.empty-state icon="bi bi-key" title="Belum ada permission" class="py-10" />
                    @endforelse
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-dark px-4">
                    <i class="bi bi-save me-2"></i>
                    {{ isset($role) ? 'Simpan Perubahan' : 'Buat Role' }}
                </button>
                <a href="{{ route('superadmin.rbac.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Batal
                </a>
            </div>

        </form>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-question-circle me-2"></i>Tips
                </h6>
                <ul class="small mb-0" style="padding-left:16px;">
                    <li class="mb-2">Buat role spesifik sesuai fungsi, misal <strong>"bbm_officer"</strong> yang hanya bisa request BBM.</li>
                    <li class="mb-2">Role <strong>"view_only"</strong> hanya perlu permission <code>*.view</code>.</li>
                    <li class="mb-2">Role sistem tidak bisa diedit atau dihapus.</li>
                    <li>User bisa punya lebih dari satu role.</li>
                </ul>
            </div>
        </div>

        <aside class="grid gap-4 lg:sticky lg:top-20">
            <x-ui.card class="overflow-hidden py-0">
                <div class="bg-brand-gradient px-5 py-4 text-white">
                    <span class="text-xs uppercase tracking-wider text-white/75">Ringkasan</span>
                    <p class="mt-1 text-3xl font-semibold tabular-nums"><span data-perm-count>0</span><span class="text-base font-normal text-white/75"> permission</span></p>
                </div>
                <x-ui.card-content class="grid gap-3 py-4">
                    <ul data-perm-summary class="grid gap-1.5 text-sm"></ul>
                    <x-ui.button type="submit" class="w-full"><i class="bi bi-check2"></i> {{ $editing ? 'Simpan perubahan' : 'Buat role' }}</x-ui.button>
                    <x-ui.button variant="outline" class="w-full" :href="route('superadmin.rbac.index')">Batal</x-ui.button>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.alert variant="info" title="Tips">
                <ul class="list-inside list-disc text-sm">
                    <li>Buat role sesuai fungsi, misalnya bbm_officer yang hanya mengajukan BBM.</li>
                    <li>Role baca saja cukup permission berakhiran .view.</li>
                    <li>Role sistem tidak bisa diubah atau dihapus.</li>
                </ul>
            </x-ui.alert>
        </aside>
    </form>

    @vite('resources/js/pages/rbac/form.js')
</x-layouts.app>
