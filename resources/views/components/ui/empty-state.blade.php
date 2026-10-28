{{-- shadcn/ui Empty: ikon + judul + keterangan + aksi (slot). --}}
@props(['icon' => 'bi bi-inbox', 'title', 'description' => null])

<div data-slot="empty" {{ $attributes->class('flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-8 text-center') }}>
    <span class="mb-1 flex size-10 items-center justify-center rounded-lg bg-muted text-lg text-muted-foreground"><i class="{{ $icon }}"></i></span>
    <p class="font-medium">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-muted-foreground">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
