{{-- Judul halaman + kicker (label kecil di atas judul) + deskripsi + tombol aksi (slot `actions`). --}}
@props(['title', 'description' => null, 'kicker' => null, 'icon' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div class="flex min-w-0 items-start gap-3">
        @if ($icon)
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-lg text-primary-foreground shadow-sm shadow-primary/30"><i class="{{ $icon }}"></i></span>
        @endif
        <div class="grid min-w-0 gap-1">
            @if ($kicker)
                <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">{{ $kicker }}</span>
            @endif
            <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="text-sm text-muted-foreground">{{ $description }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
