{{-- Sub-tabs untuk History dan Reports --}}
<ul class="nav nav-pills mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="all-requests-subtab" data-bs-toggle="tab" data-bs-target="#all-requests-pane" type="button">
      <i class="bi bi-list-check"></i> Semua Permohonan
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="reports-subtab" data-bs-toggle="tab" data-bs-target="#reports-pane" type="button">
      <i class="bi bi-file-earmark-check"></i> Laporan Transaksi
    </button>
  </li>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="prognosis-subtab" data-bs-toggle="tab" data-bs-target="#prognosis-pane" type="button">
      <i class="bi bi-journal-check"></i> Prognosa Transaksi
    </button>
  </li>
</ul>

<div class="tab-content">
  {{-- Sub-Tab 1: All Requests --}}
  <div class="tab-pane fade show active" id="all-requests-pane">
    <div class="table-responsive">
      <table class="table table-hover" id="allRequestsTable">
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Driver</th>
            <th>Tanggal</th>
            <th>Kendaraan</th>
            <th>BBM</th>
            <th>Jumlah</th>
            <th>Status</th>
            <th>Voucher</th>
            <th>Admin</th>
          </tr>
        </thead>
       <tbody>
  @forelse($allRequests as $req)
    <tr>
      {{-- Request ID --}}
      <td><small class="text-muted">{{ $req->request_number }}</small></td>

      {{-- Driver --}}
      <td>
        @if($req->requester)
          <strong>{{ $req->requester->nama_lengkap }}</strong>
        @else
          <span class="text-muted">Unknown User</span>
        @endif
      </td>

      {{-- Tanggal --}}
      <td data-order="{{ $req->request_date->timestamp }}">
        <small>{{ $req->request_date->format('d/m/Y H:i') }}</small>
      </td>

      {{-- Kendaraan --}}
      <td>
        @if($req->vehicle)
          <div>
            <strong class="text-primary">{{ $req->vehicle->consumerial_code }}</strong>
          </div>
          <div><strong>{{ $req->vehicle->consumerial_name }}</strong></div>
          <small class="text-muted">{{ $req->vehicle->consumerial_type }}</small>
        @else
          <span class="badge bg-secondary">
            <i class="bi bi-exclamation-triangle"></i> Kendaraan Dihapus
          </span>
        @endif
      </td>

      {{-- BBM --}}
      <td><span class="badge bg-info">{{ $req->gasoline_type }}</span></td>

      {{-- Jumlah --}}
      <td data-order="{{ $req->bill_amounts }}">
        <strong>Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</strong>
      </td>

      {{-- Status --}}
      <td>
        @php
          $statusClass = $req->status == 'pending' ? 'bg-warning text-dark' :
                       ($req->status == 'pending_vehicle_approval' ? 'bg-info' :
                       ($req->status == 'approved' ? 'bg-success' :
                       ($req->status == 'completed' ? 'bg-primary' :
                       ($req->status == 'cancelled' ? 'bg-secondary' : 'bg-danger'))));

          $statusIcon = $req->status == 'pending' ? 'bi-hourglass-split' :
                      ($req->status == 'pending_vehicle_approval' ? 'bi-clock-history' :
                      ($req->status == 'approved' ? 'bi-check-circle-fill' :
                      ($req->status == 'completed' ? 'bi-check-all' :
                      ($req->status == 'cancelled' ? 'bi-x-octagon' : 'bi-x-circle-fill'))));
        @endphp
        <span class="badge {{ $statusClass }}">
          <i class="bi {{ $statusIcon }}"></i> {{ strtoupper($req->status) }}
        </span>
      </td>

      {{-- Voucher --}}
      <td>
        @if($req->approvedRequest)
          <span class="badge bg-light text-dark" style="font-family: 'Courier New', monospace;">
            {{ $req->approvedRequest->voucher_code }}
          </span>
        @else
          <span class="text-muted">-</span>
        @endif
      </td>

      {{-- Admin --}}
      <td>
        @if($req->authorizer)
          <small class="text-muted">{{ $req->authorizer->nama_lengkap }}</small>
        @else
          <small class="text-muted">-</small>
        @endif
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="9">
        <div class="text-center py-4 text-muted">
          <i class="bi bi-file-earmark-text" style="font-size: 2rem; opacity: 0.5;"></i>
          <div class="mt-2">Belum ada permohonan</div>
        </div>
      </td>
    </tr>
  @endforelse
</tbody>
      </table>
    </div>
  </div>

  {{-- Sub-Tab 2: Transaction Reports --}}
  <div class="tab-pane fade" id="reports-pane">
    <div class="table-responsive">
      <table class="table table-hover" id="reportsTable">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Request ID</th>
            <th>Pemohon</th>
            <th>Kendaraan</th>
            <th>Voucher</th>
            <th>Tanggal Pengisian</th>
            <th>Volume (L)</th>
            <th>KM Akhir</th>
            <!--<th>Foto</th>-->
            <th>Tanggal Lapor</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $report)
            <tr>
              <td><small class="text-muted">{{ $report->transaction_id }}</small></td>
              <td><small class="text-muted">{{ $report->permohonan->request_number }}</small></td>
              <td><strong>{{ $report->permohonan->requester->nama_lengkap }}</strong></td>
             <td>
                @if($report->permohonan && $report->permohonan->vehicle)
                    <div>
                    <small class="fw-bold">{{ $report->permohonan->vehicle->consumerial_code }}</small>
                    </div>
                    <small class="text-muted">{{ $report->permohonan->vehicle->consumerial_name }}</small>
                @else
                    <span class="badge bg-secondary">Kendaraan Dihapus</span>
                @endif
                </td>
              <td>
                <span class="badge bg-light text-dark" style="font-family: 'Courier New', monospace; font-size: 0.75rem;">
                  {{ $report->voucher_number }}
                </span>
              </td>
              <td data-order="{{ $report->purchase_datetime ? strtotime($report->purchase_datetime) : 0 }}">
                <small>{{ $report->purchase_datetime ? date('d/m/Y H:i', strtotime($report->purchase_datetime)) : '-' }}</small>
              </td>
              <td><strong>{{ $report->fuel_volume }} L</strong></td>
              <td><strong>{{ number_format($report->km_terakhir, 0, ',', '.') }}</strong></td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                    @if($report->odometer_photo_path)
                    <button class="btn btn-outline-primary btn-sm"
                            onclick="loadAndShowPhoto({{ $report->id }}, 'odometer', 'Foto Odometer')"
                            title="Lihat Foto KM">
                        <i class="bi bi-speedometer2"></i>
                    </button>
                    @endif

                    @if($report->struk_bbm_path)
                    <button class="btn btn-outline-success btn-sm"
                            onclick="loadAndShowPhoto({{ $report->id }}, 'struk', 'Foto Struk BBM')"
                            title="Lihat Foto Struk">
                        <i class="bi bi-receipt"></i>
                    </button>
                    @endif
                </div>
                </td>
              <td data-order="{{ $report->created_at->timestamp }}">
                <small>{{ $report->created_at->format('d/m/Y H:i') }}</small>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10">
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-file-earmark-x" style="font-size: 2rem; opacity: 0.5;"></i>
                  <div class="mt-2">Belum ada laporan transaksi</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ✅ ✅ ✅ Sub-Tab 3: Prognosa Transaksi (Voucher yang Terbit) --}}
  <div class="tab-pane fade" id="prognosis-pane">
    {{-- Header dengan tombol export --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="mb-0">Daftar Voucher yang Sudah Terbit</h6>
        <small class="text-muted">Voucher dengan status Approved dan Expired untuk validasi</small>
      </div>
      <button type="button" class="btn btn-success btn-sm" onclick="exportPrognosisToCSV()">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
      </button>
    </div>

    {{-- Filter Status --}}
    <div class="mb-3">
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-primary active" onclick="filterPrognosis('all')">
          <i class="bi bi-list"></i> Semua
        </button>
        <button type="button" class="btn btn-outline-success" onclick="filterPrognosis('approved')">
          <i class="bi bi-check-circle"></i> Aktif
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="filterPrognosis('cancelled')">
          <i class="bi bi-x-circle"></i> Expired
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-bordered" id="prognosisTable">
        <thead class="table-light">
          <tr>
            <th style="width: 20%;">Pemohon</th>
            <th style="width: 25%;">Kendaraan / Alat</th>
            <th style="width: 15%;">Nominal</th>
            <th style="width: 20%;">Kode Voucher</th>
            <th style="width: 10%;">Status</th>
            <th style="width: 10%; text-align: center;">Checklist</th>
          </tr>
        </thead>
        <tbody>
          @forelse($approvedVouchers as $voucher)
            @php
              // Tentukan apakah expired
              $isExpired = \Carbon\Carbon::parse($voucher->valid_until)->isPast();
              $statusBadge = $isExpired ? 'bg-danger' : 'bg-success';
              $statusText = $isExpired ? 'EXPIRED' : 'AKTIF';
              $statusIcon = $isExpired ? 'bi-x-circle' : 'bi-check-circle';
              $rowClass = $isExpired ? 'table-danger' : '';
            @endphp
            <tr class="{{ $rowClass }}" data-status="{{ $isExpired ? 'cancelled' : 'approved' }}">
              {{-- Pemohon --}}
              <td>
                @if($voucher->permohonan && $voucher->permohonan->requester)
                  <strong>{{ $voucher->permohonan->requester->nama_lengkap }}</strong>
                @else
                  <span class="text-muted">Unknown</span>
                @endif
              </td>

              {{-- Kendaraan / Alat --}}
              <td>
                @if($voucher->permohonan && $voucher->permohonan->vehicle)
                  <div>
                    <strong class="text-primary">{{ $voucher->permohonan->vehicle->consumerial_code }}</strong>
                  </div>
                  <div class="text-muted small">{{ $voucher->permohonan->vehicle->consumerial_name }}</div>
                  <span class="badge bg-secondary badge-sm">{{ $voucher->permohonan->vehicle->consumerial_type }}</span>
                @else
                  <span class="badge bg-secondary">Kendaraan Dihapus</span>
                @endif
              </td>

              {{-- Nominal --}}
              <td>
                <strong class="text-dark">Rp {{ number_format($voucher->approved_amount, 0, ',', '.') }}</strong>
              </td>

              {{-- Kode Voucher (Bold) --}}
              <td>
                <strong style="font-family: 'Courier New', monospace; font-size: 0.95rem; letter-spacing: 0.5px;">
                  {{ $voucher->voucher_code }}
                </strong>
                <div class="small text-muted mt-1">
                  <i class="bi bi-calendar3"></i>
                  Valid: {{ \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y H:i') }}
                </div>
              </td>

              {{-- Status --}}
              <td>
                <span class="badge {{ $statusBadge }}">
                  <i class="bi {{ $statusIcon }}"></i> {{ $statusText }}
                </span>
              </td>

              {{-- Checklist (kosong untuk diisi manual) --}}
              <td style="text-align: center; vertical-align: middle;">
                <div style="width: 30px; height: 30px; border: 2px solid #64748B; border-radius: 4px; margin: 0 auto;"></div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6">
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-journal-x" style="font-size: 2rem; opacity: 0.5;"></i>
                  <div class="mt-2">Belum ada voucher yang terbit</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Modal Preview Foto --}}
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="photoModalTitle">Preview Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="photoModalImage" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
      </div>
      <div class="modal-footer">
        <a id="photoModalLink" href="" target="_blank" class="btn btn-primary">
          <i class="bi bi-box-arrow-up-right"></i> Buka di Tab Baru
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>

// ✅ NEW: Load and show photo on-demand via AJAX
function loadAndShowPhoto(reportId, type, title) {
  // Show loading indicator
  const loadingHtml = `
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Memuat foto...</p>
    </div>
  `;

  // Set loading state in modal
  document.getElementById('photoModalTitle').textContent = title;
  document.getElementById('photoModalImage').style.display = 'none';
  document.querySelector('#photoModal .modal-body').innerHTML = loadingHtml;
  document.getElementById('photoModalLink').style.display = 'none';

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('photoModal'));
  modal.show();

  // Fetch image via AJAX
  fetch(`/transaction-photo/${reportId}/${type}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Foto tidak ditemukan');
      }
      return response.json();
    })
    .then(data => {
      if (!data.success || !data.base64) {
        throw new Error('Format data tidak valid');
      }

      // Restore modal body
      const modalBody = document.querySelector('#photoModal .modal-body');
      modalBody.innerHTML = '<img id="photoModalImage" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">';

      // Set image
      const img = document.getElementById('photoModalImage');
      img.src = data.base64;
      img.style.display = 'block';

      // Optional: Show filename
      if (data.filename) {
        document.getElementById('photoModalTitle').textContent = `${title} - ${data.filename}`;
      }
    })
    .catch(error => {
      // Show error
      document.querySelector('#photoModal .modal-body').innerHTML = `
        <div class="text-center py-5">
          <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
          <p class="mt-3 text-danger">Gagal memuat foto</p>
          <p class="text-muted small">${error.message}</p>
        </div>
      `;
    });
}


  // Show photo in modal
  // ✅ NEW: Show photo using base64 data
function showPhotoBase64(base64Data, title) {
  if (!base64Data || base64Data === '') {
    alert('Foto tidak tersedia');
    return;
  }

  document.getElementById('photoModalTitle').textContent = title;
  document.getElementById('photoModalImage').src = base64Data;

  // Disable "Buka di Tab Baru" button karena base64 tidak bisa dibuka langsung
  const linkBtn = document.getElementById('photoModalLink');
  linkBtn.style.display = 'none';

  const modal = new bootstrap.Modal(document.getElementById('photoModal'));
  modal.show();
}

// ✅ KEEP old function for backward compatibility (if needed elsewhere)
function showPhoto(photoUrl, title) {
  if (!photoUrl || photoUrl === '') {
    alert('Foto tidak tersedia');
    return;
  }

  document.getElementById('photoModalTitle').textContent = title;
  document.getElementById('photoModalImage').src = photoUrl;
  document.getElementById('photoModalLink').href = photoUrl;
  document.getElementById('photoModalLink').style.display = '';

  const modal = new bootstrap.Modal(document.getElementById('photoModal'));
  modal.show();
}

  // ✅ ✅ ✅ Filter prognosis table by status
  function filterPrognosis(status) {
    const table = document.getElementById('prognosisTable');
    const rows = table.querySelectorAll('tbody tr:not([colspan])');

    // Update button active state
    document.querySelectorAll('[onclick^="filterPrognosis"]').forEach(btn => {
      btn.classList.remove('active');
    });
    event.target.closest('button').classList.add('active');

    rows.forEach(row => {
      if (status === 'all') {
        row.style.display = '';
      } else {
        const rowStatus = row.getAttribute('data-status');
        row.style.display = rowStatus === status ? '' : 'none';
      }
    });
  }

  // ✅ ✅ ✅ Export prognosis to CSV
  function exportPrognosisToCSV() {
    const table = document.getElementById('prognosisTable');
    const rows = table.querySelectorAll('tbody tr:not([colspan])');

    if (rows.length === 0) {
      alert('Tidak ada data untuk diexport');
      return;
    }

    // ✅ Format header dengan separator yang jelas
    let csv = 'Pemohon,Kendaraan/Alat,Kode Kendaraan,Tipe,Nominal,Kode Voucher,Status,Valid Sampai,Checklist\r\n';

    rows.forEach(row => {
      // Skip if row is hidden by filter
      if (row.style.display === 'none') return;

      const cells = row.querySelectorAll('td');

      // Pemohon - bersihkan whitespace dan newline
      let pemohon = cells[0].querySelector('strong')?.textContent || 'Unknown';
      pemohon = pemohon.trim().replace(/\s+/g, ' ');

      // Kendaraan data - bersihkan whitespace
      const vehicleCell = cells[1];
      let kodeKendaraan = vehicleCell.querySelector('.text-primary')?.textContent || '-';
      kodeKendaraan = kodeKendaraan.trim().replace(/\s+/g, ' ');

      let namaKendaraan = vehicleCell.querySelector('.text-muted')?.textContent || '-';
      namaKendaraan = namaKendaraan.trim().replace(/\s+/g, ' ');

      let tipeKendaraan = vehicleCell.querySelector('.badge')?.textContent || '-';
      tipeKendaraan = tipeKendaraan.trim().replace(/\s+/g, ' ');

      // Nominal - hanya angka tanpa Rp dan titik
      let nominal = cells[2].querySelector('strong')?.textContent || '0';
      nominal = nominal.replace(/[^0-9]/g, ''); // Hapus semua karakter non-digit

      // Voucher - bersihkan whitespace
      let voucherCode = cells[3].querySelector('strong')?.textContent || '';
      voucherCode = voucherCode.trim().replace(/\s+/g, ' ');

      // Valid until - ambil hanya bagian tanggal, bersihkan
      let validUntil = cells[3].querySelector('.text-muted')?.textContent || '';
      validUntil = validUntil.replace('Valid:', '').trim().replace(/\s+/g, ' ');

      // Status - bersihkan whitespace
      let status = cells[4].querySelector('.badge')?.textContent || '';
      status = status.trim().replace(/\s+/g, ' ');

      // ✅ Fungsi format yang lebih ketat untuk CSV
      const formatCell = (text) => {
        // Bersihkan text
        text = String(text).trim();
        // Escape double quotes dengan double-double quotes
        text = text.replace(/"/g, '""');
        // Selalu wrap dengan quotes untuk keamanan
        return `"${text}"`;
      };

      // ✅ Build row dengan CRLF line ending (standar CSV)
      csv += [
        formatCell(pemohon),
        formatCell(namaKendaraan),
        formatCell(kodeKendaraan),
        formatCell(tipeKendaraan),
        formatCell(nominal),
        formatCell(voucherCode),
        formatCell(status),
        formatCell(validUntil),
        '""' // Checklist column (kosong tapi tetap di-quote)
      ].join(',') + '\r\n';
    });

    // ✅ Download CSV dengan BOM untuk Excel compatibility
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const timestamp = new Date().toISOString().slice(0, 10);

    link.setAttribute('href', url);
    link.setAttribute('download', `prognosa-voucher-${timestamp}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Cleanup
    URL.revokeObjectURL(url);
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for All Requests when history tab is shown
    document.getElementById('history-tab').addEventListener('shown.bs.tab', function () {
      const allRequestsTable = document.getElementById('allRequestsTable');
      if (allRequestsTable && allRequestsTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.allRequestsDataTable) {
          window.allRequestsDataTable = new simpleDatatables.DataTable(allRequestsTable, {
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
              { select: 2, sort: "desc" } // Sort by date
            ]
          });
        }
      }
    });

    // Initialize DataTable for Reports when reports sub-tab is shown
    document.getElementById('reports-subtab').addEventListener('shown.bs.tab', function () {
      const reportsTable = document.getElementById('reportsTable');
      if (reportsTable && reportsTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.reportsDataTable) {
          window.reportsDataTable = new simpleDatatables.DataTable(reportsTable, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [5, 10, 25, 50, 100],
            labels: {
              placeholder: "Cari laporan...",
              perPage: "Per halaman:",
              noRows: "Tidak ada data",
              info: "Menampilkan {start} sampai {end} dari {rows} data"
            },
            columns: [
              { select: 9, sort: "desc" }, // Sort by report date
              { select: 8, sortable: false } // Disable sort on photo column
            ]
          });
        }
      }
    });

    // ✅ ✅ ✅ Initialize DataTable for Prognosis when prognosis sub-tab is shown
    document.getElementById('prognosis-subtab').addEventListener('shown.bs.tab', function () {
      const prognosisTable = document.getElementById('prognosisTable');
      if (prognosisTable && prognosisTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.prognosisDataTable) {
          window.prognosisDataTable = new simpleDatatables.DataTable(prognosisTable, {
            searchable: true,
            fixedHeight: false,
            perPage: 15,
            perPageSelect: [10, 15, 25, 50, 100],
            labels: {
              placeholder: "Cari voucher...",
              perPage: "Per halaman:",
              noRows: "Tidak ada data",
              info: "Menampilkan {start} sampai {end} dari {rows} data"
            },
            columns: [
              { select: 5, sortable: false } // Disable sort on checklist column
            ]
          });
        }
      }
    });
  });
</script>
