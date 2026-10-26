@extends('templates.main')

@section('title', 'Tambah Kendaraan / Alat')

@section('content')

<div class="pagetitle">
    <h1>Tambah Kendaraan / Alat</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vehicles', ['tab' => 'list']) }}">Kendaraan & Alat</a></li>
            <li class="breadcrumb-item active">Tambah Baru</li>
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
            <div class="card-header bg-success text-white rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-plus-circle me-2"></i>Form Tambah Kendaraan / Alat
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('vehicles.store') }}" method="POST" id="createVehicleForm">
                    @csrf

                    {{-- ================= DATA UTAMA ================= --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-card-heading me-2"></i>Data Utama</h6>

                    <!-- Tipe -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Tipe Konsumsi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('consumerial_type') is-invalid @enderror"
                                name="consumerial_type" id="consumerialTypeSelect" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="KENDARAAN" {{ old('consumerial_type') == 'KENDARAAN' ? 'selected' : '' }}>Kendaraan</option>
                            <option value="ALAT" {{ old('consumerial_type') == 'ALAT' ? 'selected' : '' }}>Alat</option>
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
                               value="{{ old('consumerial_code') }}"
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
                               value="{{ old('consumerial_name') }}"
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
                                  placeholder="Catatan tambahan tentang kendaraan/alat ini...">{{ old('consumerial_notes') }}</textarea>
                        @error('consumerial_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    {{-- ================= UNIT & SITE ================= --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-2"></i>Unit Bisnis & Site Operasional</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Unit Bisnis <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('unit_id') is-invalid @enderror"
                                    name="unit_id" id="unitSelect" required>
                                <option value="">-- Pilih Unit --</option>
                                @foreach($units ?? [] as $unit)
                                <option value="{{ $unit->id }}"
                                    {{ (string) old('unit_id', $defaultUnitId ?? '') === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->code }})
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Unit pemilik aset. Dipakai untuk memfilter daftar kendaraan per unit —
                                driver hanya melihat plat nomor dari unitnya sendiri.
                            </small>
                            @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site / Lokasi Operasional</label>
                            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" id="siteSelect">
                                <option value="">-- Pilih Site --</option>
                                @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}"
                                        data-unit="{{ $site->unit_id }}"
                                        {{ (string) old('site_id') === (string) $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}{{ $site->unit ? ' — '.$site->unit->name : '' }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Menentukan region harga BBM untuk benchmark. Site mengikuti unit yang dipilih.</small>
                            @error('site_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    {{-- ================= IDENTITAS KENDARAAN ================= --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-truck me-2"></i>Identitas <span id="identityHeading">Kendaraan</span></h6>
                    <div class="row g-3">
                        <div class="col-md-6" id="jenisKendaraanWrapper">
                            <label class="form-label fw-semibold">Jenis <span class="identityNoun">Kendaraan</span></label>
                            <select class="form-select" name="jenis_kendaraan">
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\VehicleDetail::jenisKendaraanOptions() as $jenis)
                                <option value="{{ $jenis }}" {{ old('jenis_kendaraan') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Merk</label>
                            <input type="text" class="form-control" name="merk" value="{{ old('merk') }}"
                                   placeholder="Contoh: Toyota, Honda, Mitsubishi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model / Tipe</label>
                            <input type="text" class="form-control" name="model" value="{{ old('model') }}"
                                   placeholder="Contoh: Avanza, CR-V, Colt Diesel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Pembuatan</label>
                            <input type="number" class="form-control" name="tahun_pembuatan"
                                   value="{{ old('tahun_pembuatan') }}" min="1900" max="{{ date('Y') + 1 }}"
                                   placeholder="Contoh: 2020">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warna</label>
                            <input type="text" class="form-control" name="warna" value="{{ old('warna') }}"
                                   placeholder="Contoh: Putih, Hitam, Silver">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Mesin (CC)</label>
                            <input type="number" class="form-control" name="kapasitas_mesin"
                                   value="{{ old('kapasitas_mesin') }}" min="0" placeholder="Contoh: 1500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="labelNomorRangka">Nomor Rangka (VIN)</label>
                            <input type="text" class="form-control" name="nomor_rangka"
                                   value="{{ old('nomor_rangka') }}" placeholder="Nomor rangka kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="labelNomorMesin">Nomor Mesin</label>
                            <input type="text" class="form-control" name="nomor_mesin"
                                   value="{{ old('nomor_mesin') }}" placeholder="Nomor mesin kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Odometer Terakhir (km)</label>
                            <input type="number" class="form-control" name="odometer"
                                   value="{{ old('odometer') }}" min="0" placeholder="Contoh: 85000">
                            <small class="text-muted">Pembacaan odometer saat pencatatan aset.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Kepemilikan</label>
                            <select class="form-select" name="status_kepemilikan">
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\VehicleDetail::statusKepemilikanOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('status_kepemilikan') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih <strong>Sewa / Rental</strong> untuk kendaraan sewaan.</small>
                        </div>
                    </div>

                    <div id="dokumenFields">
                        <hr>

                        {{-- ================= DOKUMEN & LEGALITAS ================= --}}
                        <h6 class="fw-semibold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Dokumen & Legalitas</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nomor BPKB</label>
                                <input type="text" class="form-control" name="nomor_bpkb"
                                       value="{{ old('nomor_bpkb') }}" placeholder="Nomor BPKB">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nomor STNK</label>
                                <input type="text" class="form-control" name="nomor_stnk"
                                       value="{{ old('nomor_stnk') }}" placeholder="Nomor STNK">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nomor KIR</label>
                                <input type="text" class="form-control" name="nomor_kir"
                                       value="{{ old('nomor_kir') }}" placeholder="Nomor uji KIR">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pajak Berlaku Sampai</label>
                                <input type="date" class="form-control" name="pajak_berlaku_sampai"
                                       value="{{ old('pajak_berlaku_sampai') }}">
                                <small class="text-muted">Pengingat masa berlaku pajak kendaraan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">KIR Berlaku Sampai</label>
                                <input type="date" class="form-control" name="kir_berlaku_sampai"
                                       value="{{ old('kir_berlaku_sampai') }}">
                                <small class="text-muted">Pengingat masa berlaku uji KIR.</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- ================= KAPASITAS & BUDGET BBM ================= --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-fuel-pump me-2"></i>Kapasitas & Budget BBM</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Penumpang</label>
                            <input type="number" class="form-control" name="kapasitas_penumpang"
                                   value="{{ old('kapasitas_penumpang') }}" min="1" max="100" placeholder="Contoh: 7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Tangki BBM (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="volume_tangki"
                                   value="{{ old('volume_tangki') }}" min="0" placeholder="Contoh: 45">
                            <small class="text-muted">Volume maksimal tangki bahan bakar.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Bahan Bakar</label>
                            <select class="form-select" name="jenis_bahan_bakar">
                                <option value="">-- Pilih --</option>
                                @foreach(['Premium', 'Pertalite', 'Pertamax', 'Pertamax Turbo', 'Solar', 'Biosolar', 'Dexlite', 'Pertamina Dex', 'Listrik'] as $bbm)
                                <option value="{{ $bbm }}" {{ old('jenis_bahan_bakar') == $bbm ? 'selected' : '' }}>{{ $bbm }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Menentukan harga BBM untuk benchmark.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Nominal Operasional (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal_full_tank"
                                   value="{{ old('nominal_full_tank') }}" min="0" placeholder="Contoh: 450000">
                            <small class="text-muted">Patokan budget isi full tank & validasi nominal BBM operasional.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Nominal SPD (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal_spd_default"
                                   value="{{ old('nominal_spd_default') }}" min="0" placeholder="Contoh: 200000">
                            <small class="text-muted">Budget default per hari untuk SPD kendaraan ini.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kuota BBM Tahunan (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="kapasitas_bbm_tahunan"
                                   value="{{ old('kapasitas_bbm_tahunan') }}" min="0" placeholder="Contoh: 1200">
                        </div>
                    </div>

                    <div class="alert alert-success border-0 bg-success bg-opacity-10 mt-3">
                        <i class="bi bi-info-circle me-2 text-success"></i>
                        <small>Kendaraan/Alat yang ditambahkan oleh <strong>Superadmin</strong> akan langsung berstatus <strong>Approved</strong> dan siap digunakan.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-2"></i>Simpan Kendaraan / Alat
                        </button>
                        <a href="{{ route('vehicles', ['tab' => 'list']) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-lightbulb me-2 text-warning"></i>Panduan Pengisian
                </h6>
                <div id="guideKendaraan">
                    <p class="small text-muted mb-2"><strong>Plat Nomor:</strong></p>
                    <ul class="small text-muted mb-3">
                        <li>Gunakan format standar plat nomor Indonesia</li>
                        <li>Contoh: <code>B 1234 XYZ</code> atau <code>D 5678 ABC</code></li>
                        <li>Spasi dan huruf akan otomatis uppercase</li>
                    </ul>
                    <p class="small text-muted mb-2"><strong>Unit Bisnis:</strong></p>
                    <ul class="small text-muted mb-3">
                        <li>Plat nomor hanya tampil untuk driver di unit yang sama</li>
                        <li>Site otomatis mengikuti unit yang dipilih</li>
                    </ul>
                    <p class="small text-muted mb-2"><strong>Identitas & Dokumen:</strong></p>
                    <ul class="small text-muted mb-0">
                        <li>Isi nomor rangka, nomor mesin, BPKB, STNK, dan KIR</li>
                        <li>Isi masa berlaku pajak & KIR untuk pengingat</li>
                    </ul>
                </div>
                <div id="guideAlat" class="d-none">
                    <p class="small text-muted mb-2"><strong>Kode Alat:</strong></p>
                    <ul class="small text-muted mb-3">
                        <li>Gunakan kode unik yang mudah diingat</li>
                        <li>Contoh: <code>GENSET-001</code>, <code>POMPA-002</code></li>
                    </ul>
                    <p class="small text-muted mb-2"><strong>Nama Alat:</strong></p>
                    <ul class="small text-muted mb-0">
                        <li>Tuliskan nama/deskripsi alat</li>
                        <li>Contoh: <code>Generator 5 KVA</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectType(type) {
    const isKendaraan = type === 'KENDARAAN';

    document.getElementById('labelKode').innerHTML = isKendaraan
        ? 'Plat Nomor <span class="text-danger">*</span>'
        : 'Kode Alat <span class="text-danger">*</span>';
    document.getElementById('consumerialCode').placeholder = isKendaraan ? 'Contoh: B 1234 XYZ' : 'Contoh: GENSET-001';
    document.getElementById('hintKode').textContent = isKendaraan
        ? 'Nomor plat kendaraan (otomatis uppercase)'
        : 'Kode unik alat (otomatis uppercase)';

    document.getElementById('labelNama').innerHTML = isKendaraan
        ? 'Merk & Tipe <span class="text-danger">*</span>'
        : 'Nama Alat <span class="text-danger">*</span>';
    document.getElementById('consumerialName').placeholder = isKendaraan ? 'Contoh: Toyota Avanza' : 'Contoh: Generator 5 KVA';
    document.getElementById('hintNama').textContent = isKendaraan
        ? 'Merk dan tipe kendaraan (otomatis uppercase)'
        : 'Nama atau deskripsi alat (otomatis uppercase)';

    // Dokumen legalitas hanya relevan untuk kendaraan.
    document.getElementById('dokumenFields').classList.toggle('d-none', !isKendaraan);

    // Istilah menyesuaikan tipe.
    document.getElementById('identityHeading').textContent = isKendaraan ? 'Kendaraan' : 'Alat';
    document.querySelectorAll('.identityNoun').forEach(el => { el.textContent = isKendaraan ? 'Kendaraan' : 'Alat'; });
    document.getElementById('labelNomorRangka').textContent = isKendaraan ? 'Nomor Rangka (VIN)' : 'Nomor Seri / Identitas Alat';
    document.getElementById('labelNomorMesin').textContent = isKendaraan ? 'Nomor Mesin' : 'Nomor Mesin / Model Alat';

    document.getElementById('guideKendaraan').classList.toggle('d-none', !isKendaraan);
    document.getElementById('guideAlat').classList.toggle('d-none', isKendaraan);
}

/**
 * Site mengikuti unit: option site disaring sesuai unit terpilih,
 * dan memilih site otomatis mengisi unitnya.
 */
function filterSitesByUnit() {
    const unitSelect = document.getElementById('unitSelect');
    const siteSelect = document.getElementById('siteSelect');
    if (!unitSelect || !siteSelect) return;

    const unitId = unitSelect.value;
    let selectedStillValid = true;

    Array.from(siteSelect.options).forEach(function (opt, index) {
        if (index === 0) return; // placeholder

        const siteUnit = opt.dataset.unit || '';
        const matches = !unitId || siteUnit === '' || siteUnit === unitId;
        opt.hidden = !matches;
        opt.disabled = !matches;

        if (opt.selected && !matches) {
            selectedStillValid = false;
        }
    });

    if (!selectedStillValid) {
        siteSelect.value = '';
    }
}

function syncUnitFromSite() {
    const unitSelect = document.getElementById('unitSelect');
    const siteSelect = document.getElementById('siteSelect');
    if (!unitSelect || !siteSelect) return;

    const opt = siteSelect.options[siteSelect.selectedIndex];
    const siteUnit = opt ? (opt.dataset.unit || '') : '';

    if (siteUnit) {
        unitSelect.value = siteUnit;
        filterSitesByUnit();
    }
}

document.getElementById('consumerialTypeSelect')?.addEventListener('change', function() {
    selectType(this.value);
});

document.getElementById('unitSelect')?.addEventListener('change', filterSitesByUnit);
document.getElementById('siteSelect')?.addEventListener('change', syncUnitFromSite);

['consumerialCode', 'consumerialName'].forEach(function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const type = '{{ old('consumerial_type') }}';
    if (type) selectType(type);
    filterSitesByUnit();
});
</script>

@endsection
