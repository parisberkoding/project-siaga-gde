@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Pengaturan</li>
            <li class="breadcrumb-item active">Tahap Fitur</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <strong>Terjadi kesalahan:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="alert alert-info border-0 small">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Production</strong> = halaman terbuka untuk semua user yang berhak (sesuai RBAC &amp; gate modul unit).
    <strong>Development</strong> = halaman hanya bisa dilihat pemegang permission <code>features.manage</code>
    (role <strong>developers</strong>), user lain mendapat pesan "masih dikembangkan".
    Fitur yang <strong>belum terdaftar</strong> diperlakukan sebagai production supaya halaman yang lupa
    didaftarkan tidak hilang.
</div>

{{-- ═══════════════ DAFTAR FITUR ═══════════════ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-toggle2 me-2"></i>Daftar Fitur</h5>
    </div>
    <div class="card-body p-0">
        @if($features->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox" style="font-size:2rem; opacity:0.5"></i>
                <div class="mt-2">Belum ada fitur terdaftar. Tambahkan lewat formulir di bawah.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fitur</th>
                        <th>Modul</th>
                        <th class="text-center">Tahap</th>
                        <th>Diaktifkan oleh</th>
                        <th width="200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($features as $feature)
                    <tr>
                        <td>
                            <strong>{{ $feature->name }}</strong>
                            <br><small class="text-muted">code: <code>{{ $feature->code }}</code></small>
                            @if($feature->description)
                                <br><small class="text-muted">{{ $feature->description }}</small>
                            @endif
                        </td>
                        <td>
                            @if($feature->module)
                                <span class="badge bg-primary">{{ $feature->module->code }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($feature->isProductionStage())
                                <span class="badge bg-success">
                                    <i class="bi bi-rocket-takeoff me-1"></i>Production
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-hammer me-1"></i>Development
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($feature->activated_at)
                                <small>
                                    {{ $feature->activator?->nama_lengkap ?? '—' }}
                                    <br><span class="text-muted">{{ $feature->activated_at->format('d/m/Y H:i') }}</span>
                                </small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($feature->isProductionStage())
                                <form action="{{ route('superadmin.features.deactivate', $feature) }}" method="POST"
                                      onsubmit="return confirm('Turunkan {{ $feature->name }} ke development? User biasa tidak bisa mengaksesnya lagi.')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-arrow-down-circle me-1"></i>Ke Development
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('superadmin.features.activate', $feature) }}" method="POST"
                                      onsubmit="return confirm('Naikkan {{ $feature->name }} ke production? Fitur terbuka untuk semua user yang berhak.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-arrow-up-circle me-1"></i>Ke Production
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════ FORM TAMBAH FITUR ═══════════════ --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-light rounded-top">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-lg me-2"></i>Daftarkan Fitur Baru</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('superadmin.features.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kode Fitur <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code') }}" placeholder="Contoh: spd.lpj" required>
                <small class="text-muted">Huruf kecil, angka, titik, dash, underscore.</small>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Nama Fitur <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="Contoh: LPJ SPD" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Modul <small class="text-muted fw-normal">(opsional)</small></label>
                <select name="module_id" class="form-select @error('module_id') is-invalid @enderror">
                    <option value="">— tanpa modul —</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}" {{ old('module_id') == $module->id ? 'selected' : '' }}>
                            {{ $module->code }} — {{ $module->name }}
                        </option>
                    @endforeach
                </select>
                @error('module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tahap Awal</label>
                <select name="stage" class="form-select">
                    <option value="development" {{ old('stage') === 'development' ? 'selected' : '' }}>Development</option>
                    <option value="production" {{ old('stage') === 'production' ? 'selected' : '' }}>Production</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Deskripsi <small class="text-muted">(opsional)</small></label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" maxlength="255">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Daftarkan Fitur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
