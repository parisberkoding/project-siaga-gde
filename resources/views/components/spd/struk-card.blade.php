{{--
  Kartu satu item struk di halaman Pelaporan & Validasi SPD.
  Tanpa :item dirender kosong di dalam <template>, lalu diisi oleh resources/js/pages/spd/validasi.js
  (fillCard) untuk item hasil upload. Atribut data-* di bawah dipakai JS, jaga namanya.
--}}
@props(['item' => null])

@php
    $meta = $item?->metadata_json ?: [];
    $kategori = $item?->struk_kategori ?? 'Lainnya';
    $tanggal = $item?->tanggal?->format('Y-m-d');
    $plat = ! array_key_exists('plat_cocok', $meta) ? null : match ($meta['plat_cocok']) { true => 'ok', false => 'beda', default => 'unknown' };
    $tanggalWarn = ! empty($meta['tanggal_di_luar_periode']) || ! empty($meta['tanggal_tahun_beda']);
    $tanggalWarnTitle = 'Tanggal hasil OCR di luar periode perjalanan dinas.'
        . (! empty($meta['tanggal_periode_spd']) ? ' Periode SPD: ' . $meta['tanggal_periode_spd'] . '.' : '')
        . (! empty($meta['tanggal_mentah']) ? ' Terbaca di struk: "' . $meta['tanggal_mentah'] . '".' : '')
        . ' Periksa & perbaiki pada kolom Tanggal Struk.';
    $kategoriClass = [
        'BBM' => 'bg-destructive-soft text-destructive', 'Tol' => 'bg-accent text-accent-foreground', 'Parkir' => 'bg-info-soft text-info',
        'Makan' => 'bg-success-soft text-success', 'Hotel' => 'bg-secondary text-secondary-foreground', 'Tambahan' => 'bg-warning-soft text-warning',
    ][$kategori] ?? 'bg-muted text-muted-foreground';
    $field = 'h-8 w-full rounded-md border border-input bg-card px-2.5 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
    $label = 'text-[11px] font-semibold uppercase tracking-wider text-muted-foreground';
@endphp

<div data-struk data-slug="{{ $item?->slug }}" data-group-key="{{ $tanggal ?? 'tanpa-tanggal' }}" @class([
    'relative grid gap-4 rounded-lg border border-l-4 bg-card p-4 shadow-xs transition-shadow hover:shadow-md sm:grid-cols-[88px_1fr]',
    'data-[bulk-edit]:border-primary data-[bulk-edit]:ring-2 data-[bulk-edit]:ring-primary/15',
    $item?->spd_daily_report_id ? 'border-l-success' : 'border-l-warning',
])>
    <x-ui.button variant="ghost" size="icon-sm" data-del class="absolute right-2 top-2 text-destructive hover:bg-destructive-soft hover:text-destructive" title="Hapus file" aria-label="Hapus file">
        <i class="bi bi-trash"></i>
    </x-ui.button>

    <a data-thumb href="{{ $item?->path_url }}" target="_blank" rel="noopener" title="Lihat struk"
       class="flex size-[88px] items-center justify-center overflow-hidden rounded-md border bg-muted text-2xl text-muted-foreground">
        <img data-thumb-img @if ($item?->path_image) src="{{ $item->path_url }}" @else hidden @endif alt="Struk {{ $item?->item_code }}" loading="lazy" class="size-full object-cover">
        <i data-thumb-empty class="bi bi-image" @if ($item?->path_image || ! $item) hidden @endif></i>
    </a>

    <div class="grid min-w-0 content-start gap-2 pr-8">
        <div class="flex flex-wrap items-center gap-2">
            <span data-kategori-badge class="rounded-md px-2 py-0.5 text-xs font-medium {{ $kategoriClass }}">{{ $kategori }}</span>
            <span data-nominal class="text-lg font-bold tabular-nums tracking-tight">Rp {{ number_format((float) $item?->nominal, 0, ',', '.') }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <code data-code class="max-w-full truncate text-muted-foreground" title="{{ $item?->item_code }}">{{ $item?->item_code }}</code>
            <span data-date @unless ($tanggal) hidden @endunless class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-muted-foreground">
                <i class="bi bi-calendar3"></i> <span data-date-label>{{ $item?->tanggal?->translatedFormat('d M Y') }}</span>
            </span>
            <x-ui.badge variant="success" data-plat="ok" :hidden="$plat !== 'ok'" title="Plat kendaraan SPD tercocokkan dengan struk.">
                <i class="bi bi-shield-check"></i> Plat cocok <span data-plat-nomor>{{ strtoupper($meta['plat_nomor'] ?? '') }}</span>
            </x-ui.badge>
            <x-ui.badge variant="danger" data-plat="beda" :hidden="$plat !== 'beda'" title="Plat pada struk berbeda dari plat kendaraan SPD ({{ $meta['plat_kendaraan'] ?? '-' }}).">
                <i class="bi bi-shield-exclamation"></i> Plat beda
            </x-ui.badge>
            <x-ui.badge variant="muted" data-plat="unknown" :hidden="$plat !== 'unknown'" title="Nomor polisi tidak terbaca pada struk / tidak dapat dibandingkan.">
                <i class="bi bi-shield"></i> Plat tidak terbaca
            </x-ui.badge>
            <x-ui.badge variant="warning" data-tanggal-warning :hidden="! $tanggalWarn" :title="$tanggalWarnTitle">
                <i class="bi bi-calendar-x"></i> Tanggal perlu dicek
            </x-ui.badge>
            @if (! empty($meta['volume']))
                <x-ui.badge variant="outline"><i class="bi bi-fuel-pump"></i> {{ number_format((float) $meta['volume'], 2, ',', '.') }} L</x-ui.badge>
            @endif
            @if (! empty($meta['harga_per_liter']))
                <x-ui.badge variant="outline"><i class="bi bi-tag"></i> Rp {{ number_format((float) $meta['harga_per_liter'], 0, ',', '.') }}/L</x-ui.badge>
            @endif
        </div>
        <p data-ket @unless ($item?->keterangan) hidden @endunless class="break-words text-sm text-muted-foreground" title="{{ $item?->keterangan }}">{{ $item?->keterangan }}</p>
    </div>

    <div class="grid gap-3 border-t border-dashed pt-3 sm:col-span-2">
        <div class="grid items-end gap-3 sm:grid-cols-[1fr_1.3fr_1fr_auto]">
            <label class="grid gap-1"><span class="{{ $label }}">Tanggal Struk</span>
                <input type="date" data-field="tanggal" value="{{ $tanggal }}" class="{{ $field }}">
            </label>
            <label class="grid gap-1"><span class="{{ $label }}">Nominal (Rp)</span>
                <input type="number" data-field="nominal" value="{{ (float) $item?->nominal }}" min="0" step="0.01" inputmode="decimal" class="{{ $field }}">
            </label>
            <label class="grid gap-1"><span class="{{ $label }}">Kategori</span>
                <select data-field="kategori" class="{{ $field }}">
                    @foreach (\App\Models\StrukItem::KATEGORI as $k)
                        <option value="{{ $k }}" @selected($kategori === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </label>
            <x-ui.button size="sm" data-save class="[[data-bulk-edit]_&]:hidden"><i class="bi bi-check2"></i> Simpan</x-ui.button>
        </div>
        <label class="grid gap-1"><span class="{{ $label }}">Keterangan</span>
            <input type="text" data-field="keterangan" value="{{ $item?->keterangan }}" maxlength="500" placeholder="cth: pengisian BBM di Patuha" class="{{ $field }}">
        </label>
        <span data-autosave hidden class="inline-flex items-center gap-1 text-xs font-medium text-success"><i class="bi bi-check-circle"></i> Tersimpan otomatis</span>
    </div>
</div>
