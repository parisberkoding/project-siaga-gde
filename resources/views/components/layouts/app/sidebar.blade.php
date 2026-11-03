{{-- Sidebar aplikasi (struktur shadcn Sidebar, variant inset). Isi menu dari partials/sidebar-menu. --}}
@php
    $module = app(\App\Services\ModuleContext::class)->current();
    $user = Auth::user();
    $initials = collect(explode(' ', trim($user->nama_lengkap ?? $user->username)))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $menuItem = 'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground [&>i]:text-muted-foreground';
@endphp

<aside class="bg-sidebar-gradient fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col text-sidebar-foreground transition-transform lg:translate-x-0 lg:bg-none [[data-sidebar-open]_&]:translate-x-0 lg:[[data-sidebar-collapsed]_&]:-translate-x-full">
    <div data-slot="sidebar-header" class="flex h-16 shrink-0 items-center px-4">
        <a href="{{ route('modules.index') }}" class="flex items-center gap-2.5 rounded-lg p-1 outline-none focus-visible:ring-2 focus-visible:ring-white/60">
            <span class="flex size-9 items-center justify-center rounded-lg bg-white shadow-sm">
                <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="" class="size-7 object-contain">
            </span>
            <span class="grid leading-tight">
                <span class="text-base font-bold tracking-tight text-white">SIAGA</span>
                <span class="text-[11px] uppercase tracking-[0.12em] text-sidebar-muted-foreground">PT Geo Dipa Energi</span>
            </span>
        </a>
    </div>

    @if ($module)
        {{-- Seluruh kartu modul aktif bisa diklik: membuka daftar modul untuk pindah langsung. --}}
        @php
            $modules = app(\App\Services\ModuleContext::class)->availableModules($user);
            $moduleTile = fn ($code) => match ($code) {
                'bbm' => 'bg-module-bbm',
                'spd' => 'bg-module-spd',
                default => 'bg-white/15',
            };
        @endphp
        <div class="px-3 pb-2">
            <button type="button" popovertarget="module-switcher" aria-haspopup="menu"
                    class="group flex w-full cursor-pointer items-center gap-3 rounded-lg bg-white/8 p-2 text-left ring-1 ring-white/10 outline-none transition-colors hover:bg-white/14 hover:ring-white/25 focus-visible:ring-2 focus-visible:ring-white/60">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-md text-white shadow-sm {{ $moduleTile($module->code) }}">
                    <i class="{{ $module->icon ?: 'bi bi-grid' }}"></i>
                </span>
                <span class="grid min-w-0 flex-1 leading-tight">
                    <span class="text-[11px] uppercase tracking-wider text-sidebar-muted-foreground">Modul aktif</span>
                    <span class="line-clamp-2 text-sm font-semibold text-white">{{ $module->name }}</span>
                </span>
                <span class="flex size-7 shrink-0 items-center justify-center rounded-md text-sidebar-muted-foreground transition-colors group-hover:bg-white/15 group-hover:text-white">
                    <i class="bi bi-chevron-expand"></i>
                </span>
            </button>

            <div id="module-switcher" popover data-dropdown data-align="start" role="menu" aria-label="Pindah modul"
                 class="m-0 w-72 rounded-lg border bg-popover p-1.5 text-popover-foreground shadow-lg">
                <p class="px-2 pb-1.5 pt-1 text-xs font-medium text-muted-foreground">Pindah modul</p>
                @foreach ($modules as $item)
                    @php $isCurrent = $item->is($module); @endphp
                    <form action="{{ route('modules.activate', $item->code) }}" method="POST">
                        @csrf
                        <button type="submit" role="menuitem" @if ($isCurrent) aria-current="page" @endif
                                @class(['flex w-full items-center gap-3 rounded-md p-2 text-left outline-none transition-colors hover:bg-accent focus-visible:bg-accent', 'bg-accent' => $isCurrent])>
                            <span @class(['flex size-8 shrink-0 items-center justify-center rounded-md text-white shadow-sm', $moduleTile($item->code) === 'bg-white/15' ? 'bg-primary' : $moduleTile($item->code)])>
                                <i class="{{ $item->icon ?: 'bi bi-grid' }}"></i>
                            </span>
                            <span class="grid min-w-0 flex-1 leading-tight">
                                <span class="truncate text-sm font-medium">{{ $item->name }}</span>
                                @if ($item->description)
                                    <span class="truncate text-xs text-muted-foreground" title="{{ $item->description }}">{{ $item->description }}</span>
                                @endif
                            </span>
                            @if ($isCurrent)<i class="bi bi-check2 text-primary"></i>@endif
                        </button>
                    </form>
                @endforeach
                <div class="my-1 h-px bg-border"></div>
                <a href="{{ route('modules.index') }}" role="menuitem" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none hover:bg-accent focus-visible:bg-accent">
                    <i class="bi bi-grid text-muted-foreground"></i> Lihat semua modul
                </a>
            </div>
        </div>
    @endif

    <div data-slot="sidebar-content" class="flex-1 overflow-y-auto [scrollbar-color:rgb(255_255_255/0.2)_transparent]">
        @include('partials.sidebar-menu', ['menuView' => 'components.layouts.app.sidebar-nav'])
    </div>

    {{-- Akun di kaki sidebar (seperti T-Cash): profil, ganti modul, keluar. --}}
    <div data-slot="sidebar-footer" class="border-t border-sidebar-border p-2">
        <details data-menu class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-lg p-2 outline-none hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-white/60 [&::-webkit-details-marker]:hidden">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-primary">{{ $initials }}</span>
                <span class="grid min-w-0 flex-1 leading-tight">
                    <span class="truncate text-sm font-medium text-white">{{ $user->nama_lengkap }}</span>
                    <span class="truncate text-xs text-sidebar-muted-foreground">{{ ucfirst(strtolower($user->jabatan ?? '')) ?: $user->username }}</span>
                </span>
                <i class="bi bi-chevron-expand text-sidebar-muted-foreground"></i>
            </summary>

            <div class="absolute inset-x-0 bottom-full z-50 mb-2 rounded-md border bg-popover p-1 text-popover-foreground shadow-lg">
                <a href="{{ route('modules.index') }}" class="{{ $menuItem }}"><i class="bi bi-grid-1x2"></i> All Modules</a>
                <a href="{{ route('profile') }}" class="{{ $menuItem }}"><i class="bi bi-person"></i> Profil Saya</a>
                <div class="-mx-1 my-1 h-px bg-border"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="{{ $menuItem }} text-destructive [&>i]:text-destructive"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                </form>
            </div>
        </details>
    </div>
</aside>
