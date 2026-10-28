{{-- Renderer menu untuk layout baru (dipanggil dari partials/sidebar-menu). --}}
@php
    $menuButton = 'flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm outline-none transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/50 [&>i:first-child]:text-base';
    $activeButton = 'bg-sidebar-accent font-medium text-sidebar-accent-foreground';
    $badge = fn ($item) => ! empty($item['badge']) ? $item['badge']() : 0;
    $url = fn ($item) => ! empty($item['params']) ? route($item['route'], $item['params']) : route($item['route']);
@endphp

<nav data-slot="sidebar-group" class="flex flex-col gap-1 p-3">
    <p class="px-2 pb-1 text-xs font-medium text-muted-foreground">Menu</p>

    <ul data-slot="sidebar-menu" class="flex flex-col gap-1">
        @foreach ($menuDef as $item)
            @if (! empty($item['children']))
                @php
                    $kids = array_values(array_filter($item['children'], $showItem));
                @endphp
                @continue(empty($kids))

                <li>
                    <details class="group/collapsible" @if ($routeIsAny($item['active'])) open @endif>
                        <summary class="{{ $menuButton }} cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                            <i class="{{ $item['icon'] }}"></i>
                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                            <i class="bi bi-chevron-right text-xs transition-transform group-open/collapsible:rotate-90"></i>
                        </summary>
                        <ul data-slot="sidebar-menu-sub" class="mx-3.5 mt-1 flex flex-col gap-1 border-l border-sidebar-border py-0.5 pl-2.5">
                            @foreach ($kids as $kid)
                                <li>
                                    <a href="{{ $url($kid) }}" @class([$menuButton, 'py-1.5', $activeButton => $childIsActive($kid)])>
                                        <span class="flex-1 truncate">{{ $kid['label'] }}</span>
                                        @if ($count = $badge($kid))
                                            <x-ui.badge variant="destructive" class="rounded-full">{{ $count }}</x-ui.badge>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                </li>
            @elseif ($showItem($item))
                <li>
                    <a href="{{ $url($item) }}" @class([$menuButton, $activeButton => $routeIsAny($item['active'])])>
                        <i class="{{ $item['icon'] }}"></i>
                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        @if ($count = $badge($item))
                            <x-ui.badge variant="destructive" class="rounded-full">{{ $count }}</x-ui.badge>
                        @endif
                    </a>
                </li>
            @endif
        @endforeach

        <li>
            <a href="{{ route('profile') }}" @class([$menuButton, $activeButton => request()->routeIs('profile')])>
                <i class="bi bi-person-circle"></i>
                <span class="flex-1 truncate">Profil Saya</span>
            </a>
        </li>
    </ul>
</nav>
