{{-- shadcn/ui TabsContent --}}
@props(['value'])

<div role="tabpanel" data-tabs-content="{{ $value }}" data-slot="tabs-content" {{ $attributes->class('outline-none') }}>
    {{ $slot }}
</div>
