@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Lunch Forecast</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Forecast</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-3 g-2 align-items-center">
        <div class="col-md-4">
            <form method="GET" action="{{ route('lunch.forecasts.index') }}" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
            </form>
        </div>
        <div class="col-md-8 text-md-end">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#overrideModal">
                <i class="bi bi-pencil-square me-1"></i>Manual Override
            </button>
            <form method="POST" action="{{ route('lunch.forecasts.generate') }}" class="d-inline"
                  onsubmit="return confirm('Generate forecast untuk {{ $date }}? Manual override tidak akan ditimpa.')">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button class="btn btn-primary"><i class="bi bi-magic me-1"></i>Generate Forecast</button>
            </form>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-3">
        @foreach($sites as $site)
        <div class="col-md-2 col-6">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold text-primary mb-0">{{ $totalsBySite[$site->id] ?? 0 }}</h4>
                <small class="text-muted">{{ $site->code }}</small>
            </div></div>
        </div>
        @endforeach
        <div class="col-md-2 col-6">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold text-success mb-0">
                    {{ $accuracy['accuracy'] !== null ? $accuracy['accuracy'] . '%' : '—' }}
                </h4>
                <small class="text-muted">Akurasi (terisi actual)</small>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-2">
                <small class="text-muted d-block mb-1">Sumber prediksi:</small>
                @forelse($totalsBySource as $source => $total)
                    <span class="badge bg-light text-dark border me-1">{{ $source }}: {{ $total }}</span>
                @empty
                    <span class="text-muted small">Belum ada forecast.</span>
                @endforelse
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Site Prediksi</th>
                            <th>Sumber</th>
                            <th class="text-center">Confidence</th>
                            <th>Site Aktual (QR)</th>
                            <th class="text-center">Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecasts as $forecast)
                        <tr>
                            <td class="fw-semibold">
                                {{ $forecast->employee?->employee_name ?? '-' }}
                                <br><small class="text-muted">{{ $forecast->employee?->position }}</small>
                            </td>
                            <td>
                                @if($forecast->predictedSite)
                                    <span class="badge bg-primary">{{ $forecast->predictedSite->code }}</span>
                                @else — @endif
                            </td>
                            <td><span class="badge bg-{{ $forecast->sourceBadgeClass() }}">{{ $forecast->sourceLabel() }}</span></td>
                            <td class="text-center">{{ $forecast->confidence !== null ? $forecast->confidence . '%' : '—' }}</td>
                            <td>
                                @if($forecast->actualSite)
                                    <span class="badge bg-success">{{ $forecast->actualSite->code }}</span>
                                @else <span class="text-muted">belum scan</span> @endif
                            </td>
                            <td class="text-center">
                                @if($forecast->is_prediction_correct === true)
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @elseif($forecast->is_prediction_correct === false)
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                @else — @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            Belum ada forecast untuk tanggal ini. Klik <strong>Generate Forecast</strong>.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $forecasts->links('pagination::bootstrap-5') }}
        </div>
    </div>

</section>

{{-- MODAL: Manual Override --}}
<div class="modal fade" id="overrideModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.forecasts.override') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Manual Override</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Override adalah prioritas tertinggi — tidak akan ditimpa saat forecast digenerate ulang.</p>
                <div class="mb-3">
                    <label class="form-label">Karyawan</label>
                    <select name="slug" class="form-select" required>
                        <option value="">— pilih —</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->slug }}">{{ $employee->employee_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control" required>
                </div>
                <div class="mb-1">
                    <label class="form-label">Site</label>
                    <select name="site_id" class="form-select" required>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Override</button>
            </div>
        </form>
    </div>
</div>
@endsection
