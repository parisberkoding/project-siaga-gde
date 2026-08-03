@extends('templates.main')

@section('title', $title)

@section('content')

<style>
/* NiceAdmin Enhanced Styles */
.info-card {
    padding-bottom: 10px;
}

.info-card h6 {
    font-size: 28px;
    color: #012970;
    font-weight: 700;
    margin: 0;
    padding: 0;
}

.card-icon {
    font-size: 32px;
    line-height: 0;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    flex-grow: 0;
}

.sales-card .card-icon {
    color: #4154f1;
    background: #f6f6fe;
}

.revenue-card .card-icon {
    color: #2eca6a;
    background: #e0f8e9;
}

.customers-card .card-icon {
    color: #ff771d;
    background: #ffecdf;
}

.warning-card .card-icon {
    color: #ffbb2c;
    background: #fff4de;
}

/* Filter Card */
.filter-card {
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #4154f1;
    box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.15);
}

/* Badge Styles */
.badge-method-manual {
    background-color: #0d6efd;
}

.badge-method-auto {
    background-color: #198754;
}

.badge-method-override {
    background-color: #ffc107;
    color: #000;
}

/* Table Hover */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* No Baseline Alert */
.alert-no-baseline {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}
</style>

<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('superadmin.trip-analysis.index') }}">Analisis Trip</a></li>
            <li class="breadcrumb-item active">Baseline Efisiensi</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">

    {{-- Statistics Cards --}}
    <div class="row">
        <!-- Total Baselines -->
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Baseline Terdaftar</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $baselines->total() }}</h6>
                            <span class="text-muted small pt-2">Baseline aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicles Without Baseline -->
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Kendaraan Tanpa Baseline</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $vehiclesWithoutBaseline->count() }}</h6>
                            <span class="text-muted small pt-2">Perlu setup</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Baseline -->
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Rata-rata Baseline</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $baselines->count() > 0 ? round($baselines->avg('optimal_efficiency_km_per_liter'), 2) : 0 }} km/L</h6>
                            <span class="text-muted small pt-2">Efisiensi optimal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert: Vehicles Without Baseline --}}
    @if($vehiclesWithoutBaseline->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-no-baseline alert-dismissible fade show" role="alert">
                <h4 class="alert-heading">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ $vehiclesWithoutBaseline->count() }} Kendaraan Belum Memiliki Baseline
                </h4>
                <p class="mb-2">Kendaraan berikut belum memiliki baseline efisiensi BBM:</p>
                <div class="row">
                    @foreach($vehiclesWithoutBaseline->take(6) as $vehicle)
                    <div class="col-md-4 mb-2">
                        <strong>{{ $vehicle->consumerial_code }}</strong> - {{ $vehicle->consumerial_name }}
                    </div>
                    @endforeach
                </div>
                @if($vehiclesWithoutBaseline->count() > 6)
                <p class="mb-0 mt-2"><small class="text-muted">Dan {{ $vehiclesWithoutBaseline->count() - 6 }} kendaraan lainnya...</small></p>
                @endif
                <hr>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addBaselineModal">
                    <i class="bi bi-plus-circle"></i> Tambah Baseline Sekarang
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Baseline
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="bi bi-chevron-down"></i> Toggle Filter
                    </button>
                </div>

                <div class="collapse show" id="filterCollapse">
                    <div class="card-body">
                        <form method="GET" action="{{ route('superadmin.baseline.index') }}" id="filterForm">
                            <div class="row g-3">

                                {{-- Jenis BBM --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-fuel-pump text-danger"></i> Jenis BBM
                                    </label>
                                    <select class="form-select" name="fuel_type">
                                        <option value="">Semua Jenis BBM</option>
                                        <option value="Pertamax" {{ $fuelType == 'Pertamax' ? 'selected' : '' }}>
                                            Pertamax
                                        </option>
                                        <option value="Pertamina Dex" {{ $fuelType == 'Pertamina Dex' ? 'selected' : '' }}>
                                            Pertamina Dex
                                        </option>
                                        <option value="Pertalite" {{ $fuelType == 'Pertalite' ? 'selected' : '' }}>
                                            Pertalite
                                        </option>
                                    </select>
                                </div>

                                {{-- Search --}}
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-search text-primary"></i> Cari Kendaraan
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ $search }}"
                                           placeholder="Cari kode atau nama kendaraan...">
                                </div>

                                {{-- Action Buttons --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Terapkan Filter
                                    </button>
                                </div>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <a href="{{ route('superadmin.baseline.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                                    </a>

                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBaselineModal">
                                        <i class="bi bi-plus-circle"></i> Tambah Baseline Baru
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Baseline Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-table me-2"></i>Data Baseline Efisiensi
                        </h5>
                        <span class="badge bg-primary">{{ $baselines->total() }} Baseline</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Kendaraan</th>
                                    <th width="12%">Jenis BBM</th>
                                    <th width="12%" class="text-center">Efisiensi Optimal</th>
                                    <th width="13%">Metode</th>
                                    <th width="10%">Sample Size</th>
                                    <th width="15%">Terakhir Diupdate</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($baselines as $baseline)
                                <tr>
                                    {{-- No --}}
                                    <td>{{ $baselines->firstItem() + $loop->index }}</td>

                                    {{-- Kendaraan --}}
                                    <td>
                                        <div class="fw-bold text-primary">{{ $baseline->vehicle->consumerial_code }}</div>
                                        <small class="text-muted">{{ $baseline->vehicle->consumerial_name }}</small>
                                    </td>

                                    {{-- Jenis BBM --}}
                                    <td>
                                        <span class="badge bg-info">{{ $baseline->fuel_type }}</span>
                                    </td>

                                    {{-- Efisiensi Optimal --}}
                                    <td class="text-center">
                                        <span class="badge bg-success fs-5">
                                            {{ $baseline->optimal_efficiency_km_per_liter }} km/L
                                        </span>
                                    </td>

                                    {{-- Metode --}}
                                    <td>
                                        <span class="badge badge-method-{{ $baseline->calculation_method == 'manual' ? 'manual' : ($baseline->calculation_method == 'auto_calculated' ? 'auto' : 'override') }}">
                                            {{ $baseline->calculation_method_label }}
                                        </span>
                                    </td>

                                    {{-- Sample Size --}}
                                    <td>
                                        @if($baseline->sample_size)
                                            <i class="bi bi-bar-chart-fill text-success"></i> {{ $baseline->sample_size }} trip
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Last Update --}}
                                    <td>
                                        <div class="small">
                                            <i class="bi bi-clock-history text-muted"></i>
                                           {{ \Carbon\Carbon::parse($baseline->last_calibrated_at)->format('d-m-Y') }}
                                        </div>
                                        @if($baseline->calibrator)
                                        <small class="text-muted">oleh {{ $baseline->calibrator->nama_lengkap }}</small>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary"
                                                    onclick="showEditModal({{ $baseline->id }}, {{ $baseline->optimal_efficiency_km_per_liter }}, '{{ $baseline->notes }}')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <form action="{{ route('superadmin.baseline.auto-calculate', [$baseline->vehicle_id, $baseline->fuel_type]) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Auto-calculate baseline dari trip history?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">
                                                    <i class="bi bi-arrow-repeat"></i> Auto
                                                </button>
                                            </form>

                                            <button class="btn btn-outline-danger"
                                                    onclick="confirmDelete({{ $baseline->id }}, '{{ $baseline->vehicle->consumerial_code }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        <h5>Belum ada baseline terdaftar</h5>
                                        <p class="small">Tambahkan baseline untuk kendaraan agar analisis efisiensi dapat berjalan.</p>
                                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addBaselineModal">
                                            <i class="bi bi-plus-circle"></i> Tambah Baseline Pertama
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Menampilkan {{ $baselines->firstItem() ?? 0 }} - {{ $baselines->lastItem() ?? 0 }}
                            dari {{ $baselines->total() }} baseline
                        </div>
                        <div>
                            {{ $baselines->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

{{-- Modal: Add Baseline --}}
<div class="modal fade" id="addBaselineModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Baseline Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('superadmin.baseline.store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kendaraan <span class="text-danger">*</span></label>
                        <select class="form-select" name="vehicle_id" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            @foreach($vehiclesWithoutBaseline as $vehicle)
                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hanya menampilkan kendaraan yang belum memiliki baseline</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis BBM <span class="text-danger">*</span></label>
                        <select class="form-select" name="fuel_type" required>
                            <option value="">-- Pilih Jenis BBM --</option>
                            <option value="Pertamax">Pertamax</option>
                            <option value="Pertamina Dex">Pertamina Dex</option>
                            <option value="Pertalite">Pertalite</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Efisiensi Optimal (km/Liter) <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control"
                               name="optimal_efficiency_km_per_liter"
                               step="0.01"
                               min="1"
                               max="50"
                               required
                               placeholder="Contoh: 12.5">
                        <small class="text-muted">Masukkan efisiensi optimal dalam km per liter (1-50)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control"
                                  name="notes"
                                  rows="3"
                                  placeholder="Contoh: Baseline berdasarkan spesifikasi pabrik"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Tips:</strong> Gunakan fitur <strong>Auto-Calculate</strong> untuk menghitung baseline otomatis dari data trip history (jika tersedia).
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Simpan Baseline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Edit Baseline --}}
<div class="modal fade" id="editBaselineModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-fill me-2"></i>Edit Baseline
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditBaseline" method="POST">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Efisiensi Optimal (km/Liter) <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control"
                               id="editEfficiency"
                               name="optimal_efficiency_km_per_liter"
                               step="0.01"
                               min="1"
                               max="50"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control"
                                  id="editNotes"
                                  name="notes"
                                  rows="3"></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Perubahan ini akan mengubah metode menjadi <strong>"Admin Override"</strong> dan mempengaruhi perhitungan efisiensi di trip analysis.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Baseline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Form Delete Hidden --}}
<form id="formDeleteBaseline" method="POST" style="display: none;">
    @csrf
</form>

<script>
// ==================== EDIT BASELINE ====================
function showEditModal(baselineId, efficiency, notes) {
    const modal = new bootstrap.Modal(document.getElementById('editBaselineModal'));
    const form = document.getElementById('formEditBaseline');

    form.action = `/baseline/${baselineId}/update`;
    document.getElementById('editEfficiency').value = efficiency;
    document.getElementById('editNotes').value = notes || '';

    modal.show();
}

// ==================== DELETE BASELINE ====================
function confirmDelete(baselineId, vehicleCode) {
    if (confirm(`Hapus baseline untuk ${vehicleCode}?\n\nTrip analysis akan menggunakan baseline default (12 km/L) jika baseline ini dihapus.`)) {
        const form = document.getElementById('formDeleteBaseline');
        form.action = `/baseline/${baselineId}/delete`;
        form.submit();
    }
}

// ==================== AUTO CLEAR MESSAGES ====================
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            if (!alert.classList.contains('alert-no-baseline')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});
</script>

@endsection
