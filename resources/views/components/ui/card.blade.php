{{-- shadcn/ui Card --}}
<div data-slot="card" {{ $attributes->class('flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm') }}>
    {{ $slot }}
</div>
