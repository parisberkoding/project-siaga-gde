{{-- shadcn/ui Label --}}
@props(['required' => false])

<label data-slot="label" {{ $attributes->class('flex select-none items-center gap-1 text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50') }}>
    {{ $slot }}
    @if ($required)<span class="text-destructive">*</span>@endif
</label>
