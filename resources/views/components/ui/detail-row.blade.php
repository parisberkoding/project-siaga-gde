{{-- Satu baris label : nilai untuk halaman detail. Letakkan di dalam <dl class="divide-y">. numeric = nilai rata kanan (nominal). --}}
@props(['label', 'numeric' => false])

<div {{ $attributes->class('grid gap-1 py-2.5 text-sm first:pt-0 last:pb-0 sm:grid-cols-3 sm:gap-4') }}>
    <dt class="text-muted-foreground">{{ $label }}</dt>
    <dd @class(['min-w-0 sm:col-span-2', 'tabular-nums sm:text-right' => $numeric])>{{ $slot }}</dd>
</div>
