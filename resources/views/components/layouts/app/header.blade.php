{{-- Header di dalam panel konten: tombol sidebar, breadcrumb, tanggal hari ini. Menu akun ada di kaki sidebar. --}}
@props(['breadcrumbs' => []])

@php
    // Crumb pertama selalu modul aktif, menuju dashboard modul (seperti "Home" di T-Cash).
    $module = app(\App\Services\ModuleContext::class)->current();
    if ($module && ! array_key_exists($module->name, $breadcrumbs)) {
        $dashboard = 'modules.dashboard.' . $module->code;
        $breadcrumbs = [$module->name => \Illuminate\Support\Facades\Route::has($dashboard) ? route($dashboard) : null] + $breadcrumbs;
    }
@endphp

<header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b bg-card/90 px-4 backdrop-blur md:px-6 lg:top-2 lg:rounded-t-xl">
    <x-ui.button variant="ghost" size="icon-sm" data-sidebar-toggle class="-ml-1" aria-label="Buka atau tutup menu samping">
        <i class="bi bi-layout-sidebar text-base"></i>
    </x-ui.button>
    <div class="mr-1 h-4 w-px bg-border"></div>

    {{-- Akses cepat ke pilihan modul saat sidebar tidak terlihat (mobile, atau sidebar disembunyikan di desktop). --}}
    <a href="{{ route('modules.index') }}" title="Pilih modul lain"
       class="group/module inline-flex h-8 shrink-0 items-center gap-2 rounded-md border bg-card pl-1 pr-2.5 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground lg:hidden lg:[[data-sidebar-collapsed]_&]:inline-flex">
        <span @class([
            'flex size-6 items-center justify-center rounded text-xs text-white',
            'bg-module-bbm' => $module?->code === 'bbm',
            'bg-module-spd' => $module?->code === 'spd',
            'bg-primary' => ! in_array($module?->code, ['bbm', 'spd'], true),
        ])><i class="{{ $module?->icon ?: 'bi bi-grid' }}"></i></span>
        <span class="hidden sm:inline">Modul</span>
        <i class="bi bi-grid-3x3-gap text-muted-foreground group-hover/module:text-foreground"></i>
    </a>
    <div class="mr-1 h-4 w-px bg-border lg:hidden lg:[[data-sidebar-collapsed]_&]:block"></div>

    @if ($breadcrumbs)
        <nav aria-label="breadcrumb" class="min-w-0">
            <ol class="flex items-center gap-1.5 text-sm text-muted-foreground">
                @foreach ($breadcrumbs as $label => $url)
                    {{-- HP: hanya crumb terakhir tampil, jadi pemisah crumb tengah ikut disembunyikan. --}}
                    @if (! $loop->first)
                        <li aria-hidden="true" @class(['text-xs', 'hidden sm:block' => ! $loop->last])><i class="bi bi-chevron-right"></i></li>
                    @endif
                    <li @class(['truncate', 'hidden sm:block' => ! $loop->last])>
                        @if ($url && ! $loop->last)
                            <a href="{{ $url }}" class="transition-colors hover:text-foreground">{{ $label }}</a>
                        @else
                            <span @if ($loop->last) aria-current="page" class="font-medium text-foreground" @endif>{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="ml-auto flex items-center gap-2">
        <x-layouts.app.notifications />
        <span class="hidden items-center gap-1.5 rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground md:inline-flex">
            <i class="bi bi-calendar3"></i> {{ now()->locale('id')->translatedFormat('l, d F Y') }}
        </span>
    </div>
</header>
