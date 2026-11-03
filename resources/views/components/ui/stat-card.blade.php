{{-- Kartu ringkasan angka (pola "section cards" pada dashboard shadcn). href = seluruh kartu jadi tautan ke halaman tindak lanjut. --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'primary', 'href' => null])

@php
    $iconClass = match ($tone) {
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-destructive-soft text-destructive',
        'muted' => 'bg-muted text-muted-foreground',
        default => 'bg-accent text-accent-foreground',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('group/stat block rounded-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50') }}>
@endif
<x-ui.card @class(['gap-3', 'h-full transition-shadow group-hover/stat:border-primary/30 group-hover/stat:shadow-md' => $href, ($attributes->get('class') ?? '') => ! $href])>
    <x-ui.card-content class="flex items-start justify-between gap-3">
        <div class="grid gap-1">
            <span class="flex items-center gap-1 text-sm text-muted-foreground">
                {{ $label }}
                @if ($href)<i class="bi bi-arrow-up-right text-xs opacity-0 transition-opacity group-hover/stat:opacity-100 group-focus-visible/stat:opacity-100"></i>@endif
            </span>
            <span class="text-2xl font-semibold tabular-nums tracking-tight">{{ $value }}</span>
            @if ($hint)
                <span class="text-xs text-muted-foreground">{{ $hint }}</span>
            @endif
        </div>
        @if ($icon)
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg text-lg {{ $iconClass }}"><i class="{{ $icon }}"></i></span>
        @endif
    </x-ui.card-content>
</x-ui.card>
@if ($href)
    </a>
@endif
