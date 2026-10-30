{{--
  All Modules: "rumah" lintas modul. Tanpa sidebar supaya user memilih modul secara sadar lebih dulu;
  sidebar & menu mengikuti modul yang dipilih.
--}}
@php
    $initials = collect(explode(' ', trim($user->nama_lengkap ?? $user->username)))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $menuItem = 'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground [&>i]:text-muted-foreground';

    // Warna aksen per modul (dipakai lewat variabel --tone); modul baru jatuh ke warna primary.
    $tones = [
        'bbm' => '#ff771d', 'spd' => '#4154f1', 'vehicle' => '#0f9f6e', 'lunch' => '#d97706',
        'ai' => '#8b5cf6', 'users' => '#0284c7', 'rbac' => '#e11d48', 'settings' => '#475569',
    ];

    // Deskripsi modul berbentuk "Siklus X: a -> b -> c": tampilkan sebagai alur langkah.
    $flow = function (?string $description) {
        if (! $description || ! str_contains($description, '->')) {
            return null;
        }
        $steps = str_contains($description, ':') ? \Illuminate\Support\Str::after($description, ':') : $description;

        return array_map(fn ($step) => rtrim(trim($step), '.'), explode('->', $steps));
    };
@endphp

<x-layouts.app title="All Modules" :sidebar="false">
    <x-slot:topbar>
        <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b bg-card/90 px-4 backdrop-blur md:px-6 lg:top-2 lg:rounded-t-xl">
            <a href="{{ route('modules.index') }}" class="flex items-center gap-2.5 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                <span class="flex size-8 items-center justify-center rounded-lg border bg-white shadow-xs">
                    <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="" class="size-6 object-contain">
                </span>
                <span class="grid leading-tight">
                    <span class="text-sm font-bold tracking-tight">SIAGA</span>
                    <span class="text-[10px] uppercase tracking-[0.12em] text-muted-foreground">PT Geo Dipa Energi</span>
                </span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <x-layouts.app.notifications />
                <span class="hidden items-center gap-1.5 rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground md:inline-flex">
                    <i class="bi bi-calendar3"></i> {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                </span>
                <details data-menu class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full p-0.5 pr-2 outline-none hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/50 [&::-webkit-details-marker]:hidden" aria-label="Menu akun">
                        <span class="flex size-7 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">{{ $initials }}</span>
                        <i class="bi bi-chevron-down text-xs text-muted-foreground"></i>
                    </summary>
                    <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-md border bg-popover p-1 text-popover-foreground shadow-lg">
                        <div class="grid px-2 py-1.5 leading-tight">
                            <span class="truncate text-sm font-medium">{{ $user->nama_lengkap }}</span>
                            <span class="truncate text-xs text-muted-foreground">{{ $user->email ?? $user->username }}</span>
                        </div>
                        <div class="-mx-1 my-1 h-px bg-border"></div>
                        <a href="{{ route('profile') }}" class="{{ $menuItem }}"><i class="bi bi-person"></i> Profil Saya</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="{{ $menuItem }} text-destructive [&>i]:text-destructive"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
    </x-slot:topbar>

    {{-- Sapaan + konteks akun --}}
    <section class="bg-sidebar-gradient ornament-corner relative overflow-hidden rounded-xl px-6 py-7 text-white shadow-lg shadow-primary/20 md:px-8" style="--ornament: #fff; --ornament-dot: #fff">
        <div class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
            <div class="grid gap-2">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/20">
                    <i class="bi bi-grid-1x2"></i> Pusat Kendali
                </span>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ $greeting }}, {{ \Illuminate\Support\Str::of($user->nama_lengkap ?? $user->username)->title() }}</h1>
                <p class="max-w-2xl text-sm text-white/80">Pilih satu modul untuk mulai bekerja. Sidebar dan menu akan mengikuti modul yang Anda pilih.</p>
            </div>
            <dl class="grid grid-cols-3 gap-2 sm:w-fit">
                @foreach ([['bi-building', 'Unit', $unit?->name ?? 'Belum terdaftar'], ['bi-shield-check', 'Hak akses', $isSuperadmin ? 'Superadmin' : 'Unit'], ['bi-grid', 'Modul', $modules->count() . ' tersedia']] as [$icon, $label, $value])
                    <div class="grid min-w-0 gap-0.5 rounded-lg bg-white/10 px-3 py-2 ring-1 ring-white/15 backdrop-blur-sm sm:min-w-32">
                        <dt class="flex items-center gap-1.5 text-[11px] uppercase tracking-wider text-white/70"><i class="bi {{ $icon }}"></i> {{ $label }}</dt>
                        <dd class="truncate text-sm font-semibold" title="{{ $value }}">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if ($modules->isEmpty())
        <x-ui.empty-state icon="bi bi-inboxes" title="Belum ada modul yang bisa dibuka" class="bg-card"
            description="Akun Anda belum memiliki permission pada modul mana pun, atau semua modul untuk unit Anda sedang dinonaktifkan. Hubungi Superadmin Pusat untuk penyesuaian hak akses.">
            <x-ui.button :href="route('profile')" variant="outline"><i class="bi bi-person-circle"></i> Buka Profil</x-ui.button>
        </x-ui.empty-state>
    @else
        <section class="grid gap-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">Pilih modul</h2>
                    <p class="text-sm text-muted-foreground">Anda bekerja di dalam satu modul pada satu waktu. Kembali ke halaman ini untuk berpindah modul.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($modules as $module)
                    @php
                        $tone = $tones[$module->code] ?? 'var(--primary)';
                        $steps = $flow($module->description);
                    @endphp
                    <form method="POST" action="{{ route('modules.activate', $module) }}" class="flex">
                        @csrf
                        <button type="submit" style="--tone: {{ $tone }}; --ornament: {{ $tone }}"
                            class="ornament-corner group/module relative flex w-full flex-col gap-4 overflow-hidden rounded-xl border bg-card p-5 text-left shadow-xs outline-none transition-[box-shadow,border-color,translate] hover:border-(--tone)/40 hover:shadow-lg focus-visible:ring-[3px] focus-visible:ring-ring/50 motion-safe:hover:-translate-y-0.5">
                            <span class="absolute inset-x-0 top-0 h-1 bg-(--tone) opacity-80" aria-hidden="true"></span>

                            <span class="flex items-start justify-between gap-3">
                                <span class="flex size-11 items-center justify-center rounded-xl bg-(--tone) text-lg text-white shadow-md shadow-(--tone)/30">
                                    <i class="{{ $module->icon ?: 'bi bi-grid' }}"></i>
                                </span>
                                @if ($module->is_always_on)
                                    <x-ui.badge variant="muted"><i class="bi bi-infinity"></i> Selalu aktif</x-ui.badge>
                                @endif
                            </span>

                            <span class="grid gap-1.5">
                                <span class="font-semibold leading-snug">{{ $module->name }}</span>
                                @if ($steps)
                                    <span class="flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                                        @foreach ($steps as $step)
                                            <span class="rounded bg-muted px-1.5 py-0.5">{{ $step }}</span>
                                            @unless ($loop->last)<i class="bi bi-chevron-right text-[9px] opacity-60"></i>@endunless
                                        @endforeach
                                    </span>
                                @elseif ($module->description)
                                    <span class="text-sm text-muted-foreground">{{ $module->description }}</span>
                                @endif
                            </span>

                            <span class="mt-auto flex items-center justify-between border-t pt-3 text-sm font-medium text-(--tone)">
                                Buka modul
                                <span class="flex size-7 items-center justify-center rounded-full bg-(--tone)/10 transition-transform group-hover/module:translate-x-0.5">
                                    <i class="bi bi-arrow-right"></i>
                                </span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <p class="flex items-center justify-center gap-1.5 text-center text-xs text-muted-foreground">
        <i class="bi bi-shield-check"></i>
        Modul yang tampil sudah disaring berdasarkan permission role Anda dan modul yang diaktifkan untuk unit {{ $unit?->name ?? 'Anda' }}.
    </p>
</x-layouts.app>
