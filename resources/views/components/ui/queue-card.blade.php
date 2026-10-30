{{--
  Kartu antrian validasi (pola kartu Approval BBM): avatar + nama, badge status + waktu tunggu,
  rincian ringkas (slot), angka utama di kanan, catatan, dan aksi di dalam kartu.
    <x-ui.queue-card name="..." subtitle="REQ-..." :since="$model->created_at" figure-label="Nominal" figure="Rp 900.000" :note="$catatan">
        ...rincian (baris <p>)...
        <x-slot:actions>...tombol...</x-slot:actions>
    </x-ui.queue-card>
  Atribut lain (data-*, class) diteruskan ke <article>.
--}}
@props([
    'name',
    'subtitle' => null,
    'status' => 'Menunggu',
    'tone' => 'warning',
    'since' => null,
    'urgentAfterDays' => 2,
    'figureLabel' => null,
    'figure' => null,
    'figureHint' => null,
    'note' => null,
])

@php
    $initials = collect(explode(' ', trim($name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
    $urgent = $since && $since->diffInDays(now()) >= $urgentAfterDays;
    [$badge, $dot] = match ($tone) {
        'info' => ['bg-info-soft text-info', 'bg-info'],
        'danger' => ['bg-destructive-soft text-destructive', 'bg-destructive'],
        'success' => ['bg-success-soft text-success', 'bg-success'],
        default => ['bg-warning-soft text-warning', 'bg-warning'],
    };
@endphp

<article {{ $attributes->class('ornament-corner flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg motion-reduce:transition-none motion-reduce:hover:translate-y-0') }}>
    <div class="grid flex-1 content-start gap-4 p-4">
        <div class="flex items-start gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-sm font-semibold text-white shadow-sm ring-4 ring-card">{{ $initials }}</span>
            <div class="grid min-w-0 flex-1 gap-1">
                <p class="truncate text-base font-semibold leading-tight">{{ $name }}</p>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold {{ $badge }}">
                        <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full opacity-60 motion-reduce:animate-none {{ $dot }}"></span><span class="relative inline-flex size-1.5 rounded-full {{ $dot }}"></span></span>
                        {{ $status }}
                    </span>
                    @if ($since)
                        <span @class(['inline-flex items-center gap-1', 'font-semibold text-destructive' => $urgent, 'text-muted-foreground' => ! $urgent]) title="Masuk {{ $since->format('d/m/Y H:i') }}">
                            <i class="bi bi-clock-history"></i> {{ $since->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            @isset($aside)
                <div class="shrink-0">{{ $aside }}</div>
            @endisset
        </div>

        <div class="flex items-stretch justify-between gap-4">
            <div class="grid min-w-0 content-center gap-1.5 text-sm">
                {{ $slot }}
                @if ($subtitle)
                    <p class="font-mono text-xs text-muted-foreground">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($figure !== null)
                <div class="grid min-w-36 shrink-0 content-center gap-1.5 rounded-xl bg-primary/5 px-4 py-3 text-right ring-1 ring-primary/10">
                    <p class="text-[10px] font-semibold uppercase leading-none tracking-wider text-muted-foreground">{{ $figureLabel }}</p>
                    <p class="whitespace-nowrap text-xl font-bold leading-none tracking-tight tabular-nums text-primary">{{ $figure }}</p>
                    @if ($figureHint)
                        <p class="whitespace-nowrap text-[11px] leading-none text-muted-foreground">{{ $figureHint }}</p>
                    @endif
                </div>
            @endif
        </div>

        @if ($note)
            <blockquote class="relative rounded-lg bg-muted/60 py-2 pl-9 pr-3 text-sm text-foreground/80">
                <i class="bi bi-quote absolute left-3 top-1.5 text-lg text-primary/40"></i>
                {{ $note }}
            </blockquote>
        @endif

        @isset($extra)
            {{ $extra }}
        @endisset
    </div>

    @isset($actions)
        <div class="mx-4 flex flex-wrap items-center justify-end gap-2 border-t py-3">{{ $actions }}</div>
    @endisset
</article>
