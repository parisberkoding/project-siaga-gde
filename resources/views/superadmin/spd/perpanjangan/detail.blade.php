@extends('templates.main')

{{-- VIEW PERPANJANGAN DETAIL --}}

@section('content')
<div class="pagetitle">
    <h1>Detail & Validasi Surat Tugas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('surat.superadmin.index') }}">Validasi Surat</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        {{-- Detail Surat (Left Column) --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">
                        <span>Detail Surat Tugas Perjalanan Dinas</span>
                        {{-- Status Badge --}}
                        @if($surat->status == 'pending')
                            <span class="badge bg-warning text-dark fs-6">
                                <i class="bi bi-clock-history"></i> Menunggu Validasi
                            </span>
                        @elseif($surat->status == 'approved')
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle"></i> Disetujui
                            </span>
                        @elseif($surat->status == 'completed')
                            <span class="badge bg-primary fs-6">
                                <i class="bi bi-check-all"></i> Selesai
                            </span>
                        @else
                            <span class="badge bg-danger fs-6">
                                <i class="bi bi-x-circle"></i> Ditolak
                            </span>
                        @endif
                    </h5>

                    {{-- Extension Badge --}}
                    @if($surat->is_extension)
                        <div class="alert alert-info py-2 mb-3">
                            <i class="bi bi-info-circle"></i>
                            Ini adalah <strong>Surat Perpanjangan</strong> dari:
                            <a href="{{ route('surat.superadmin.show', $surat->parentSurat->id) }}">
                                {{ $surat->parentSurat->nomor_surat }}
                            </a>
                        </div>
                    @endif

                    {{-- Nomor Surat --}}
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Nomor Surat:</strong></div>
                        <div class="col-md-8">
                            <span class="fs-5 fw-bold text-primary">{{ $surat->nomor_surat }}</span>
                        </div>
                    </div>

                    <hr>

                    {{-- Informasi Pemberi Tugas --}}
                    <h6 class="text-primary mb-3"><i class="bi bi-person-badge"></i> Pemberi Tugas</h6>
                    <div class="row mb-2">
                        <div class="col-md-4">Nama:</div>
                        <div class="col-md-8"><strong>{{ $surat->pemberi_tugas_nama }}</strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">Jabatan:</div>
                        <div class="col-md-8">{{ $surat->pemberi_tugas_jabatan }}</div>
                    </div>

                    <hr>

                    {{-- Informasi Perjalanan --}}
                    <h6 class="text-success mb-3"><i class="bi bi-geo-alt"></i> Informasi Perjalanan</h6>
                    <div class="row mb-2">
                        <div class="col-md-4">Nama Pengemudi:</div>
                        <div class="col-md-8"><strong>{{ $surat->pengemudi_nama }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Tempat Tujuan:</div>
                        <div class="col-md-8">{{ $surat->tempat_tujuan }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Melayani Bidang:</div>
                        <div class="col-md-8">{{ $surat->melayani_bidang }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Jumlah Pemakai:</div>
                        <div class="col-md-8">{{ $surat->jumlah_pemakai }} orang</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">Kendaraan:</div>
                        <div class="col-md-8">
                            <span class="badge bg-dark">{{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</span>
                            {{ $surat->vehicle->consumerial_name ?? '' }}
                        </div>
                    </div>

                    <hr>

                    {{-- Daftar Nama Lengkap --}}
                    <h6 class="text-info mb-3"><i class="bi bi-people"></i> Daftar Nama Pemakai</h6>
                    @if($surat->details->isNotEmpty())
                        <ol class="mb-3">
                            @foreach($surat->details as $detail)
                                <li>{{ $detail->nama_lengkap }}</li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-muted mb-3">Tidak ada data penumpang</p>
                    @endif

                    <hr>

                    {{-- Jadwal Perjalanan --}}
                    <h6 class="text-warning mb-3"><i class="bi bi-calendar-check"></i> Jadwal Perjalanan</h6>
                    <div class="row mb-2">
                        <div class="col-md-4">Tanggal Berangkat:</div>
                        <div class="col-md-8"><strong>{{ $surat->tanggal_berangkat->translatedFormat('d F Y') }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Tanggal Kembali:</div>
                        <div class="col-md-8"><strong>{{ $surat->tanggal_kembali->translatedFormat('d F Y') }}</strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">Lama Perjalanan:</div>
                        <div class="col-md-8"><span class="badge bg-info fs-6">{{ $surat->lama_perjalanan_hari }} hari</span></div>
                    </div>

                    {{-- Budget (jika sudah diisi) --}}
                    @if($surat->budget_per_hari && $surat->budget_per_hari > 0)
                        <hr>
                        <h6 class="text-success mb-3"><i class="bi bi-cash-stack"></i> Informasi Budget</h6>
                        <div class="row mb-2">
                            <div class="col-md-4">Budget Per Hari:</div>
                            <div class="col-md-8"><strong>Rp {{ number_format($surat->budget_per_hari, 0, ',', '.') }}</strong></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">Total Budget:</div>
                            <div class="col-md-8">
                                <span class="fs-5 fw-bold text-success">Rp {{ number_format($surat->total_budget, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Catatan --}}
                    @if($surat->catatan)
                        <hr>
                        <h6 class="mb-3"><i class="bi bi-journal-text"></i> Catatan</h6>
                        <p class="text-muted">{{ $surat->catatan }}</p>
                    @endif

                    {{-- Riwayat Validasi --}}
                    @if($surat->status != 'pending')
                        <hr>
                        <h6 class="mb-3"><i class="bi bi-clock-history"></i> Riwayat Validasi</h6>
                        <div class="alert {{ $surat->status == 'approved' || $surat->status == 'completed' ? 'alert-success' : 'alert-danger' }} mb-0">
                            @if($surat->status == 'approved' || $surat->status == 'completed')
                                <strong><i class="bi bi-check-circle"></i> Disetujui</strong> oleh
                                <strong>{{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }}</strong><br>
                                <small>{{ $surat->approved_at->translatedFormat('d F Y H:i') }}</small>
                                @if($surat->lpj_deadline)
                                    <br><small class="text-muted">Deadline LPJ: {{ $surat->lpj_deadline->format('d M Y') }}</small>
                                @endif
                            @else
                                <strong><i class="bi bi-x-circle"></i> Ditolak</strong> oleh
                                <strong>{{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }}</strong><br>
                                <small>{{ $surat->rejected_at->translatedFormat('d F Y H:i') }}</small><br>
                                <strong>Alasan:</strong> {{ $surat->rejection_reason }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Child Extensions --}}
            @if($surat->childExtensions && $surat->childExtensions->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-diagram-3"></i> Surat Perpanjangan</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nomor Surat</th>
                                        <th>Tanggal Kembali Baru</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($surat->childExtensions as $ext)
                                        <tr>
                                            <td>{{ $ext->nomor_surat }}</td>
                                            <td>{{ $ext->tanggal_kembali->format('d M Y') }}</td>
                                            <td>
                                                @if($ext->status == 'pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($ext->status == 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('surat.superadmin.show', $ext->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Action Panel (Right Column) --}}
        <div class="col-lg-4">
            {{-- Validasi Actions --}}
            @if($surat->status == 'pending')
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-shield-check"></i> Aksi Validasi</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Periksa detail surat dengan teliti sebelum memvalidasi.
                        </p>

                        {{-- Form Approve dengan Input Budget --}}
                        <form action="{{ route('surat.superadmin.approve', $surat->id) }}" method="POST" id="approveForm">
                            @csrf

                            {{-- Budget Input --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-cash-stack text-success"></i> Budget Per Hari <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="budget_per_hari"
                                           id="budgetPerHari" min="0" step="1000" required
                                           placeholder="Contoh: 500000">
                                </div>
                                <div class="form-text">Tentukan budget harian untuk perjalanan ini</div>
                            </div>

                            {{-- Kalkulasi Budget --}}
                            <div class="alert alert-light border mb-3" id="budgetInfo">
                                <div class="row text-center">
                                    <div class="col-6 border-end">
                                        <small class="text-muted d-block">Lama Perjalanan</small>
                                        <strong class="fs-5">{{ $surat->lama_perjalanan_hari }}</strong>
                                        <small class="text-muted d-block">hari</small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Budget</small>
                                        <strong class="fs-5 text-success" id="totalBudgetCalc">Rp 0</strong>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-check-circle"></i> Setujui Surat Tugas
                            </button>
                        </form>

                        <hr>

                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle"></i> Tolak Surat Tugas
                        </button>
                    </div>
                </div>
            @endif

            {{-- Print Button --}}
            @if($surat->status == 'approved' || $surat->status == 'completed')
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-printer"></i> Cetak Dokumen</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('surat.superadmin.print', $surat->id) }}" target="_blank" class="btn btn-primary w-100">
                            <i class="bi bi-printer"></i> Cetak Surat Tugas
                        </a>
                    </div>
                </div>
            @endif

            {{-- Info Pengajuan --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi Pengajuan</h6>

                    <div class="mb-2">
                        <small class="text-muted">Diajukan oleh:</small><br>
                        <strong>{{ $surat->user->nama_lengkap ?? $surat->user->name }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Tanggal Pengajuan:</small><br>
                        <strong>{{ $surat->created_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted">Terakhir Update:</small><br>
                        <strong>{{ $surat->updated_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>
                </div>
            </div>

            {{-- LPJ Status --}}
            @if($surat->status == 'approved' || $surat->status == 'completed')
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-file-earmark-check"></i> Status LPJ</h6>

                        @if($surat->lpjPenggantian)
                            <div class="alert alert-success mb-0 py-2">
                                <i class="bi bi-check-circle"></i> LPJ sudah disubmit
                                <br><small>Status: {{ ucfirst($surat->lpjPenggantian->status) }}</small>
                            </div>
                        @else
                            <div class="alert {{ $surat->isLpjDeadlineOverdue() ? 'alert-danger' : 'alert-warning' }} mb-0 py-2">
                                @if($surat->isLpjDeadlineOverdue())
                                    <i class="bi bi-exclamation-triangle"></i> LPJ belum disubmit (TERLAMBAT)
                                @else
                                    <i class="bi bi-clock"></i> LPJ belum disubmit
                                @endif
                                @if($surat->lpj_deadline)
                                    <br><small>Deadline: {{ $surat->lpj_deadline->format('d M Y') }}</small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Back Button --}}
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('surat.superadmin.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Tolak Surat Tugas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('surat.superadmin.reject', $surat->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Anda yakin ingin menolak surat tugas ini?</p>
                    <div class="alert alert-light border mb-3">
                        <strong>{{ $surat->nomor_surat }}</strong><br>
                        <small class="text-muted">{{ $surat->pengemudi_nama }} - {{ $surat->tempat_tujuan }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="rejection_reason" rows="4"
                                  placeholder="Jelaskan alasan penolakan..." required minlength="10"></textarea>
                        <div class="form-text">Minimal 10 karakter</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Ya, Tolak Surat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const budgetPerHari = document.getElementById('budgetPerHari');
    const totalBudgetCalc = document.getElementById('totalBudgetCalc');
    const lamaHari = {{ $surat->lama_perjalanan_hari }};

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    }

    function calculateTotal() {
        const budget = parseFloat(budgetPerHari.value) || 0;
        const total = budget * lamaHari;
        totalBudgetCalc.textContent = formatRupiah(total);
    }

    if (budgetPerHari) {
        budgetPerHari.addEventListener('input', calculateTotal);
    }

    // Form validation
    document.getElementById('approveForm')?.addEventListener('submit', function(e) {
        const budget = parseFloat(budgetPerHari.value) || 0;
        if (budget <= 0) {
            e.preventDefault();
            alert('Silakan masukkan budget per hari terlebih dahulu!');
            budgetPerHari.focus();
            return false;
        }

        const total = budget * lamaHari;
        if (!confirm(`Anda yakin ingin menyetujui surat tugas ini?\n\nBudget per hari: ${formatRupiah(budget)}\nTotal budget: ${formatRupiah(total)}\nDeadline LPJ: 3 hari setelah tanggal kembali`)) {
            e.preventDefault();
        }
    });
});
</script>
@endsection
