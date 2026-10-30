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
    color: #093D7D;
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
    color: #0B50A1;
    background: #EAF1FA;
}

.revenue-card .card-icon {
    color: #4A7A1C;
    background: #F2F9E8;
}

.customers-card .card-icon {
    color: #B35B00;
    background: #ffecdf;
}

.warning-card .card-icon {
    color: #8A6A00;
    background: #fff4de;
}

.danger-card .card-icon {
    color: #ff4560;
    background: #ffe5e9;
}

/* Badge Enhancements */
.badge {
    font-weight: 600;
    padding: 0.35em 0.65em;
}

/* Table Enhancements */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.trip-checkbox:checked {
    background-color: #0B50A1;
    border-color: #0B50A1;
}

/* Alert Level Badges */
.badge-alert-normal {
    background-color: #4A7A1C;
}

.badge-alert-warning {
    background-color: #D8CE3A;
    color: #000;
}

.badge-alert-critical {
    background-color: #C4142A;
}

/* Efficiency Status */
.efficiency-good {
    color: #4A7A1C;
    font-weight: 600;
}

.efficiency-fair {
    color: #D8CE3A;
    font-weight: 600;
}

.efficiency-poor {
    color: #C4142A;
    font-weight: 600;
}

/* Filter Card */
.filter-card {
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #0B50A1;
    box-shadow: 0 0 0 0.2rem rgba(11, 80, 161, 0.15);
}

/* Bulk Actions Bar */
#bulkActionsBar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}
</style>

<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Analisis Trip</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">

    {{-- Statistics Cards --}}
    <div class="row">
        <!-- Total Trips -->
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Trip Analyzed</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-map"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['total']) }}</h6>
                            <span class="text-muted small pt-2">Trip yang teranalisis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need Validation -->
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Perlu Validasi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['needValidation']) }}</h6>
                            <span class="text-muted small pt-2">Menunggu validasi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Level -->
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Warning Level</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-flag"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['warning']) }}</h6>
                            <span class="text-muted small pt-2">Trip perlu perhatian</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Level -->
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Critical Level</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['critical']) }}</h6>
                            <span class="text-muted small pt-2">Trip kritis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Efficiency -->
        <div class="col-xxl-6 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Rata-rata Efisiensi Aktual</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ round($stats['avgEfficiency'], 2) }} km/L</h6>
                            <span class="text-muted small pt-2">Efisiensi rata-rata</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Deviation -->
        <div class="col-xxl-6 col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Rata-rata Deviasi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                             style="color: {{ $stats['avgDeviation'] < -20 ? '#ED1B2F' : ($stats['avgDeviation'] < -10 ? '#D8CE3A' : '#71A62F') }};
                                    background: {{ $stats['avgDeviation'] < -20 ? '#ffe5e9' : ($stats['avgDeviation'] < -10 ? '#fff4de' : '#F2F9E8') }};">
                            <i class="bi bi-graph-down"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ round($stats['avgDeviation'], 2) }}%</h6>
                            <span class="text-muted small pt-2">Deviasi dari baseline</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Data Trip
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="bi bi-chevron-down"></i> Toggle Filter
                    </button>
                </div>

                <div class="collapse show" id="filterCollapse">
                    <div class="card-body">
                        <form method="GET" action="{{ route('superadmin.trip-analysis.index') }}" id="filterForm" data-autofilter>
                            <div class="row g-3">

                                {{-- Alert Level --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-flag text-warning"></i> Alert Level
                                    </label>
                                    <select class="form-select" name="alert_level">
                                        <option value="">Semua Level</option>
                                        <option value="normal" {{ $alertLevel == 'normal' ? 'selected' : '' }}>
                                            ✅ Normal
                                        </option>
                                        <option value="warning" {{ $alertLevel == 'warning' ? 'selected' : '' }}>
                                            ⚠️ Warning
                                        </option>
                                        <option value="critical" {{ $alertLevel == 'critical' ? 'selected' : '' }}>
                                            🔴 Critical
                                        </option>
                                    </select>
                                </div>

                                {{-- Vehicle --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-truck text-info"></i> Kendaraan
                                    </label>
                                    <select class="form-select" name="vehicle_id">
                                        <option value="">Semua Kendaraan</option>
                                        @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ $vehicleId == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Driver --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-person-badge text-secondary"></i> Driver
                                    </label>
                                    <select class="form-select" name="requester_id">
                                        <option value="">Semua Driver</option>
                                        @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ $requesterId == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->nama_lengkap }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Validation Status --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-check-circle text-success"></i> Status Validasi
                                    </label>
                                    <select class="form-select" name="validated">
                                        <option value="">Semua Status</option>
                                        <option value="false" {{ $validated === 'false' ? 'selected' : '' }}>
                                            Belum Divalidasi
                                        </option>
                                        <option value="true" {{ $validated === 'true' ? 'selected' : '' }}>
                                            Sudah Divalidasi
                                        </option>
                                    </select>
                                </div>

                                {{-- Search --}}
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-search text-primary"></i> Cari
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ $search }}"
                                           placeholder="Cari kendaraan, driver...">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold d-block">&nbsp;</label>
                                    <a href="{{ route('superadmin.trip-analysis.index') }}" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                                    </a>
                                </div>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div>
                                        <form action="{{ route('superadmin.trip-analysis.superadmin.trip-validations.recalculate-all') }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Recalculate semua trip metrics? Proses ini mungkin memakan waktu.')">
                                            @csrf
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-arrow-repeat"></i> Recalculate All Metrics
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Trip Analysis Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-table me-2"></i>Data Trip Analysis
                        </h5>
                        <span class="badge bg-primary">{{ $tripAnalyses->total() }} Trip</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="checkAll"
                                               onchange="toggleCheckAll()">
                                    </th>
                                    <th width="10%">Driver</th>
                                    <th width="12%">Kendaraan</th>
                                    <th width="10%">Periode</th>
                                    <th width="8%" class="text-center">Jarak (KM)</th>
                                    <th width="8%" class="text-center">BBM (L)</th>
                                    <th width="10%" class="text-center">Efisiensi</th>
                                    <th width="10%" class="text-center">Deviasi</th>
                                    <th width="10%" class="text-center">Range Usage</th>
                                    <th width="10%">Alert Level</th>
                                    <th width="9%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tripAnalyses as $trip)
                                <tr>
                                    {{-- Checkbox --}}
                                    <td>
                                        @if(!$trip->validated_at)
                                        <input type="checkbox"
                                               class="form-check-input trip-checkbox"
                                               value="{{ $trip->id }}">
                                        @endif
                                    </td>

                                    {{-- Driver --}}
                                    <td>
                                        <strong class="small">
                                            {{ $trip->transactionProofPrevious->permohonan->requester->nama_lengkap ?? 'Unknown' }}
                                        </strong>
                                    </td>

                                    {{-- Vehicle --}}
                                    <td>
                                        @php
                                            $vehicle = $trip->transactionProofPrevious->permohonan->consumerialTool ?? null;
                                        @endphp
                                        <div class="small">
                                            <strong class="text-primary">{{ $vehicle->consumerial_code ?? '-' }}</strong><br>
                                            <span class="text-muted">{{ $vehicle->consumerial_name ?? '-' }}</span>
                                        </div>
                                    </td>

                                    {{-- Periode Column --}}
                                   <td>
                                        <div class="d-flex flex-column gap-1">
                                            {{-- ✅ Link Previous --}}
                                            <a href="javascript:void(0)"
                                            class="text-primary text-decoration-underline trip-detail-link"
                                            data-request-number="{{ $trip->transactionProofPrevious->permohonan->request_number ?? '' }}"
                                            data-datetime="{{ $trip->transactionProofPrevious->purchase_datetime->format('d/m/Y H:i') }}"
                                            data-odo="{{ $trip->previous_odo }}"
                                            data-volume="{{ $trip->transactionProofPrevious->fuel_volume ?? 0 }}"
                                            data-fuel-type="{{ $trip->fuel_type }}"
                                            data-transaction-id="{{ $trip->transactionProofPrevious->transaction_id ?? '' }}"
                                            data-position="previous"
                                            @php
                                                $strukPath = $trip->transactionProofPrevious->struk_bbm_path ?? null;
                                                $odoPath = $trip->transactionProofPrevious->odometer_photo_path ?? null;

                                                $strukBase64 = '';
                                                $odoBase64 = '';

                                                if ($strukPath && Storage::disk('public')->exists($strukPath)) {
                                                    $strukBase64 = 'data:image/' . pathinfo($strukPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(Storage::disk('public')->get($strukPath));
                                                }

                                                if ($odoPath && Storage::disk('public')->exists($odoPath)) {
                                                    $odoBase64 = 'data:image/' . pathinfo($odoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(Storage::disk('public')->get($odoPath));
                                                }
                                            @endphp
                                            data-struk-photo="{{ $strukBase64 }}"
                                            data-odo-photo="{{ $odoBase64 }}"
                                            onclick="showTripDetailEnhanced(this)">
                                                <small>{{ $trip->transactionProofPrevious->purchase_datetime->format('d/m/y') }}</small>
                                            </a>

                                            <div class="text-center">
                                                <i class="bi bi-arrow-down"></i>
                                            </div>

                                            {{-- ✅ Link Latest --}}
                                            <a href="javascript:void(0)"
                                            class="text-success text-decoration-underline trip-detail-link"
                                            data-request-number="{{ $trip->transactionProofLatest->permohonan->request_number ?? '' }}"
                                            data-datetime="{{ $trip->transactionProofLatest->purchase_datetime->format('d/m/Y H:i') }}"
                                            data-odo="{{ $trip->latest_odo }}"
                                            data-volume="{{ $trip->transactionProofLatest->fuel_volume ?? 0 }}"
                                            data-fuel-type="{{ $trip->fuel_type }}"
                                            data-transaction-id="{{ $trip->transactionProofLatest->transaction_id ?? '' }}"
                                            data-position="latest"
                                            @php
                                                $strukPath = $trip->transactionProofLatest->struk_bbm_path ?? null;
                                                $odoPath = $trip->transactionProofLatest->odometer_photo_path ?? null;

                                                $strukBase64 = '';
                                                $odoBase64 = '';

                                                if ($strukPath && Storage::disk('public')->exists($strukPath)) {
                                                    $strukBase64 = 'data:image/' . pathinfo($strukPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(Storage::disk('public')->get($strukPath));
                                                }

                                                if ($odoPath && Storage::disk('public')->exists($odoPath)) {
                                                    $odoBase64 = 'data:image/' . pathinfo($odoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(Storage::disk('public')->get($odoPath));
                                                }
                                            @endphp
                                            data-struk-photo="{{ $strukBase64 }}"
                                            data-odo-photo="{{ $odoBase64 }}"
                                            onclick="showTripDetailEnhanced(this)">
                                                <small>{{ $trip->transactionProofLatest->purchase_datetime->format('d/m/y') }}</small>
                                            </a>

                                            <small class="text-muted">({{ $trip->days_between_refuel }} hari)</small>
                                        </div>
                                    </td>

                                    {{-- Jarak --}}
                                    <td class="text-center">
                                        <span class="badge {{ $trip->diff_odo >= 300 ? 'bg-danger' : 'bg-success' }} fs-6">
                                            {{ number_format($trip->diff_odo, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- BBM --}}
                                    <td class="text-center">
                                        <strong>{{ $trip->fuel_volume ?? '-' }}</strong>
                                    </td>

                                    {{-- Efisiensi --}}
                                    <td class="text-center">
                                        @if($trip->actual_efficiency)
                                        <div>
                                            <span class="efficiency-{{ $trip->efficiency_status }}">
                                                {{ $trip->actual_efficiency }} km/L
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            (optimal: {{ $trip->expected_efficiency ?? '-' }} km/L)
                                        </small>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Deviasi --}}
                                    <td class="text-center">
                                        @if($trip->efficiency_deviation_percent !== null)
                                        <span class="badge bg-{{ $trip->efficiency_status_badge }}">
                                            {{ round($trip->efficiency_deviation_percent, 1) }}%
                                        </span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Range Usage --}}
                                    <td class="text-center">
                                        @if($trip->range_usage_percent !== null)
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $trip->is_premature_refuel ? 'bg-danger' : ($trip->range_usage_percent < 80 ? 'bg-warning' : 'bg-success') }}"
                                                 style="width: {{ min($trip->range_usage_percent, 100) }}%">
                                                {{ round($trip->range_usage_percent) }}%
                                            </div>
                                        </div>
                                        @if($trip->is_premature_refuel)
                                            <small class="text-danger fw-bold">Terlalu Cepat!</small>
                                        @endif
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Alert Level --}}
                                    <td>
                                        <span class="badge bg-{{ $trip->alert_level_badge }}">
                                            {{ $trip->alert_level_label }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td>
                                        @if(!$trip->validated_at)
                                       <button class="btn btn-sm btn-primary w-100"
                                            onclick="showValidationModal(
                                                {{ $trip->id }},
                                                '{{ $vehicle->consumerial_code ?? '' }}',
                                                {{ $trip->diff_odo }},
                                                '{{ $trip->fuel_type }}'
                                            )">
                                        <i class="bi bi-check-circle"></i> Validasi
                                    </button>
                                        @else
                                        <span class="badge bg-{{ $trip->status_badge }} w-100">
                                            {{ $trip->status_label }}
                                        </span>
                                        @if($trip->long_trip_notes)
                                        <small class="text-muted d-block mt-1">
                                            {{ Str::limit($trip->long_trip_notes, 30) }}
                                        </small>
                                        @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        <h5>Tidak ada data trip analysis</h5>
                                        <p class="small">Gunakan filter atau recalculate metrics untuk generate data.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Menampilkan {{ $tripAnalyses->firstItem() ?? 0 }} - {{ $tripAnalyses->lastItem() ?? 0 }}
                            dari {{ $tripAnalyses->total() }} trip
                        </div>
                        <div>
                            {{ $tripAnalyses->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

{{-- Bulk Actions Bar (Fixed Bottom) --}}
<div id="bulkActionsBar" class="card shadow-lg" style="display: none;">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <strong class="text-primary">
                    <span id="selectedCount">0</span> trip dipilih
                </strong>
            </div>
            <div class="btn-group">
                <button class="btn btn-success" onclick="bulkValidate(true)">
                    <i class="bi bi-airplane"></i> Perjalanan Dinas
                </button>
                <button class="btn btn-primary" onclick="bulkValidate(false)">
                    <i class="bi bi-check-circle"></i> Perjalanan Normal
                </button>
                <button class="btn btn-outline-secondary" onclick="clearSelection()">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Validasi Trip --}}
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle-fill me-2"></i>Validasi Trip Kendaraan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formValidation" method="POST">
                @csrf
                <div class="modal-body">

                    {{-- Trip Info --}}
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle fs-4 me-3"></i>
                            <div>
                                <strong id="modalTripInfo"></strong>
                                <div class="small mt-1">
                                    <span class="badge bg-white text-primary" id="modalFuelType"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 1: Jenis Perjalanan --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">
                            <i class="bi bi-pin-map text-primary"></i> Jenis Perjalanan
                        </label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="is_long_trip"
                                   id="isLongTripYes" value="1" required>
                            <label class="form-check-label" for="isLongTripYes">
                                <i class="bi bi-airplane text-success me-1"></i>
                                <strong>Perjalanan Dinas</strong> - Luar kota (Jakarta, Dieng, dll)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_long_trip"
                                   id="isLongTripNo" value="0" required>
                            <label class="form-check-label" for="isLongTripNo">
                                <i class="bi bi-house text-primary me-1"></i>
                                <strong>Perjalanan Normal</strong> - Dalam kota/sekitar area
                            </label>
                        </div>
                    </div>

                    {{-- Section 2: Keterangan Tujuan --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-journal-text text-secondary"></i> Tujuan/Keperluan
                        </label>
                        <textarea class="form-control" name="long_trip_notes" rows="2"
                                  placeholder="Contoh: Meeting dengan klien di Jakarta, Inspeksi lapangan Dieng..."></textarea>
                        <small class="text-muted">Opsional - Isi jika perjalanan dinas</small>
                    </div>

                    <hr class="my-4">

                    {{-- ✅ Section 3: Patokan Pemakaian BBM (NEW) --}}
                    <div class="card border-warning mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="bi bi-speedometer2 text-warning"></i>
                                Patokan Pemakaian BBM
                            </h6>

                            <div class="mb-3">
                                <label class="form-label">Standar Efisiensi (km/liter)</label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control form-control-md"
                                           id="baselineOverride"
                                           name="baseline_override"
                                           step="0.1"
                                           min="1"
                                           max="30"
                                           oninput="syncBaselineReasonRequired(this.form)"
                                           placeholder="Kosongkan untuk patokan standar">
                                    <span class="input-group-text"><strong>km/liter</strong></span>
                                </div>

                                <div class="form-text mt-2" id="baselineDefaultText">
                                    <!-- Dinamis dari JavaScript -->
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    Alasan Perubahan Patokan <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control"
                                          name="baseline_override_reason"
                                          rows="2"
                                          placeholder="Contoh: Kondisi macet parah, jalan rusak, membawa beban berat, perjalanan pegunungan..."></textarea>
                                @error('baseline_override_reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    💡 <strong>Wajib diisi</strong> jika patokan efisiensi diubah, agar perubahan punya jejak audit.
                                </small>
                            </div>

                            <div class="alert alert-light border-0 mb-0 mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb"></i>
                                    Biarkan kosong untuk menggunakan patokan standar sistem.
                                    Sesuaikan hanya jika ada kondisi khusus pada trip ini.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle"></i> Simpan Validasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Detail Permohonan Tanggal Click --}}
{{-- ✅ Modal Detail Transaction (Enhanced with Photos) --}}
<div class="modal fade" id="tripDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Detail Transaksi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Request Info --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">Nomor Permohonan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-receipt"></i>
                        </span>
                        <input type="text" class="form-control" id="detailRequestNumber" readonly>
                    </div>
                </div>

                {{-- Transaction ID --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">ID Transaksi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-hash"></i>
                        </span>
                        <input type="text" class="form-control" id="detailTransactionId" readonly>
                    </div>
                </div>

                {{-- Datetime --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">Tanggal & Waktu Pengisian</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-calendar-event"></i>
                        </span>
                        <input type="text" class="form-control" id="detailDatetime" readonly>
                    </div>
                </div>

                <div class="row">
                    {{-- Odometer --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted small">Odometer</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-speedometer2"></i>
                            </span>
                            <input type="text" class="form-control" id="detailOdo" readonly>
                            <span class="input-group-text">KM</span>
                        </div>
                    </div>

                    {{-- Volume --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted small">Volume BBM</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-fuel-pump"></i>
                            </span>
                            <input type="text" class="form-control" id="detailVolume" readonly>
                            <span class="input-group-text">Liter</span>
                        </div>
                    </div>
                </div>

                {{-- Fuel Type --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">Jenis BBM</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-droplet-fill"></i>
                        </span>
                        <input type="text" class="form-control" id="detailFuelType" readonly>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ✅ FOTO SECTION - Struk Only --}}
                <h6 class="mb-3">
                    <i class="bi bi-image text-primary me-2"></i>
                    Bukti Transaksi
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">Foto Struk BBM</label>
                    <div class="border rounded p-2 bg-light text-center" style="min-height: 200px;">
                        <img id="detailStrukPhoto"
                            src=""
                            alt="Foto Struk"
                            class="img-fluid rounded"
                            style="max-height: 400px; cursor: pointer;"
                            onclick="openImageModal(this.src, 'Foto Struk BBM')">
                        <p id="detailStrukNoPhoto" class="text-muted my-5" style="display: none;">
                            <i class="bi bi-image-alt fs-1"></i><br>
                            Tidak ada foto struk
                        </p>
                    </div>
                </div>

                {{-- Position Badge --}}
                <div class="text-center mt-3">
                    <span class="badge bg-secondary" id="detailPosition"></span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ✅ Modal Image Fullscreen --}}
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="imageModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="imageModalContent" src="" alt="" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
// ==================== GLOBAL VARIABLES ====================
let selectedTrips = new Set();

// ==================== CHECKBOX MANAGEMENT ====================
function toggleCheckAll() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.trip-checkbox');

    checkboxes.forEach(cb => {
        cb.checked = checkAll.checked;
        if (checkAll.checked) {
            selectedTrips.add(cb.value);
        } else {
            selectedTrips.delete(cb.value);
        }
    });

    updateBulkActions();
}

// Listen to individual checkbox changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('trip-checkbox')) {
        if (e.target.checked) {
            selectedTrips.add(e.target.value);
        } else {
            selectedTrips.delete(e.target.value);
        }
        updateBulkActions();
    }
});

function updateBulkActions() {
    const bar = document.getElementById('bulkActionsBar');
    const countEl = document.getElementById('selectedCount');

    countEl.textContent = selectedTrips.size;
    bar.style.display = selectedTrips.size > 0 ? 'block' : 'none';
}

function clearSelection() {
    selectedTrips.clear();
    document.querySelectorAll('.trip-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
    updateBulkActions();
}

// ==================== VALIDATION MODAL ====================
function showValidationModal(tripId, vehicleCode, distance, fuelType) {
    const modal = new bootstrap.Modal(document.getElementById('validationModal'));
    const form = document.getElementById('formValidation');
    const info = document.getElementById('modalTripInfo');
    const fuelTypeBadge = document.getElementById('modalFuelType');
    const baselineDefaultText = document.getElementById('baselineDefaultText');

    // Set form action
    form.action = `/trip-validations/${tripId}/validate`;

    // Set trip info
    info.textContent = `${vehicleCode}: ${distance.toLocaleString('id-ID')} KM`;
    fuelTypeBadge.textContent = fuelType;

    // ✅ Set default baseline text based on fuel type
    let defaultBaseline = 12; // fallback
    let baselineColor = 'info';

    if (fuelType === 'Pertamax') {
        defaultBaseline = 15;
        baselineColor = 'success';
    } else if (fuelType === 'Pertamina Dex') {
        defaultBaseline = 7;
        baselineColor = 'warning';
    }

    baselineDefaultText.innerHTML = `
        <span class="badge bg-${baselineColor}">
            Patokan Standar: ${defaultBaseline} km/L
        </span>
        untuk ${fuelType}. Kosongkan jika menggunakan patokan standar.
    `;

    // Clear previous values
    form.reset();
    document.getElementById('baselineOverride').value = '';

    // BUG-EFF-001: alasan wajib diisi bila patokan efisiensi diubah.
    syncBaselineReasonRequired(form);

    modal.show();
}

/**
 * Toggle atribut required pada field alasan sesuai isi patokan BBM.
 * Validasi server tetap menjadi penentu akhir; ini hanya UX di sisi klien.
 */
function syncBaselineReasonRequired(form) {
    const baselineInput = form.querySelector('#baselineOverride');
    const reasonInput = form.querySelector('textarea[name="baseline_override_reason"]');

    if (!baselineInput || !reasonInput) {
        return;
    }

    const wajib = baselineInput.value.trim() !== '';
    reasonInput.required = wajib;
    reasonInput.classList.toggle('border-warning', wajib);
}

// ==================== BULK VALIDATION ====================
function bulkValidate(isLongTrip) {
    if (selectedTrips.size === 0) {
        alert('Pilih minimal 1 trip untuk divalidasi');
        return;
    }

    const confirmText = isLongTrip
        ? 'Konfirmasi semua sebagai Perjalanan Dinas?'
        : 'Konfirmasi semua sebagai Perjalanan Normal?';

    if (!confirm(`${confirmText}\n\n${selectedTrips.size} trip akan divalidasi.`)) {
        return;
    }

    // Create form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("superadmin.trip-validations.bulk-validate") }}';

    // CSRF Token
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    // Trip IDs
    selectedTrips.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'validation_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    // Is Long Trip
    const isLongTripInput = document.createElement('input');
    isLongTripInput.type = 'hidden';
    isLongTripInput.name = 'is_long_trip';
    isLongTripInput.value = isLongTrip ? '1' : '0';
    form.appendChild(isLongTripInput);

    // Submit
    document.body.appendChild(form);
    form.submit();
}

// ==================== AUTO CLEAR MESSAGES ====================
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Realtime filter: submit otomatis tanpa tombol Terapkan Filter
    document.querySelectorAll('form[data-autofilter]').forEach(function(form) {
        const search = form.querySelector('input[name="search"]');
        if (search) {
            let timer;
            search.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(function() { form.submit(); }, 400);
            });
        }
        form.querySelectorAll('select').forEach(function(sel) {
            sel.addEventListener('change', function() { form.submit(); });
        });
    });
});

// ==================== TRIP DETAIL MODAL onclick Dates ===================
/**
 * Show trip detail modal with photos (struk only)
 */
function showTripDetailEnhanced(element) {
    // Get data from element attributes
    const requestNumber = element.dataset.requestNumber;
    const datetime = element.dataset.datetime;
    const odo = element.dataset.odo;
    const volume = element.dataset.volume;
    const fuelType = element.dataset.fuelType;
    const transactionId = element.dataset.transactionId;
    const position = element.dataset.position;
    const strukPhoto = element.dataset.strukPhoto;

    // Populate modal fields
    document.getElementById('detailRequestNumber').value = requestNumber;
    document.getElementById('detailTransactionId').value = transactionId;
    document.getElementById('detailDatetime').value = datetime;
    document.getElementById('detailOdo').value = parseInt(odo).toLocaleString('id-ID');
    document.getElementById('detailVolume').value = parseFloat(volume).toFixed(2);
    document.getElementById('detailFuelType').value = fuelType;

    // ✅ Handle Struk Photo
    const strukImg = document.getElementById('detailStrukPhoto');
    const strukNoPhoto = document.getElementById('detailStrukNoPhoto');
    if (strukPhoto && strukPhoto.length > 0) {
        strukImg.src = strukPhoto;
        strukImg.style.display = 'block';
        strukNoPhoto.style.display = 'none';
    } else {
        strukImg.style.display = 'none';
        strukNoPhoto.style.display = 'block';
    }

    // Set position badge
    const positionBadge = document.getElementById('detailPosition');
    if (position === 'previous') {
        positionBadge.className = 'badge bg-primary';
        positionBadge.innerHTML = '<i class="bi bi-arrow-up-circle me-1"></i>Transaksi Awal';
    } else {
        positionBadge.className = 'badge bg-success';
        positionBadge.innerHTML = '<i class="bi bi-arrow-down-circle me-1"></i>Transaksi Akhir';
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('tripDetailModal'));
    modal.show();
}


/**
 * Open image in fullscreen modal
 */
function openImageModal(src, title) {
    if (!src || src.length === 0) return;

    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('imageModalContent').src = src;

    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>

@endsection
