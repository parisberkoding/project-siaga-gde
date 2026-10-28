{{-- Kartu ringkasan angka (pola "section cards" pada dashboard shadcn). --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'primary'])

<x-ui.card {{ $attributes->class('gap-3') }}>
    <x-ui.card-content class="flex items-start justify-between gap-3">
        <div class="grid gap-1">
            <span class="text-sm text-muted-foreground">{{ $label }}</span>
            <span class="text-2xl font-semibold tabular-nums tracking-tight">{{ $value }}</span>
            @if ($hint)
                <span class="text-xs text-muted-foreground">{{ $hint }}</span>
            @endif
        </div>
        @if ($icon)
            <span @class([
                'flex size-10 shrink-0 items-center justify-center rounded-lg text-lg',
                match ($tone) {
                    'success' => 'bg-success-soft text-success',
                    'warning' => 'bg-warning-soft text-warning',
                    'danger' => 'bg-destructive-soft text-destructive',
                    'muted' => 'bg-muted text-muted-foreground',
                    default => 'bg-accent text-accent-foreground',
                },
            ])><i class="{{ $icon }}"></i></span>
        @endif
    </x-ui.card-content>
</x-ui.card>
