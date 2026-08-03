@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Ajukan Perpanjangan Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Home</li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Ajukan Perpanjangan</li>
        </ol>
    </nav>
</div>

<section class="section">
    {{-- Alert Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($suratList->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 mb-3">Tidak ada surat yang bisa diperpanjang</p>
                <p class="text-muted small">Anda hanya bisa mengajukan perpanjangan untuk surat yang sudah disetujui.</p>
                <a href="{{ route('requester.surat.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    @else
        <form action="{{ route('requester.perpanjangan.store') }}" method="POST" id="formPerpanjangan">
            @csrf
            <div class="row">
                {{-- Form Utama --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-clock-history"></i> Form Perpanjangan</h5>

                            {{-- Pilih Surat --}}
                            <div class="mb-4">
                                <label class="form-label">Pilih Surat yang Akan Diperpanjang <span class="text-danger">*</span></label>
                                <select class="form-select @error('surat_spd_id') is-invalid @enderror"
                                        name="surat_spd_id" required id="selectSurat">
                                    <option value="">-- Pilih Surat Tugas --</option>
                                    @foreach($suratList as $surat)
                                        <option value="{{ $surat->id }}"
                                                data-tujuan="{{ $surat->tempat_tujuan }}"
                                                data-berangkat="{{ $surat->tanggal_berangkat->format('d M Y') }}"
                                                data-kembali="{{ $surat->tanggal_kembali->format('Y-m-d') }}"
                                                data-kembali-formatted="{{ $surat->tanggal_kembali->format('d M Y') }}"
                                                data-lama="{{ $surat->lama_perjalanan_hari }}"
                                                data-budget="{{ $surat->budget_per_hari }}"
                                                data-extension-count="{{ $surat->getExtensionCount() }}"
                                                {{ (old('surat_spd_id') == $surat->id || request('surat_id') == $surat->id) ? 'selected' : '' }}>
                                            {{ $surat->nomor_surat }} - {{ Str::limit($surat->tempat_tujuan, 30) }}
                                            @if($surat->getExtensionCount() > 0)
                                                (sudah {{ $surat->getExtensionCount() }}x perpanjangan)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('surat_spd_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Info Surat Terpilih --}}
                            <div id="infoSurat" class="alert alert-info mb-4" style="display: none;">
                                <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Informasi Surat</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Tujuan:</small><br>
                                        <span id="infoTujuan">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Tanggal Perjalanan:</small><br>
                                        <span id="infoTanggal">-</span>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Tanggal Kembali Sekarang:</small><br>
                                        <strong id="infoKembali" class="text-primary">-</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Budget Per Hari:</small><br>
                                        <strong id="infoBudget">-</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Tanggal Kembali Baru --}}
                            <div class="mb-4">
                                <label class="form-label">Tanggal Kembali Baru <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_kembali_baru') is-invalid @enderror"
                                       name="tanggal_kembali_baru" value="{{ old('tanggal_kembali_baru') }}"
                                       required id="tanggalKembaliBaru">
                                @error('tanggal_kembali_baru')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Tanggal harus setelah tanggal kembali sebelumnya</div>
                            </div>

                            {{-- Info Perpanjangan --}}
                            <div id="infoPerpanjangan" class="alert alert-success mb-4" style="display: none;">
                                <div class="row">
                                    <div class="col-md-4 text-center border-end">
                                        <small class="text-muted d-block">Durasi Tambahan</small>
                                        <h4 class="mb-0" id="durasiTambahan">-</h4>
                                        <small>hari</small>
                                    </div>
                                    <div class="col-md-4 text-center border-end">
                                        <small class="text-muted d-block">Budget Tambahan</small>
                                        <h4 class="mb-0" id="budgetTambahan">-</h4>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="text-muted d-block">Deadline LPJ Baru</small>
                                        <h4 class="mb-0" id="deadlineBaru">-</h4>
                                    </div>
                                </div>
                            </div>

                            {{-- Alasan Perpanjangan --}}
                            <div class="mb-3">
                                <label class="form-label">Alasan Perpanjangan <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('alasan_perpanjangan') is-invalid @enderror"
                                          name="alasan_perpanjangan" rows="4" required minlength="10"
                                          placeholder="Jelaskan alasan mengapa perjalanan perlu diperpanjang...">{{ old('alasan_perpanjangan') }}</textarea>
                                @error('alasan_perpanjangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Minimal 10 karakter</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="col-lg-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-send"></i> Submit Perpanjangan</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                Setelah diajukan, perpanjangan akan menunggu persetujuan dari Superadmin.
                            </p>

                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-send"></i> Ajukan Perpanjangan
                            </button>

                            <a href="{{ route('requester.surat.index') }}" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-left"></i> Batal
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi</h6>
                            <ul class="small text-muted mb-0">
                                <li>Perpanjangan akan membuat surat baru</li>
                                <li>Surat lama tetap valid</li>
                                <li>Setiap surat memiliki LPJ masing-masing</li>
                                <li>Deadline LPJ baru = tanggal kembali baru + 3 hari</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectSurat = document.getElementById('selectSurat');
    const tanggalKembaliBaru = document.getElementById('tanggalKembaliBaru');
    const infoSurat = document.getElementById('infoSurat');
    const infoPerpanjangan = document.getElementById('infoPerpanjangan');

    let currentKembali = null;
    let budgetPerHari = 0;

    function formatRupiah(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function updateSuratInfo() {
        const selected = selectSurat.options[selectSurat.selectedIndex];

        if (!selected.value) {
            infoSurat.style.display = 'none';
            infoPerpanjangan.style.display = 'none';
            tanggalKembaliBaru.min = '';
            currentKembali = null;
            return;
        }

        // Show surat info
        document.getElementById('infoTujuan').textContent = selected.dataset.tujuan;
        document.getElementById('infoTanggal').textContent = selected.dataset.berangkat + ' - ' + selected.dataset.kembaliFormatted + ' (' + selected.dataset.lama + ' hari)';
        document.getElementById('infoKembali').textContent = selected.dataset.kembaliFormatted;
        document.getElementById('infoBudget').textContent = 'Rp ' + formatRupiah(selected.dataset.budget);
        infoSurat.style.display = 'block';

        // Set min date for tanggal kembali baru
        currentKembali = new Date(selected.dataset.kembali);
        const minDate = new Date(currentKembali);
        minDate.setDate(minDate.getDate() + 1);
        tanggalKembaliBaru.min = minDate.toISOString().split('T')[0];

        // Store budget
        budgetPerHari = parseFloat(selected.dataset.budget);

        // Update calculation if tanggal already filled
        updateCalculation();
    }

    function updateCalculation() {
        if (!currentKembali || !tanggalKembaliBaru.value) {
            infoPerpanjangan.style.display = 'none';
            return;
        }

        const newKembali = new Date(tanggalKembaliBaru.value);
        if (newKembali <= currentKembali) {
            infoPerpanjangan.style.display = 'none';
            return;
        }

        // Calculate
        const diffTime = Math.abs(newKembali - currentKembali);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const budgetTambahan = diffDays * budgetPerHari;

        // Deadline LPJ baru = tanggal kembali baru + 3 hari
        const deadline = new Date(newKembali);
        deadline.setDate(deadline.getDate() + 3);

        // Update display
        document.getElementById('durasiTambahan').textContent = '+' + diffDays;
        document.getElementById('budgetTambahan').textContent = 'Rp ' + formatRupiah(budgetTambahan);
        document.getElementById('deadlineBaru').textContent = formatDate(deadline);
        infoPerpanjangan.style.display = 'block';
    }

    selectSurat.addEventListener('change', updateSuratInfo);
    tanggalKembaliBaru.addEventListener('change', updateCalculation);

    // Initialize if pre-selected
    updateSuratInfo();
});
</script>
@endsection
