@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Buat Surat SPD via Superadmin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Shortcut</li>
        </ol>
    </nav>
</div>

<section class="section">
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

    <!-- <div class="alert alert-warning">
        <i class="bi bi-lightning-charge-fill me-1"></i>
        <strong>Shortcut Superadmin:</strong> Surat langsung <strong>disetujui</strong> tanpa flow validasi GA.
    </div> -->

    <form action="{{ route('surat.superadmin.store-shortcut') }}" method="POST" id="formSurat">
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
                                <small class="text-muted">Pilih pejabat dari Master Pekerja atau biarkan untuk input manual.</small>
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

                {{-- Pengemudi (Hibrid: dari Master Pekerja / manual) --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person"></i> Pengemudi</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ambil dari Master Pekerja <small class="text-muted fw-normal">(opsional)</small></label>
                                <select class="form-select" name="pengemudi_employee_id" id="pengemudiEmployee">
                                    <option value="">— Input manual —</option>
                                    @foreach($pengemudiOptions as $peng)
                                        <option value="{{ $peng->id }}"
                                                data-nama="{{ $peng->employee_name }}"
                                                {{ old('pengemudi_employee_id') == $peng->id ? 'selected' : '' }}>
                                            {{ $peng->employee_name }} · {{ $peng->position ?? 'Tanpa jabatan' }} ({{ $peng->defaultSite?->name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Pengemudi lain (tanpa akun) tetap bisa diisi manual.
                                    Pengemudi yang dipilih akan melihat surat ini di menu
                                    <strong>Perjalanan Dinas Saya</strong> dan dapat membuat LPJ.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pengemudi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pengemudi_nama') is-invalid @enderror"
                                       name="pengemudi_nama" id="pengemudiNama"
                                       value="{{ old('pengemudi_nama') }}" required placeholder="Nama lengkap pengemudi">
                                @error('pengemudi_nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                      name="tempat_tujuan" rows="2" required>{{ old('tempat_tujuan') }}</textarea>
                            @error('tempat_tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Melayani Bidang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('melayani_bidang') is-invalid @enderror"
                                       name="melayani_bidang" value="{{ old('melayani_bidang') }}" required>
                                @error('melayani_bidang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jumlah Pemakai <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('jumlah_pemakai') is-invalid @enderror"
                                       name="jumlah_pemakai" value="{{ old('jumlah_pemakai', 1) }}"
                                       min="1" max="7" required id="jumlahPemakai">
                                @error('jumlah_pemakai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipe SPD <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" name="type" required id="tipeSpd">
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="biasa" {{ old('type') == 'biasa' ? 'selected' : '' }}>Biasa</option>
                                    <option value="mendadak" {{ old('type') == 'mendadak' ? 'selected' : '' }}>Mendadak</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jenis SPD</label>
                                <select class="form-select @error('jenis_spd') is-invalid @enderror" name="jenis_spd" id="jenisSpd">
                                    <option value="">-- Pilih Jenis SPD --</option>
                                    <option value="luar_kota" {{ old('jenis_spd') == 'luar_kota' ? 'selected' : '' }}>SPD Luar Kota</option>
                                    <option value="dalam_kota" {{ old('jenis_spd') == 'dalam_kota' ? 'selected' : '' }}>SPD Dalam Kota</option>
                                    <option value="lainnya" {{ old('jenis_spd') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3" id="jenisSpdLainnyaWrapper" style="display: none;">
                                <label class="form-label">Jenis SPD Lainnya</label>
                                <input type="text" class="form-control" name="jenis_spd_lainnya"
                                       placeholder="Isi jika 'Lainnya'" value="{{ old('jenis_spd_lainnya') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kendaraan <span class="text-danger">*</span></label>
                            <select class="form-select @error('vehicle_id') is-invalid @enderror" name="vehicle_id" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Kode GL & Nominal Pemberian --}}
                <div class="card border-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-credit-card"></i> Kode GL & Nominal Pemberian</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode GL <span class="text-muted">(SAP)</span></label>
                                <input type="text" class="form-control @error('kode_gl') is-invalid @enderror"
                                       name="kode_gl" placeholder="Contoh: 5121000" value="{{ old('kode_gl') }}">
                                @error('kode_gl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Uang yang Diberikan (Rp)</label>
                                <input type="number" class="form-control @error('uang_diberikan') is-invalid @enderror"
                                       name="uang_diberikan" min="0" step="0.01"
                                       placeholder="Nominal uang diberikan ke driver" value="{{ old('uang_diberikan') }}">
                                @error('uang_diberikan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <small class="text-muted">Diisi saat penerbitan surat tugas; dapat ditambah kemudian bila ada penambahan dana.</small>
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
                                       required id="tanggalBerangkat">
                                @error('tanggal_berangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_kembali') is-invalid @enderror"
                                       name="tanggal_kembali" value="{{ old('tanggal_kembali') }}"
                                       required id="tanggalKembali">
                                @error('tanggal_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <div id="containerNama">
                            @for($i = 1; $i <= 7; $i++)
                                <div class="mb-2 row-nama" id="rowNama{{ $i }}" style="{{ $i > 1 ? 'display: none;' : '' }}">
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $i }}.</span>
                                        <input type="text" class="form-control pemakai-input" name="nama_lengkap_{{ $i }}"
                                               data-index="{{ $i }}" autocomplete="off"
                                               placeholder="Ketik nama pemakai ke-{{ $i }}…"
                                               value="{{ old('nama_lengkap_' . $i) }}">
                                        <input type="hidden" name="user_id_{{ $i }}" id="userId{{ $i }}"
                                               value="{{ old('user_id_' . $i) }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Pemberian Dana (per transfer/pemberian, BUKAN per hari) --}}
                <div class="card" id="budgetCard">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-wallet2"></i> Pemberian Dana</h5>
                        <p class="text-muted small mb-2">
                            Catat setiap kali dana diberikan ke driver (boleh lebih dari satu kali: uang muka,
                            transfer susulan, dst). Satu baris = satu pemberian/transfer, tersimpan pada surat SPD ini.
                        </p>
                        @include('partials.pencairan-input', [
                            'name'           => 'pencairans',
                            'rows'           => collect(),
                            'tanggalDefault' => old('tanggal_berangkat', now()->toDateString()),
                            'totalTargetId'  => 'summaryBudget',
                        ])
                    </div>
                </div>

                {{-- Mendadak Notice --}}
                <div class="card border-warning" id="mendadakNotice" style="display: none;">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><i class="bi bi-exclamation-triangle"></i> SPD Mendadak</h5>
                        <p class="mb-0">Pemberian dana akan diisi saat pencetakan surat.</p>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-journal-text"></i> Catatan (Opsional)</h5>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Catatan tambahan...">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="col-lg-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-lightning-charge-fill"></i> Shortcut — Langsung Approve</h6>
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
                            <tr class="border-top">
                                <td class="fw-bold">Total Dana Diberikan:</td>
                                <td class="text-end fw-bold text-success" id="summaryBudget">Rp 0</td>
                            </tr>
                        </table>

                        <div class="alert alert-success py-2 mb-3">
                            <small><i class="bi bi-check-circle"></i> Surat akan langsung <strong>disetujui</strong></small>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-send"></i> Buat & Setujui Langsung
                        </button>

                        <a href="{{ route('surat.superadmin.index') }}" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
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
    const summaryLama = document.getElementById('summaryLama');
    const summaryPemakai = document.getElementById('summaryPemakai');
    const jenisSpd = document.getElementById('jenisSpd');
    const jenisSpdLainnya = document.getElementById('jenisSpdLainnyaWrapper');

    // Toggle input "Lainnya" pada dropdown jenis SPD
    function updateJenisSpd() {
        if (jenisSpd && jenisSpdLainnya) {
            jenisSpdLainnya.style.display = jenisSpd.value === 'lainnya' ? 'block' : 'none';
        }
    }
    if (jenisSpd) {
        jenisSpd.addEventListener('change', updateJenisSpd);
        updateJenisSpd();
    }
    const summaryBudget = document.getElementById('summaryBudget');

    function calculate() {
        const berangkat = new Date(tanggalBerangkat.value);
        const kembali = new Date(tanggalKembali.value);

        if (tanggalBerangkat.value && tanggalKembali.value && kembali >= berangkat) {
            const diffTime = Math.abs(kembali - berangkat);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            lamaHari.textContent = diffDays;
            infoLama.style.display = 'block';
            summaryLama.textContent = diffDays + ' hari';
        } else {
            infoLama.style.display = 'none';
            summaryLama.textContent = '-';
        }
    }

    function updateNamaRows() {
        const count = parseInt(jumlahPemakai.value) || 1;
        for (let i = 1; i <= 7; i++) {
            const row = document.getElementById('rowNama' + i);
            if (row) row.style.display = i <= count ? 'block' : 'none';
        }
        summaryPemakai.textContent = count + ' orang';
    }

    // Pemberian dana kini per transfer (bukan per hari) — kartunya tetap
    // disembunyikan untuk SPD mendadak karena diisi saat pencetakan surat.
    function toggleBudgetByType() {
        const tipe = document.getElementById('tipeSpd').value;
        const budgetCard = document.getElementById('budgetCard');
        const mendadakNotice = document.getElementById('mendadakNotice');

        if (tipe === 'mendadak') {
            budgetCard.style.display = 'none';
            mendadakNotice.style.display = 'block';
        } else {
            budgetCard.style.display = 'block';
            mendadakNotice.style.display = 'none';
        }

        calculate();
    }

    document.getElementById('tipeSpd').addEventListener('change', toggleBudgetByType);

    tanggalBerangkat.addEventListener('change', function() {
        tanggalKembali.min = this.value;
        if (tanggalKembali.value && tanggalKembali.value < this.value) {
            tanggalKembali.value = this.value;
        }
        calculate();
    });

    tanggalKembali.addEventListener('change', calculate);
    jumlahPemakai.addEventListener('change', updateNamaRows);

    updateNamaRows();
    calculate();
    toggleBudgetByType();

    // ── Autofill Pemberi Tugas & Pengemudi dari Master Pekerja ──
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

    const pengemudiEmployee = document.getElementById('pengemudiEmployee');
    const pengemudiNama = document.getElementById('pengemudiNama');
    if (pengemudiEmployee) {
        pengemudiEmployee.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value && opt.dataset.nama && pengemudiNama) {
                pengemudiNama.value = opt.dataset.nama;
            }
        });
    }
});
</script>

{{-- Autocomplete nama pemakai (Master Pekerja) --}}
@include('partials.pemakai-autocomplete')
@endsection
