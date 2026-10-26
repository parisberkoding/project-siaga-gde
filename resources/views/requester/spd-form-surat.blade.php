@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Surat Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a>Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Buat Surat Tugas</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card py-2 px-2">
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('requester.surat.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf

                        <input type="hidden" class="form-control" name="pemberi_tugas_nama" value="Genny Munteni Yudawinata" required>
                        <input type="hidden" class="form-control" name="pemberi_tugas_jabatan" value="HC, GA & Finance Manager" required>
                        </div>

                        {{-- Informasi Perjalanan --}}
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <strong>Informasi Perjalanan Dinas</strong>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Nama Pengemudi <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="pengemudi_nama"
                                               value="{{ Auth::user()->nama_lengkap }}" readonly required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Tempat Tujuan <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="tempat_tujuan"
                                               value="{{ old('tempat_tujuan') }}" placeholder="DIENG" required>
                                        <div class="invalid-feedback">Tempat tujuan wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Melayani Bidang <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="melayani_bidang"
                                               value="{{ old('melayani_bidang') }}" placeholder="Contoh: Teknik Produksi" required>
                                        <div class="invalid-feedback">Bidang yang dilayani wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Jumlah Pemakai <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" name="jumlah_pemakai"
                                               value="{{ old('jumlah_pemakai', 1) }}" min="1" required>
                                        <div class="invalid-feedback">Jumlah pemakai wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Kendaraan (No. Polisi) <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-select" name="vehicle_id" required>
                                            <option value="">-- Pilih Kendaraan --</option>
                                            @foreach($vehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                                    {{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Kendaraan wajib dipilih.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nama Lengkap Pemakai --}}
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <strong>Daftar Nama Lengkap Pemakai Kendaraan</strong>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-info-circle"></i>
                                    Isi nama lengkap penumpang/pemakai kendaraan. Kosongkan jika tidak ada.
                                </p>

                                @for($i = 1; $i <= 7; $i++)
                                <div class="row mb-2">
                                    <label class="col-sm-2 col-form-label">Nama {{ $i }}</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="nama_lengkap_{{ $i }}"
                                               value="{{ old('nama_lengkap_' . $i) }}" placeholder="Nama lengkap penumpang {{ $i }}">
                                    </div>
                                </div>
                                @endfor
                            </div>
                        </div>

                        {{-- Jadwal Perjalanan --}}
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                <strong>Jadwal Perjalanan</strong>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Tanggal Berangkat <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="date" class="form-control" name="tanggal_berangkat"
                                               value="{{ old('tanggal_berangkat') }}" required>
                                        <div class="invalid-feedback">Tanggal berangkat wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="date" class="form-control" name="tanggal_kembali"
                                               value="{{ old('tanggal_kembali') }}" required>
                                        <div class="invalid-feedback">Tanggal kembali wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Lama Perjalanan</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="lama_perjalanan"
                                               value="{{ old('lama_perjalanan') }}" placeholder="Contoh: 3 hari" readonly>
                                        <small class="text-muted">Akan otomatis terisi berdasarkan tanggal</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Catatan</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" name="catatan" rows="3"
                                                  placeholder="Catatan tambahan jika ada...">{{ old('catatan') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Ajukan Surat Tugas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Auto calculate lama perjalanan
document.addEventListener('DOMContentLoaded', function() {
    const tanggalBerangkat = document.querySelector('input[name="tanggal_berangkat"]');
    const tanggalKembali = document.querySelector('input[name="tanggal_kembali"]');
    const lamaPerjalanan = document.querySelector('input[name="lama_perjalanan"]');

    function hitungLamaPerjalanan() {
        if (tanggalBerangkat.value && tanggalKembali.value) {
            const start = new Date(tanggalBerangkat.value);
            const end = new Date(tanggalKembali.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            lamaPerjalanan.value = diffDays + ' hari';
        }
    }

    tanggalBerangkat.addEventListener('change', hitungLamaPerjalanan);
    tanggalKembali.addEventListener('change', hitungLamaPerjalanan);
});
</script>

@endsection
