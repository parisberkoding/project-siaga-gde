{{--
  shadcn/ui Select (bukan NativeSelect): tombol pemicu + daftar pilihan di popover (Popover API, posisi dari
  ui/dropdown.js: terbuka ke bawah, pindah ke atas bila tidak muat). Interaksi: resources/js/ui/select-menu.js.
  options: [['value' => .., 'label' => .., 'href' => opsional, 'icon' => opsional], ...]
  - dengan name: nilai disimpan di <input type="hidden"> supaya ikut terkirim bersama form;
  - opsi ber-href adalah tautan (mis. preset periode), opsi tanpa href memilih nilai.
  required: dicek saat submit oleh ui/select-menu.js (input tersembunyi tidak divalidasi browser).
  invalid: default dari error validasi untuk name ini. JS halaman: resources/js/lib/select-menu.js.
  searchable: kotak cari di atas daftar (pola Combobox shadcn) untuk daftar panjang, mis. kendaraan.
--}}
@props(['options' => [], 'value' => null, 'name' => null, 'id' => null, 'placeholder' => 'Pilih', 'icon' => null, 'required' => false, 'invalid' => null, 'searchable' => false])

@php
    $id ??= 'select-' . \Illuminate\Support\Str::random(6);
    $ariaLabel = $attributes->get('aria-label');
    $invalid ??= $name && $errors->has($name);
    $current = collect($options)->first(fn ($o) => (string) $o['value'] === (string) $value);
@endphp

<div data-select @if ($required) data-required @endif {{ $attributes->except('aria-label')->class('relative w-full min-w-0') }}>
    @if ($name)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-select-input>
    @endif
    <button type="button" id="{{ $id }}" popovertarget="{{ $id }}-list" aria-haspopup="listbox" data-select-trigger @if ($invalid) aria-invalid="true" @endif @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
            class="flex h-9 w-full cursor-pointer items-center justify-between gap-2 rounded-md border border-input bg-card px-3 text-left text-sm shadow-xs outline-none transition-[color,box-shadow] hover:bg-accent/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-[3px] aria-invalid:ring-destructive/20">
        <span class="flex min-w-0 items-center gap-2">
            @if ($icon)<i class="{{ $icon }} text-muted-foreground"></i>@endif
            <span data-select-label data-placeholder="{{ $placeholder }}" @class(['truncate', 'text-muted-foreground' => ! $current])>{{ $current['label'] ?? $placeholder }}</span>
        </span>
        <i class="bi bi-chevron-down shrink-0 text-xs text-muted-foreground"></i>
    </button>
    <div id="{{ $id }}-list" popover data-dropdown data-align="start" role="listbox" aria-labelledby="{{ $id }}"
         class="m-0 max-h-72 min-w-32 overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
        @if ($searchable)
            <div class="sticky -top-1 z-10 -mx-1 -mt-1 mb-1 border-b bg-popover p-1.5">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground"></i>
                    <input type="search" data-select-search autocomplete="off" placeholder="Cari..." aria-label="Cari pilihan"
                           class="h-8 w-full rounded-md border border-input bg-card pl-8 pr-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50">
                </div>
            </div>
            <p data-select-empty hidden class="px-2 py-3 text-center text-sm text-muted-foreground">Tidak ada yang cocok</p>
        @endif
        <div data-select-options>
        @foreach ($options as $option)
            @php
                $selected = (string) $option['value'] === (string) $value;
                $tag = isset($option['href']) ? 'a' : 'button';
            @endphp
            <{{ $tag }} @if ($tag === 'a') href="{{ $option['href'] }}" @else type="button" @endif
                role="option" data-value="{{ $option['value'] }}" aria-selected="{{ $selected ? 'true' : 'false' }}"
                class="group/opt relative flex w-full cursor-pointer items-center gap-2 rounded-sm py-1.5 pl-2 pr-8 text-left text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-selected:font-medium">
                @if (! empty($option['icon']))<i class="{{ $option['icon'] }} text-muted-foreground"></i>@endif
                <span class="truncate">{{ $option['label'] }}</span>
                <i class="bi bi-check2 invisible absolute right-2 text-primary group-aria-selected/opt:visible"></i>
            </{{ $tag }}>
        @endforeach
        </div>
    </div>
</div>
