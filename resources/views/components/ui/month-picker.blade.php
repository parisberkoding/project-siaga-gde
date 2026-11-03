{{--
  Month picker gaya shadcn (Popover + kisi 12 bulan per tahun). Tiap bulan adalah tautan yang
  mengganti parameter `param` di URL sekarang, jadi tidak butuh form. Bulan di luar `months`
  (bulan yang punya data) tampil nonaktif. Pindah tahun lewat resources/js/ui/month-picker.js.

  months   = koleksi Carbon awal bulan yang boleh dipilih
  selected = Carbon bulan aktif, null berarti "semua"
--}}
@props(['id', 'months', 'selected' => null, 'param' => 'bulan', 'allValue' => 'semua', 'allLabel' => 'Semua bulan'])

@php
    $available = $months->map(fn ($m) => $m->format('Y-m'))->all();
    $years = $months->map(fn ($m) => $m->year)->unique()->sortDesc()->values();
    if ($years->isEmpty()) $years = collect([now()->year]);
    $activeYear = $selected?->year ?? $years->first();
    $url = fn ($value) => request()->fullUrlWithQuery([$param => $value, 'page' => null]);
    $label = $selected ? $selected->locale('id')->translatedFormat('F Y') : $allLabel;
    $nav = 'inline-flex size-7 items-center justify-center rounded-md border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-40';
@endphp

<button type="button" popovertarget="{{ $id }}" aria-haspopup="dialog"
        {{ $attributes->class('inline-flex h-9 min-w-48 cursor-pointer items-center justify-between gap-3 rounded-md border bg-card px-3 text-sm shadow-xs transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50') }}>
    <span class="flex items-center gap-2">
        <i class="bi bi-calendar3 text-muted-foreground"></i>
        <span class="font-medium">{{ $label }}</span>
    </span>
    <i class="bi bi-chevron-down text-xs text-muted-foreground"></i>
</button>

<div id="{{ $id }}" popover data-dropdown data-align="start" data-month-picker aria-label="Pilih bulan"
     class="m-0 w-72 rounded-lg border bg-popover p-3 text-popover-foreground shadow-lg">
    @foreach ($years as $i => $year)
        <div data-year-panel @if ($year !== $activeYear) hidden @endif>
            <div class="mb-3 flex items-center justify-between">
                <button type="button" class="{{ $nav }}" data-year-step="1" aria-label="Tahun sebelumnya" @disabled(! $years->has($i + 1))>
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="text-sm font-semibold tabular-nums">{{ $year }}</span>
                <button type="button" class="{{ $nav }}" data-year-step="-1" aria-label="Tahun berikutnya" @disabled($i === 0)>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="grid grid-cols-3 gap-1.5">
                @foreach (range(1, 12) as $m)
                    @php
                        $month = \Carbon\Carbon::create($year, $m, 1)->locale('id');
                        $value = $month->format('Y-m');
                        $isSelected = $selected?->format('Y-m') === $value;
                    @endphp
                    @if (in_array($value, $available, true))
                        <a href="{{ $url($value) }}" role="menuitem" @if ($isSelected) aria-current="date" @endif
                           title="{{ $month->translatedFormat('F Y') }}"
                           @class([
                               'flex h-9 items-center justify-center rounded-md text-sm outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50',
                               'bg-primary font-medium text-primary-foreground shadow-xs' => $isSelected,
                               'hover:bg-accent hover:text-accent-foreground' => ! $isSelected,
                           ])>{{ $month->translatedFormat('M') }}</a>
                    @else
                        <span class="flex h-9 cursor-not-allowed items-center justify-center rounded-md text-sm text-muted-foreground/40"
                              aria-disabled="true" title="Tidak ada data">{{ $month->translatedFormat('M') }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="mt-3 border-t pt-3">
        <a href="{{ $url($allValue) }}" role="menuitem" @if (! $selected) aria-current="date" @endif
           @class([
               'flex h-8 w-full items-center justify-center gap-2 rounded-md text-sm outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50',
               'bg-primary font-medium text-primary-foreground' => ! $selected,
               'hover:bg-accent' => $selected,
           ])>
            <i class="bi bi-collection"></i> {{ $allLabel }}
        </a>
    </div>
</div>
