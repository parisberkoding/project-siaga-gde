@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('superadmin.sites.index') }}">Site / Lokasi</a></li>
            <li class="breadcrumb-item active">{{ isset($site) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Terjadi kesalahan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white rounded-top">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-geo-alt me-2"></i>Detail Site</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($site) ? route('superadmin.sites.update', $site) : route('superadmin.sites.store') }}" method="POST">
                        @csrf
                        @if(isset($site)) @method('PUT') @endif

                        @if(!isset($site))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Site <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code"
                                   value="{{ old('code') }}" placeholder="Contoh: Basecamp, SUMUR-1" required style="text-transform:uppercase">
                            <small class="text-muted">Hanya huruf, angka, dash, underscore. Unik.</small>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Site <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                   value="{{ old('name', $site->name ?? '') }}" placeholder="Contoh: Site Basecamp" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" name="unit_id" required>
                                <option value="">-- Pilih Unit --</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $site->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->code }})
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Setiap site wajib terhubung ke satu unit (mis. Basecamp, Sumur, PTH-BC → Unit Patuha).</small>
                            @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Region / Provinsi <small class="text-muted fw-normal">(opsional)</small></label>
                            <input type="text" class="form-control" name="province_slug"
                                   value="{{ old('province_slug', $site->province_slug ?? '') }}"
                                   placeholder="Contoh: jawa-barat (untuk acuan harga BBM)">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', isset($site) ? $site->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Site aktif</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Simpan
                            </button>
                            <a href="{{ route('superadmin.sites.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x me-1"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-light shadow-sm mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-diagram-3 me-2 text-primary"></i>Unit vs Site</h6>
                    <p class="small text-muted mb-0">
                        <strong>Unit</strong> = level wilayah bisnis (Unit Patuha, Unit Pusat, Unit Dieng).
                        <strong>Site</strong> = lokasi operasional di bawah unit (Basecamp, Sumur, PTH-BC, dll).
                        Satu site pasti milik satu unit. User GA yang diberi unit tertentu hanya melihat data site dalam unit-nya.
                    </p>
                </div>
            </div>
            <div class="alert alert-warning border-0 small">
                <i class="bi bi-key me-2"></i>Kelola <strong>unit</strong> lewat menu <a href="{{ route('superadmin.units.index') }}">Kelola Unit</a>.
            </div>
        </div>
    </div>
</section>
@endsection