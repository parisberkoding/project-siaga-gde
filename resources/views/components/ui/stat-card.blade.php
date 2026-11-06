{{-- Kartu ringkasan angka (pola "section cards" pada dashboard shadcn). href = seluruh kartu jadi tautan ke halaman tindak lanjut. --}}
{{-- short = mode ringkas di HP (grid 2 kolom): ikon pindah ke atas label, angka memakai short (true = sama dengan value). --}}
{{-- Nada (tone) mewarnai ikon (gradien) dan latar kartu (gradien lembut ke kanan bawah), supaya deret kartu tidak monoton. --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'primary', 'href' => null, 'short' => null])

@php
    [$iconClass, $tint, $accent] = match ($tone) {
        'success' => ['from-emerald-500 to-teal-600 shadow-emerald-500/25', 'to-success-soft', 'bg-emerald-500'],
        'warning' => ['from-amber-400 to-orange-500 shadow-orange-500/25', 'to-warning-soft', 'bg-amber-500'],
        'danger' => ['from-rose-500 to-red-600 shadow-rose-500/25', 'to-destructive-soft', 'bg-rose-500'],
        'muted' => ['from-violet-500 to-indigo-600 shadow-violet-500/25', 'to-violet-50', 'bg-violet-500'],
        default => ['from-blue-600 to-indigo-600 shadow-blue-600/25', 'to-info-soft', 'bg-blue-600'],
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('group/stat block rounded-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50') }}>
@endif
<x-ui.card @class(['relative gap-3 overflow-hidden bg-linear-to-br from-card from-55%', $tint, 'h-full transition-shadow group-hover/stat:border-primary/30 group-hover/stat:shadow-md' => $href, ($attributes->get('class') ?? '') => ! $href])>
    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 {{ $accent }} opacity-80"></span>
    <x-ui.card-content @class(['flex items-start justify-between gap-3', 'max-sm:flex-col-reverse max-sm:gap-2 max-sm:px-4' => $short])>
        <div class="grid gap-1">
            <span class="flex items-center gap-1 text-sm text-muted-foreground">
                {{ $label }}
                @if ($href)<i class="bi bi-arrow-up-right text-xs opacity-0 transition-opacity group-hover/stat:opacity-100 group-focus-visible/stat:opacity-100"></i>@endif
            </span>
            @if ($short)
                <span class="text-xl font-semibold tabular-nums tracking-tight sm:hidden">{{ $short === true ? $value : $short }}</span>
                <span class="text-2xl font-semibold tabular-nums tracking-tight max-sm:hidden">{{ $value }}</span>
            @else
                <span class="text-2xl font-semibold tabular-nums tracking-tight">{{ $value }}</span>
            @endif
            @if ($hint)
                <span class="text-xs text-muted-foreground">{{ $hint }}</span>
            @endif
        </div>
        @if ($icon)
            <span @class(['flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-lg text-white shadow-md', $iconClass, 'max-sm:size-9 max-sm:text-base' => $short])><i class="{{ $icon }}"></i></span>
        @endif
    </x-ui.card-content>
</x-ui.card>
@if ($href)
    </a>
@endif
