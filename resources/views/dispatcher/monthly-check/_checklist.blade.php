{{--
    PARTIAL: Checklist Item Pengecekan Bulanan
    Menerima: $parts (Collection VehiclePart, check_frequency=monthly)
    Diinclude oleh: dispatcher/monthly-check/create.blade.php
--}}

@php
    $categories = $parts->groupBy('category');
    $categoryIcons = [
        'kelengkapan' => 'bi-gear',
        'keselamatan' => 'bi-heart-pulse',
        'kesiapan'    => 'bi-shield-check',
    ];
@endphp

@forelse($categories as $category => $items)
<div class="card {{ !$loop->first ? 'mt-3' : '' }}">
    <div class="card-body pt-3">

        <div class="category-header mb-3">
            <i class="bi {{ $categoryIcons[$category] ?? 'bi-calendar-check' }} me-1"></i>
            {{ strtoupper($category) }} — Pengecekan Bulanan
        </div>

        @foreach($items as $part)
        <div class="check-item-row">
            <div class="row align-items-center g-2">

                <div class="col-12 col-md-5">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-light text-dark mt-1">{{ $part->sort_order }}</span>
                        <div>
                            <strong>{{ $part->name }}</strong>
                            @if($part->standard_description)
                                <br><span class="standard-desc">{{ $part->standard_description }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <input type="radio"
                               name="items[{{ $part->id }}][status]"
                               value="ok"
                               id="ok_{{ $part->id }}"
                               class="status-toggle status-toggle-ok"
                               {{ old("items.{$part->id}.status") == 'ok' ? 'checked' : '' }}>
                        <label for="ok_{{ $part->id }}"><i class="bi bi-check2-circle"></i></label>

                        <input type="radio"
                               name="items[{{ $part->id }}][status]"
                               value="ng"
                               id="ng_{{ $part->id }}"
                               class="status-toggle status-toggle-ng"
                               {{ old("items.{$part->id}.status") == 'ng' ? 'checked' : '' }}>
                        <label for="ng_{{ $part->id }}"><i class="bi bi-x-circle"></i></label>
                    </div>
                </div>

                <div class="col-auto">
                    <div class="form-check">
                        <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                        <input type="checkbox"
                               name="items[{{ $part->id }}][needs_replacement]"
                               value="1"
                               class="form-check-input replacement-check"
                               id="replace_{{ $part->id }}"
                               {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                        <label for="replace_{{ $part->id }}" class="form-check-label small text-danger fw-semibold">
                            <i class="bi bi-wrench me-1"></i>Ganti
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md">
                    <textarea name="items[{{ $part->id }}][notes]"
                              class="item-notes"
                              rows="1"
                              placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                </div>

            </div>
        </div>
        @endforeach

    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center text-muted py-4">
        <i class="bi bi-inbox fs-2"></i>
        <p class="mt-2">Belum ada item pengecekan bulanan yang aktif.</p>
    </div>
</div>
@endforelse
