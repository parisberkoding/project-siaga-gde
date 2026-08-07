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

                    {{-- Master Vehicle: data budget BBM (sinkron dengan tabel detail) --}}
                    <h6 class="fw-semibold mb-3"><i class="bi bi-fuel-pump me-2"></i>Data Kendaraan & Budget BBM</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site / Lokasi Operasional</label>
                            <select class="form-select" name="site_id">
                                <option value="">-- Pilih Site --</option>
                                @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                                @endforeach
                            </select>
                        </div>
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
                    <p class="small text-muted mb-2"><strong>Merk & Tipe:</strong></p>
                    <ul class="small text-muted mb-0">
                        <li>Tuliskan merk dan tipe kendaraan</li>
                        <li>Contoh: <code>Toyota Avanza</code>, <code>Honda CRV</code></li>
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
    if (type === 'KENDARAAN') {
        document.getElementById('labelKode').innerHTML = 'Plat Nomor <span class="text-danger">*</span>';
        document.getElementById('consumerialCode').placeholder = 'Contoh: B 1234 XYZ';
        document.getElementById('hintKode').textContent = 'Nomor plat kendaraan (otomatis uppercase)';
        document.getElementById('labelNama').innerHTML = 'Merk & Tipe <span class="text-danger">*</span>';
        document.getElementById('consumerialName').placeholder = 'Contoh: Toyota Avanza';
        document.getElementById('hintNama').textContent = 'Merk dan tipe kendaraan (otomatis uppercase)';
        document.getElementById('guideKendaraan').classList.remove('d-none');
        document.getElementById('guideAlat').classList.add('d-none');
    } else if (type === 'ALAT') {
        document.getElementById('labelKode').innerHTML = 'Kode Alat <span class="text-danger">*</span>';
        document.getElementById('consumerialCode').placeholder = 'Contoh: GENSET-001';
        document.getElementById('hintKode').textContent = 'Kode unik alat (otomatis uppercase)';
        document.getElementById('labelNama').innerHTML = 'Nama Alat <span class="text-danger">*</span>';
        document.getElementById('consumerialName').placeholder = 'Contoh: Generator 5 KVA';
        document.getElementById('hintNama').textContent = 'Nama atau deskripsi alat (otomatis uppercase)';
        document.getElementById('guideKendaraan').classList.add('d-none');
        document.getElementById('guideAlat').classList.remove('d-none');
    }
}

document.getElementById('consumerialTypeSelect')?.addEventListener('change', function() {
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

document.addEventListener('DOMContentLoaded', function() {
    const type = '{{ old('consumerial_type') }}';
    if (type) selectType(type);
});
</script>

@endsection
