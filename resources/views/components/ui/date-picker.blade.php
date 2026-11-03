{{--
  Date picker gaya shadcn (Popover + Calendar) di atas <input type="date"> asli.
  Input asli tetap ada (transparan di bawah tombol), jadi name, value/old(), required, min/max,
  validasi browser, dan JS halaman yang membaca atau mengubah .value/.min tetap bekerja.
  Kalender dirender oleh resources/js/ui/date-picker.js.
--}}
@props(['id' => null, 'placeholder' => 'Pilih tanggal'])

@php
    $id ??= 'date-' . \Illuminate\Support\Str::random(6);
    $name = $attributes->get('name');
    $invalid = $name && $errors->has(str_replace(['[', ']'], ['.', ''], $name));
@endphp

<div data-date-picker {{ $attributes->only('class')->class('relative') }}>
    <input type="date" id="{{ $id }}" tabindex="-1" @if ($invalid) aria-invalid="true" @endif
           {{ $attributes->except('class') }} class="pointer-events-none absolute inset-0 size-full opacity-0">
    <button type="button" popovertarget="{{ $id }}-calendar" aria-haspopup="dialog" data-date-trigger @if ($invalid) aria-invalid="true" @endif
            class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-md border border-input bg-card px-3 text-left text-base shadow-xs outline-none transition-[color,box-shadow] hover:bg-accent/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm">
        <i class="bi bi-calendar3 text-muted-foreground"></i>
        <span data-date-label data-placeholder="{{ $placeholder }}" class="min-w-0 flex-1 truncate text-muted-foreground">{{ $placeholder }}</span>
        <i class="bi bi-chevron-down text-xs text-muted-foreground"></i>
    </button>
    <div id="{{ $id }}-calendar" popover data-dropdown data-align="start" data-date-calendar role="dialog" aria-label="Pilih tanggal"
         class="m-0 w-[18.5rem] rounded-xl border bg-popover p-3 text-popover-foreground shadow-lg"></div>
</div>
