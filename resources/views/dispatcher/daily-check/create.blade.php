@extends('templates.main')

@section('title', 'Pengecekan Harian')

@section('content')

<style>
    .check-item-row {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
        transition: background 0.15s;
    }
    .check-item-row:hover { background: #f8f9fa; }
    .check-item-row:last-child { border-bottom: none; }

    .status-toggle { display: none; }
    .status-toggle + label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        height: 42px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.15s;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    .status-toggle-ok:checked + label  { background: #198754; border-color: #198754; color: #fff; }
    .status-toggle-ng:checked + label  { background: #dc3545; border-color: #dc3545; color: #fff; }

    .category-header {
        background: linear-gradient(135deg, #012970 0%, #1a3a8a 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
    }
    .standard-desc { font-size: 12px; color: #6c757d; font-style: italic; }

    .item-notes {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 13px;
        width: 100%;
        resize: none;
    }
    .item-notes:focus {
        border-color: #4154f1;
        box-shadow: 0 0 0 0.15rem rgba(65,84,241,.1);
        outline: none;
    }

    @media (max-width: 576px) {
        .status-toggle + label { min-width: 48px; height: 38px; font-size: 14px; }
    }
</style>

<div class="pagetitle">
    <h1>Pengecekan Harian</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.daily-check.index') }}">Pengecekan Harian</a></li>
            <li class="breadcrumb-item active">Form Pengecekan</li>
        </ol>
    </nav>
</div>

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

<form action="{{ route('dispatcher.daily-check.store') }}" method="POST" id="dailyCheckForm">
    @csrf

    {{-- Header: Kendaraan & Driver --}}
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-3">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label for="vehicle_id" class="form-label fw-semibold">
                                    <i class="bi bi-truck me-1"></i> Kendaraan <span class="text-danger">*</span>
                                </label>
                                <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                    <option value="">— Pilih Kendaraan —</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                            {{ old('vehicle_id', request('vehicle_id')) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} — {{ $vehicle->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="current_driver_id" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i> Driver <span class="text-danger">*</span>
                                </label>
                                <select name="current_driver_id" id="current_driver_id" class="form-select" required>
                                    <option value="">— Pilih Driver —</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ old('current_driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('current_driver_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="notes" class="form-label fw-semibold">
                                    <i class="bi bi-chat-left-text me-1"></i> Catatan Umum
                                </label>
                                <input type="text" name="notes" id="notes" class="form-control"
                                       placeholder="Opsional..." value="{{ old('notes') }}">
                            </div>

                        </div>

                        {{-- Badge: tidak ada jadwal harian --}}
                        <div class="mt-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-calendar-day me-1"></i>
                                Pengecekan Harian — {{ now()->translatedFormat('d F Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Checklist Items (partial) --}}
    <section class="section" style="margin-top: -10px;">
        <div class="row">
            <div class="col-12">
                @include('dispatcher.daily-check._checklist', ['parts' => $parts])
            </div>
        </div>
    </section>

    {{-- Submit Bar (sticky) --}}
    <div class="position-sticky bottom-0 bg-white py-3 border-top shadow-sm" style="z-index: 100;">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted small" id="checkedCount">0 item dicek</span>
                    <span class="text-danger small ms-2" id="ngCount" style="display:none;">
                        <i class="bi bi-exclamation-circle"></i> <span id="ngCountNum">0</span> item bermasalah
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dispatcher.daily-check.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i> Submit Pengecekan Harian
                    </button>
                </div>
            </div>
        </div>
    </div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function updateCounts() {
        const checked = document.querySelectorAll('.status-toggle:checked').length;
        const ng = document.querySelectorAll('.status-toggle-ng:checked').length;

        document.getElementById('checkedCount').textContent = checked + ' item dicek';

        const ngEl = document.getElementById('ngCount');
        if (ng > 0) {
            ngEl.style.display = 'inline';
            document.getElementById('ngCountNum').textContent = ng;
        } else {
            ngEl.style.display = 'none';
        }
    }

    document.querySelectorAll('.status-toggle').forEach(r => r.addEventListener('change', updateCounts));
    updateCounts();

    // Centang "Ganti" → auto-set NG & lock status buttons
    document.querySelectorAll('.replacement-check').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const partId = this.name.match(/items\[(\d+)\]/)[1];
            const okRadio = document.getElementById('ok_' + partId);
            const ngRadio = document.getElementById('ng_' + partId);

            if (this.checked) {
                ngRadio.checked = true;
                okRadio.checked = false;
                okRadio.disabled = true;
                ngRadio.disabled = true;
                okRadio.nextElementSibling.style.opacity = '0.4';
                ngRadio.nextElementSibling.style.opacity = '0.4';
            } else {
                okRadio.disabled = false;
                ngRadio.disabled = false;
                okRadio.nextElementSibling.style.opacity = '1';
                ngRadio.nextElementSibling.style.opacity = '1';
            }
            updateCounts();
        });

        if (checkbox.checked) checkbox.dispatchEvent(new Event('change'));
    });

    // Validation + Cegah double-submit
    document.getElementById('dailyCheckForm').addEventListener('submit', function (e) {
        // Re-enable disabled radios so they get submitted
        document.querySelectorAll('.status-toggle:disabled').forEach(r => {
            r.disabled = false;
        });

        // Check all items have a status selected
        const allItems = document.querySelectorAll('.check-item-row');
        let uncheckedItems = [];
        allItems.forEach((row, idx) => {
            const radios = row.querySelectorAll('.status-toggle');
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
