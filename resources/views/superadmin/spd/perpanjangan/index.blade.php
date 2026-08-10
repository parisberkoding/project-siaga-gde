@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Manajemen Perpanjangan SPD</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Perpanjangan</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Perlu Approval</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['pending'] }}</h6>
                            <span class="text-muted small pt-2">Menunggu persetujuan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Perlu Checklist</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['acknowledged'] }}</h6>
                            <span class="text-muted small pt-2">Perlu acknowledge</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['done'] }}</h6>
                            <span class="text-muted small pt-2">Proses selesai</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Ditolak</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['rejected'] }}</h6>
                            <span class="text-muted small pt-2">Permohonan ditolak</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card with Tabs --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-clock-history"></i> Daftar Pengajuan Perpanjangan
            </h5>

            {{-- Tabs --}}
            <ul class="nav nav-tabs nav-tabs-bordered" id="extTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative" id="pending-tab" data-bs-toggle="tab"
                            data-bs-target="#pending" type="button" role="tab">
                        Perlu Approval
                        @if($stats['pending'] > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $stats['pending'] }}</span>
                        @endif
                    </button>
                </li>


                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="acknowledge-tab" data-bs-toggle="tab"
                            data-bs-target="#acknowledge" type="button" role="tab">
                        Perlu Checklist
                        @if($stats['need_acknowledge'] > 0)
                            <span class="badge bg-info ms-1">{{ $stats['need_acknowledge'] }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="done-tab" data-bs-toggle="tab"
                            data-bs-target="#done" type="button" role="tab">
                        Selesai
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rejected-tab" data-bs-toggle="tab"
                            data-bs-target="#rejected" type="button" role="tab">
                        Ditolak
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content pt-3" id="extTabsContent">
                {{-- Pending Tab --}}
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    @if($ext_pending->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 mb-0">Tidak ada perpanjangan yang perlu diapprove</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Original</th>
                                        <th>Driver</th>
                                        <th>Tujuan</th>
                                        <th>Perpanjangan</th>
                                        <th>Durasi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ext_pending as $ext)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $ext->originalSurat->nomor_surat }}</span>
                                            </td>
                                            <td>
                                                {{ $ext->originalSurat->pengemudi_nama }}
                                                <br><small class="text-muted">{{ $ext->originalSurat->user->email ?? '' }}</small>
                                            </td>
                                            <td>{{ Str::limit($ext->originalSurat->tempat_tujuan, 25) }}</td>
                                            <td>
                                                <small class="text-muted">Dari:</small> {{ $ext->tanggal_kembali_original->format('d M Y') }}<br>
                                                <small class="text-success">Ke:</small> <strong>{{ $ext->tanggal_kembali_baru->format('d M Y') }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">+{{ $ext->durasi_tambahan_hari }} hari</span>
                                                <br><small class="text-muted">Rp {{ number_format($ext->budget_tambahan, 0, ',', '.') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-success btn-ext-approve"
                                                            data-id="{{ $ext->id }}"
                                                            data-nomor="{{ $ext->originalSurat->nomor_surat }}"
                                                            data-driver="{{ $ext->originalSurat->pengemudi_nama }}"
                                                            data-durasi="{{ $ext->durasi_tambahan_hari }}"
                                                            title="Setujui">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-ext-reject"
                                                            data-id="{{ $ext->id }}"
                                                            data-nomor="{{ $ext->originalSurat->nomor_surat }}"
                                                            data-driver="{{ $ext->originalSurat->pengemudi_nama }}"
                                                            title="Tolak">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Need Acknowledge Tab --}}
                <div class="tab-pane fade" id="acknowledge" role="tabpanel">
                    @if($ext_need_acknowledge->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Tidak ada perpanjangan yang perlu di-checklist</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Perpanjangan</th>
                                        <th>Driver</th>
                                        <th>Perpanjangan</th>
                                        <th>Disetujui</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ext_need_acknowledge as $ext)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $ext->nomor_surat_extension }}</span>
                                                <br><small class="text-muted">Dari: {{ $ext->originalSurat->nomor_surat }}</small>
                                            </td>
                                            <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                            <td>
                                                <span class="badge bg-info">+{{ $ext->durasi_tambahan_hari }} hari</span>
                                                <br><small>s/d {{ $ext->tanggal_kembali_baru->format('d M Y') }}</small>
                                            </td>
                                            <td>
                                                {{ $ext->approver->nama_lengkap ?? $ext->approver->name ?? '-' }}
                                                <br><small class="text-muted">{{ $ext->approved_at->format('d M Y H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-info btn-ext-acknowledge"
                                                        data-id="{{ $ext->id }}"
                                                        data-nomor="{{ $ext->nomor_surat_extension }}"
                                                        data-driver="{{ $ext->originalSurat->pengemudi_nama }}"
                                                        data-durasi="{{ $ext->durasi_tambahan_hari }}">
                                                    <i class="bi bi-check2-square"></i> Checklist
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Done Tab --}}
                <div class="tab-pane fade" id="done" role="tabpanel">
                    @if($ext_acknowledged->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Belum ada perpanjangan yang selesai</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Perpanjangan</th>
                                        <th>Driver</th>
                                        <th>Perpanjangan</th>
                                        <th>Checklist Oleh</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ext_acknowledged as $ext)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $ext->nomor_surat_extension }}</span>
                                            </td>
                                            <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                            <td>
                                                <span class="badge bg-success">+{{ $ext->durasi_tambahan_hari }} hari</span>
                                            </td>
                                            <td>
                                                {{ $ext->acknowledger->nama_lengkap ?? $ext->acknowledger->name ?? '-' }}
                                                <br><small class="text-muted">{{ $ext->acknowledged_at->format('d M Y H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('surat.superadmin.show', $ext->extensionSurat) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Rejected Tab --}}
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    @if($ext_rejected->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Tidak ada perpanjangan yang ditolak</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Original</th>
                                        <th>Driver</th>
                                        <th>Ditolak Oleh</th>
                                        <th>Alasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ext_rejected as $ext)
                                        <tr>
                                            <td>{{ $ext->originalSurat->nomor_surat }}</td>
                                            <td>{{ $ext->originalSurat->pengemudi_nama }}</td>
                                            <td>
                                                {{ $ext->rejector->nama_lengkap ?? $ext->rejector->name ?? '-' }}
                                                <br><small class="text-muted">{{ $ext->rejected_at->format('d M Y H:i') }}</small>
                                            </td>
                                            <td>{{ Str::limit($ext->rejection_reason, 50) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal Approve --}}
<div class="modal fade" id="modalApprove" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formApprove" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Setujui Perpanjangan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menyetujui perpanjangan ini?</p>
                    <div class="alert alert-light border">
                        <strong id="approveNomor">-</strong><br>
                        <small class="text-muted" id="approveDriver">-</small><br>
                        <small class="text-info" id="approveDurasi">-</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Ya, Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Reject --}}
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formReject" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Tolak Perpanjangan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menolak perpanjangan ini?</p>
                    <div class="alert alert-light border">
                        <strong id="rejectNomor">-</strong><br>
                        <small class="text-muted" id="rejectDriver">-</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan" class="form-control" rows="3" required minlength="10" placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg"></i> Ya, Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Acknowledge --}}
<div class="modal fade" id="modalAcknowledge" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formAcknowledge" method="POST">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-check2-square"></i> Checklist Perpanjangan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Konfirmasi bahwa perpanjangan ini telah diproses (surat sudah dicetak)?</p>
                    <div class="alert alert-light border">
                        <strong id="ackNomor">-</strong><br>
                        <small class="text-muted" id="ackDriver">-</small><br>
                        <small class="text-success" id="ackDurasi">-</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan untuk dokumentasi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check2-square"></i> Checklist Selesai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Approve Modal
    document.querySelectorAll('.btn-ext-approve').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('approveNomor').textContent = this.dataset.nomor;
            document.getElementById('approveDriver').textContent = 'Driver: ' + this.dataset.driver;
            document.getElementById('approveDurasi').textContent = 'Tambahan: +' + this.dataset.durasi + ' hari';
            document.getElementById('formApprove').action = `{{ url('admin/spd/perpanjangan') }}/${id}/approve`;
            new bootstrap.Modal(document.getElementById('modalApprove')).show();
        });
    });

    // Handle Reject Modal
    document.querySelectorAll('.btn-ext-reject').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('rejectNomor').textContent = this.dataset.nomor;
            document.getElementById('rejectDriver').textContent = 'Driver: ' + this.dataset.driver;
            document.getElementById('formReject').action = `{{ url('admin/spd/perpanjangan') }}/${id}/reject`;
            new bootstrap.Modal(document.getElementById('modalReject')).show();
        });
    });

    // Handle Acknowledge Modal
    document.querySelectorAll('.btn-ext-acknowledge').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('ackNomor').textContent = this.dataset.nomor;
            document.getElementById('ackDriver').textContent = 'Driver: ' + this.dataset.driver;
            document.getElementById('ackDurasi').textContent = 'Disetujui: +' + this.dataset.durasi + ' hari';
            document.getElementById('formAcknowledge').action = `{{ url('admin/spd/perpanjangan') }}/${id}/acknowledge`;
            new bootstrap.Modal(document.getElementById('modalAcknowledge')).show();
        });
    });
});
</script>
@endsection
