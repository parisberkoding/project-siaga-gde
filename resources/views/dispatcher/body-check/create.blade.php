@extends('templates.main')

@section('title', 'Form Pengecekan Body Kendaraan')

@section('content')

<style>
    /* ═══════════════════════════════════════════ */
    /* BODY CHECK - MOBILE-FIRST FORM             */
    /* ═══════════════════════════════════════════ */

    .check-item-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
        transition: background 0.15s;
    }
    .check-item-row:hover { background: #f8f9fa; }
    .check-item-row:last-child { border-bottom: none; }

    /* 4-level radio buttons — big tap targets */
    .scale-toggle { display: none; }
    .scale-toggle + label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        height: 40px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.15s;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        text-align: center;
        padding: 0 6px;
    }
    .scale-baik:checked + label   { background: #198754; border-color: #198754; color: #fff; }
    .scale-cukup:checked + label  { background: #0dcaf0; border-color: #0dcaf0; color: #fff; }
    .scale-kurang:checked + label { background: #ffc107; border-color: #ffc107; color: #000; }
    .scale-tidak:checked + label  { background: #dc3545; border-color: #dc3545; color: #fff; }

    /* Category header */
    .category-header {
        background: linear-gradient(135deg, #012970 0%, #1a3a8a 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 8px;
        margin-bottom: 8px;
        font-weight: 600;
    }

    /* Replacement checkbox */
    .replacement-check {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    /* Item notes */
    .item-notes {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 13px;
        width: 100%;
        resize: none;
        transition: border-color 0.15s;
    }
    .item-notes:focus {
        border-color: #4154f1;
        box-shadow: 0 0 0 0.15rem rgba(65, 84, 241, 0.1);
        outline: none;
    }

    .standard-desc {
        font-size: 12px;
        color: #6c757d;
        font-style: italic;
    }

    /* ═══════════════════════════════════════════ */
    /* DAMAGE PHOTO SECTION — VERTICAL CARD STYLE */
    /* ═══════════════════════════════════════════ */

    .damage-row {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 0;
        margin-bottom: 12px;
        overflow: hidden;
    }

    /* Full-width photo preview */
    .damage-photo-preview {
        width: 100%;
        height: 180px;
        background: #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        cursor: pointer;
    }
    .damage-photo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .damage-photo-preview .preview-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #6c757d;
        gap: 6px;
    }
    .damage-photo-preview .preview-placeholder i {
        font-size: 40px;
    }
    .damage-photo-preview .preview-placeholder span {
        font-size: 13px;
    }
    /* Overlay label so tapping the preview also triggers file input */
    .damage-photo-preview label.photo-tap-area {
        position: absolute;
        inset: 0;
        cursor: pointer;
        margin: 0;
    }

    .damage-body {
        padding: 10px 12px 12px;
    }

    /* Hide native file input */
    .camera-input { display: none; }

    /* BBM Gauge */
    .bbm-gauge {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bbm-gauge label {
        font-weight: 600;
        font-size: 14px;
        min-width: 20px;
        text-align: center;
    }
    .bbm-gauge input[type="range"] {
        flex: 1;
        accent-color: #4154f1;
    }
    .bbm-value {
        font-weight: 700;
        font-size: 16px;
        color: #012970;
        min-width: 40px;
        text-align: center;
    }

    /* Mobile adjustments */
    @media (max-width: 576px) {
        .scale-toggle + label {
            min-width: 44px;
            height: 36px;
            font-size: 11px;
        }
        .damage-photo-preview {
            height: 160px;
        }
    }
</style>

<div class="pagetitle">
    <h1>Pengecekan Body Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.body-check.index') }}">Pengecekan Body</a></li>
            <li class="breadcrumb-item active">Form Pengecekan</li>
        </ol>
    </nav>
</div>

{{-- Validation Errors --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle me-1"></i> Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('dispatcher.body-check.store') }}" method="POST" enctype="multipart/form-data" id="bodyCheckForm">
    @csrf

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SECTION 1: HEADER INFO                          --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Informasi Kendaraan</h5>
                        <div class="row g-3">

                            {{-- Kendaraan --}}
                            <div class="col-md-4">
                                <label for="vehicle_id" class="form-label fw-semibold">
                                    <i class="bi bi-truck me-1"></i> Kendaraan <span class="text-danger">*</span>
                                </label>
                                <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                    <option value="">— Pilih Kendaraan —</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                                data-nopol="{{ $vehicle->consumerial_code }}"
                                                {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} — {{ $vehicle->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Driver --}}
                            <div class="col-md-4">
                                <label for="current_driver_id" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i> Driver <span class="text-danger">*</span>
                                </label>
                                <select name="current_driver_id" id="current_driver_id" class="form-select" required>
                                    <option value="">— Pilih Driver —</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('current_driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Keperluan --}}
                            <div class="col-md-4">
                                <label for="keperluan" class="form-label fw-semibold">
                                    <i class="bi bi-card-text me-1"></i> Keperluan
                                </label>
                                <input type="text" name="keperluan" id="keperluan" class="form-control"
                                       placeholder="Cek rutin bulanan / sebelum perjalanan dinas..." value="{{ old('keperluan') }}">
                            </div>

                            {{-- BBM Gauge --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-fuel-pump me-1"></i> Level BBM
                                </label>
                                <div class="bbm-gauge">
                                    <label>E</label>
                                    <input type="range" name="bbm_level" id="bbm_level" class="form-range"
                                           min="0" max="100" step="12.5" value="{{ old('bbm_level', 50) }}"
                                           oninput="document.getElementById('bbmDisplay').textContent = this.value + '%'">
                                    <label>F</label>
                                    <span class="bbm-value" id="bbmDisplay">{{ old('bbm_level', 50) }}%</span>
                                </div>
                            </div>

                            {{-- Catatan Umum --}}
                            <div class="col-md-6">
                                <label for="notes" class="form-label fw-semibold">
                                    <i class="bi bi-chat-left-text me-1"></i> Catatan Umum
                                </label>
                                <input type="text" name="notes" id="notes" class="form-control"
                                       placeholder="Opsional..." value="{{ old('notes') }}">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SECTION 2: CHECKLIST KELENGKAPAN BODY           --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="section" style="margin-top: -10px;">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-3">

                        <div class="category-header">
                            <i class="bi bi-car-front me-1"></i> KELENGKAPAN KENDARAAN — Diisi Oleh Dispatcher
                        </div>

                        {{-- Legend --}}
                        <div class="d-flex flex-wrap gap-3 mb-3 mt-2 small">
                            <span><span class="badge bg-success">Baik</span> Kondisi prima</span>
                            <span><span class="badge bg-info">Cukup</span> Masih layak</span>
                            <span><span class="badge bg-warning text-dark">Kurang</span> Perlu perhatian</span>
                            <span><span class="badge bg-danger">Tidak</span> Tidak layak / Tidak ada</span>
                        </div>

                        @foreach($bodyParts as $part)
                        <div class="check-item-row">
                            <div class="row align-items-center g-2">

                                {{-- No + Nama --}}
                                <div class="col-12 col-md-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge bg-light text-dark mt-1">{{ $loop->iteration }}</span>
                                        <div>
                                            <strong>{{ $part->name }}</strong>
                                            @if($part->standard_description)
                                                <br><span class="standard-desc">{{ $part->standard_description }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- 4-level radio: Baik / Cukup / Kurang / Tidak --}}
                                <div class="col-auto">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <input type="radio" name="items[{{ $part->id }}][status]" value="baik"
                                               id="baik_{{ $part->id }}" class="scale-toggle scale-baik"
                                               {{ old("items.{$part->id}.status") == 'baik' ? 'checked' : '' }}>
                                        <label for="baik_{{ $part->id }}">Baik</label>

                                        <input type="radio" name="items[{{ $part->id }}][status]" value="cukup"
                                               id="cukup_{{ $part->id }}" class="scale-toggle scale-cukup"
                                               {{ old("items.{$part->id}.status") == 'cukup' ? 'checked' : '' }}>
                                        <label for="cukup_{{ $part->id }}">Cukup</label>

                                        <input type="radio" name="items[{{ $part->id }}][status]" value="kurang"
                                               id="kurang_{{ $part->id }}" class="scale-toggle scale-kurang"
                                               {{ old("items.{$part->id}.status") == 'kurang' ? 'checked' : '' }}>
                                        <label for="kurang_{{ $part->id }}">Kurang</label>

                                        <input type="radio" name="items[{{ $part->id }}][status]" value="tidak"
                                               id="tidak_{{ $part->id }}" class="scale-toggle scale-tidak"
                                               {{ old("items.{$part->id}.status") == 'tidak' ? 'checked' : '' }}>
                                        <label for="tidak_{{ $part->id }}">Tidak</label>
                                    </div>
                                </div>

                                {{-- Ganti --}}
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                                        <input type="checkbox" name="items[{{ $part->id }}][needs_replacement]" value="1"
                                               class="form-check-input replacement-check" id="replace_{{ $part->id }}"
                                               {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                                        <label for="replace_{{ $part->id }}" class="form-check-label small">Ganti</label>
                                    </div>
                                </div>

                                {{-- Keterangan (full width di mobile) --}}
                                <div class="col-12 col-md">
                                    <textarea name="items[{{ $part->id }}][notes]" class="item-notes"
                                              rows="1" placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                                </div>

                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SECTION 3: DOKUMENTASI FOTO KERUSAKAN           --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="section" style="margin-top: -10px;">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-3">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0 pb-0">
                                <i class="bi bi-camera me-1"></i> Dokumentasi Kerusakan
                                <small class="text-muted fw-normal">(Opsional)</small>
                            </h5>
                        </div>

                        <div class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Foto bagian kendaraan yang rusak atau perlu perhatian. Disarankan jika ada item berstatus <strong>Kurang</strong> atau <strong>Tidak</strong>.
                        </div>

                        {{-- Container repeatable cards (responsive grid) --}}
                        <div id="damageContainer" class="row g-3">
                            {{-- Cards will be added here by JS --}}
                        </div>

                        {{-- Empty state --}}
                        <div id="damageEmpty" class="text-center py-4">
                            <i class="bi bi-camera text-muted fs-1"></i>
                            <p class="text-muted mt-2 mb-0">Belum ada foto kerusakan</p>
                        </div>

                        {{-- Add button — always visible at bottom --}}
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary w-100" id="btnAddDamage">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Foto Kerusakan
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- STICKY SUBMIT BAR                               --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <div class="position-sticky bottom-0 bg-white py-3 border-top shadow-sm" style="z-index: 100;">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted small" id="checkedCount">0 item dicek</span>
                    <span class="text-muted small ms-2" id="photoCount" style="display: none;">
                        <i class="bi bi-camera"></i> <span id="photoCountNum">0</span> foto
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dispatcher.body-check.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i> Submit Pengecekan
                    </button>
                </div>
            </div>
        </div>
    </div>

</form>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TEMPLATE: Damage Photo Card (vertical stacked, cloned by JS) --}}
{{-- Layout: foto → delete → dropdown zona → notes              --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<template id="damageRowTemplate">
    {{-- col-12 col-sm-6 col-lg-4 → 1 per baris di mobile, 2 di tablet, 3 di desktop --}}
    <div class="col-12 col-sm-6 col-lg-4" data-row-index="__INDEX__">
        <div class="damage-row h-100">

            {{-- 1. FOTO — full-width, tap untuk ambil/ganti foto --}}
            <div class="damage-photo-preview" id="preview___INDEX__">
                <div class="preview-placeholder">
                    <i class="bi bi-camera"></i>
                    <span>Tap untuk ambil foto</span>
                </div>
                {{-- Invisible label overlay so tapping anywhere on preview opens camera --}}
                <label for="camera___INDEX__" class="photo-tap-area"></label>
            </div>

            {{-- Hidden file input --}}
            <input type="file" name="damage_photos[__INDEX__][photo]" id="camera___INDEX__"
                   class="camera-input" accept="image/*" capture="environment"
                   onchange="previewDamagePhoto(this, __INDEX__)">

            <div class="damage-body d-flex flex-column gap-2">

                {{-- 2. TOMBOL DELETE --}}
                <button type="button"
                        class="btn btn-outline-danger btn-sm w-100"
                        onclick="removeDamageRow(__INDEX__)">
                    <i class="bi bi-trash me-1"></i> Hapus Foto Ini
                </button>

                {{-- 3. DROPDOWN ZONA --}}
                <select name="damage_photos[__INDEX__][zone]" class="form-select form-select-sm">
                    <option value="depan">Tampak Depan</option>
                    <option value="belakang">Tampak Belakang</option>
                    <option value="kiri">Tampak Kiri</option>
                    <option value="kanan">Tampak Kanan</option>
                    <option value="atas">Tampak Atas</option>
                    <option value="bawah">Tampak Bawah</option>
                    <option value="interior">Interior</option>
                </select>

                {{-- 4. NOTES / DESKRIPSI KERUSAKAN --}}
                <input type="text"
                       name="damage_photos[__INDEX__][description]"
                       class="form-control form-control-sm"
                       placeholder="Deskripsi kerusakan...">

            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {

    let damageRowIndex = 0;

    // ─── Add Damage Photo Card ───
    window.addDamageRow = function() {
        const template = document.getElementById('damageRowTemplate');
        const container = document.getElementById('damageContainer');
        const emptyState = document.getElementById('damageEmpty');

        const html = template.innerHTML.replace(/__INDEX__/g, damageRowIndex);
        container.insertAdjacentHTML('beforeend', html);
        damageRowIndex++;

        emptyState.style.display = 'none';
        updatePhotoCount();
    };

    // ─── Remove Damage Photo Card ───
    window.removeDamageRow = function(index) {
        const col = document.querySelector('[data-row-index="' + index + '"]');
        if (col) {
            col.remove();
            const container = document.getElementById('damageContainer');
            const emptyState = document.getElementById('damageEmpty');
            if (container.children.length === 0) {
                emptyState.style.display = 'block';
            }
            updatePhotoCount();
        }
    };

    // ─── Preview Photo ───
    window.previewDamagePhoto = function(input, index) {
        const preview = document.getElementById('preview_' + index);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML =
                    '<img src="' + e.target.result + '" alt="Preview">' +
                    '<label for="camera_' + index + '" class="photo-tap-area"></label>';
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // ─── Button listeners ───
    document.getElementById('btnAddDamage').addEventListener('click', addDamageRow);

    // ─── Ganti checkbox → auto-set Tidak + disable radios ───
    document.querySelectorAll('.replacement-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const partId = this.name.match(/items\[(\d+)\]/)[1];
            const radios = document.querySelectorAll('input[name="items[' + partId + '][status]"]');

            if (this.checked) {
                radios.forEach(r => {
                    r.checked = (r.value === 'tidak');
                    r.disabled = true;
                    r.nextElementSibling.style.opacity = '0.4';
                    r.nextElementSibling.style.pointerEvents = 'none';
                });
            } else {
                radios.forEach(r => {
                    r.disabled = false;
                    r.nextElementSibling.style.opacity = '1';
                    r.nextElementSibling.style.pointerEvents = 'auto';
                });
            }
            updateCheckedCount();
        });

        if (checkbox.checked) checkbox.dispatchEvent(new Event('change'));
    });

    // ─── Live Counts ───
    function updateCheckedCount() {
        const checked = document.querySelectorAll('.scale-toggle:checked').length;
        document.getElementById('checkedCount').textContent = checked + ' item dicek';
    }

    function updatePhotoCount() {
        const count = document.getElementById('damageContainer').children.length;
        const el = document.getElementById('photoCount');
        const num = document.getElementById('photoCountNum');
        if (count > 0) {
            el.style.display = 'inline';
            num.textContent = count;
        } else {
            el.style.display = 'none';
        }
    }

    document.querySelectorAll('.scale-toggle').forEach(radio => {
        radio.addEventListener('change', updateCheckedCount);
    });

    updateCheckedCount();

    // ─── Auto-suggest: jika ada item Kurang/Tidak, highlight tombol tambah foto ───
    document.querySelectorAll('.scale-toggle').forEach(radio => {
        radio.addEventListener('change', function() {
            const kurangTidak = document.querySelectorAll('.scale-kurang:checked, .scale-tidak:checked');
            const container = document.getElementById('damageContainer');
            const btnAdd = document.getElementById('btnAddDamage');
            if (kurangTidak.length > 0 && container.children.length === 0) {
                btnAdd.classList.add('btn-primary');
                btnAdd.classList.remove('btn-outline-primary');
                setTimeout(() => {
                    btnAdd.classList.remove('btn-primary');
                    btnAdd.classList.add('btn-outline-primary');
                }, 3000);
            }
        });
    });

<<<<<<< HEAD
    // ─── Double Submit Prevention ───
    document.getElementById('bodyCheckForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');

        // ─── Fix: Ensure all statuses set before submit ───
        document.querySelectorAll('.replacement-check:checked').forEach(cb => {
            const partId = cb.name.match(/items\\[(\\d+)\\]/)[1];
            const ngRadio = document.getElementById('tidak_' + partId);
            if (ngRadio && !ngRadio.checked) {
                ngRadio.checked = true;
            }
        });
=======
    // ─── Validation + Double Submit Prevention ───
    document.getElementById('bodyCheckForm').addEventListener('submit', function(e) {
        // Re-enable disabled radios first so they get submitted
>>>>>>> cf7ec66bad37cdb7beaf8f14d55764c062ddf59e
        document.querySelectorAll('.scale-toggle:disabled').forEach(r => {
            r.disabled = false;
        });

        // Check all items have a status selected
        const allItems = document.querySelectorAll('.check-item-row');
        let uncheckedItems = [];
        allItems.forEach((row, idx) => {
            const radios = row.querySelectorAll('.scale-toggle');
            const anyChecked = Array.from(radios).some(r => r.checked);
            if (!anyChecked) {
                uncheckedItems.push(idx + 1);
            }
        });

        if (uncheckedItems.length > 0) {
            e.preventDefault();
            alert('Semua item harus diisi status-nya. Item belum dicek: ' + uncheckedItems.join(', '));
            return false;
        }

        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    });

});
</script>

@endsection
