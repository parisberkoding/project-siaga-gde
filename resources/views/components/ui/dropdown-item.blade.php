{{-- Item DropdownMenu. variant: default | success | destructive. href = tautan (<a>), selain itu <button>.
     Atribut lain (type, form, target, data-*) diteruskan. --}}
@props(['icon' => null, 'variant' => 'default', 'type' => 'button', 'href' => null])

@php
    $classes = [
        'flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm outline-none transition-colors',
        match ($variant) {
            'success' => 'text-success hover:bg-success-soft focus-visible:bg-success-soft',
            'destructive' => 'text-destructive hover:bg-destructive-soft focus-visible:bg-destructive-soft',
            default => 'hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->class($classes) }}>
@endif
    @if ($icon)<i class="{{ $icon }} w-4 text-center {{ $variant === 'default' ? 'text-muted-foreground' : '' }}"></i>@endif
    <span class="flex-1">{{ $slot }}</span>
@if ($href)
    </a>
@else
    </button>
@endif
