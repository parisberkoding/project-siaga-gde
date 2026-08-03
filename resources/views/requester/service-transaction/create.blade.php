@extends('templates.main')

@section('title', 'Lapor Transaksi Service')

@section('content')

<style>
    .info-bar {
        background: #f0f2ff;
        border: 1px solid #4154f1;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }

    .cost-row {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px dashed #e9ecef;
    }
    .cost-row:last-child { border-bottom: none; }
    .cost-row .remove-btn {
        background: none; border: none;
        color: #dc3545; font-size: 18px;
        cursor: pointer; padding: 4px;
        line-height: 1;
    }
    .cost-row .remove-btn:hover { color: #a71d2a; }

    .total-bar {
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .total-bar .total-amount {
        font-size: 22px;
        font-weight: 700;
        color: #012970;
    }

    .photo-preview {
        width: 100%;
        max-height: 200px;
        object-fit: contain;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
        background: #f8f9fa;
        display: none;
    }
    .photo-preview.has-image {
        display: block;
        border-style: solid;
        border-color: #198754;
    }

    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 30px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .upload-area:hover { border-color: #4154f1; background: #f8f9ff; }
    .upload-area.has-file { border-color: #198754; background: #f0fdf4; }

    .sticky-submit {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 12px 0;
        border-top: 1px solid #dee2e6;
        z-index: 10;
    }
</style>

<div class="pagetitle">
    <h1>Lapor Transaksi Service</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('requester.replacement-schedule.index') }}">Jadwal</a></li>
            <li class="breadcrumb-item active">Lapor Transaksi</li>
        </ol>
    </nav>
</div>

{{-- Error --}}
@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- VPR Info Bar --}}
<div class="info-bar">
    <div class="row align-items-center g-2">
        <div class="col-md-3 col-6">
            <small class="text-muted">Kode VPR</small>
            <br><strong style="color: #4154f1;">{{ $replacement->vpr_code }}</strong>
        </div>
        <div class="col-md-3 col-6">
            <small class="text-muted">Kendaraan</small>
            <br><strong>{{ $replacement->nopol }}</strong>
        </div>
        <div class="col-md-3 col-6">
            <small class="text-muted">Item</small>
            <br><strong>{{ $replacement->replacementDetails->count() }} item</strong>
        </div>
        <div class="col-md-3 col-6">
            <small class="text-muted">Deadline</small>
            <br>
            @php
                $trxRecord    = $replacement->transaction;
                $deadlineDate = $trxRecord?->deadline_date ?? now()->addDays(3);
                $daysLeft     = now()->startOfDay()->diffInDays($deadlineDate, false);
            @endphp
            <strong class="{{ $daysLeft < 0 ? 'text-danger' : ($daysLeft <= 1 ? 'text-warning' : '') }}">
                {{ $deadlineDate->format('d/m/Y') }}
                @if($daysLeft < 0)
                    (Overdue!)
                @elseif($daysLeft == 0)
                    (Hari ini!)
                @else
                    ({{ $daysLeft }} hari)
                @endif
            </strong>
        </div>
    </div>
</div>

<form action="{{ route('requester.service-transaction.store', $replacement->vpr_code) }}"
      method="POST" enctype="multipart/form-data" id="transactionForm">
    @csrf

    <section class="section">
        <div class="row">

            {{-- ═══════════════════════════════ --}}
            {{-- LEFT: Form                      --}}
            {{-- ═══════════════════════════════ --}}
            <div class="col-lg-7">

                {{-- Service Info --}}
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Informasi Service</h5>

                        <div class="row g-3">
                            <div class="col-md-6 col-12">
                                <label class="form-label fw-semibold">
                                    Lokasi Service / Bengkel <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="service_location" class="form-control"
                                       value="{{ old('service_location', $replacement->service_location ?? '') }}"
                                       placeholder="Nama bengkel / alamat..." required>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label fw-semibold">
                                    Tanggal Service <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="service_date" class="form-control"
                                       value="{{ old('service_date', now()->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cost Details --}}
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">
                            Rincian Biaya
                            <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="addCostRow()">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Item
                            </button>
                        </h5>

                        <div id="costContainer">
                            {{-- Pre-fill dari replacement items --}}
                            @foreach($replacement->replacementDetails as $idx => $item)
                            <div class="cost-row" data-idx="{{ $idx }}">
                                <div class="flex-grow-1">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-5">
                                            <input type="text" name="costs[{{ $idx }}][item_name]"
                                                   class="form-control form-control-sm"
                                                   value="{{ old("costs.{$idx}.item_name", $item->item_name) }}"
                                                   placeholder="Nama item" required>
                                        </div>
                                        <div class="col-4 col-md-2">
                                            <input type="number" name="costs[{{ $idx }}][quantity]"
                                                   class="form-control form-control-sm qty-input"
                                                   value="{{ old("costs.{$idx}.quantity", 1) }}"
                                                   min="1" placeholder="Qty" required>
                                        </div>
                                        <div class="col-8 col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="costs[{{ $idx }}][unit_price]"
                                                       class="form-control price-input"
                                                       value="{{ old("costs.{$idx}.unit_price", 0) }}"
                                                       min="0" placeholder="Harga" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-1 d-flex align-items-center">
                                            @if($loop->count > 1)
                                            <button type="button" class="remove-btn" onclick="removeCostRow(this)" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Total --}}
                        <div class="total-bar mt-3">
                            <div>
                                <strong>Grand Total</strong>
                                <br><small class="text-muted" id="itemCountLabel">{{ $replacement->replacementDetails->count() }} item</small>
                            </div>
                            <div class="total-amount" id="grandTotal">Rp 0</div>
                        </div>
                        {{-- Hidden field untuk total — diisi JS --}}
                        <input type="hidden" name="total_amount" id="totalAmountInput" value="0">
                    </div>
                </div>

            </div>

            {{-- ═══════════════════════════════ --}}
            {{-- RIGHT: Photo + Submit           --}}
            {{-- ═══════════════════════════════ --}}
            <div class="col-lg-5">

                {{-- Photo Upload --}}
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">
                            <i class="bi bi-camera me-1"></i>
                            Bukti Struk / Nota <span class="text-danger">*</span>
                        </h5>

                        <div class="upload-area" id="uploadArea"
                             onclick="document.getElementById('proofPhoto').click()">
                            <i class="bi bi-camera fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Tap untuk foto struk/nota</p>
                            <small class="text-muted">Format: JPG/PNG, maks 5MB</small>
                        </div>

                        <img id="photoPreview" class="photo-preview mt-2" alt="Preview">

                        <input type="file" name="proof_photo" id="proofPhoto"
                               accept="image/*" capture="environment"
                               class="d-none" required
                               onchange="previewPhoto(this)">

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary w-100 mt-2 d-none"
                                id="changePhotoBtn"
                                onclick="document.getElementById('proofPhoto').click()">
                            <i class="bi bi-arrow-repeat me-1"></i> Ganti Foto
                        </button>
                    </div>
                </div>

                {{-- Items Reference --}}
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Item yang Diganti</h5>
                        @foreach($replacement->replacementDetails as $item)
                        <div class="d-flex align-items-center gap-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
                            <span style="font-size: 13px;">{{ $item->item_name }}</span>
                            @php
                                $condColor = match($item->condition_status) {
                                    'ok','baik'  => 'bg-success',
                                    'cukup'      => 'bg-info',
                                    'kurang'     => 'bg-warning text-dark',
                                    'ng','tidak' => 'bg-danger',
                                    default      => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $condColor }}" style="font-size: 10px; margin-left: auto;">
                                {{ ucfirst($item->condition_status) }}
                            </span>
                        </div>
                        @endforeach

                        @if(isset($item) && ($item->dispatcher_notes ?? false))
                        <small class="text-muted fst-italic d-block mt-2">
                            <i class="bi bi-chat-left-text me-1"></i> Catatan dispatcher: {{ $item->dispatcher_notes }}
                        </small>
                        @endif
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="card">
                    <div class="card-body pt-3">
                        <label class="form-label fw-semibold">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Catatan tambahan tentang service...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="sticky-submit">
                    <button type="submit" class="btn btn-success w-100 py-2" id="submitBtn">
                        <i class="bi bi-send me-1"></i> Kirim Laporan Service
                    </button>
                    <a href="{{ route('requester.replacement-schedule.index') }}"
                       class="btn btn-outline-secondary w-100 mt-2">
                        Batal
                    </a>
                </div>

            </div>
        </div>
    </section>
</form>

{{-- Cost Row Template --}}
<template id="costRowTemplate">
    <div class="cost-row" data-idx="__IDX__">
        <div class="flex-grow-1">
            <div class="row g-2">
                <div class="col-12 col-md-5">
                    <input type="text" name="costs[__IDX__][item_name]"
                           class="form-control form-control-sm"
                           placeholder="Nama item / jasa" required>
                </div>
                <div class="col-4 col-md-2">
                    <input type="number" name="costs[__IDX__][quantity]"
                           class="form-control form-control-sm qty-input"
                           value="1" min="1" placeholder="Qty" required>
                </div>
                <div class="col-8 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="costs[__IDX__][unit_price]"
                               class="form-control price-input"
                               value="0" min="0" placeholder="Harga" required>
                    </div>
                </div>
                <div class="col-12 col-md-1 d-flex align-items-center">
                    <button type="button" class="remove-btn" onclick="removeCostRow(this)" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let costIdx = {{ $replacement->replacementDetails->count() }};

// ─── Recalculate total ───────────────────────────────────────────
function recalculate() {
    let total = 0;
    let count = 0;

    document.querySelectorAll('#costContainer .cost-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input')?.value)   || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        total += qty * price;
        count++;
    });

    document.getElementById('grandTotal').textContent =
        'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('totalAmountInput').value = total;
    document.getElementById('itemCountLabel').textContent = count + ' item';
}

// ─── Add / Remove rows ───────────────────────────────────────────
function addCostRow() {
    const html = document.getElementById('costRowTemplate').innerHTML
                         .replaceAll('__IDX__', costIdx);
    document.getElementById('costContainer').insertAdjacentHTML('beforeend', html);

    // Attach input listeners to newly added row
    const newRow = document.querySelector(`[data-idx="${costIdx}"]`);
    attachRowListeners(newRow);
    costIdx++;
    recalculate();
}

function removeCostRow(btn) {
    btn.closest('.cost-row').remove();
    recalculate();
}

// ─── Attach listeners to a row ───────────────────────────────────
function attachRowListeners(row) {
    row.querySelectorAll('.qty-input, .price-input').forEach(input => {
        input.addEventListener('input', recalculate);
    });
}

// ─── Photo preview ───────────────────────────────────────────────
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photoPreview');
            preview.src = e.target.result;
            preview.classList.add('has-image');
            document.getElementById('uploadArea').style.display = 'none';
            document.getElementById('changePhotoBtn').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ─── Submit validation ───────────────────────────────────────────
document.getElementById('transactionForm').addEventListener('submit', function(e) {
    const total = parseFloat(document.getElementById('totalAmountInput').value || 0);
    const photo = document.getElementById('proofPhoto').files.length;

    if (total <= 0) {
        e.preventDefault();
        alert('Total biaya harus lebih dari Rp 0. Isi harga pada rincian biaya terlebih dahulu.');
        return;
    }
    if (photo === 0) {
        e.preventDefault();
        alert('Foto bukti struk/nota wajib diupload.');
        return;
    }

    // Disable button supaya tidak double submit
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';
});

// ─── Init on DOM ready ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Attach listeners ke semua pre-filled rows
    document.querySelectorAll('#costContainer .cost-row').forEach(attachRowListeners);

    // Hitung total awal (pre-filled items mungkin sudah ada value dari old())
    recalculate();
});
</script>

@endsection
