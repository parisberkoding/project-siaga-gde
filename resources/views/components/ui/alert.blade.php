{{-- shadcn/ui Alert (+ AlertTitle, AlertDescription). dismissible menambah tombol tutup. --}}
@props(['variant' => 'default', 'title' => null, 'icon' => null, 'dismissible' => false])

@php
    $icon ??= match ($variant) {
        'destructive' => 'bi bi-exclamation-triangle',
        'success' => 'bi bi-check-circle',
        'warning' => 'bi bi-exclamation-circle',
        'info' => 'bi bi-info-circle',
        default => null,
    };
@endphp

<div role="alert" data-slot="alert" @if ($dismissible) data-dismissible @endif {{ $attributes->class([
    'relative grid w-full items-start gap-y-0.5 rounded-lg border px-4 py-3 text-sm',
    'grid-cols-[1rem_1fr] gap-x-3' => $icon,
    'pr-10' => $dismissible,
    match ($variant) {
        'destructive' => 'border-destructive/30 bg-destructive-soft text-destructive',
        'success' => 'border-success/30 bg-success-soft text-success',
        'warning' => 'border-warning/30 bg-warning-soft text-warning',
        'info' => 'border-info/30 bg-info-soft text-info',
        default => 'bg-card text-card-foreground',
    },
]) }}>
    @if ($icon)<i class="{{ $icon }} translate-y-px"></i>@endif
    <div @class(['grid gap-1', 'col-start-2' => $icon])>
        @if ($title)
            <div data-slot="alert-title" class="font-medium tracking-tight">{{ $title }}</div>
        @endif
        <div data-slot="alert-description" class="grid gap-1 opacity-90 [&_p]:leading-relaxed">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" data-dismiss class="absolute right-2 top-2 rounded-md p-1 opacity-70 hover:opacity-100" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    @endif
</div>
