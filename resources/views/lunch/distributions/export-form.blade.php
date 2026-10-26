@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Export Vendor Catering</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item"><a href="{{ route('lunch.distributions.index') }}">Distribution</a></li>
            <li class="breadcrumb-item active">Export</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body pt-4">
                    <p class="text-muted">
                        Menghasilkan Excel 3 sheet: <strong>Order Catering</strong> (porsi per site per hari),
                        <strong>Data Tamu</strong>, dan <strong>Detail Per Orang</strong> (forecast vs kehadiran QR).
                    </p>

                    <form action="{{ route('lunch.export') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" required
                                   value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" required
                                   value="{{ now()->toDateString() }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site</label>
                            <select name="site_id" class="form-select">
                                <option value="">Semua Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-success w-100">
                            <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
