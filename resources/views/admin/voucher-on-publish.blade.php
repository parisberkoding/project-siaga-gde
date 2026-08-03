<div class="tab-content">
  {{-- Sub-Tab 3: Prognosa Transaksi (Voucher yang Terbit) --}}
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
                <div style="width: 30px; height: 30px; border: 2px solid #6c757d; border-radius: 4px; margin: 0 auto;"></div>
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
  // Show photo in modal
  function showPhoto(photoUrl, title) {
    if (!photoUrl || photoUrl === '') {
      alert('Foto tidak tersedia');
      return;
    }

    document.getElementById('photoModalTitle').textContent = title;
    document.getElementById('photoModalImage').src = photoUrl;
    document.getElementById('photoModalLink').href = photoUrl;

    const modal = new bootstrap.Modal(document.getElementById('photoModal'));
    modal.show();
  }

  // Filter prognosis table by status
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

  // Export prognosis to CSV
  function exportPrognosisToCSV() {
    const table = document.getElementById('prognosisTable');
    const rows = table.querySelectorAll('tbody tr:not([colspan])');

    if (rows.length === 0) {
      alert('Tidak ada data untuk diexport');
      return;
    }

    let csv = 'Pemohon,Kendaraan/Alat,Kode Kendaraan,Tipe,Nominal,Kode Voucher,Status,Valid Sampai,Checklist\n';

    rows.forEach(row => {
      // Skip if row is hidden by filter
      if (row.style.display === 'none') return;

      const cells = row.querySelectorAll('td');

      // Pemohon
      const pemohon = cells[0].querySelector('strong')?.textContent.trim() || 'Unknown';

      // Kendaraan data
      const vehicleCell = cells[1];
      const kodeKendaraan = vehicleCell.querySelector('.text-primary')?.textContent.trim() || '-';
      const namaKendaraan = vehicleCell.querySelector('.text-muted')?.textContent.trim() || '-';
      const tipeKendaraan = vehicleCell.querySelector('.badge')?.textContent.trim() || '-';

      // Nominal
      const nominal = cells[2].querySelector('strong')?.textContent.trim().replace('Rp ', '').replace(/\./g, '') || '0';

      // Voucher
      const voucherCode = cells[3].querySelector('strong')?.textContent.trim() || '';
      const validUntil = cells[3].querySelector('.text-muted')?.textContent.trim().replace('Valid: ', '') || '';

      // Status
      const status = cells[4].querySelector('.badge')?.textContent.trim() || '';

      // Format untuk CSV (escape koma dan quotes)
      const formatCell = (text) => {
        text = text.replace(/"/g, '""'); // Escape double quotes
        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
          return `"${text}"`;
        }
        return text;
      };

      csv += [
        formatCell(pemohon),
        formatCell(namaKendaraan),
        formatCell(kodeKendaraan),
        formatCell(tipeKendaraan),
        nominal,
        formatCell(voucherCode),
        formatCell(status),
        formatCell(validUntil),
        '' // Checklist column (kosong)
      ].join(',') + '\n';
    });

    // Download CSV
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' }); // Add BOM for Excel
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const timestamp = new Date().toISOString().slice(0, 10);

    link.setAttribute('href', url);
    link.setAttribute('download', `prognosa-voucher-${timestamp}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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

    // Initialize DataTable for Prognosis when prognosis sub-tab is shown
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
