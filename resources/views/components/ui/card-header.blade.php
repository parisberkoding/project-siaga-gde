{{-- shadcn/ui CardHeader. Isi slot `action` untuk tombol di kanan judul. step = nomor langkah (form bertahap) menggantikan ikon. --}}
@props(['title' => null, 'description' => null, 'icon' => null, 'step' => null])

<div data-slot="card-header" {{ $attributes->class('grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6') }}>
    @if ($title)
        <div data-slot="card-title" class="flex items-center gap-2 font-semibold leading-none">
            @if ($step)
                {{-- Warna langkah bergilir supaya form bertahap tidak monoton; urutan sama dengan form BBM. --}}
                @php
                    $stepTones = ['from-blue-600 to-indigo-600 shadow-blue-600/30', 'from-orange-500 to-amber-500 shadow-orange-500/30', 'from-emerald-500 to-teal-600 shadow-emerald-500/30',
                        'from-violet-500 to-purple-600 shadow-violet-500/30', 'from-cyan-500 to-sky-600 shadow-cyan-500/30', 'from-rose-500 to-pink-600 shadow-rose-500/30'];
                    $stepTone = $stepTones[(((int) $step ?: 1) - 1) % count($stepTones)];
                @endphp
                <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-linear-to-br text-xs font-bold text-white shadow-sm {{ $stepTone }}">{{ $step }}</span>
            @elseif ($icon)
                <i class="{{ $icon }} text-primary"></i>
            @endif
            {{ $title }}
        </div>
    @endif
    @if ($description)
        <div data-slot="card-description" @class(['text-sm text-muted-foreground', 'pl-8' => $step])>{{ $description }}</div>
    @endif
    {{ $slot }}
    @isset($action)
        <div data-slot="card-action" class="col-start-2 row-span-2 row-start-1 self-start justify-self-end">{{ $action }}</div>
    @endisset
</div>
