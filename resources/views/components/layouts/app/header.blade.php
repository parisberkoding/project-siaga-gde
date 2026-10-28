{{-- Header: tombol sidebar (mobile), breadcrumb, menu pengguna. --}}
@props(['breadcrumbs' => []])

@php
    $user = Auth::user();
    $initials = collect(explode(' ', trim($user->nama_lengkap ?? $user->username)))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $menuItem = 'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground [&>i]:text-muted-foreground';
@endphp

<header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b bg-card/95 px-4 backdrop-blur md:px-6">
    <x-ui.button variant="ghost" size="icon-sm" data-sidebar-toggle class="-ml-1 lg:hidden" aria-label="Buka menu">
        <i class="bi bi-list text-lg"></i>
    </x-ui.button>
    <div class="mr-1 h-4 w-px bg-border lg:hidden"></div>

    @if ($breadcrumbs)
        <nav aria-label="breadcrumb" class="min-w-0">
            <ol class="flex items-center gap-1.5 text-sm text-muted-foreground">
                @foreach ($breadcrumbs as $label => $url)
                    @if (! $loop->first)
                        <li aria-hidden="true" class="text-xs"><i class="bi bi-chevron-right"></i></li>
                    @endif
                    <li @class(['truncate', 'hidden sm:block' => ! $loop->last])>
                        @if ($url && ! $loop->last)
                            <a href="{{ $url }}" class="transition-colors hover:text-foreground">{{ $label }}</a>
                        @else
                            <span @if ($loop->last) aria-current="page" class="text-foreground" @endif>{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <details data-menu class="relative ml-auto">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md p-1 pr-2 hover:bg-accent [&::-webkit-details-marker]:hidden">
            <span class="flex size-8 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">{{ $initials }}</span>
            <span class="hidden max-w-48 truncate text-sm font-medium md:block">{{ $user->nama_lengkap }}</span>
            <i class="bi bi-chevron-down hidden text-xs text-muted-foreground md:block"></i>
        </summary>

        <div class="absolute right-0 z-50 mt-2 w-60 rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
            <div class="px-2 py-1.5">
                <p class="truncate text-sm font-medium">{{ $user->nama_lengkap }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ ucfirst(strtolower($user->jabatan ?? '')) ?: $user->username }}</p>
            </div>
            <div class="-mx-1 my-1 h-px bg-border"></div>
            <a href="{{ route('modules.index') }}" class="{{ $menuItem }}"><i class="bi bi-grid-1x2"></i> All Modules</a>
            <a href="{{ route('profile') }}" class="{{ $menuItem }}"><i class="bi bi-person"></i> Profil Saya</a>
            <div class="-mx-1 my-1 h-px bg-border"></div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="{{ $menuItem }} text-destructive [&>i]:text-destructive"><i class="bi bi-box-arrow-right"></i> Keluar</button>
            </form>
        </div>
    </details>
</header>
