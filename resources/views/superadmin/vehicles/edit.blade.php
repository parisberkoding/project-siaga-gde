@extends('templates.main')

@section('title', 'Edit Kendaraan / Alat')

@section('content')

<div class="pagetitle">
    <h1>Edit Kendaraan / Alat</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vehicles', ['tab' => 'list']) }}">Kendaraan & Alat</a></li>
            <li class="breadcrumb-item active">{{ $vehicle->consumerial_code }}</li>
        </ol>
    </nav>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Terjadi kesalahan:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-pencil me-2"></i>Form Edit Kendaraan / Alat
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('vehicles.update', $vehicle->id) }}" method="POST" id="editVehicleForm">
                    @csrf

                    <!-- Tipe -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Tipe Konsumsi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('consumerial_type') is-invalid @enderror"
                                name="consumerial_type" id="consumerialTypeSelect" required>
                            <option value="KENDARAAN" {{ old('consumerial_type', $vehicle->consumerial_type) == 'KENDARAAN' ? 'selected' : '' }}>Kendaraan</option>
                            <option value="ALAT" {{ old('consumerial_type', $vehicle->consumerial_type) == 'ALAT' ? 'selected' : '' }}>Alat</option>
                        </select>
                        @error('consumerial_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Kode -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="labelKode">
                            Plat Nomor <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('consumerial_code') is-invalid @enderror"
                               name="consumerial_code"
                               id="consumerialCode"
                               value="{{ old('consumerial_code', $vehicle->consumerial_code) }}"
                               placeholder="Contoh: B 1234 XYZ"
                               required>
                        <small class="text-muted" id="hintKode">Nomor plat kendaraan (otomatis uppercase)</small>
                        @error('consumerial_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nama -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="labelNama">
                            Merk & Tipe <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('consumerial_name') is-invalid @enderror"
                               name="consumerial_name"
                               id="consumerialName"
                               value="{{ old('consumerial_name', $vehicle->consumerial_name) }}"
                               placeholder="Contoh: Toyota Avanza"
                               required>
                        <small class="text-muted" id="hintNama">Merk dan tipe kendaraan (otomatis uppercase)</small>
                        @error('consumerial_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Catatan -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Catatan <small class="text-muted fw-normal">(opsional)</small></label>
                        <textarea class="form-control @error('consumerial_notes') is-invalid @enderror"
                                  name="consumerial_notes" rows="3"
                                  placeholder="Catatan tambahan tentang kendaraan/alat ini...">{{ old('consumerial_notes', $vehicle->consumerial_notes) }}</textarea>
                        @error('consumerial_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    {{-- Master Vehicle: data budget BBM & kapasitas (sinkron dengan tabel detail) --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-fuel-pump me-2"></i>Data Kendaraan, Jenis BBM & Benchmark</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site / Lokasi Operasional</label>
                            <select class="form-select" name="site_id" id="siteSelect">
                                <option value="">-- Pilih Site --</option>
                                @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}" {{ old('site_id', $detail->site_id) == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Menentukan region harga BBM untuk benchmark.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Bahan Bakar</label>
                            <select class="form-select" name="jenis_bahan_bakar" id="fuelSelect">
                                <option value="">-- Pilih --</option>
                                @foreach(['Premium', 'Pertalite', 'Pertamax', 'Pertamax Turbo', 'Solar', 'Biosolar', 'Dexlite', 'Pertamina Dex'] as $bbm)
                                <option value="{{ $bbm }}" {{ old('jenis_bahan_bakar', $detail->jenis_bahan_bakar) == $bbm ? 'selected' : '' }}>{{ $bbm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Penumpang</label>
                            <input type="number" class="form-control" name="kapasitas_penumpang" id="passengerInput"
                                   value="{{ old('kapasitas_penumpang', $detail->kapasitas_penumpang) }}" min="1" max="100" placeholder="Contoh: 7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Tangki BBM (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="volume_tangki" id="tankInput"
                                   value="{{ old('volume_tangki', $detail->volume_tangki) }}" min="0" placeholder="Contoh: 45">
                            <small class="text-muted">Volume maksimal tangki bahan bakar.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Benchmark BBM / Nominal Full Tank (Rp)</label>
                            <div class="input-group">
                                <input type="number" step="50000" class="form-control" name="nominal_full_tank" id="benchmarkInput"
                                       value="{{ old('nominal_full_tank', $detail->nominal_full_tank) }}" min="0" placeholder="Contoh: 900000">
                                <button type="button" class="btn btn-outline-primary" id="btnFillBenchmark" title="Isikan dari kalkulasi tangki x harga">
                                    <i class="bi bi-magic"></i> Hitung
                                </button>
                            </div>
                            <small class="text-muted">Benchmark = tangki (L) x harga/liter region, kelipatan Rp 50.000. Dipakai validasi nominal permohonan.</small>
                            <div id="benchmarkCalcInfo" class="mt-1"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Nominal SPD (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal_spd_default"
                                   value="{{ old('nominal_spd_default', $detail->nominal_spd_default) }}" min="0" placeholder="Contoh: 200000">
                            <small class="text-muted">Budget default per hari untuk SPD kendaraan ini.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kuota BBM Tahunan (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="kapasitas_bbm_tahunan"
                                   value="{{ old('kapasitas_bbm_tahunan', $detail->kapasitas_bbm_tahunan) }}" min="0" placeholder="Contoh: 1200">
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 mt-3">
                        <i class="bi bi-info-circle me-2 text-warning"></i>
                        <small>Benchmark digunakan sebagai acuan otomatis untuk <strong>Jenis BBM</strong> dan <strong>Nominal</strong> saat driver membuat permohonan BBM.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                        <a href="{{ route('vehicles', ['tab' => 'list']) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar: Info Benchmark -->
    <div class="col-lg-4">
        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-lightbulb me-2 text-warning"></i>Kalkulator Benchmark
                </h6>
                <div id="regionInfo" class="small mb-2 text-muted">
                    @php
                        $siteName = $detail->site?->name ?? '-';
                        $siteSlug = $detail->site?->province_slug ?: 'jawa-barat';
                    @endphp
                    Region: <strong>{{ $siteName }} ({{ $siteSlug }})</strong>
                </div>
                <ul class="list-unstyled small mb-3" id="priceList">
                    @foreach(['PERTAMAX' => 'Pertamax', 'PERTAMINA DEX' => 'Pertamina Dex'] as $code => $label)
                    <li class="mb-1">
                        {{ $label }}:
                        <strong>
                            @if(isset($regionPrices[$code]))
                            Rp {{ number_format($regionPrices[$code], 0, ',', '.') }}/L
                            @else
                            <span class="text-danger">harga belum ada</span>
                            @endif
                        </strong>
                    </li>
                    @endforeach
                </ul>
                <p class="small text-muted mb-0">
                    Benchmark terhitung = <strong id="calcRaw">-</strong><br>
                    Dibulatkan (kelipatan 50.000) = <strong id="calcRounded" class="text-success">-</strong>
                </p>
                <button type="button" class="btn btn-primary btn-sm mt-3 w-100" id="btnCalcOnly">
                    <i class="bi bi-calculator me-1"></i>Hitung & Isikan ke Benchmark
                </button>
            </div>
        </div>

        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Ringkasan Benchmark Saat Ini
                </h6>
                <table class="table table-sm small mb-0">
                    <tr>
                        <th>Tangki</th>
                        <td id="sumTank">{{ $detail->volume_tangki ? $detail->volume_tangki . ' L' : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Jenis BBM</th>
                        <td id="sumFuel">{{ $detail->jenis_bahan_bakar ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Benchmark</th>
                        <td id="sumBenchmark" class="fw-bold text-success">
                            {{ $detail->nominal_full_tank ? 'Rp ' . number_format($detail->nominal_full_tank, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Rentang Nominal</th>
                        <td id="sumRange">-</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const REGION_PRICES = @json($regionPrices);

function fuelProduct(fuel) {
    const f = (fuel || '').toUpperCase();
    if (f.includes('PERTAMAX')) return 'PERTAMAX';
    if (f.includes('DEX')) return 'PERTAMINA DEX';
    return null;
}

function priceFor(fuel) {
    const product = fuelProduct(fuel);
    return product && REGION_PRICES[product] != null ? REGION_PRICES[product] : null;
}

function formatRp(v) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
}

function roundBenchmark(raw) {
    if (!raw || raw <= 0) return null;
    let r = Math.round(raw / 50000) * 50000;
    r = Math.max(50000, Math.min(3000000, r));
    return r;
}

function calcBenchmark() {
    const tank = parseFloat(document.getElementById('tankInput').value);
    const fuel = document.getElementById('fuelSelect').value;
    const price = priceFor(fuel);

    if (!tank || !fuel || !price) {
        document.getElementById('calcRaw').textContent = '-';
        document.getElementById('calcRounded').textContent = '-';
        document.getElementById('benchmarkCalcInfo').innerHTML = '';
        return null;
    }

    const raw = tank * price;
    const rounded = roundBenchmark(raw);

    document.getElementById('calcRaw').textContent = formatRp(Math.round(raw));
    document.getElementById('calcRounded').textContent = rounded ? formatRp(rounded) : '-';

    return rounded;
}

function fillBenchmark() {
    const rounded = calcBenchmark();
    if (rounded) {
        document.getElementById('benchmarkInput').value = rounded;
        const info = document.getElementById('benchmarkCalcInfo');
        info.className = 'text-success small mt-1';
        info.innerHTML = '<i class="bi bi-check-circle me-1"></i>Benchmark diisi: <strong>' + formatRp(rounded) + '</strong>';
        updateSummary();
    } else {
        const info = document.getElementById('benchmarkCalcInfo');
        info.className = 'text-danger small mt-1';
        info.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Lengkapi site/region, jenis BBM, dan kapasitas tangki untuk menghitung benchmark.';
    }
}

function updateSummary() {
    const tank = parseFloat(document.getElementById('tankInput').value);
    const fuel = document.getElementById('fuelSelect').value;
    const bench = parseFloat(document.getElementById('benchmarkInput').value);

    document.getElementById('sumTank').textContent = tank ? tank + ' L' : '-';
    document.getElementById('sumFuel').textContent = fuel || '-';
    document.getElementById('sumBenchmark').textContent = bench ? formatRp(bench) : '-';

    const rangeEl = document.getElementById('sumRange');
    if (bench) {
        const min = Math.max(50000, bench - 500000);
        const max = Math.min(3000000, bench + 500000);
        rangeEl.textContent = formatRp(min) + ' - ' + formatRp(max);
    } else {
        rangeEl.textContent = '-';
    }
}

function selectType(type) {
    const isKendaraan = type === 'KENDARAAN';
    document.getElementById('labelKode').innerHTML = isKendaraan
        ? 'Plat Nomor <span class="text-danger">*</span>'
        : 'Kode Alat <span class="text-danger">*</span>';
    document.getElementById('consumerialCode').placeholder = isKendaraan ? 'Contoh: B 1234 XYZ' : 'Contoh: GENSET-001';
    document.getElementById('hintKode').textContent = isKendaraan ? 'Nomor plat kendaraan (otomatis uppercase)' : 'Kode unik alat (otomatis uppercase)';
    document.getElementById('labelNama').innerHTML = isKendaraan
        ? 'Merk & Tipe <span class="text-danger">*</span>'
        : 'Nama Alat <span class="text-danger">*</span>';
    document.getElementById('consumerialName').placeholder = isKendaraan ? 'Contoh: Toyota Avanza' : 'Contoh: Generator 5 KVA';
    document.getElementById('hintNama').textContent = isKendaraan ? 'Merk dan tipe kendaraan (otomatis uppercase)' : 'Nama atau deskripsi alat (otomatis uppercase)';
}

document.addEventListener('DOMContentLoaded', function() {
    const type = document.getElementById('consumerialTypeSelect').value;
    if (type) selectType(type);

    document.getElementById('consumerialTypeSelect').addEventListener('change', function() {
        selectType(this.value);
    });

    document.getElementById('consumerialCode').addEventListener('input', function() {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
    document.getElementById('consumerialName').addEventListener('input', function() {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    document.getElementById('tankInput').addEventListener('input', calcBenchmark);
    document.getElementById('fuelSelect').addEventListener('change', calcBenchmark);
    document.getElementById('siteSelect').addEventListener('change', calcBenchmark);
    document.getElementById('benchmarkInput').addEventListener('input', updateSummary);

    document.getElementById('btnFillBenchmark').addEventListener('click', fillBenchmark);
    document.getElementById('btnCalcOnly').addEventListener('click', fillBenchmark);

    calcBenchmark();
    updateSummary();
});
</script>

@endsection
