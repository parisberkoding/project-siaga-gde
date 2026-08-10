@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Ajukan Perpanjangan Surat</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a>Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a>Daftar Surat</a></li>
            <li class="breadcrumb-item active">Perpanjangan</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        {{-- Form Perpanjangan --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-clock-history"></i> Form Pengajuan Perpanjangan
                    </h5>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Perpanjangan akan <strong>menambah durasi</strong> dari tanggal kembali saat ini.
                        Pastikan alasan perpanjangan jelas agar mudah disetujui.
                    </div>

                    <form action="{{ route('requester.perpanjangan.store') }}" method="POST" id="formExtension" class="needs-validation" novalidate>
                        @csrf

                        {{-- Info Surat Saat Ini --}}
                        <div class="mb-4 p-3 bg-light rounded">
                            <h6 class="mb-3">Informasi Surat Saat Ini</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Nomor Surat</small>
                                    <p class="mb-0 fw-bold">{{ $surat->nomor_surat }}</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Tujuan</small>
                                    <p class="mb-0">{{ $surat->tempat_tujuan }}</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Tanggal Berangkat</small>
                                    <p class="mb-0">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Tanggal Kembali</small>
                                    <p class="mb-0 fw-bold text-danger">{{ \Carbon\Carbon::parse($surat->tanggal_kembali)->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Durasi Saat Ini</small>
                                    <p class="mb-0">{{ $surat->lama_perjalanan_hari }} hari</p>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Tanggal Kembali Baru --}}
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Tanggal Kembali Baru <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="date" name="tanggal_kembali_baru" id="tanggalKembaliBaru"
                                       class="form-control"
                                       min="{{ \Carbon\Carbon::parse($surat->tanggal_kembali)->addDay()->format('Y-m-d') }}"
                                       value="{{ old('tanggal_kembali_baru') }}" required>
                                <small class="text-muted">Harus setelah tanggal kembali saat ini</small>
                                <div class="invalid-feedback">Pilih tanggal kembali yang baru</div>
                            </div>
                        </div>

                        {{-- Durasi Tambahan (Auto Calculate) --}}
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Durasi Tambahan</label>
                            <div class="col-sm-8">
                                <input type="text" id="durasiTambahan" class="form-control-plaintext fw-bold text-primary"
                                       value="-" readonly>
                                <input type="hidden" name="durasi_tambahan" id="durasiTambahanHidden" value="0">
                            </div>
                        </div>

                        {{-- Budget Tambahan (Auto Calculate) --}}
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Budget Tambahan</label>
                            <div class="col-sm-8">
                                <input type="text" id="budgetTambahan" class="form-control-plaintext fw-bold text-success fs-5"
                                       value="Rp 0" readonly>
                                <small class="text-muted">Rp 1.400.000 × durasi tambahan</small>
                            </div>
                        </div>

                        <hr>

                        {{-- Alasan Perpanjangan --}}
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label">Alasan Perpanjangan <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="alasan_perpanjangan" class="form-control" rows="4"
                                          placeholder="Jelaskan alasan mengapa perlu perpanjangan waktu perjalanan..."
                                          required>{{ old('alasan_perpanjangan') }}</textarea>
                                <div class="invalid-feedback">Alasan perpanjangan wajib diisi</div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning" id="btnSubmit" disabled>
                                <i class="bi bi-send"></i> Ajukan Perpanjangan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Preview Perubahan --}}
            <div class="card" id="previewCard" style="display: none;">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-eye"></i> Preview Perubahan
                    </h5>

                    <table class="table table-sm table-borderless">
                        <tr>
                            <td colspan="2" class="text-center pb-3">
                                <small class="text-muted">Tanggal Kembali</small>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="text-decoration-line-through text-muted">
                                        {{ \Carbon\Carbon::parse($surat->tanggal_kembali)->format('d/m/Y') }}
                                    </span>
                                    <i class="bi bi-arrow-right"></i>
                                    <span class="fw-bold text-success" id="previewTanggal">-</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><small class="text-muted">Durasi Lama</small></td>
                            <td class="text-end">{{ $surat->lama_perjalanan_hari }} hari</td>
                        </tr>
                        <tr>
                            <td><small class="text-muted">Durasi Tambahan</small></td>
                            <td class="text-end text-primary" id="previewTambahan">+0 hari</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Durasi Total</strong></td>
                            <td class="text-end fw-bold" id="previewTotal">{{ $surat->lama_perjalanan_hari }} hari</td>
                        </tr>
                        <tr class="border-top">
                            <td><small class="text-muted">Budget Lama</small></td>
                            <td class="text-end">Rp {{ number_format($surat->total_budget, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td><small class="text-muted">Budget Tambahan</small></td>
                            <td class="text-end text-primary" id="previewBudgetTambahan">+Rp 0</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Budget Total</strong></td>
                            <td class="text-end fw-bold text-success" id="previewBudgetTotal">
                                Rp {{ number_format($surat->total_budget, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Info --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle"></i> Informasi
                    </h5>
                    <ul class="ps-3 mb-0">
                        <li class="mb-2">Perpanjangan memerlukan <strong>persetujuan admin</strong></li>
                        <li class="mb-2">Setelah disetujui, tanggal kembali akan diperbarui</li>
                        <li class="mb-2">Budget akan bertambah sesuai durasi tambahan</li>
                        <li>LPJ baru bisa dibuat setelah perpanjangan disetujui</li>
                    </ul>
                </div>
            </div>

            {{-- Perpanjangan Sebelumnya --}}
            @if($surat->extensions->where('status', 'approved')->isNotEmpty())
                <div class="card border-info">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <i class="bi bi-clock-history"></i> Perpanjangan Sebelumnya
                        </h5>
                        @foreach($surat->extensions->where('status', 'approved') as $ext)
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>{{ \Carbon\Carbon::parse($ext->created_at)->format('d/m/Y') }}</span>
                                <span class="text-success">+{{ $ext->durasi_tambahan }} hari</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalKembaliBaru = document.getElementById('tanggalKembaliBaru');
    const durasiTambahan = document.getElementById('durasiTambahan');
    const durasiTambahanHidden = document.getElementById('durasiTambahanHidden');
    const budgetTambahan = document.getElementById('budgetTambahan');
    const btnSubmit = document.getElementById('btnSubmit');
    const previewCard = document.getElementById('previewCard');

    const tanggalKembaliCurrent = new Date("{{ $surat->tanggal_kembali }}");
    const budgetPerHari = 1400000;
    const durasiLama = {{ $surat->lama_perjalanan_hari }};
    const budgetLama = {{ $surat->total_budget }};

    function calculateExtension() {
        if (tanggalKembaliBaru.value) {
            const tanggalBaru = new Date(tanggalKembaliBaru.value);
            const diffTime = tanggalBaru - tanggalKembaliCurrent;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 0) {
                durasiTambahan.value = '+' + diffDays + ' hari';
                durasiTambahanHidden.value = diffDays;

                const budget = diffDays * budgetPerHari;
                budgetTambahan.value = '+Rp ' + budget.toLocaleString('id-ID');

                btnSubmit.disabled = false;
                previewCard.style.display = 'block';

                // Update preview
                document.getElementById('previewTanggal').textContent = formatDate(tanggalBaru);
                document.getElementById('previewTambahan').textContent = '+' + diffDays + ' hari';
                document.getElementById('previewTotal').textContent = (durasiLama + diffDays) + ' hari';
                document.getElementById('previewBudgetTambahan').textContent = '+Rp ' + budget.toLocaleString('id-ID');
                document.getElementById('previewBudgetTotal').textContent = 'Rp ' + (budgetLama + budget).toLocaleString('id-ID');
            } else {
                resetForm();
            }
        } else {
            resetForm();
        }
    }

    function resetForm() {
        durasiTambahan.value = '-';
        durasiTambahanHidden.value = '0';
        budgetTambahan.value = 'Rp 0';
        btnSubmit.disabled = true;
        previewCard.style.display = 'none';
    }

    function formatDate(date) {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    tanggalKembaliBaru.addEventListener('change', calculateExtension);

    // Form validation
    document.getElementById('formExtension').addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });
});
</script>
@endsection
