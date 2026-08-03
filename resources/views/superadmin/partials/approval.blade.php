@extends('templates.main')

@section('title', 'Dashboard Approval Superadmin')

@section('content')
<!-- Alert Messages -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="pagetitle">
    <h1>Dashboard Approval Superadmin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Approval</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Kendaraan Rental Pending</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pendingVehicles->count() }}</h6>
                            <span class="text-muted small pt-2">Menunggu persetujuan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Permohonan Terkait</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pendingVehicleRequests->count() }}</h6>
                            <span class="text-muted small pt-2">Menunggu approve kendaraan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Vehicles Table -->
    <div class="card">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Kendaraan/Alat Rental Pending Approval</h5>
        </div>
        <div class="card-body">
            @if($pendingVehicles->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th>Kode/Plat</th>
                            <th>Nama/Merk</th>
                            <th>Catatan</th>
                            <th>Diajukan Oleh</th>
                            <th>Tanggal</th>
                            <th>Permohonan Terkait</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingVehicles as $vehicle)
                        <tr>
                            <td>
                                @if($vehicle->consumerial_type == 'Kendaraan')
                                    <span class="badge bg-primary">Kendaraan</span>
                                @else
                                    <span class="badge bg-info">Alat</span>
                                @endif
                            </td>
                            <td><strong>{{ $vehicle->consumerial_code }}</strong></td>
                            <td>{{ $vehicle->consumerial_name }}</td>
                            <td><small class="text-muted">{{ $vehicle->consumerial_notes ?? '-' }}</small></td>
                            <td>
                                <small>
                                    <strong>{{ $vehicle->creator->nama_lengkap ?? 'Unknown' }}</strong><br>
                                    <span class="text-muted">{{ $vehicle->creator->username ?? '-' }}</span>
                                </small>
                            </td>
                            <td><small>{{ $vehicle->created_at->format('d/m/Y H:i') }}</small></td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $vehicle->permohonans->count() }} permohonan</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <form action="{{ route('superadmin.vehicle.approve', $vehicle->id) }}"
                                          method="POST"
                                          style="display: inline;"
                                          onsubmit="return confirm('✅ Setujui kendaraan {{ $vehicle->consumerial_code }}?\n\nPermohonan terkait akan diteruskan ke Admin untuk approval.')">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-circle me-1"></i>Setujui
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="openRejectModal({{ $vehicle->id }}, '{{ $vehicle->consumerial_code }}', {{ $vehicle->permohonans->count() }})">
                                        <i class="bi bi-x-circle me-1"></i>Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">Tidak ada kendaraan rental yang menunggu approval</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Pending Requests Linked to Unapproved Vehicles -->
    @if($pendingVehicleRequests->count() > 0)
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Permohonan Menunggu Approval Kendaraan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Permohonan</th>
                            <th>Pemohon</th>
                            <th>Kendaraan</th>
                            <th>Jenis BBM</th>
                            <th>Nominal</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingVehicleRequests as $request)
                        <tr>
                            <td><strong>{{ $request->request_number }}</strong></td>
                            <td>
                                <small>
                                    {{ $request->requester->nama_lengkap ?? 'Unknown' }}<br>
                                    <span class="text-muted">{{ $request->requester->username ?? '-' }}</span>
                                </small>
                            </td>
                            <td>
                                <small>
                                    <strong>{{ $request->vehicle->consumerial_code ?? '-' }}</strong><br>
                                    {{ $request->vehicle->consumerial_name ?? '-' }}
                                </small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $request->gasoline_type }}</span></td>
                            <td>Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</td>
                            <td><small>{{ $request->request_date->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-clock-history me-1"></i>Menunggu Kendaraan Disetujui
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</section>

<!-- Modal: Reject Vehicle -->
<div class="modal fade" id="rejectVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectVehicleForm" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Tolak Kendaraan Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Peringatan:</strong><br>
                        Menolak kendaraan <strong id="vehicleCodeText"></strong> akan otomatis menolak
                        <strong id="relatedRequestsCount"></strong> permohonan terkait.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Penolakan (Opsional)</label>
                        <textarea class="form-control" name="rejection_notes" rows="3"
                                  placeholder="Alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-2"></i>Tolak Kendaraan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectModal(vehicleId, vehicleCode, requestCount) {
    const form = document.getElementById('rejectVehicleForm');
    form.action = `/superadmin/vehicles/${vehicleId}/reject`;

    document.getElementById('vehicleCodeText').textContent = vehicleCode;
    document.getElementById('relatedRequestsCount').textContent = requestCount;

    const modal = new bootstrap.Modal(document.getElementById('rejectVehicleModal'));
    modal.show();
}

// Auto dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<style>
.info-card {
    border: none;
    box-shadow: 0px 0 20px rgba(1, 41, 112, 0.1);
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.btn-group .btn {
    margin: 0 2px;
}
</style>
@endsection
