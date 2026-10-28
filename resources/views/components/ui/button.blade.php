{{-- shadcn/ui Button. Pakai href untuk merender <a>. --}}
@props(['variant' => 'default', 'size' => 'default', 'href' => null, 'type' => 'button'])

@php
    $classes = [
        'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none cursor-pointer disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
        match ($variant) {
            'destructive' => 'bg-destructive text-white shadow-xs hover:bg-destructive/90 focus-visible:ring-destructive/20',
            'success' => 'bg-success text-white shadow-xs hover:bg-success/90',
            'outline' => 'border bg-card shadow-xs hover:bg-accent hover:text-accent-foreground',
            'secondary' => 'bg-secondary text-secondary-foreground shadow-xs hover:bg-secondary/80',
            'ghost' => 'hover:bg-accent hover:text-accent-foreground',
            'link' => 'text-primary underline-offset-4 hover:underline',
            default => 'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90',
        },
        match ($size) {
            'sm' => 'h-8 gap-1.5 px-3',
            'lg' => 'h-10 px-6',
            'icon' => 'size-9',
            'icon-sm' => 'size-8',
            default => 'h-9 px-4 py-2',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" data-slot="button" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" data-slot="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
