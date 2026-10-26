@extends('templates.main')

@section('title', 'Export Body Checking')

@section('content')

<div class="pagetitle">
    <h1>Export Body Checking Excel</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Export Body Checking</li>
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
                            <i class="bi bi-file-earmark-excel me-1 text-success"></i> Unduh Body Checking (Excel)
                        </h5>
                    </div>

                    <p class="text-muted small mb-3">
                        Export laporan pengecekan body kendaraan per kendaraan per bulan.
                        Dilengkapi foto kerusakan (jika ada).
                    </p>

                    <form action="{{ route('dispatcher.export.body-checking-download') }}" method="POST">
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

                        <input type="hidden" name="company_name" value="{{ config('siaga.report.company_name') }}">
                        <input type="hidden" name="unit_name" value="{{ $unitName }}">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                            </button>
                            <a href="{{ route('dispatcher.export.body-checking-index') }}" class="btn btn-outline-secondary">
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
                        <li class="mb-1"><strong>Header</strong> — kop perusahaan, kendaraan, periode.</li>
                        <li class="mb-1"><strong>Detail Body Checking</strong> — daftar body part, status (Baik/Cukup/Kurang/Tidak), keterangan, dan foto.</li>
                        <li class="mb-1"><strong>Foto Kerusakan</strong> — disematkan langsung di Excel jika tersedia.</li>
                        <li class="mb-1"><strong>Tanda Tangan</strong> — Dispatcher &amp; Pengemudi.</li>
                        <li>Data terurut descending (terbaru di atas).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection