{{-- Riwayat Approval yang sudah saya proses --}}
<div class="table-responsive">
  <table class="table table-hover" id="myApprovalTable">
    <thead>
      <tr>
        <th>Request ID</th>
        <th>Pemohon</th>
        <th>Tanggal Pengajuan</th>
        <th>Tanggal Approval</th>
        <th>BBM</th>
        <th>Jumlah</th>
        <th>Status</th>
        <th>Voucher</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($myApprovals as $req)
        <tr>
          <td><small class="text-muted">{{ $req->request_number }}</small></td>
          <td><strong>{{ $req->requester->nama_lengkap }}</strong></td>
          <td data-order="{{ $req->request_date->timestamp }}">
            <small>{{ $req->request_date->format('d/m/Y H:i') }}</small>
          </td>
          <td data-order="{{ $req->authorization_date ? $req->authorization_date->timestamp : 0 }}">
            <small>{{ $req->authorization_date ? $req->authorization_date->format('d/m/Y H:i') : '-' }}</small>
          </td>
          <td><span class="badge bg-info">{{ $req->gasoline_type }}</span></td>
          <td data-order="{{ $req->bill_amounts }}">
            <strong>Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</strong>
          </td>
          <td>
            @php
              $statusClass = $req->status == 'approved' ? 'bg-success' :
                           ($req->status == 'completed' ? 'bg-primary' : 'bg-danger');
              $statusIcon = $req->status == 'approved' ? 'bi-check-circle-fill' :
                          ($req->status == 'completed' ? 'bi-check-all' : 'bi-x-circle-fill');
            @endphp
            <span class="badge {{ $statusClass }}">
              <i class="bi {{ $statusIcon }}"></i> {{ strtoupper($req->status) }}
            </span>
          </td>
          <td>
            @if($req->approvedRequest)
              <span class="badge bg-light text-dark" style="font-family: 'Courier New', monospace;">
                {{ $req->approvedRequest->voucher_code }}
              </span>
            @else
              <span class="text-muted">-</span>
            @endif
          </td>
          <td><small class="text-muted">{{ $req->authorizer_notes ?? '-' }}</small></td>
        </tr>
      @empty
        <tr>
          <td colspan="9">
            <div class="text-center py-4 text-muted">
              <i class="bi bi-clipboard-check" style="font-size: 2rem; opacity: 0.5;"></i>
              <div class="mt-2">Belum ada approval yang Anda proses</div>
            </div>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for My Approval tab
    document.getElementById('myapproval-tab').addEventListener('shown.bs.tab', function () {
      const myApprovalTable = document.getElementById('myApprovalTable');
      if (myApprovalTable && myApprovalTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.myApprovalDataTable) {
          window.myApprovalDataTable = new simpleDatatables.DataTable(myApprovalTable, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [5, 10, 25, 50, 100],
            labels: {
              placeholder: "Cari approval...",
              perPage: "Per halaman:",
              noRows: "Tidak ada data",
              info: "Menampilkan {start} sampai {end} dari {rows} data"
            },
            columns: [
              { select: 3, sort: "desc" } // Sort by approval date
            ]
          });
        }
      }
    });
  });
</script>
@endpush
