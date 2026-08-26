@extends('templates.main')

@section('title', 'Approval Permohonan BBM')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" integrity="sha384-CinX6s1UC1GpaQwL9eKshF7JhtC8H2xchDD0dWqlCgZ8t+GCCcjluxCgmsbVpdEh" crossorigin="anonymous">
<style>
  .info-card { padding-bottom: 10px; }
  .info-card h6 { font-size: 28px; color: #012970; font-weight: 700; margin: 0; padding: 0; }
  .card-icon { font-size: 32px; line-height: 0; width: 64px; height: 64px; flex-shrink: 0; flex-grow: 0; }
  .sales-card .card-icon { color: #4154f1; background: #f6f6fe; }
  .customers-card .card-icon { color: #ff771d; background: #ffecdf; }
  .warning-card .card-icon { color: #ffbb2c; background: #fff6e1; }
  .revenue-card .card-icon { color: #2eca6a; background: #e0f8e9; }
  .card { margin-bottom: 30px; border: none; border-radius: 5px; box-shadow: 0px 0 30px rgba(1, 41, 112, 0.1); }
  .card-body { padding: 20px; }

  .bulk-action-bar {
    background: #f1f5ff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    color: #0a1427;
  }
  .bulk-action-bar.show { display: block; animation: slideDown 0.3s ease; }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .action-checkbox-group { display: flex; gap: 15px; align-items: center; }

  @media (max-width: 768px) {
    .info-card h6 { font-size: 24px; }
    .card-icon { font-size: 28px; width: 56px; height: 56px; }
  }
</style>

<div class="pagetitle">
    <h1>Approval Permohonan BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Approval BBM</li>
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

  {{-- Main Card with Tabs --}}
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="bi bi-clipboard-check"></i> Approval Permohonan BBM</span>
      <button class="btn btn-light btn-sm" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
    <div class="card-body">

      {{-- Navigation Tabs --}}
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
      </ul>

      <div class="tab-content">

        {{-- Tab 1: Pending Requests (Bulk Approval) --}}
        <div class="tab-pane fade show active" id="pending-pane">
          <form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm">
            @csrf
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

            {{-- Pending Requests Table --}}
            <div class="table-responsive">
              <table class="table table-hover" id="pendingTable">
                <thead>
                  <tr>
                    <th style="width: 120px;">Request ID</th>
                    <th>Pemohon</th>
                    <th>Tanggal</th>
                    <th>Kendaraan</th>
                    <th>BBM</th>
                    <th>Jumlah</th>
                    <th>Catatan</th>
                    <th>Catatan Admin</th>
                    <th style="width: 220px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($pendingRequests as $req)
                    <tr>
                      <td><small class="text-muted">{{ $req->request_number }}</small></td>
                      <td><strong>{{ $req->requester->nama_lengkap }}</strong></td>
                      <td data-order="{{ $req->request_date->timestamp }}">
                        <small>{{ $req->request_date->format('d/m/Y H:i') }}</small>
                      </td>
                      <td>
                        <div><strong>{{ $req->vehicle->consumerial_name }}</strong></div>
                        <small class="text-muted">{{ $req->vehicle->consumerial_type }}</small>
                      </td>
                      <td><span class="badge badge-sm bg-info">{{ $req->gasoline_type }}</span></td>
                      <td data-order="{{ $req->bill_amounts }}">
                        <strong>Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</strong>
                      </td>
                      <td><small class="text-muted">{{ $req->requester_notes ?? '-' }}</small></td>
                      <td>
                        <input type="text" id="catatan_admin_{{ $req->id }}" class="form-control" placeholder="Catatan Admin (opsional)">
                      </td>
                      <td>
                        <div class="action-checkbox-group">
                          <div class="form-check">
                            <input class="form-check-input decision-checkbox" type="checkbox"
                                   data-id="{{ $req->id }}"
                                   data-action="approve"
                                   id="approve_{{ $req->id }}">
                            <label class="form-check-label text-success" for="approve_{{ $req->id }}">
                              <i class="bi bi-check-circle"></i> Approve
                            </label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input decision-checkbox" type="checkbox"
                                   data-id="{{ $req->id }}"
                                   data-action="reject"
                                   id="reject_{{ $req->id }}">
                            <label class="form-check-label text-danger" for="reject_{{ $req->id }}">
                              <i class="bi bi-x-circle"></i> Reject
                            </label>
                          </div>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="9">
                        <div class="text-center py-4 text-muted">
                          <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                          <div class="mt-2">Tidak ada permohonan pending</div>
                        </div>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </form>
        </div>

        {{-- Tab 2: My Approval History --}}
        <div class="tab-pane fade" id="myapproval-pane">
          @include('admin.myapproval')
        </div>

      </div>
    </div>
  </div>

</section>

<script>
  const bulkDecisions = {};
  let pendingDataTable = null;

  document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeCheckboxes();
  });

  function initializeDataTable() {
    const pendingTable = document.getElementById('pendingTable');
    if (pendingTable && pendingTable.querySelector('tbody tr td:not([colspan])')) {
      try {
        pendingDataTable = new simpleDatatables.DataTable(pendingTable, {
          searchable: true,
          fixedHeight: false,
          perPage: 10,
          perPageSelect: [5, 10, 25, 50, 100],
          labels: {
            placeholder: "Cari permohonan...",
            perPage: "Per halaman:",
            noRows: "Tidak ada data",
            info: "Menampilkan {start} sampai {end} dari {rows} data"
          },
          columns: [
            { select: 2, sort: "desc" },
            { select: 7, sortable: false }
          ]
        });
        setTimeout(() => { initializeCheckboxes(); }, 100);
      } catch (error) {
        console.error('DataTable initialization error:', error);
      }
    }
  }

  function initializeCheckboxes() {
    document.querySelectorAll('.decision-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        handleCheckboxChange(this.dataset.id, this.dataset.action, this.checked);
      });
    });
  }

  function handleCheckboxChange(requestId, action, isChecked) {
    const approveCheckbox = document.getElementById(`approve_${requestId}`);
    const rejectCheckbox = document.getElementById(`reject_${requestId}`);

    if (action === 'approve') {
      if (isChecked) { rejectCheckbox.checked = false; bulkDecisions[requestId] = 'approve'; }
      else { delete bulkDecisions[requestId]; }
    }
    if (action === 'reject') {
      if (isChecked) { approveCheckbox.checked = false; bulkDecisions[requestId] = 'reject'; }
      else { delete bulkDecisions[requestId]; }
    }
    updateBulkActionBar();
  }

  function updateBulkActionBar() {
    const totalSelected = Object.keys(bulkDecisions).length;
    const approveCount = Object.values(bulkDecisions).filter(v => v === 'approve').length;
    const rejectCount = Object.values(bulkDecisions).filter(v => v === 'reject').length;

    document.getElementById('selectedCount').textContent = totalSelected;
    document.getElementById('approveCount').textContent = approveCount;
    document.getElementById('rejectCount').textContent = rejectCount;

    const bar = document.getElementById('bulkActionBar');
    if (totalSelected > 0) { bar.classList.add('show'); }
    else { bar.classList.remove('show'); }
  }

  window.saveBulkActions = function() {
    const totalSelected = Object.keys(bulkDecisions).length;
    if (totalSelected === 0) {
      alert('Tidak ada permohonan yang dipilih');
      return;
    }

    const decisionsArray = [];
    for (const [requestId, status] of Object.entries(bulkDecisions)) {
      decisionsArray.push({ request_id: parseInt(requestId), status: status });
    }

    const approveCount = decisionsArray.filter(d => d.status === 'approve').length;
    const rejectCount = decisionsArray.filter(d => d.status === 'reject').length;
    const message = `Simpan keputusan?\n\nApprove: ${approveCount} permohonan\nReject: ${rejectCount} permohonan`;

    if (!confirm(message)) return;

    document.getElementById('decisionsInput').value = JSON.stringify(decisionsArray);
    document.getElementById('bulkApprovalForm').submit();
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/umd/simple-datatables.min.js" integrity="sha384-BEpG7sFvcL57u3OEtietm61i9CEIOFR3zR2mlbaAtw+CyHWKNNMEJ7lb852F1iNb" crossorigin="anonymous"></script>

@endsection
