{{-- Baris "label + angka" dengan batang proporsional terhadap $max. Dipakai di dalam <ul class="grid gap-3">. --}}
@props(['label', 'value', 'max' => 1, 'color' => 'bg-primary'])

@php($percent = $max > 0 ? round($value / $max * 100) : 0)

<li {{ $attributes->class('grid gap-1.5 text-sm') }}>
    <div class="flex items-center justify-between gap-2">
        <span class="truncate">{{ $label }}</span>
        <span class="font-semibold tabular-nums">{{ number_format($value) }}</span>
    </div>
    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
        <div class="h-full rounded-full {{ $color }}" style="width: {{ $percent }}%"></div>
    </div>
</li>
