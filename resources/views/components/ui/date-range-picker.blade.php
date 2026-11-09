{{--
  shadcn/ui Date Range Picker (Popover + Calendar mode="range"). Klik pertama = tanggal awal, klik kedua =
  tanggal akhir (otomatis ditukar bila terbalik). Nilai di dua <input type="hidden">, jadi letakkan komponen
  ini di dalam <form>; tombol Terapkan adalah submit form itu. Kalender: resources/js/ui/date-range-picker.js.
--}}
{{-- Slot berisi = tombol pemicu kustom (mis. segmen "Kustom" di toolbar); triggerClass & current mengatur gayanya. --}}
{{-- submit=false: Terapkan hanya mengisi nilai (form isian, mis. cetak laporan), bukan mengirim form. --}}
{{-- presets: ['Bulan ini' => ['2026-11-01', '2026-11-09'], ...] = chip rentang cepat di bawah pemicu. --}}
@props(['id' => null, 'fromName' => 'start_date', 'toName' => 'end_date', 'from' => null, 'to' => null, 'max' => null, 'placeholder' => 'Pilih rentang tanggal', 'label' => null, 'triggerClass' => null, 'current' => false, 'submit' => true, 'presets' => []])

@php
    $id ??= 'range-' . \Illuminate\Support\Str::random(6);
    $customTrigger = $slot->isNotEmpty();
    $label ??= $from && $to && ! $customTrigger
        ? \Illuminate\Support\Carbon::parse($from)->translatedFormat('j M Y') . ' – ' . \Illuminate\Support\Carbon::parse($to)->translatedFormat('j M Y')
        : null;
@endphp

<div data-range-picker data-max="{{ $max }}" {{ $attributes->class('relative') }}>
    <input type="hidden" name="{{ $fromName }}" value="{{ $from }}" data-range-from>
    <input type="hidden" name="{{ $toName }}" value="{{ $to }}" data-range-to>
    <button type="button" popovertarget="{{ $id }}-calendar" aria-haspopup="dialog" data-range-trigger @if ($customTrigger) aria-current="{{ $current ? 'true' : 'false' }}" @endif
            class="{{ $triggerClass ?? 'flex h-9 w-full cursor-pointer items-center gap-2 rounded-md border border-input bg-card px-3 text-left text-sm shadow-xs outline-none transition-[color,box-shadow] hover:bg-accent/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50' }}">
        @if ($customTrigger)
            {{ $slot }}
        @else
            <i class="bi bi-calendar-range text-muted-foreground"></i>
            <span data-range-label data-placeholder="{{ $label ?? $placeholder }}" class="min-w-0 flex-1 truncate">{{ $label ?? $placeholder }}</span>
        @endif
    </button>
    @if ($presets)
        <div class="mt-2 flex flex-wrap gap-1.5" role="group" aria-label="Rentang cepat">
            @foreach ($presets as $presetLabel => [$presetFrom, $presetTo])
                <button type="button" data-range-preset="{{ $presetFrom }},{{ $presetTo }}" aria-pressed="{{ $presetFrom === $from && $presetTo === $to ? 'true' : 'false' }}"
                        class="h-7 cursor-pointer rounded-full border bg-card px-3 text-xs font-medium text-muted-foreground shadow-xs transition-colors hover:bg-accent hover:text-foreground aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-primary-foreground">{{ $presetLabel }}</button>
            @endforeach
        </div>
    @endif
    <div id="{{ $id }}-calendar" popover data-dropdown data-align="start" data-range-calendar role="dialog" aria-label="Pilih rentang tanggal"
         class="m-0 rounded-xl border bg-popover p-3 text-popover-foreground shadow-lg">
        <div data-range-months class="flex flex-col gap-4 sm:flex-row"></div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t pt-3">
            <p data-range-summary class="text-xs text-muted-foreground" aria-live="polite">Pilih tanggal awal</p>
            <div class="flex gap-2">
                <button type="button" data-range-cancel class="h-8 rounded-md px-3 text-sm text-muted-foreground hover:bg-accent">Batal</button>
                <button type="{{ $submit ? 'submit' : 'button' }}" data-range-apply disabled class="h-8 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50">Terapkan</button>
            </div>
        </div>
    </div>
</div>
