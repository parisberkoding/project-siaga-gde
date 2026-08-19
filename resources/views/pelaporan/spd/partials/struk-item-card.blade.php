{{--
    Kartu satu item struk (dipakai di halaman validasi SPD — FLOW BARU).

    Variabel yang dibutuhkan:
      $item => App\Models\StrukItem

    Struktur kelas wajib dijaga agar JS binding (bindItemCard / recalcTotals /
    saveItem / deleteItem) pada validasi.blade.php tetap berfungsi:
      .card[data-slug]  .item-nominal  .item-kategori  .item-keterangan
      .btn-save-item    .btn-del-item
--}}
@php
    $vMeta = $item->metadata_json ?: [];
    $vBadge = $item->getKategoriBadgeClass();
@endphp
<div class="card border-light shadow-sm h-100" data-slug="{{ $item->slug }}">
    <div class="card-body p-2">
        <div class="d-flex align-items-start gap-2">
            <div class="flex-shrink-0">
                <a href="{{ asset('storage/' . $item->path_image) }}" target="_blank">
                    <img src="{{ asset('storage/' . $item->path_image) }}" alt="Struk"
                         style="max-height:80px; max-width:100px; border-radius:4px; object-fit:cover;">
                </a>
            </div>
            <div class="flex-grow-1 small">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="badge {{ $vBadge }}">{{ $item->struk_kategori }}</span>
                    <strong class="text-dark">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</strong>
                </div>
                <div class="text-muted" style="font-size:0.7rem;">{{ $item->item_code }}</div>
                @if($item->tanggal)
                    <div class="text-muted" style="font-size:0.7rem;">{{ $item->tanggal->format('Y-m-d') }}</div>
                @endif
                @if($item->keterangan)
                    <div class="text-muted text-truncate" style="font-size:0.75rem;" title="{{ $item->keterangan }}">{{ $item->keterangan }}</div>
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-del-item" title="Hapus file">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <hr class="my-1">
        <div class="d-flex gap-1 align-items-end">
            <div class="flex-grow-1">
                <label class="small text-muted mb-0">Nominal (Rp)</label>
                <input type="number" class="form-control form-control-sm item-nominal" value="{{ (float) $item->nominal }}" min="0" step="0.01">
            </div>
            <div>
                <label class="small text-muted mb-0">Kategori</label>
                <select class="form-select form-select-sm item-kategori">
                    @foreach(\App\Models\StrukItem::KATEGORI as $k)
                        <option value="{{ $k }}" {{ $item->struk_kategori === $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary btn-save-item">
                <i class="bi bi-check2"></i>
            </button>
        </div>
        <div class="mt-1">
            <label class="small text-muted mb-0">Keterangan</label>
            <input type="text" class="form-control form-control-sm item-keterangan" value="{{ $item->keterangan }}" placeholder="cth: pengisian BBM di Patuha">
        </div>
        @if(!empty($vMeta['volume']) || !empty($vMeta['harga_per_liter']))
            <div class="small text-muted mt-1">
                @if(!empty($vMeta['volume']))
                    {{ number_format((float) $vMeta['volume'], 2, ',', '.') }} L
                @endif
                @if(!empty($vMeta['harga_per_liter']))
                    @ {{ number_format((float) $vMeta['harga_per_liter'], 0, ',', '.') }}/L
                @endif
            </div>
        @endif
        <div class="small text-success autosave-status d-none mt-1">
            <i class="bi bi-check-circle"></i> Tersimpan otomatis
        </div>
    </div>
</div>