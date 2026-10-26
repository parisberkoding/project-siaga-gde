@php
    $voucherDate = $voucherDate ?? now()->subDay()->toDateString();
@endphp

<div class="tab-content">
  {{-- Sub-Tab: Voucher Terbit + Tracking Approval --}}
  <div class="tab-pane fade show active" id="voucher-prognosis-pane">
    {{-- Header + filter realtime (tanpa tombol) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <div>
        <h6 class="mb-0">Daftar Voucher Terbit & Tracking Approval</h6>
        <small class="text-muted">Filter tanggal & status otomatis · default: kemarin</small>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <label class="form-label mb-0 small text-muted me-1">Tanggal:</label>
        <input type="date" id="voucherDate" class="form-control form-control-sm" value="{{ $voucherDate }}" style="width:auto">
        <select id="voucherStatus" class="form-select form-select-sm" style="width:auto">
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="expired">Expired</option>
        </select>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportVouchers('selected')">
          <i class="bi bi-file-earmark-spreadsheet"></i> Export Terpilih
        </button>
        <button type="button" class="btn btn-success btn-sm" onclick="exportVouchers('all')">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Semua
        </button>
      </div>
    </div>

    {{-- Hidden form untuk export (POST → download xlsx) --}}
    <form id="voucherExportForm" method="POST" action="{{ route('admin.vouchers.export') }}" class="d-none">
      @csrf
      <input type="hidden" name="date" id="exportDate">
      <input type="hidden" name="status" id="exportStatus">
      <div id="exportIdsContainer"></div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle" id="voucherTable">
        <thead class="table-light">
          <tr>
            <th style="width:3%; text-align:center;">
              <input type="checkbox" id="voucherSelectAll" title="Pilih semua">
            </th>
            <th style="width:18%;">Pemohon</th>
            <th style="width:22%;">Kendaraan / Alat</th>
            <th style="width:12%;">Nominal</th>
            <th style="width:15%;">Kode Voucher</th>
            <th style="width:10%;">Status</th>
            <th style="width:20%;">Tracking Approval</th>
          </tr>
        </thead>
        <tbody id="voucherBody">
          @forelse($approvedVouchers as $voucher)
            @php
              $perm = $voucher->permohonan;
              $veh = $perm?->vehicle;
              $isExpired = $voucher->valid_until && \Carbon\Carbon::parse($voucher->valid_until)->isPast();
              $statusBadge = $isExpired ? 'bg-danger' : 'bg-success';
              $statusText = $isExpired ? 'EXPIRED' : strtoupper($voucher->status);
              $statusIcon = $isExpired ? 'bi-x-circle' : 'bi-check-circle';
            @endphp
            <tr data-id="{{ $voucher->id }}" data-status="{{ $isExpired ? 'expired' : 'active' }}">
              <td class="text-center"><input type="checkbox" class="voucher-check" value="{{ $voucher->id }}"></td>
              <td>
                @if($perm && $perm->requester)
                  <strong>{{ $perm->requester->nama_lengkap }}</strong>
                  <div class="small text-muted">{{ $perm->request_number }}</div>
                @else
                  <span class="text-muted">Unknown</span>
                @endif
              </td>
              <td>
                @if($veh)
                  <div><strong class="text-primary">{{ $veh->consumerial_code }}</strong></div>
                  <div class="text-muted small">{{ $veh->consumerial_name }}</div>
                  <span class="badge bg-secondary">{{ $veh->consumerial_type }}</span>
                @else
                  <span class="badge bg-secondary">Kendaraan Dihapus</span>
                @endif
              </td>
              <td><strong class="text-dark">Rp {{ number_format($voucher->approved_amount, 0, ',', '.') }}</strong></td>
              <td>
                <strong style="font-family:'Courier New',monospace; font-size:0.95rem; letter-spacing:0.5px;">
                  {{ $voucher->voucher_code }}
                </strong>
                <div class="small text-muted mt-1"><i class="bi bi-calendar3"></i>
                  Valid: {{ $voucher->valid_until ? \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y H:i') : '-' }}
                </div>
              </td>
              <td>
                <span class="badge {{ $statusBadge }}"><i class="bi {{ $statusIcon }}"></i> {{ $statusText }}</span>
              </td>
              <td class="small">
                <div><i class="bi bi-person-check text-primary"></i>
                  Approver: <strong>{{ $voucher->authorizer?->nama_lengkap ?? '-' }}</strong>
                </div>
                <div><i class="bi bi-clock text-muted"></i>
                  Approval: {{ $voucher->approval_date ? \Carbon\Carbon::parse($voucher->approval_date)->format('d/m/Y H:i') : '-' }}
                </div>
                <div><i class="bi bi-flag text-muted"></i>
                  Permohonan: <span class="badge bg-light text-dark">{{ strtoupper($perm?->status ?? '-') }}</span>
                </div>
                @if($voucher->approval_notes || $perm?->authorizer_notes)
                  <div class="text-muted"><i class="bi bi-sticky"></i>
                    {{ $voucher->approval_notes ?? $perm?->authorizer_notes }}
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7">
              <div class="text-center py-4 text-muted">
                <i class="bi bi-journal-x" style="font-size:2rem; opacity:0.5;"></i>
                <div class="mt-2">Tidak ada voucher terbit pada tanggal ini</div>
              </div>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center px-1 py-2">
      <small class="text-muted"><span id="voucherCount">{{ $approvedVouchers->count() }}</span> voucher</small>
      <small class="text-muted"><span id="voucherSelectedCount">0</span> dipilih</small>
    </div>
  </div>
</div>

<script>
(function () {
  const dateInput = document.getElementById('voucherDate');
  const statusSelect = document.getElementById('voucherStatus');
  const tbody = document.getElementById('voucherBody');
  const selectAll = document.getElementById('voucherSelectAll');
  const countLabel = document.getElementById('voucherCount');
  const selectedLabel = document.getElementById('voucherSelectedCount');
  const exportForm = document.getElementById('voucherExportForm');
  const exportDate = document.getElementById('exportDate');
  const exportStatus = document.getElementById('exportStatus');
  const idsContainer = document.getElementById('exportIdsContainer');

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function statusBadge(isExpired, rawStatus) {
    if (isExpired) return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> EXPIRED</span>';
    return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> ' + (rawStatus || 'AKTIF') + '</span>';
  }

  function rowHtml(r) {
    const checked = '';
    return '<tr data-id="' + r.id + '" data-status="' + r.status + '">' +
      '<td class="text-center"><input type="checkbox" class="voucher-check" value="' + r.id + '"></td>' +
      '<td><strong>' + escapeHtml(r.pemohon) + '</strong>' +
        '<div class="small text-muted">' + escapeHtml(r.request_number) + '</div></td>' +
      '<td><div><strong class="text-primary">' + escapeHtml(r.vehicle_code) + '</strong></div>' +
        '<div class="text-muted small">' + escapeHtml(r.vehicle_name) + '</div>' +
        '<span class="badge bg-secondary">' + escapeHtml(r.vehicle_type) + '</span></td>' +
      '<td><strong class="text-dark">Rp ' + formatNumber(r.approved_amount) + '</strong></td>' +
      '<td><strong style="font-family:\'Courier New\',monospace; font-size:0.95rem; letter-spacing:0.5px;">' + escapeHtml(r.voucher_code) + '</strong>' +
        '<div class="small text-muted mt-1"><i class="bi bi-calendar3"></i> Valid: ' + escapeHtml(r.valid_until) + '</div></td>' +
      '<td>' + statusBadge(r.status === 'expired', r.status_label) + '</td>' +
      '<td class="small">' +
        '<div><i class="bi bi-person-check text-primary"></i> Approver: <strong>' + escapeHtml(r.approver) + '</strong></div>' +
        '<div><i class="bi bi-clock text-muted"></i> Approval: ' + escapeHtml(r.approval_date) + '</div>' +
        '<div><i class="bi bi-flag text-muted"></i> Permohonan: <span class="badge bg-light text-dark">' + escapeHtml(r.permohonan_status) + '</span></div>' +
        (r.approval_notes ? '<div class="text-muted"><i class="bi bi-sticky"></i> ' + escapeHtml(r.approval_notes) + '</div>' : '') +
      '</td>' +
    '</tr>';
  }

  function emptyHtml() {
    return '<tr><td colspan="7"><div class="text-center py-4 text-muted">' +
      '<i class="bi bi-journal-x" style="font-size:2rem; opacity:0.5;"></i>' +
      '<div class="mt-2">Tidak ada voucher terbit pada tanggal ini</div></div></td></tr>';
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatNumber(n) {
    return Number(n || 0).toLocaleString('id-ID');
  }

  function loadVouchers() {
    const date = dateInput.value;
    const status = statusSelect.value;
    const url = '{{ route("admin.vouchers.list") }}?date=' + encodeURIComponent(date) + '&status=' + encodeURIComponent(status);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => {
        if (!res.data || res.data.length === 0) {
          tbody.innerHTML = emptyHtml();
        } else {
          tbody.innerHTML = res.data.map(rowHtml).join('');
        }
        countLabel.textContent = res.count ?? 0;
        updateSelectedCount();
        selectAll.checked = false;
      })
      .catch(() => { tbody.innerHTML = emptyHtml(); countLabel.textContent = 0; });
  }

  function updateSelectedCount() {
    const checked = tbody.querySelectorAll('.voucher-check:checked').length;
    selectedLabel.textContent = checked;
  }

  // Realtime filter — tanpa tombol
  dateInput.addEventListener('change', loadVouchers);
  statusSelect.addEventListener('change', loadVouchers);

  // Select all
  selectAll.addEventListener('change', function () {
    tbody.querySelectorAll('.voucher-check').forEach(cb => { cb.checked = this.checked; });
    updateSelectedCount();
  });

  // Row checkbox (delegated)
  tbody.addEventListener('change', function (e) {
    if (e.target.classList.contains('voucher-check')) updateSelectedCount();
  });

  // Export
  window.exportVouchers = function (mode) {
    exportDate.value = dateInput.value;
    exportStatus.value = statusSelect.value;
    idsContainer.innerHTML = '';

    if (mode === 'selected') {
      const ids = Array.from(tbody.querySelectorAll('.voucher-check:checked')).map(cb => cb.value);
      if (ids.length === 0) { alert('Pilih minimal satu voucher untuk diexport.'); return; }
      ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        idsContainer.appendChild(inp);
      });
    }
    exportForm.submit();
  };

  // Inisialisasi hitung terpilih
  updateSelectedCount();
})();
</script>
