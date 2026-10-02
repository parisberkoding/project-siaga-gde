{{--
    Kartu satu item struk (dipakai di halaman validasi SPD — FLOW BARU).

    Variabel yang dibutuhkan:
      $item => App\Models\StrukItem

    Struktur kelas wajib dijaga agar JS binding (bindItemCard / recalcTotals /
    saveItem / deleteItem) pada validasi.blade.php tetap berfungsi:
      .card[data-slug]  .item-nominal  .item-kategori  .item-keterangan
      .btn-save-item    .btn-del-item  .autosave-status
--}}
@php
    $vMeta = $item->metadata_json ?: [];
    $vBadge = $item->getKategoriBadgeClass();
    $vPrefix = \App\Models\StrukItem::KATEGORI_PREFIX[$item->struk_kategori] ?? $item->struk_kategori;
@endphp
<div class="card border-0 gde-struk {{ $item->spd_daily_report_id ? 'is-final' : 'is-draft' }}" data-slug="{{ $item->slug }}">
    <button type="button" class="btn btn-sm btn-outline-danger gde-struk__del btn-del-item" title="Hapus file" aria-label="Hapus">
        <i class="bi bi-trash"></i>
    </button>

    {{-- Thumbnail kiri (dilayani oleh rute terproteksi auth) --}}
    <a href="{{ $item->path_url }}" target="_blank" rel="noopener" class="gde-struk__thumb" title="Lihat struk">
        @if($item->path_image)
            <img src="{{ $item->path_url }}" alt="Struk {{ $item->item_code }}" loading="lazy">
        @else
            <i class="bi bi-image"></i>
        @endif
    </a>

    {{-- Ringkasan kanan --}}
    <div class="gde-struk__summary">
        <div class="gde-struk__row">
            <span class="badge {{ $vBadge }}">{{ $item->struk_kategori }}</span>
            <span class="gde-struk__nominal">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</span>
        </div>
        <div class="gde-struk__row mt-2">
            <span class="gde-struk__code" title="{{ $item->item_code }}">{{ $item->item_code }}</span>
            @if($item->tanggal)
                <span class="gde-struk__date"><i class="bi bi-calendar3"></i> {{ $item->tanggal->translatedFormat('d M Y') }}</span>
            @endif
            @if(array_key_exists('plat_cocok', $vMeta))
                @if($vMeta['plat_cocok'] === true)
                    <span class="badge text-bg-success" title="Plat kendaraan SPD tercocokkan dengan struk.">
                        <i class="bi bi-shield-check"></i> Plat cocok {{ $vMeta['plat_nomor'] ?? '' }}
                    </span>
                @elseif($vMeta['plat_cocok'] === false)
                    <span class="badge text-bg-danger" title="Plat pada struk berbeda dari plat kendaraan SPD ({{ $vMeta['plat_kendaraan'] ?? '-' }}).">
                        <i class="bi bi-shield-exclamation"></i> Plat beda
                    </span>
                @else
                    <span class="badge text-bg-light border text-muted" title="Nomor polisi tidak terbaca pada struk / tidak dapat dibandingkan.">
                        <i class="bi bi-shield"></i> Plat tidak terbaca
                    </span>
                @endif
            @endif
        </div>
        @if(!empty($vMeta['volume']) || !empty($vMeta['harga_per_liter']))
            <div class="gde-struk__row mt-2">
                @if(!empty($vMeta['volume']))
                    <span class="gde-struk__chip"><i class="bi bi-fuel-pump"></i> {{ number_format((float) $vMeta['volume'], 2, ',', '.') }} L</span>
                @endif
                @if(!empty($vMeta['harga_per_liter']))
                    <span class="gde-struk__chip"><i class="bi bi-tag"></i> Rp {{ number_format((float) $vMeta['harga_per_liter'], 0, ',', '.') }}/L</span>
                @endif
            </div>
        @endif
        @if($item->keterangan)
            <div class="gde-struk__ket mt-2" title="{{ $item->keterangan }}">{{ $item->keterangan }}</div>
        @endif
    </div>

    {{-- Panel edit --}}
    <div class="gde-struk__edit">
        <div class="gde-struk__edit-grid">
            <div>
                <label for="nominal-{{ $item->slug }}" class="form-label">Nominal (Rp)</label>
                <input type="number" id="nominal-{{ $item->slug }}" class="form-control form-control-sm item-nominal"
                       value="{{ (float) $item->nominal }}" min="0" step="0.01" inputmode="decimal">
            </div>
            <div>
                <label for="kategori-{{ $item->slug }}" class="form-label">Kategori</label>
                <select id="kategori-{{ $item->slug }}" class="form-select form-select-sm item-kategori">
                    @foreach(\App\Models\StrukItem::KATEGORI as $k)
                        <option value="{{ $k }}" {{ $item->struk_kategori === $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-primary btn-sm gde-btn--block btn-save-item" title="Simpan perubahan">
                <i class="bi bi-check2"></i> Simpan
            </button>
        </div>
        <div>
            <label for="keterangan-{{ $item->slug }}" class="form-label">Keterangan</label>
            <input type="text" id="keterangan-{{ $item->slug }}" class="form-control form-control-sm item-keterangan"
                   value="{{ $item->keterangan }}" maxlength="500" placeholder="cth: pengisian BBM di Patuha">
        </div>
        <div class="gde-struk__autosave autosave-status d-none">
            <i class="bi bi-check-circle"></i> Tersimpan otomatis
        </div>
    </div>
</div>