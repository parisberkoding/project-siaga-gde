@extends('templates.main')

@section('title', 'Export Form Pengecekan')

@section('content')

<div class="pagetitle">
    <h1>Export Form Pengecekan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Export Form Pengecekan</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i>
    @foreach($errors->all() as $err)
        <div>{{ $err }}</div>
    @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<section class="section">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex align-items-center gap-2 pb-2">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-excel me-1 text-success"></i> Unduh Form Pengecekan (Excel)
                        </h5>
                    </div>

                    <p class="text-muted small mb-3">
                        Buat format pengecekan dispatcher per kendaraan per bulan. Logo perusahaan
                        bisa diganti di bawah (kolom Pengemudi sengaja dikosongkan untuk diisi tulis tangan).
                    </p>

                    <form action="{{ route('dispatcher.export-checking.download') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Kendaraan --}}
                        <div class="mb-3">
                            <label class="form-label">Kendaraan <span class="text-danger">*</span></label>
                            <select name="vehicle_id" class="form-select" required>
                                <option value="">— Pilih Kendaraan —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->dropdown_label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Periode --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Periode Bulan <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <option value="">— Bulan —</option>
                                    @foreach($months as $m)
                                        <option value="{{ $m->month }}" {{ old('month') == $m->month || (empty(old('month')) && $m->month == now()->month) ? 'selected' : '' }}>
                                            {{ $m->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Periode Tahun <span class="text-danger">*</span></label>
                                <select name="year" class="form-select" required>
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ old('year') == $y || (empty(old('year')) && $y == now()->year) ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr>

                        {{-- Kop laporan --}}
                        <h6 class="fw-bold text-secondary mb-2">
                            <i class="bi bi-shop me-1"></i> Kop Laporan
                        </h6>

                        <input type="hidden" name="company_name" value="{{ config('siaga.report.company_name') }}">
                        <input type="hidden" name="unit_name" value="{{ $unitName }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Perusahaan</label>
                                <input type="text" class="form-control" value="{{ config('siaga.report.company_name') }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit / Divisi</label>
                                <input type="text" class="form-control" value="{{ $unitName }}" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo Perusahaan <small class="text-muted">(opsional, ganti logo default)</small></label>
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg"
                                   aria-describedby="logoHelp">
                            <div id="logoHelp" class="form-text">
                                PNG/JPG maks 2 MB. Kosongkan jika ingin memakai logo default.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                            </button>
                            <a href="{{ route('dispatcher.export-checking.index') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle me-1 text-primary"></i> Isi Format Export
                    </h5>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-1"><strong>Pengecekan Harian</strong> — kolom Hari 1..N berisi V (baik) / X (rusak) sesuai data dispatcher.</li>
                        <li class="mb-1"><strong>Pengecekan Mingguan</strong> — kolom menyesuaikan tanggal pengecekan mingguan pada periode tersebut.</li>
                        <li class="mb-1"><strong>Pengecekan Bulanan</strong> — daftar item beserta tanggal, status, dan keterangan.</li>
                        <li class="mb-1"><strong>Pengemudi &amp; Tanda Tangan</strong> — sengaja dikosongkan, diisi manual / tulis tangan.</li>
                        <li>Data kosong pada sel berarti kendaraan belum dicek pada hari/pekan itu.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection