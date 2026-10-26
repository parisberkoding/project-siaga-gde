@extends('templates.main')

@section('title', $title)

@section('content')
<style>
    /* ===== INFO CARDS ===== */
    .info-card { padding-bottom: 10px; transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .info-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(13, 44, 114, 0.12) !important; }
    .info-card h6 { font-size: 28px; color: #093D7D; font-weight: 700; margin: 0; padding: 0; }
    .card-icon {
        font-size: 32px; line-height: 0; width: 64px; height: 64px;
        flex-shrink: 0; flex-grow: 0; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .sales-card .card-icon        { color: #0B50A1; background: #EAF1FA; }
    .revenue-card .card-icon      { color: #4A7A1C; background: #F2F9E8; }
    .customers-card .card-icon    { color: #B35B00; background: #ffecdf; }
    .transactions-card .card-icon { color: #6D28D9; background: #f3e8ff; }
    .danger-card .card-icon       { color: #C4142A; background: #fbe4e6; }
    .warning-card .card-icon      { color: #B35B00; background: #ffecdf; }

    .card { border-radius: var(--gde-r-lg); }
    .card-header { border-radius: 10px 10px 0 0 !important; }
    .border-dashed { border: 2px dashed #E4E9F0; }
</style>

<div class="pagetitle">
    <h1>Dashboard Permohonan BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item active">Permohonan BBM</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    {{-- Alert Container --}}
    <div id="alertContainer">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <!-- {{-- Stat Cards --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Permohonan</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $totalRequests }}</h6>
                            <span class="text-muted small pt-2">Semua status</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Menunggu</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pendingCount }}</h6>
                            <span class="text-muted small pt-2">Pending / Menunggu vehicle</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $approvedCount }}</h6>
                            <span class="text-muted small pt-2">Approved aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $completedCount }}</h6>
                            <span class="text-muted small pt-2">
                                @if($totalWithProof > 0)
                                    On-time {{ $onTimeRate }}%
                                @else
                                    Menunggu laporan
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    {{-- Form Permohonan BBM --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Form Permohonan BBM Baru</h5>
                </div>
                <div class="card-body pt-4">
                    @if($activeCount >= 5)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Slot permohonan Anda sudah penuh ({{ $activeCount }}/5). Silakan selesaikan permohonan yang ada terlebih dahulu.
                    </div>
                    @else
                    <form action="{{ route('requester-create') }}" method="POST" id="requestForm">
                        @csrf

                        {{--
                        ============================================================
                        OPSI "Tambah Kendaraan/Alat Baru" DINONAKTIFKAN sementara.
                        Untuk mengaktifkan kembali: hapus tanda komentar pada blok
                        toggle di bawah, lalu batalkan komentar pada id
                        "new_vehicle" & "newVehicleSection" di akhir file ini.
                        ============================================================

                        <div class="row">
                            <div class="col-12 mt-3 mb-3">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="vehicle_option" id="existing_vehicle" value="existing" checked>
                                    <label class="btn btn-outline-primary" for="existing_vehicle">
                                        <i class="bi bi-list-ul me-1"></i> Siap Guna
                                    </label>

                                    <input type="radio" class="btn-check" name="vehicle_option" id="new_vehicle" value="new">
                                    <label class="btn btn-outline-success" for="new_vehicle">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Kendaraan/Alat Baru
                                    </label>
                                </div>
                            </div>
                        </div>
                        --}}

                        {{-- Form hanya memakai kendaraan siap guna (opsi Tambah Kendaraan/Alat Baru sementara dinonaktifkan) --}}
                        <input type="hidden" name="vehicle_option" value="existing">

                        {{-- Section: Pilih Kendaraan Siap Guna --}}
                        <div class="row">
                            <div class="col-md-6 mb-3 position-relative">
                                <label for="vehicle_search" class="form-label">Kendaraan / Alat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('vehicle_id') is-invalid @enderror"
                                       id="vehicle_search" name="vehicle_search"
                                       placeholder="Ketik kode / plat / nama kendaraan…"
                                       autocomplete="off"
                                       value="{{ $selectedVehicleLabel ?? '' }}">
                                <input type="hidden" id="vehicle_id" name="vehicle_id"
                                       value="{{ old('vehicle_id') }}">
                                <div id="vehicleSuggestions" class="list-group position-absolute w-100 shadow-sm d-none"
                                     style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
                                <div class="form-text" id="vehicleBudgetInfo"></div>
                                @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="gasoline_type" class="form-label">Jenis BBM <span class="text-danger">*</span></label>
                                <select class="form-select @error('gasoline_type') is-invalid @enderror"
                                        id="gasoline_type" name="gasoline_type" required>
                                    <option value="">Pilih Jenis BBM</option>
                                    @forelse($gasolineTypes as $gasoline)
                                        <option value="{{ $gasoline->gasoline_name }}" {{ old('gasoline_type') == $gasoline->gasoline_name ? 'selected' : '' }}>{{ $gasoline->gasoline_name }}</option>
                                    @empty
                                        <option value="Pertamax" {{ old('gasoline_type') == 'Pertamax' ? 'selected' : '' }}>Pertamax</option>
                                        <option value="Pertamina Dex" {{ old('gasoline_type') == 'Pertamina Dex' ? 'selected' : '' }}>Pertamina Dex</option>
                                    @endforelse
                                </select>
                                @error('gasoline_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bill_payment" class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                                <select class="form-select @error('bill_payment') is-invalid @enderror"
                                        id="bill_payment" name="bill_payment" required>
                                    <option value="">-- Pilih Nominal --</option>
                                </select>
                                <div class="form-text" id="billPaymentInfo"></div>
                                @error('bill_payment')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="requester_notes" class="form-label">Catatan Permohonan (Opsional)</label>
                                <textarea class="form-control @error('requester_notes') is-invalid @enderror"
                                        id="requester_notes" name="requester_notes" rows="2"
                                        placeholder="Catatan tambahan untuk permohonan...">{{ old('requester_notes') }}</textarea>
                                @error('requester_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- newVehicleSection (dinonaktifkan, diaktifkan kembali bersama toggle di atas) --}}
                        {{-- Uncomment blok bawah ini bersama toggle di atas untuk mengaktifkan opsi Tambah Kendaraan/Alat Baru:
                        <div id="newVehicleSection" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>permohonan dengan Kendaraan atau Alat Rental baru </strong> memerlukan persetujuan dari Supervisor General Affairs Langsung sebelum dapat diproses lebih lanjut.
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_vehicle_type" class="form-label">Tipe <span class="text-danger">*</span></label>
                                    <select class="form-select @error('new_vehicle_type') is-invalid @enderror"
                                            id="new_vehicle_type" name="new_vehicle_type">
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="KENDARAAN">Kendaraan</option>
                                        <option value="ALAT">Alat</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="new_vehicle_code" class="form-label">Kode/Plat Nomor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="new_vehicle_code" name="new_vehicle_code"
                                        placeholder="Contoh: B 1234 XYZ">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="new_vehicle_name" class="form-label">Nama/Merk <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="new_vehicle_name" name="new_vehicle_name"
                                        placeholder="Contoh: Toyota Avanza">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="new_vehicle_notes" class="form-label">Catatan Kendaraan (Opsional)</label>
                                    <textarea class="form-control" id="new_vehicle_notes" name="new_vehicle_notes" rows="2"
                                        placeholder="Contoh: Mobil rental dari vendor X"></textarea>
                                </div>
                            </div>
                        </div>
                        --}}

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-send me-1"></i> Ajukan Permohonan Baru
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Slot Permohonan Aktif --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Slot Permohonan Aktif ({{ $activeCount }}/5)
                    </h5>
                    <a href="{{ route('requester-history') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-clock-history"></i> Lihat Riwayat
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @for($i = 0; $i < 5; $i++)
                            @if(isset($activeSlots[$i]))
                                @php $request = $activeSlots[$i]; @endphp
                                <div class="col-md-6 col-lg-4 mb-3">
                                    @if($request->status === 'pending' || $request->status === 'pending_vehicle_approval')
                                        @include('requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
                                    @elseif($request->status === 'approved')
                                        @include('requester.partials.slot-approved', ['request' => $request, 'slotNum' => $i + 1])
                                    @endif
                                </div>
                            @else
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-dashed" style="min-height: 300px;">
                                        <div class="card-body text-center d-flex flex-column justify-content-center">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="text-muted mt-3 mb-0">Slot {{ $i + 1 }} - Kosong</p>
                                            <small class="text-muted">Siap untuk permohonan baru</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vehicleSearch = document.getElementById('vehicle_search');
    const vehicleId     = document.getElementById('vehicle_id');
    const suggestions   = document.getElementById('vehicleSuggestions');
    const billPayment   = document.getElementById('bill_payment');

    const fallbackOptions = @json($fallbackOptions);

    // Data kendaraan siap guna untuk pencarian & isi ulang
    const vehicles = @json($vehicleOptions);

    const rupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);

    function populateBillPayment(min, max) {
        const infoEl = document.getElementById('billPaymentInfo');
        if (!infoEl) return;

        if (!min || !max || min > max) {
            billPayment.innerHTML = '<option value="">-- Pilih Nominal --</option>';
            infoEl.textContent = 'Nominal belum tersedia. Hubungi tim IT untuk melengkapi data kendaraan.';
            return;
        }

        const prevValue = billPayment.value;
        let html = '<option value="">-- Pilih Nominal --</option>';
        for (let value = min; value <= max; value += 50000) {
            html += '<option value="' + value + '">' + rupiah(value) + '</option>';
        }
        billPayment.innerHTML = html;

        if (prevValue >= min && prevValue <= max) {
            billPayment.value = prevValue;
        }
    }

    function renderSuggestions(items) {
        suggestions.innerHTML = '';
        if (!items.length) {
            suggestions.classList.add('d-none');
            return;
        }

        items.forEach((item) => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action py-2';
            a.innerHTML = `<strong>${escapeHtml(item.code)}</strong>` +
                `<br><small class="text-muted">${escapeHtml(item.name)} (${escapeHtml(item.type)})</small>`;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                vehicleSearch.value = item.label;
                vehicleId.value = item.id;
                suggestions.classList.add('d-none');
                updateVehicleInfo(item);
            });
            suggestions.appendChild(a);
        });

        suggestions.classList.remove('d-none');
    }

    function updateVehicleInfo(vehicle) {
        const infoEl = document.getElementById('vehicleBudgetInfo');
        const fuelSelect = document.getElementById('gasoline_type');
        if (!infoEl || !fuelSelect) return;

        if (!vehicle) {
            vehicleId.value = '';
            infoEl.textContent = 'Ketik untuk memilih kendaraan / alat.';
            populateBillPayment(fallbackOptions[0], fallbackOptions[fallbackOptions.length - 1]);
            fuelSelect.value = '';
            return;
        }

        const benchmark = vehicle.benchmark ? parseInt(vehicle.benchmark) : null;
        const range = vehicle.range && vehicle.range.min && vehicle.range.max
            ? vehicle.range
            : null;
        const fuel = vehicle.fuel || '';
        const tank = vehicle.tank ? parseFloat(vehicle.tank) : null;

        if (range) {
            populateBillPayment(range.min, range.max);
        } else {
            populateBillPayment(fallbackOptions[0], fallbackOptions[fallbackOptions.length - 1]);
        }

        // Auto-fill Jenis BBM sesuai master kendaraan (case-insensitive)
        if (fuel) {
            let matched = false;
            for (const optEl of fuelSelect.options) {
                if (optEl.value && optEl.value.toLowerCase() === fuel.toLowerCase()) {
                    fuelSelect.value = optEl.value;
                    matched = true;
                    break;
                }
            }
            if (!matched && !fuelSelect.value) {
                fuelSelect.value = '';
            }
        }

        // Auto-fill Nominal sesuai benchmark (kelipatan 50.000 sudah pasti ada di opsi)
        if (benchmark && range && benchmark >= range.min && benchmark <= range.max) {
            billPayment.value = benchmark;
        }

        let html = '';
        if (benchmark) {
            html += '<i class="bi bi-fuel-pump me-1"></i> Benchmark BBM: <strong>' + rupiah(benchmark) + '</strong>';
            if (tank) html += ' <span class="text-muted">(tangki ' + tank + ' L)</span>';
            if (fuel) html += ' <span class="text-muted">· ' + fuel + '</span>';
        } else {
            html = '<span class="text-muted">Data tangki/harga belum lengkap. Menggunakan rentang umum (50.000 - 3.000.000).</span>';
        }
        infoEl.innerHTML = html;
    }

    // ─── Autocomplete pencarian kendaraan ──────────────────────
    vehicleSearch.addEventListener('input', () => {
        if (!vehicleSearch.value || vehicleSearch.value.trim() === '') {
            vehicleId.value = '';
            suggestions.classList.add('d-none');
            updateVehicleInfo(null);
            return;
        }

        const q = vehicleSearch.value.trim().toLowerCase();
        const matches = vehicles.filter((v) =>
            (v.label + ' ' + v.code + ' ' + v.name).toLowerCase().includes(q)
        ).slice(0, 8);

        renderSuggestions(matches);
    });

    // Tutup saran saat klik di luar
    document.addEventListener('click', (e) => {
        if (!suggestions.contains(e.target) && e.target !== vehicleSearch) {
            suggestions.classList.add('d-none');
        }
    });

    // Isi ulang dari old('vehicle_id') setelah error validasi
    const preselected = vehicles.find((v) => v.id === vehicleId.value);
    if (preselected) {
        vehicleSearch.value = preselected.label;
        updateVehicleInfo(preselected);
    } else {
        updateVehicleInfo(null);
    }

    // ─── Helpers ───────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
    }
});
</script>
@endsection