@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Dashboard Permohonan BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Permohonan BBM</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <!-- Alert Container -->
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

    <!-- Form Permohonan BBM -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Form Permohonan BBM Baru</h5>
                </div>
                <div class="card-body">
                    @if($activeCount >= 5)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Slot permohonan Anda sudah penuh (5/5). Silakan selesaikan permohonan yang ada terlebih dahulu.
                    </div>
                    @else
                    {{-- Update form section --}}
                    <form action="{{ route('requester-create') }}" method="POST" id="requestForm">
                        @csrf
                        <div class="row">
                            {{-- Toggle: Pilih Existing atau Tambah Baru --}}
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

                        {{-- Section: Pilih Existing --}}
                        <div id="existingVehicleSection">
                            <div class="col-12 mb-3">
                                <label for="vehicle_id" class="form-label">Kendaraan / Alat <span class="text-danger">*</span></label>
                                <select class="form-select @error('vehicle_id') is-invalid @enderror"
                                        id="vehicle_id" name="vehicle_id">
                                    <option value="">Pilih Kendaraan/Alat</option>
                                    @foreach($vehicles->where('is_approved', true) as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                            data-benchmark="{{ $vehicle->fuel_benchmark ?? '' }}"
                                            data-range="{{ json_encode($vehicle->fuel_benchmark_range) }}"
                                            data-fuel="{{ $vehicle->detail?->jenis_bahan_bakar ?? '' }}"
                                            data-tank="{{ $vehicle->detail?->volume_tangki ?? '' }}"
                                            {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->dropdown_label }} ({{ $vehicle->consumerial_type }})
                                    </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="vehicleBudgetInfo"></div>
                                @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Section: Tambah Baru --}}
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
                                        <option value="KENDARAAN" {{ old('new_vehicle_type') == 'KENDARAAN' ? 'selected' : '' }}>Kendaraan</option>
                                        <option value="ALAT" {{ old('new_vehicle_type') == 'ALAT' ? 'selected' : '' }}>Alat</option>
                                    </select>
                                    @error('new_vehicle_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="new_vehicle_code" class="form-label">Kode/Plat Nomor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('new_vehicle_code') is-invalid @enderror"
                                        id="new_vehicle_code" name="new_vehicle_code"
                                        value="{{ old('new_vehicle_code') }}"
                                        placeholder="Contoh: B 1234 XYZ">
                                    @error('new_vehicle_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="new_vehicle_name" class="form-label">Nama/Merk <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('new_vehicle_name') is-invalid @enderror"
                                        id="new_vehicle_name" name="new_vehicle_name"
                                        value="{{ old('new_vehicle_name') }}"
                                        placeholder="Contoh: Toyota Avanza">
                                    @error('new_vehicle_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="new_vehicle_notes" class="form-label">Catatan Kendaraan (Opsional)</label>
                                    <textarea class="form-control @error('new_vehicle_notes') is-invalid @enderror"
                                            id="new_vehicle_notes" name="new_vehicle_notes" rows="2"
                                            placeholder="Contoh: Mobil rental dari vendor X">{{ old('new_vehicle_notes') }}</textarea>
                                    @error('new_vehicle_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Gasoline Type & Bill Payment (sama seperti sebelumnya) --}}
                        <div class="row">
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
                        </div>

                        <div class="mb-3">
                            <label for="requester_notes" class="form-label">Catatan Permohonan (Opsional)</label>
                            <textarea class="form-control @error('requester_notes') is-invalid @enderror"
                                    id="requester_notes" name="requester_notes" rows="3"
                                    placeholder="Catatan tambahan untuk permohonan...">{{ old('requester_notes') }}</textarea>
                            @error('requester_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-send"></i> Ajukan Permohonan Baru
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Slot Permohonan Aktif -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-grid-3x3-gap"></i> Slot Permohonan Aktif ({{ $activeCount }}/5)
                </h5>
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

    <!-- Quick Link to History -->
    {{-- <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Lihat Riwayat Lengkap</h5>
                    <p class="text-muted">Lihat semua riwayat permohonan BBM Anda</p>
                    <a href="{{ route('requester-history') }}" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history"></i> Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div> --}}
</section>

<style>
.border-dashed {
    border: 2px dashed #dee2e6;
}

.info-card {
    border: none;
    box-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
}

.card-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
}

.card-icon i {
    font-size: 32px;
    color: white;
}
</style>

<script>
// Toggle between existing and new vehicle
document.addEventListener('DOMContentLoaded', function() {
    const existingRadio = document.getElementById('existing_vehicle');
    const newRadio = document.getElementById('new_vehicle');
    const existingSection = document.getElementById('existingVehicleSection');
    const newSection = document.getElementById('newVehicleSection');
    const vehicleSelect = document.getElementById('vehicle_id');
    const billPayment = document.getElementById('bill_payment');
    const newVehicleInputs = ['new_vehicle_type', 'new_vehicle_code', 'new_vehicle_name'];

    const fallbackOptions = @json($fallbackOptions);

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

        // infoEl.innerHTML = 'Rentang nominal: <strong>' + rupiah(min) + ' - ' + rupiah(max) + '</strong> (kelipatan Rp 50.000)';
    }

    function updateVehicleInfo() {
        const infoEl = document.getElementById('vehicleBudgetInfo');
        const fuelSelect = document.getElementById('gasoline_type');
        if (!infoEl || !fuelSelect) return;

        const opt = vehicleSelect.options[vehicleSelect.selectedIndex];
        if (!opt || !opt.value) {
            infoEl.textContent = 'Pilih kendaraan untuk melihat benchmark BBM.';
            populateBillPayment(fallbackOptions[0], fallbackOptions[fallbackOptions.length - 1]);
            fuelSelect.value = '';
            return;
        }

        const benchmark = opt.dataset.benchmark ? parseInt(opt.dataset.benchmark) : null;
        let range = null;
        try { range = opt.dataset.range ? JSON.parse(opt.dataset.range) : null; } catch (e) { range = null; }

        const fuel = opt.dataset.fuel || '';
        const tank = opt.dataset.tank ? parseFloat(opt.dataset.tank) : null;

        if (range && range.min && range.max) {
            populateBillPayment(range.min, range.max);
        } else {
            populateBillPayment(fallbackOptions[0], fallbackOptions[fallbackOptions.length - 1]);
        }

        // ✅ Auto-fill Jenis BBM sesuai master kendaraan (case-insensitive)
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

        // ✅ Auto-fill Nominal sesuai benchmark (kelipatan 50.000 sudah pasti ada di opsi)
        if (benchmark && range && benchmark >= range.min && benchmark <= range.max) {
            billPayment.value = benchmark;
        }

        let html = '';
        // if (benchmark) {
        //     html += '<i class="bi bi-fuel-pump"></i> Benchmark BBM: <strong>' + rupiah(benchmark) + '</strong>';
        //     if (tank) html += ' <span class="text-muted">(tangki ' + tank + ' L</span>';
        //     if (fuel) html += ' <span class="text-muted">· ' + fuel + ')</span>';
        // } else {
        //     html = '<span class="text-muted">Data tangki/harga belum lengkap. Menggunakan rentang umum (50.000 - 3.000.000).</span>';
        // }
        infoEl.innerHTML = html;
    }

    function toggleSections() {
        if (existingRadio.checked) {
            existingSection.style.display = 'block';
            newSection.style.display = 'none';
            vehicleSelect.required = true;
            newVehicleInputs.forEach(id => {
                document.getElementById(id).required = false;
            });
        } else {
            existingSection.style.display = 'none';
            newSection.style.display = 'block';
            vehicleSelect.required = false;
            newVehicleInputs.forEach(id => {
                document.getElementById(id).required = true;
            });
        }
    }

    existingRadio.addEventListener('change', toggleSections);
    newRadio.addEventListener('change', toggleSections);
    vehicleSelect.addEventListener('change', updateVehicleInfo);
    updateVehicleInfo();
});
</script>
@endsection
