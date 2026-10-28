{{-- Sidebar aplikasi (struktur shadcn Sidebar). Isi menu dari partials/sidebar-menu. --}}
@php($module = app(\App\Services\ModuleContext::class)->current())

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform lg:translate-x-0 [[data-sidebar-open]_&]:translate-x-0">
    <div data-slot="sidebar-header" class="flex h-14 items-center gap-2 border-b border-sidebar-border px-4">
        <a href="{{ route('modules.index') }}" class="flex items-center gap-2">
            <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="Geodipa" class="size-8 object-contain">
            <span class="grid leading-tight">
                <span class="font-semibold text-foreground">SIAGA</span>
                <span class="text-xs text-muted-foreground">PT Geo Dipa Energi</span>
            </span>
        </a>
    </div>

    @if ($module)
        <div class="border-b border-sidebar-border p-3">
            <div class="flex items-center gap-3 rounded-lg bg-muted/60 p-2">
                <span @class([
                    'flex size-9 shrink-0 items-center justify-center rounded-md text-white',
                    'bg-module-bbm' => $module->code === 'bbm',
                    'bg-module-spd' => $module->code === 'spd',
                    'bg-primary' => ! in_array($module->code, ['bbm', 'spd'], true),
                ])>
                    <i class="{{ $module->icon ?: 'bi bi-grid' }}"></i>
                </span>
                <span class="grid min-w-0 leading-tight">
                    <span class="text-xs text-muted-foreground">Modul aktif</span>
                    <span class="truncate text-sm font-medium text-foreground">{{ $module->name }}</span>
                </span>
            </div>
            <a href="{{ route('modules.index') }}" class="mt-2 flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <i class="bi bi-grid-1x2"></i> Ganti modul
            </a>
        </div>
    @endif

    <div data-slot="sidebar-content" class="flex-1 overflow-y-auto">
        @include('partials.sidebar-menu', ['menuView' => 'components.layouts.app.sidebar-nav'])
    </div>
</aside>
