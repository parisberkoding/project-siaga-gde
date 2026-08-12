@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Buat Surat Tugas Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Buat Surat</li>
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

    <form action="{{ route('requester.surat.store') }}" method="POST" id="formSurat">
        @csrf
        <div class="row">
            {{-- Form Utama (Left Column) --}}
            <div class="col-lg-8">
                {{-- Pemberi Tugas (Hibrid: dari Master Pekerja / manual, level AM/Manager) --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person-badge"></i> Pemberi Tugas</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Level Pemberi Tugas <span class="text-danger">*</span></label>
                                <select class="form-select @error('pemberi_tugas_level') is-invalid @enderror" name="pemberi_tugas_level" id="pemberiLevel" required>
                                    <option value="">-- Pilih Level --</option>
                                    <option value="manager" {{ old('pemberi_tugas_level') == 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="assistant_manager" {{ old('pemberi_tugas_level') == 'assistant_manager' ? 'selected' : '' }}>Assistant Manager</option>
                                </select>
                                @error('pemberi_tugas_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ambil dari Master Pekerja <small class="text-muted fw-normal">(opsional)</small></label>
                                <select class="form-select" name="pemberi_employee_id" id="pemberiEmployee">
                                    <option value="">— Input manual —</option>
                                    @foreach($pejabatOptions as $pejabat)
                                        <option value="{{ $pejabat->id }}"
                                                data-nama="{{ $pejabat->employee_name }}"
                                                data-jabatan="{{ $pejabat->position ?? '' }}"
                                                {{ old('pemberi_employee_id') == $pejabat->id ? 'selected' : '' }}>
                                            {{ $pejabat->employee_name }} · {{ $pejabat->position ?? 'Tanpa jabatan' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pemberi Tugas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pemberi_tugas_nama') is-invalid @enderror"
                                       name="pemberi_tugas_nama" id="pemberiNama"
                                       value="{{ old('pemberi_tugas_nama') }}" required placeholder="Nama lengkap pemberi tugas">
                                @error('pemberi_tugas_nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pemberi_tugas_jabatan') is-invalid @enderror"
                                       name="pemberi_tugas_jabatan" id="pemberiJabatan"
                                       value="{{ old('pemberi_tugas_jabatan') }}" required placeholder="Contoh: HC, GA, & Finance Manager">
                                @error('pemberi_tugas_jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Informasi Perjalanan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-geo-alt"></i> Informasi Perjalanan</h5>

                        <div class="mb-3">
                            <label class="form-label">Tempat Tujuan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('tempat_tujuan') is-invalid @enderror"
                                      name="tempat_tujuan" rows="2" required placeholder="Contoh: Kantor PLN Bandung, Jl. Asia Afrika No. 123">{{ old('tempat_tujuan') }}</textarea>
                            @error('tempat_tujuan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Melayani Bidang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('melayani_bidang') is-invalid @enderror"
                                       name="melayani_bidang" value="{{ old('melayani_bidang') }}" required
                                       placeholder="Contoh: Operasional, Teknik, dll">
                                @error('melayani_bidang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jumlah Pemakai <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('jumlah_pemakai') is-invalid @enderror"
                                       name="jumlah_pemakai" value="{{ old('jumlah_pemakai', 1) }}"
                                       min="1" max="7" required id="jumlahPemakai">
                                @error('jumlah_pemakai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kendaraan <span class="text-danger">*</span></label>
                            <select class="form-select @error('vehicle_id') is-invalid @enderror"
                                    name="vehicle_id" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->consumerial_code ?? $vehicle->plat_nomor }} - {{ $vehicle->consumerial_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Jadwal --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-check"></i> Jadwal Perjalanan</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Berangkat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_berangkat') is-invalid @enderror"
                                       name="tanggal_berangkat" value="{{ old('tanggal_berangkat') }}"
                                       required id="tanggalBerangkat" min="{{ date('Y-m-d') }}">
                                @error('tanggal_berangkat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_kembali') is-invalid @enderror"
                                       name="tanggal_kembali" value="{{ old('tanggal_kembali') }}"
                                       required id="tanggalKembali">
                                @error('tanggal_kembali')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info py-2" id="infoLama" style="display: none;">
                            <i class="bi bi-info-circle"></i> Lama perjalanan: <strong id="lamaHari">0</strong> hari
                        </div>
                    </div>
                </div>

                {{-- Daftar Nama Pemakai --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people"></i> Daftar Nama Pemakai</h5>
                        <p class="text-muted small">Masukkan nama lengkap pemakai kendaraan (maksimal 7 orang)</p>

                        <div id="containerNama">
                            @for($i = 1; $i <= 7; $i++)
                                <div class="mb-2 row-nama" id="rowNama{{ $i }}" style="{{ $i > 1 ? 'display: none;' : '' }}">
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $i }}.</span>
                                        <input type="text" class="form-control" name="nama_lengkap_{{ $i }}"
                                               placeholder="Nama lengkap pemakai ke-{{ $i }}"
                                               value="{{ old('nama_lengkap_' . $i) }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-journal-text"></i> Catatan (Opsional)</h5>

                        <textarea class="form-control" name="catatan" rows="3"
                                  placeholder="Catatan tambahan jika diperlukan...">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="col-lg-4">
                {{-- Ringkasan --}}
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Ringkasan Pengajuan</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted">Lama Perjalanan:</td>
                                <td class="text-end"><strong id="summaryLama">-</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jumlah Pemakai:</td>
                                <td class="text-end"><strong id="summaryPemakai">1 orang</strong></td>
                            </tr>
                        </table>

                        <div class="alert alert-info py-2 mb-3">
                            <small><i class="bi bi-info-circle"></i> Budget akan ditentukan oleh Superadmin saat persetujuan</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-send"></i> Ajukan Surat Tugas
                        </button>

                        <a href="{{ route('requester.surat.index') }}" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
                    </div>
                </div>

                {{-- Info --}}
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi</h6>
                        <ul class="small text-muted mb-0">
                            <li>Surat akan diproses oleh Superadmin</li>
                            <li>Budget per hari akan ditentukan saat persetujuan</li>
                            <li>Setelah disetujui, deadline LPJ adalah <strong>3 hari</strong> setelah tanggal kembali</li>
                            <li>Pastikan data yang diisi sudah benar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalBerangkat = document.getElementById('tanggalBerangkat');
    const tanggalKembali = document.getElementById('tanggalKembali');
    const jumlahPemakai = document.getElementById('jumlahPemakai');
    const infoLama = document.getElementById('infoLama');
    const lamaHari = document.getElementById('lamaHari');

    // Summary elements
    const summaryLama = document.getElementById('summaryLama');
    const summaryPemakai = document.getElementById('summaryPemakai');

    // Calculate lama perjalanan
    function calculate() {
        const berangkat = new Date(tanggalBerangkat.value);
        const kembali = new Date(tanggalKembali.value);

        if (tanggalBerangkat.value && tanggalKembali.value && kembali >= berangkat) {
            const diffTime = Math.abs(kembali - berangkat);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

            lamaHari.textContent = diffDays;
            infoLama.style.display = 'block';

            // Update summary
            summaryLama.textContent = diffDays + ' hari';
        } else {
            infoLama.style.display = 'none';
            summaryLama.textContent = '-';
        }
    }

    // Show/hide nama rows based on jumlah pemakai
    function updateNamaRows() {
        const count = parseInt(jumlahPemakai.value) || 1;
        for (let i = 1; i <= 7; i++) {
            const row = document.getElementById('rowNama' + i);
            row.style.display = i <= count ? 'block' : 'none';
        }
        summaryPemakai.textContent = count + ' orang';
    }

    // Set min date for tanggal kembali
    tanggalBerangkat.addEventListener('change', function() {
        tanggalKembali.min = this.value;
        if (tanggalKembali.value && tanggalKembali.value < this.value) {
            tanggalKembali.value = this.value;
        }
        calculate();
    });

    tanggalKembali.addEventListener('change', calculate);
    jumlahPemakai.addEventListener('change', updateNamaRows);

    // Initialize
    updateNamaRows();
    calculate();

    // Autofill Pemberi Tugas dari Master Pekerja
    const pemberiEmployee = document.getElementById('pemberiEmployee');
    const pemberiNama = document.getElementById('pemberiNama');
    const pemberiJabatan = document.getElementById('pemberiJabatan');
    if (pemberiEmployee) {
        pemberiEmployee.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value && opt.dataset.nama) {
                if (pemberiNama) pemberiNama.value = opt.dataset.nama;
                if (pemberiJabatan) pemberiJabatan.value = opt.dataset.jabatan || '';
            }
        });
    }
});
</script>
@endsection
