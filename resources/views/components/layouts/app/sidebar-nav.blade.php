{{--
  Renderer menu untuk layout baru (dipanggil dari partials/sidebar-menu).
  Pola shadcn SidebarGroup: item datar di bawah label seksi, tanpa grup buka-tutup. Seksi diambil dari
  kunci `section` item/anak menu (bila kosong: label grup induknya); item tanpa seksi tampil paling atas.
--}}
@php
    $menuButton = 'group/nav relative flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm font-medium outline-none transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-white/60';
    $activeButton = 'bg-sidebar-primary! text-sidebar-primary-foreground! shadow-sm';
    $badge = fn ($item) => ! empty($item['badge']) ? $item['badge']() : 0;
    $url = fn ($item) => ! empty($item['params']) ? route($item['route'], $item['params']) : route($item['route']);

    // Ratakan grup menjadi daftar item, lalu kelompokkan per seksi sesuai urutan kemunculan pertama.
    $sections = [];
    foreach ($menuDef as $item) {
        if (! empty($item['children'])) {
            foreach (array_filter($item['children'], $showItem) as $kid) {
                $sections[$kid['section'] ?? $item['label']][] = ['item' => $kid, 'active' => $childIsActive($kid)];
            }
        } elseif ($showItem($item)) {
            $sections[$item['section'] ?? ''][] = ['item' => $item, 'active' => $routeIsAny($item['active'])];
        }
    }
@endphp

<nav class="flex flex-col gap-4 px-3 py-2">
    @foreach ($sections as $section => $entries)
        <div data-slot="sidebar-group" class="flex flex-col gap-1">
            @if ($section !== '')
                <p class="px-2.5 pb-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-sidebar-muted-foreground">{{ $section }}</p>
            @endif
            <ul data-slot="sidebar-menu" class="flex flex-col gap-0.5">
                @foreach ($entries as ['item' => $entry, 'active' => $active])
                    <li>
                        <a href="{{ $url($entry) }}" @class([$menuButton, $activeButton => $active]) @if ($active) aria-current="page" @endif>
                            <i @class([
                                $entry['icon'] ?? 'bi bi-dot', 'shrink-0 text-base',
                                'text-sidebar-primary-foreground' => $active,
                                'text-sidebar-muted-foreground group-hover/nav:text-white' => ! $active,
                            ])></i>
                            <span class="min-w-0 flex-1 truncate" title="{{ $entry['label'] }}">{{ $entry['label'] }}</span>
                            @if ($count = $badge($entry))
                                <x-ui.badge variant="destructive" class="rounded-full">{{ $count }}</x-ui.badge>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
