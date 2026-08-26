@extends('templates.main')

@section('title', 'Admin Dashboard')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" integrity="sha384-CinX6s1UC1GpaQwL9eKshF7JhtC8H2xchDD0dWqlCgZ8t+GCCcjluxCgmsbVpdEh" crossorigin="anonymous">
<style>
  /* NiceAdmin Info Card Style */
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
    background: #fff6e1;
  }

  .card {
    margin-bottom: 30px;
    border: none;
    border-radius: 5px;
    box-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
  }

  .card-body {
    padding: 20px;
  }

  @media (max-width: 768px) {
    .info-card h6 {
      font-size: 24px;
    }

    .card-icon {
      font-size: 28px;
      width: 56px;
      height: 56px;
    }
  }
  .bulk-action-bar {
    background: #ffffff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: none;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    color: #212529;
  }

  .bulk-action-bar.show {
    display: block;
    animation: slideDown 0.3s ease;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .action-checkbox-group {
    display: flex;
    gap: 15px;
    align-items: center;
  }

  .form-check-label {
    font-size: 0.9rem;
    margin-left: 5px;
    cursor: pointer;
  }

  .nav-tabs .nav-link {
    color: #899bbd;
    border: none;
    border-bottom: 2px solid transparent;
  }

  .nav-tabs .nav-link.active {
    color: #4154f1;
    border-bottom: 2px solid #4154f1;
    background: transparent;
  }
</style>

<div class="pagetitle">
    <h1>Admin Dashboard</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

{{-- Alert Messages --}}
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

<section class="section dashboard">

{{-- Statistics Cards --}}
  <div class="row">
    <!-- Pending Card -->
    <div class="col-xxl-3 col-md-6">
      <div class="card info-card warning-card">
        <div class="card-body">
          <h5 class="card-title">Pending</h5>
          <div class="d-flex align-items-center">
            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="ps-3">
              <h6>{{ $stats['pending'] }}</h6>
              <span class="text-muted small pt-2 ps-1">Menunggu approval</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Approved Card -->
    <div class="col-xxl-3 col-md-6">
      <div class="card info-card sales-card">
        <div class="card-body">
          <h5 class="card-title">Approved</h5>
          <div class="d-flex align-items-center">
            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
              <i class="bi bi-check-circle"></i>
            </div>
            <div class="ps-3">
              <h6>{{ $stats['approved'] }}</h6>
              <span class="text-muted small pt-2 ps-1">Telah disetujui</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejected Card -->
    <div class="col-xxl-3 col-md-6">
      <div class="card info-card customers-card">
        <div class="card-body">
          <h5 class="card-title">Rejected</h5>
          <div class="d-flex align-items-center">
            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
              <i class="bi bi-x-circle"></i>
            </div>
            <div class="ps-3">
              <h6>{{ $stats['rejected'] }}</h6>
              <span class="text-muted small pt-2 ps-1">Ditolak</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Total Card -->
    <div class="col-xxl-3 col-md-6">
      <div class="card info-card revenue-card">
        <div class="card-body">
          <h5 class="card-title">Total Permohonan</h5>
          <div class="d-flex align-items-center">
            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
              <i class="bi bi-list-check"></i>
            </div>
            <div class="ps-3">
              <h6>{{ $stats['total'] }}</h6>
              <span class="text-muted small pt-2 ps-1">Semua permohonan</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Card with Tabs -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul"></i> Permohonan BBM</span>
      <button class="btn btn-light btn-sm" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
    <div class="card-body">
      <!-- Navigation Tabs -->
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button">
            <i class="bi bi-hourglass-split"></i> Permohonan Pending ({{ $stats['pending'] }})
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="myapproval-tab" data-bs-toggle="tab" data-bs-target="#myapproval-pane" type="button">
            <i class="bi bi-person-check"></i> Riwayat Approval Saya
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button">
            <i class="bi bi-clock-history"></i> Semua History & Laporan
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="voucher-tab" data-bs-toggle="tab" data-bs-target="#voucher-pane" type="button">
            <i class="bi bi-ticket-perforated"></i> Kode Voucher Kemarin
          </button>
        </li>
      </ul>

      <div class="tab-content">

<!-- Tab 1: Pending Requests (Main - Bulk Approval) -->
<div class="tab-pane fade show active" id="pending-pane">
  {{-- Form untuk Bulk Action --}}
  <form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm">
    @csrf

    {{-- Hidden input untuk menyimpan decisions dalam format JSON --}}
    <input type="hidden" name="decisions" id="decisionsInput" value="">

    {{-- Bulk Action Bar --}}
    <div class="bulk-action-bar" id="bulkActionBar">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">
          <i class="bi bi-check-square-fill"></i>
          <strong><span id="selectedCount">0</span></strong> permohonan dipilih
          <small class="ms-2">
            (<span id="approveCount" class="text-success">0</span> approve,
            <span id="rejectCount" class="text-danger">0</span> reject)
          </small>
        </span>
        <button class="btn btn-light" type="button" onclick="window.saveBulkActions()">
          <i class="bi bi-save"></i> Simpan Keputusan
        </button>
      </div>
    </div>

    {{-- Daftar Permohonan Pending (Card per request) --}}
    <div class="row g-3" id="pendingCards">
      @forelse($pendingRequests as $req)
        <div class="col-12">
          <div class="card request-card" id="card_{{ $req->id }}">
            <div class="card-body py-3">
              <div class="row align-items-center g-3">

                {{-- Kolom 1: Informasi Permohonan --}}
                <div class="col-12 col-lg-4">
                  <small class="text-muted" style="opacity: .75; font-size: 12px;">
                    {{ $req->request_number }} &middot; {{ $req->request_date->format('d/m/Y H:i') }}
                  </small>
                  <div class="fw-bold text-dark mt-1" style="font-size: 13px;">
                    {{ $req->requester->nama_lengkap }}
                  </div>
                  <div class="fw-bold text-dark" style="font-size: 12px;">
                    {{ $req->vehicle->consumerial_code }} - {{ $req->vehicle->consumerial_name }}
                  </div>
                  <div class="fw-bold text-primary" style="font-size: 12px;">
                    Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}
                  </div>
                  <span class="badge badge-sm bg-info mt-1">{{ $req->gasoline_type }}</span>
                  @if($req->requester_notes)
                    <small class="d-block text-muted mt-1" style="font-size: 12px;">
                      <i class="bi bi-chat-dots"></i> {{ $req->requester_notes }}
                    </small>
                  @endif
                </div>

                {{-- Kolom 2: Komentar Admin --}}
                <div class="col-12 col-lg-4">
                  <label class="form-label small text-muted mb-1" for="catatan_admin_{{ $req->id }}">
                    <i class="bi bi-chat-left-text"></i> Komentar Admin
                  </label>
                  <input type="text" name="catatan_admin[]" id="catatan_admin_{{ $req->id }}"
                         class="form-control form-control-sm" placeholder="Tulis komentar..." autocomplete="off">
                </div>

                {{-- Kolom 3: Tombol Keputusan --}}
                <div class="col-12 col-lg-4 d-flex justify-content-lg-end">
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm decision-btn"
                            data-id="{{ $req->id }}" data-action="approve" id="approve_{{ $req->id }}">
                      <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm decision-btn"
                            data-id="{{ $req->id }}" data-action="reject" id="reject_{{ $req->id }}">
                      <i class="bi bi-x-circle"></i> Reject
                    </button>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
            <div class="mt-2">Tidak ada permohonan pending</div>
          </div>
        </div>
      @endforelse
    </div>
  </form>
</div>

        <!-- Tab 2: My Approval History -->
        <div class="tab-pane fade" id="myapproval-pane">
          @include('admin.myapproval')
        </div>

        <!-- Tab 3: All History & Reports -->
        <div class="tab-pane fade" id="history-pane">
          @include('admin.requester_reports')
        </div>

        <!-- Tab 4: Kode Voucher yang terbit kemarin -->
        <div class="tab-pane fade" id="voucher-pane">
          @include('admin.voucher-on-publish')
        </div>
      </div>
    </div>
  </div>

</section>

<script>
  // ✅ Global object untuk menyimpan keputusan
  // Format: { "123": "approve", "124": "reject" }
  const bulkDecisions = {};

  // Initialize everything when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeDecisionButtons();
  });

  // Attach click listener ke setiap tombol keputusan
  function initializeDecisionButtons() {
    document.querySelectorAll('.decision-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const requestId = this.dataset.id;
        const action = this.dataset.action;
        toggleDecision(requestId, action, this);
      });
    });
  }

  // Toggle keputusan approve/reject (mutually exclusive per request)
  function toggleDecision(requestId, action, btn) {
    const approveBtn = document.getElementById(`approve_${requestId}`);
    const rejectBtn = document.getElementById(`reject_${requestId}`);

    if (bulkDecisions[requestId] === action) {
      delete bulkDecisions[requestId];
      setButtonState(approveBtn, false);
      setButtonState(rejectBtn, false);
    } else {
      bulkDecisions[requestId] = action;
      setButtonState(approveBtn, action === 'approve');
      setButtonState(rejectBtn, action === 'reject');
    }

    updateBulkActionBar();
  }

  // Ubah tampilan tombol sesuai state aktif/non-aktif
  function setButtonState(btn, active) {
    if (!btn) return;
    const isApprove = btn.dataset.action === 'approve';
    btn.classList.remove(isApprove ? 'btn-success' : 'btn-danger', isApprove ? 'btn-outline-success' : 'btn-outline-danger');
    btn.classList.add(active
      ? (isApprove ? 'btn-success' : 'btn-danger')
      : (isApprove ? 'btn-outline-success' : 'btn-outline-danger'));
  }

  // Update bulk action bar
  function updateBulkActionBar() {
    const totalSelected = Object.keys(bulkDecisions).length;
    const approveCount = Object.values(bulkDecisions).filter(v => v === 'approve').length;
    const rejectCount = Object.values(bulkDecisions).filter(v => v === 'reject').length;

    document.getElementById('selectedCount').textContent = totalSelected;
    document.getElementById('approveCount').textContent = approveCount;
    document.getElementById('rejectCount').textContent = rejectCount;

    const bulkActionBar = document.getElementById('bulkActionBar');
    if (totalSelected > 0) {
      bulkActionBar.classList.add('show');
    } else {
      bulkActionBar.classList.remove('show');
    }
  }

  // Simpan keputusan bulk (+ komentar admin per request)
  window.saveBulkActions = function() {
    const totalSelected = Object.keys(bulkDecisions).length;
    if (totalSelected === 0) {
      alert('Tidak ada permohonan yang dipilih');
      return;
    }

    // Format: [{ "request_id": 123, "status": "approve", "note": "..." }, ...]
    const decisionsArray = [];
    for (const [requestId, status] of Object.entries(bulkDecisions)) {
      const input = document.getElementById(`catatan_admin_${requestId}`);
      const note = input ? input.value.trim() : '';
      decisionsArray.push({
        request_id: parseInt(requestId),
        status: status,
        note: note || null
      });
    }

    const approveCount = decisionsArray.filter(d => d.status === 'approve').length;
    const rejectCount = decisionsArray.filter(d => d.status === 'reject').length;

    const message = `Simpan keputusan?\n\n` +
                    `Approve: ${approveCount} permohonan\n` +
                    `Reject: ${rejectCount} permohonan`;

    if (!confirm(message)) {
      return;
    }

    // ✅ Set hidden input dengan JSON string
    document.getElementById('decisionsInput').value = JSON.stringify(decisionsArray);
    document.getElementById('bulkApprovalForm').submit();
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/umd/simple-datatables.min.js" integrity="sha384-BEpG7sFvcL57u3OEtietm61i9CEIOFR3zR2mlbaAtw+CyHWKNNMEJ7lb852F1iNb" crossorigin="anonymous"></script>

@endsection
