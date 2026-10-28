{{-- shadcn/ui CardHeader. Isi slot `action` untuk tombol di kanan judul. --}}
@props(['title' => null, 'description' => null, 'icon' => null])

<div data-slot="card-header" {{ $attributes->class('grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6') }}>
    @if ($title)
        <div data-slot="card-title" class="flex items-center gap-2 font-semibold leading-none">
            @if ($icon)<i class="{{ $icon }} text-muted-foreground"></i>@endif
            {{ $title }}
        </div>
    @endif
    @if ($description)
        <div data-slot="card-description" class="text-sm text-muted-foreground">{{ $description }}</div>
    @endif
    {{ $slot }}
    @isset($action)
        <div data-slot="card-action" class="col-start-2 row-span-2 row-start-1 self-start justify-self-end">{{ $action }}</div>
    @endisset
</div>
