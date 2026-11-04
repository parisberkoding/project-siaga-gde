@extends('templates.main')

@section('title', 'Cetak Laporan BBM')

@section('content')

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

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Terjadi kesalahan:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Page Header -->
<div class="d-flex align-items-center mb-4">
    <div class="bg-primary rounded-3 p-3 me-3 shadow-sm">
        <i class="bi bi-printer-fill text-white fs-4"></i>
    </div>
    <div>
        <h4 class="mb-0 fw-bold text-dark">Cetak Laporan BBM</h4>
        <small class="text-muted">Generate laporan transaksi BBM dalam format PDF</small>
    </div>
</div>

<div class="row g-4">
    <!-- Form Card -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-funnel me-2"></i>Filter Laporan
                </h5>
            </div>
            <div class="card-body p-4">
                <form id="printReportForm" action="{{ route('superadmin.print-report') }}" method="GET" target="_blank">
                    @csrf

                    <!-- Date Range -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-event me-1 text-primary"></i>Tanggal Mulai <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                                   name="start_date" id="startDate"
                                   value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required>
                            @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-check me-1 text-primary"></i>Tanggal Akhir <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                                   name="end_date" id="endDate"
                                   value="{{ old('end_date', now()->format('Y-m-d')) }}" required>
                            @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tag me-1 text-primary"></i>Status Permohonan
                        </label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status[]" value="approved"
                                           id="statusApproved" checked>
                                    <label class="form-check-label" for="statusApproved">
                                        <span class="badge bg-success">Approved</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status[]" value="completed"
                                           id="statusCompleted" checked>
                                    <label class="form-check-label" for="statusCompleted">
                                        <span class="badge bg-primary">Completed</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status[]" value="pending"
                                           id="statusPending">
                                    <label class="form-check-label" for="statusPending">
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status[]" value="rejected"
                                           id="statusRejected">
                                    <label class="form-check-label" for="statusRejected">
                                        <span class="badge bg-danger">Rejected</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Default: Approved + Completed</small>
                    </div>

                    <!-- Jenis BBM -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-fuel-pump me-1 text-primary"></i>Jenis BBM (Opsional)
                        </label>
                        <select class="form-select form-select-sm" name="gasoline_type">
                            <option value="">-- Semua Jenis BBM --</option>
                            <option value="Pertalite" {{ old('gasoline_type') == 'Pertalite' ? 'selected' : '' }}>Pertalite</option>
                            <option value="Pertamax" {{ old('gasoline_type') == 'Pertamax' ? 'selected' : '' }}>Pertamax</option>
                            <option value="Pertamina Dex" {{ old('gasoline_type') == 'Pertamina Dex' ? 'selected' : '' }}>Pertamina Dex</option>
                        </select>
                        <small class="text-muted">Kosongkan untuk semua jenis BBM</small>
                    </div>

                    <!-- Filter Approver -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-check me-1 text-primary"></i>Diapprove Oleh (Opsional)
                        </label>
                        <select class="form-select form-select-sm" name="authorizer_id" id="authorizerId">
                            <option value="">-- Semua Approver --</option>
                            @foreach($approvers as $approver)
                                <option value="{{ $approver->id }}" {{ old('authorizer_id') == $approver->id ? 'selected' : '' }}>
                                    {{ $approver->nama_lengkap }} ({{ $approver->role }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Filter laporan berdasarkan siapa yang meng-approve</small>
                    </div>

                    <hr>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4" id="btnGenerate">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                        </button>
                    </div>
                    <input type="hidden" name="download_token" id="downloadToken">
                </form>

                {{-- Loading overlay --}}
                <div id="loadingOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="z-index:9999;background:rgba(0,0,0,0.5);">
                    <div class="bg-white rounded-3 shadow p-4 text-center" style="min-width:260px;">
                        <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6 class="fw-bold mb-1">Membuat PDF...</h6>
                        <small class="text-muted">Mohon tunggu, laporan sedang diproses</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="col-lg-4">
        <!-- Quick Range Presets -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-semibold text-muted">
                    <i class="bi bi-lightning me-2 text-warning"></i>Rentang Cepat
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setRange('today')">
                        <i class="bi bi-calendar-day me-1"></i>Hari Ini
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setRange('week')">
                        <i class="bi bi-calendar-week me-1"></i>Minggu Ini
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setRange('month')">
                        <i class="bi bi-calendar-month me-1"></i>Bulan Ini
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setRange('lastmonth')">
                        <i class="bi bi-calendar2 me-1"></i>Bulan Lalu
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setRange('year')">
                        <i class="bi bi-calendar me-1"></i>Tahun Ini
                    </button>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="card border-0 bg-light shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-2">
                    <i class="bi bi-info-circle me-1 text-info"></i>Informasi
                </h6>
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-1"><i class="bi bi-dot"></i>Laporan berisi data permohonan BBM berdasarkan filter</li>
                    <li class="mb-1"><i class="bi bi-dot"></i>PDF akan dibuka di tab baru</li>
                    <li class="mb-1"><i class="bi bi-dot"></i>Laporan mencakup: Requester, Kendaraan, Jenis BBM, Nominal, Struk</li>
                    <li class="mb-1"><i class="bi bi-dot"></i>File dapat diunduh atau dicetak langsung dari browser</li>
                    <li><i class="bi bi-dot"></i>Rentang tanggal maksimal 1 tahun untuk performa optimal</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Download with loading indicator using token polling
(function () {
    const form = document.getElementById('printReportForm');
    const overlay = document.getElementById('loadingOverlay');
    const tokenInput = document.getElementById('downloadToken');

    form.addEventListener('submit', function (e) {
        // Generate unique token
        const token = 'dl_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
        tokenInput.value = token;

        // Show loading overlay
        overlay.classList.remove('d-none');
        overlay.classList.add('d-flex');
        document.getElementById('btnGenerate').disabled = true;

        // Poll for cookie set by server when PDF is ready
        const cookieName = 'download_ready_' + token;
        const pollInterval = setInterval(function () {
            if (document.cookie.indexOf(cookieName) !== -1) {
                clearInterval(pollInterval);
                // Clean up cookie
                document.cookie = cookieName + '=; Max-Age=0; path=/';
                hideLoading();
            }
        }, 500);

        // Fallback: hide after 60 seconds max
        setTimeout(function () {
            clearInterval(pollInterval);
            hideLoading();
        }, 60000);
    });

    function hideLoading() {
        overlay.classList.add('d-none');
        overlay.classList.remove('d-flex');
        document.getElementById('btnGenerate').disabled = false;
        tokenInput.value = '';
    }
})();

function setRange(range) {
    const today = new Date();
    let startDate, endDate;

    switch(range) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'week':
            const day = today.getDay();
            const diffToMonday = (day === 0 ? -6 : 1 - day);
            startDate = new Date(today);
            startDate.setDate(today.getDate() + diffToMonday);
            endDate = today;
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = today;
            break;
        case 'lastmonth':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = today;
            break;
    }

    document.getElementById('startDate').value = formatDate(startDate);
    document.getElementById('endDate').value = formatDate(endDate);
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function resetForm() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('startDate').value = formatDate(firstDay);
    document.getElementById('endDate').value = formatDate(today);
    document.querySelector('select[name="gasoline_type"]').value = '';
    document.getElementById('authorizerId').value = '';
    document.getElementById('statusApproved').checked = true;
    document.getElementById('statusCompleted').checked = true;
    document.getElementById('statusPending').checked = false;
    document.getElementById('statusRejected').checked = false;
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        try {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        } catch(e) {}
    });
}, 5000);
</script>

@endsection
