{{-- Judul halaman + deskripsi + tombol aksi (slot `actions`). --}}
@props(['title', 'description' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div class="grid gap-1">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        @if ($description)
            <p class="text-sm text-muted-foreground">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
