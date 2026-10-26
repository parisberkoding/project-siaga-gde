@extends('templates.main')

@section('title', 'Edit Detail Kendaraan')

@section('content')
<div class="pagetitle">
    <h1>Edit Detail Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vehicles', ['tab' => 'list']) }}">Kendaraan & Alat</a></li>
            <li class="breadcrumb-item"><a href="{{ route('superadmin.vehicle.detail', $vehicle->id) }}">{{ $vehicle->consumerial_code }}</a></li>
            <li class="breadcrumb-item active">Edit Detail</li>
        </ol>
    </nav>
</div>

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

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $currentUnitId = old('unit_id', $vehicle->unit_id);
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Form Detail Kendaraan</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('superadmin.vehicle.detail.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Unit & Site --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-2"></i>Unit Bisnis & Site</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Bisnis</label>
                            <select class="form-select" name="unit_id" id="unitSelect">
                                <option value="">-- Pilih Unit --</option>
                                @foreach($units ?? [] as $unit)
                                <option value="{{ $unit->id }}" {{ (string) $currentUnitId === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->code }})
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Menentukan kendaraan ini ter-view di unit mana.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site / Lokasi Operasional</label>
                            <select class="form-select" name="site_id" id="siteSelect">
                                <option value="">-- Pilih Site --</option>
                                @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}"
                                        data-unit="{{ $site->unit_id }}"
                                        {{ (string) old('site_id', $detail->site_id) === (string) $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}{{ $site->unit ? ' — '.$site->unit->name : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    {{-- Identitas --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-card-list me-2"></i>Identitas Kendaraan</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Kendaraan</label>
                            <select class="form-select" name="jenis_kendaraan">
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\VehicleDetail::jenisKendaraanOptions() as $jenis)
                                <option value="{{ $jenis }}" {{ old('jenis_kendaraan', $detail->jenis_kendaraan) == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Merk</label>
                            <input type="text" class="form-control" name="merk" value="{{ old('merk', $detail->merk) }}" placeholder="Contoh: Toyota, Honda, Mitsubishi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model</label>
                            <input type="text" class="form-control" name="model" value="{{ old('model', $detail->model) }}" placeholder="Contoh: Avanza, CR-V, Colt Diesel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Pembuatan</label>
                            <input type="number" class="form-control" name="tahun_pembuatan" value="{{ old('tahun_pembuatan', $detail->tahun_pembuatan) }}" min="1900" max="{{ date('Y') + 1 }}" placeholder="Contoh: 2020">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warna</label>
                            <input type="text" class="form-control" name="warna" value="{{ old('warna', $detail->warna) }}" placeholder="Contoh: Putih, Hitam, Silver">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Mesin (CC)</label>
                            <input type="number" class="form-control" name="kapasitas_mesin" value="{{ old('kapasitas_mesin', $detail->kapasitas_mesin) }}" min="0" placeholder="Contoh: 1500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Rangka (VIN)</label>
                            <input type="text" class="form-control" name="nomor_rangka" value="{{ old('nomor_rangka', $detail->nomor_rangka) }}" placeholder="Nomor rangka kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Mesin</label>
                            <input type="text" class="form-control" name="nomor_mesin" value="{{ old('nomor_mesin', $detail->nomor_mesin) }}" placeholder="Nomor mesin kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Odometer Terakhir (km)</label>
                            <input type="number" class="form-control" name="odometer" value="{{ old('odometer', $detail->odometer) }}" min="0" placeholder="Contoh: 85000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Kepemilikan</label>
                            <select class="form-select" name="status_kepemilikan">
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\VehicleDetail::statusKepemilikanOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('status_kepemilikan', $detail->status_kepemilikan) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih <strong>Sewa / Rental</strong> untuk kendaraan sewaan.</small>
                        </div>
                    </div>

                    <hr>

                    {{-- Dokumen & legalitas --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Dokumen & Legalitas</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nomor BPKB</label>
                            <input type="text" class="form-control" name="nomor_bpkb" value="{{ old('nomor_bpkb', $detail->nomor_bpkb) }}" placeholder="Nomor BPKB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nomor STNK</label>
                            <input type="text" class="form-control" name="nomor_stnk" value="{{ old('nomor_stnk', $detail->nomor_stnk) }}" placeholder="Nomor STNK">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nomor KIR</label>
                            <input type="text" class="form-control" name="nomor_kir" value="{{ old('nomor_kir', $detail->nomor_kir) }}" placeholder="Nomor uji KIR">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pajak Berlaku Sampai</label>
                            <input type="date" class="form-control" name="pajak_berlaku_sampai"
                                   value="{{ old('pajak_berlaku_sampai', optional($detail->pajak_berlaku_sampai)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">KIR Berlaku Sampai</label>
                            <input type="date" class="form-control" name="kir_berlaku_sampai"
                                   value="{{ old('kir_berlaku_sampai', optional($detail->kir_berlaku_sampai)->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <hr>

                    {{-- Kapasitas & BBM --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-fuel-pump me-2"></i>Kapasitas & Budget BBM</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Penumpang</label>
                            <input type="number" class="form-control" name="kapasitas_penumpang" value="{{ old('kapasitas_penumpang', $detail->kapasitas_penumpang) }}" min="1" max="100" placeholder="Contoh: 7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Bahan Bakar (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="kapasitas_bahan_bakar" value="{{ old('kapasitas_bahan_bakar', $detail->kapasitas_bahan_bakar) }}" min="0" placeholder="Contoh: 45">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Bahan Bakar</label>
                            <select class="form-select" name="jenis_bahan_bakar">
                                <option value="">-- Pilih --</option>
                                @foreach(['Premium', 'Pertalite', 'Pertamax', 'Pertamax Turbo', 'Solar', 'Biosolar', 'Dexlite', 'Pertamina Dex', 'Listrik'] as $bbm)
                                <option value="{{ $bbm }}" {{ old('jenis_bahan_bakar', $detail->jenis_bahan_bakar) == $bbm ? 'selected' : '' }}>{{ $bbm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Volume Tangki (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="volume_tangki" value="{{ old('volume_tangki', $detail->volume_tangki) }}" min="0" placeholder="Contoh: 45">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nominal Full Tank (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal_full_tank" value="{{ old('nominal_full_tank', $detail->nominal_full_tank) }}" min="0" placeholder="Contoh: 450000">
                            <small class="text-muted">Dipakai sebagai patokan budget isi full tank & validasi nominal BBM otomatis.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Nominal SPD (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal_spd_default" value="{{ old('nominal_spd_default', $detail->nominal_spd_default) }}" min="0" placeholder="Contoh: 200000">
                            <small class="text-muted">Budget default per hari untuk SPD kendaraan ini.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kuota BBM Tahunan (liter)</label>
                            <input type="number" step="0.01" class="form-control" name="kapasitas_bbm_tahunan" value="{{ old('kapasitas_bbm_tahunan', $detail->kapasitas_bbm_tahunan) }}" min="0" placeholder="Contoh: 1200">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="3" placeholder="Catatan tambahan...">{{ old('catatan', $detail->catatan) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Simpan Detail
                        </button>
                        <a href="{{ route('superadmin.vehicle.detail', $vehicle->id) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-truck me-2"></i>{{ $vehicle->consumerial_code }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Nama</th>
                        <td>{{ $vehicle->consumerial_name }}</td>
                    </tr>
                    <tr>
                        <th>Tipe</th>
                        <td>
                            @if($vehicle->consumerial_type == 'KENDARAAN')
                                <span class="badge bg-primary">Kendaraan</span>
                            @else
                                <span class="badge bg-info text-dark">Alat</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Unit</th>
                        <td>
                            @if($vehicle->unit)
                                <span class="badge bg-success">{{ $vehicle->unit->name }}</span>
                            @else
                                <span class="badge bg-warning text-dark">Belum diisi</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($vehicle->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-warning text-dark">Tidak Aktif</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-2"></i>Petunjuk</h6>
                <small class="text-muted">
                    Isi detail kendaraan selengkap mungkin untuk dokumentasi aset.
                    Field yang tidak diisi akan dikosongkan.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
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
        if (index === 0) return;

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

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('unitSelect')?.addEventListener('change', filterSitesByUnit);
    document.getElementById('siteSelect')?.addEventListener('change', syncUnitFromSite);
    filterSitesByUnit();
});
</script>
@endsection
