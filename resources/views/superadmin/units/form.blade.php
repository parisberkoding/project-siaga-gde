@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('superadmin.units.index') }}">Unit Bisnis</a></li>
            <li class="breadcrumb-item active">{{ isset($unit) ? 'Edit' : 'Tambah' }}</li>
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
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-diagram-3 me-2"></i>Detail Unit</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($unit) ? route('superadmin.units.update', $unit) : route('superadmin.units.store') }}" method="POST">
                        @csrf
                        @if(isset($unit)) @method('PUT') @endif

                        @if(!isset($unit))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code"
                                   value="{{ old('code') }}" placeholder="Contoh: PATUHA, PUSAT, DIENG" required style="text-transform:uppercase">
                            <small class="text-muted">Hanya huruf, angka, dash, underscore. Unik.</small>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                   value="{{ old('name', $unit->name ?? '') }}" placeholder="Contoh: Unit Patuha" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi <small class="text-muted fw-normal">(opsional)</small></label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $unit->description ?? '') }}</textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', isset($unit) ? $unit->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Unit aktif</label>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Simpan
                            </button>
                            <a href="{{ route('superadmin.units.index') }}" class="btn btn-outline-secondary">
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
                        Satu site pasti milik satu unit — kelola lewat menu <strong>Site / Lokasi</strong>.
                        User GA yang diberi unit tertentu hanya melihat data site dalam unit-nya.
                    </p>
                </div>
            </div>
            <div class="alert alert-warning border-0 small">
                <i class="bi bi-key me-2"></i>Penempatan user ke unit dilakukan superadmin lewat menu <strong>Manajemen User</strong>.
            </div>
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i>Site dalam unit ini</h6>
                    @if(isset($unit) && $unit->sites_count > 0)
                        <a href="{{ route('superadmin.sites.index', ['unit_id' => $unit->id]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-geo-alt me-1"></i>Lihat {{ $unit->sites_count }} site
                        </a>
                    @else
                        <p class="small text-muted mb-0">Belum ada site. Kelola site lewat menu <strong>Site / Lokasi</strong>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection