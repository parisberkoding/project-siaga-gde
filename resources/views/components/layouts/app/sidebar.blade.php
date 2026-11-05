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
        {{-- Identitas modul aktif + jalan kembali ke halaman Semua Modul (tanpa pemilih modul di sidebar:
             pindah modul selalu lewat halaman modul supaya user sadar konteksnya berganti). --}}
        <div class="px-3 pb-2">
            <div class="overflow-hidden rounded-lg bg-white/8 ring-1 ring-white/10">
                <div class="flex items-center gap-3 p-2">
                    <span @class(['flex size-9 shrink-0 items-center justify-center rounded-md text-white shadow-sm', match ($module->code) { 'bbm' => 'bg-module-bbm', 'spd' => 'bg-module-spd', default => 'bg-white/15' }])>
                        <i class="{{ $module->icon ?: 'bi bi-grid' }}"></i>
                    </span>
                    <span class="grid min-w-0 flex-1 leading-tight">
                        <span class="text-[11px] uppercase tracking-wider text-sidebar-muted-foreground">Modul aktif</span>
                        <span class="line-clamp-2 text-sm font-semibold text-white">{{ $module->name }}</span>
                    </span>
                </div>
                <a href="{{ route('modules.index') }}"
                   class="flex items-center gap-2 border-t border-white/10 px-3 py-2.5 text-sm font-medium text-sidebar-muted-foreground outline-none transition-colors hover:bg-white/10 hover:text-white focus-visible:bg-white/10 focus-visible:text-white">
                    <i class="bi bi-arrow-left"></i> Kembali ke Semua Modul
                </a>
            </div>
        </div>
    @endif

    <div data-slot="sidebar-content" class="flex-1 overflow-y-auto [scrollbar-color:rgb(255_255_255/0.2)_transparent]">
        @include('partials.sidebar-menu', ['menuView' => 'components.layouts.app.sidebar-nav'])
    </div>

    {{-- Akun di kaki sidebar (seperti T-Cash): profil dan keluar. Pindah modul lewat kartu modul di atas. --}}
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
