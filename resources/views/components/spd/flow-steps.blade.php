{{-- Alur proses dalam bentuk timeline vertikal. :steps="[['bi-send', 'Judul', 'Keterangan'], ...]"; langkah pertama ditandai aktif. --}}
@props(['title' => 'Alur pengajuan', 'steps' => []])

<x-ui.card class="ornament-corner gap-4">
    <x-ui.card-header :title="$title" icon="bi bi-diagram-3" />
    <x-ui.card-content>
        <ol class="grid">
            @foreach ($steps as [$icon, $label, $hint])
                <li class="relative grid grid-cols-[2rem_1fr] gap-3 pb-4 last:pb-0">
                    @unless ($loop->last)
                        <span class="absolute left-4 top-8 bottom-0 w-px -translate-x-1/2 bg-border" aria-hidden="true"></span>
                    @endunless
                    <span @class([
                        'relative flex size-8 items-center justify-center rounded-full text-sm',
                        'bg-primary text-primary-foreground shadow-sm shadow-primary/30' => $loop->first,
                        'border bg-card text-muted-foreground' => ! $loop->first,
                    ])><i class="bi {{ $icon }}"></i></span>
                    <div class="grid gap-0.5 pt-1">
                        <p class="text-sm font-medium leading-tight">{{ $label }}</p>
                        <p class="text-xs text-muted-foreground">{!! $hint !!}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-ui.card-content>
</x-ui.card>
