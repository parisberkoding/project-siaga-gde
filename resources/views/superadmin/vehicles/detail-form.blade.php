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

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Form Detail Kendaraan</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('superadmin.vehicle.detail.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Kendaraan</label>
                            <select class="form-select" name="jenis_kendaraan">
                                <option value="">-- Pilih --</option>
                                @foreach(['SUV', 'MPV', 'Sedan', 'Pickup', 'Truck', 'Bus', 'Motor', 'Mini Bus', 'Double Cabin'] as $jenis)
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
                            <label class="form-label fw-semibold">Nomor Rangka (VIN)</label>
                            <input type="text" class="form-control" name="nomor_rangka" value="{{ old('nomor_rangka', $detail->nomor_rangka) }}" placeholder="Nomor rangka kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Mesin</label>
                            <input type="text" class="form-control" name="nomor_mesin" value="{{ old('nomor_mesin', $detail->nomor_mesin) }}" placeholder="Nomor mesin kendaraan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warna</label>
                            <input type="text" class="form-control" name="warna" value="{{ old('warna', $detail->warna) }}" placeholder="Contoh: Putih, Hitam, Silver">
                        </div>
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
                            <label class="form-label fw-semibold">Site / Lokasi Operasional</label>
                            <select class="form-select" name="site_id">
                                <option value="">-- Pilih Site --</option>
                                @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}" {{ old('site_id', $detail->site_id) == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                                @endforeach
                            </select>
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
@endsection
