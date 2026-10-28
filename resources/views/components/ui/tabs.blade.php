{{-- shadcn/ui Tabs. Isi dengan <x-ui.tabs-list>, <x-ui.tabs-trigger value> dan <x-ui.tabs-content value>. --}}
@props(['default' => null])

<div data-tabs="{{ $default }}" data-slot="tabs" {{ $attributes->class('flex flex-col gap-4') }}>
    {{ $slot }}
</div>
