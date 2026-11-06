{{-- shadcn/ui Badge, ditambah varian lembut untuk status (success, warning, info, danger). --}}
@props(['variant' => 'default'])

<span data-slot="badge" {{ $attributes->class([
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-md border px-2 py-0.5 text-xs font-medium',
    match ($variant) {
        'secondary' => 'border-transparent bg-secondary text-secondary-foreground',
        'destructive' => 'border-transparent bg-destructive text-white',
        'outline' => 'text-foreground',
        'success' => 'border-transparent bg-success-soft text-success',
        'warning' => 'border-transparent bg-warning-soft text-warning',
        'info' => 'border-transparent bg-info-soft text-info',
        'danger' => 'border-transparent bg-destructive-soft text-destructive',
        'muted' => 'border-transparent bg-muted text-muted-foreground',
        default => 'border-transparent bg-primary text-primary-foreground',
    },
]) }}>{{ $slot }}</span>
