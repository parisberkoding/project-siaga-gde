@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Laporan Pertanggungjawaban Penggantian</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Laporan Penggantian</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Form Laporan Pertanggungjawaban Biaya Perjalanan</h5>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
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

                    <form action="{{ route('requester.lpj.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf

                        {{-- Informasi Umum --}}
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <strong>Informasi Umum</strong>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Pilih Surat Tugas <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-select" name="surat_spd_id" id="suratSpdSelect" required>
                                            <option value="">-- Pilih Surat Tugas SPD --</option>
                                            @foreach($surat_spd_list as $surat)
                                                <option value="{{ $surat->id }}"
                                                        data-nomor-polisi="{{ $surat->vehicle->plat_nomor }}"
                                                        data-tujuan="{{ $surat->tempat_tujuan }}"
                                                        {{ old('surat_spd_id') == $surat->id ? 'selected' : '' }}>
                                                    {{ $surat->nomor_surat }} - {{ $surat->tempat_tujuan }} ({{ $surat->tanggal_berangkat }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Pilih surat tugas yang sudah disetujui</small>
                                        <div class="invalid-feedback">Surat tugas wajib dipilih.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">No. Polisi</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="nomorPolisi" readonly>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Nama Pemohon <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="nama"
                                               value="{{ old('nama', Auth::user()->nama_lengkap) }}" required>
                                        <div class="invalid-feedback">Nama pemohon wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Jabatan <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="jabatan"
                                               value="{{ old('jabatan') }}" placeholder="Contoh: Driver" required>
                                        <div class="invalid-feedback">Jabatan wajib diisi.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Uraian Perjalanan <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" name="uraian" rows="3"
                                                  placeholder="Jelaskan uraian perjalanan..." required>{{ old('uraian') }}</textarea>
                                        <div class="invalid-feedback">Uraian perjalanan wajib diisi.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Rincian Biaya --}}
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <strong>Rincian Biaya</strong>
                            </div>
                            <div class="card-body">
                                <div id="biayaContainer">
                                    <div class="biaya-item mb-3" data-index="0">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="items[0][tanggal]" required>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small mb-1">Jenis Biaya <span class="text-danger">*</span></label>
                                                <select class="form-select jenis-select" name="items[0][jenis]" required>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="BBM">BBM</option>
                                                    <option value="Tol">Tol</option>
                                                    <option value="Lain-lain">Lain-lain</option>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small mb-1">Nominal <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control biaya-input"
                                                       name="items[0][nominal]" placeholder="0" min="0" required>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small mb-1">Keterangan</label>
                                                <input type="text" class="form-control"
                                                       name="items[0][keterangan]" placeholder="Opsional">
                                            </div>
                                            <div class="col-12 col-md-1 d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-success add-item-btn">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">>
                                    <div class="col-md-3"><strong>Total BBM:</strong></div>
                                    <div class="col-md-3" id="totalBbm">Rp 0</div>
                                    <div class="col-md-3"><strong>Total Tol:</strong></div>
                                    <div class="col-md-3" id="totalTol">Rp 0</div>
                                </div>
                                <div class="row">>
                                    <div class="col-md-3"><strong>Total Portir:</strong></div>
                                    <div class="col-md-3" id="totalPortir">Rp 0 </div>
                                    <div class="col-md-3"><strong>Total Lain-lain:</strong></div>
                                    <div class="col-md-3" id="totalLain">Rp 0</div>
                                </div>
                                <hr>
                                <div class="row">>
                                    <div class="col-md-3"><strong>Grand Total:</strong></div>
                                    <div class="col-md-3" id="grandTotal">Rp 0</div>
                                </div>
                            </div>
                        </div>



                        {{-- Informasi Keuangan --}}
                        {{-- <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                <strong>Informasi Keuangan</strong>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Uang Dari</label>
                                    <div class="col-sm-9">
                                        <select class="form-select" name="uang_dari">
                                            <option value="KAS" {{ old('uang_dari') == 'KAS' ? 'selected' : '' }}>KAS</option>
                                            <option value="Transfer" {{ old('uang_dari') == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Jumlah Diberikan</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="jumlah" id="jumlahDiberikan"
                                                   value="{{ old('jumlah', 0) }}" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Total Pengeluaran</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" id="totalPengeluaran" readonly>
                                        </div>
                                        <small class="text-muted">Otomatis dari total biaya</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Pengembalian</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" id="pengembalian" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Kekurangan</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" id="kekurangan" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Catatan</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="catatan"
                                               value="{{ old('catatan', 'Bon Terlampir') }}"
                                               placeholder="Catatan tambahan">
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Ajukan Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let itemIndex = 1;

// Event delegation untuk add & remove button
document.getElementById('biayaContainer').addEventListener('click', function(e) {
    // Handle add button
    if (e.target.closest('.add-item-btn')) {
        const container = document.getElementById('biayaContainer');
        const newItem = `
            <div class="biaya-item mb-3" data-index="${itemIndex}">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="items[${itemIndex}][tanggal]" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Jenis Biaya <span class="text-danger">*</span></label>
                        <select class="form-select jenis-select" name="items[${itemIndex}][jenis]" required>
                            <option value="">-- Pilih --</option>
                            <option value="BBM">BBM</option>
                            <option value="Tol">Tol</option>
                            <option value="Parkir">Parkir</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Nominal <span class="text-danger">*</span></label>
                        <input type="number" class="form-control biaya-input"
                               name="items[${itemIndex}][nominal]" placeholder="0" min="0" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Keterangan</label>
                        <input type="text" class="form-control"
                               name="items[${itemIndex}][keterangan]" placeholder="Opsional">
                    </div>
                    <div class="col-12 col-md-1 d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-success add-item-btn">
                            <i class="bi bi-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', newItem);
        itemIndex++;
        attachCalculateHandlers();
    }

    // Handle remove button
    if (e.target.closest('.remove-item-btn')) {
        const items = document.querySelectorAll('.biaya-item');
        if (items.length > 1) {
            e.target.closest('.biaya-item').remove();
            calculateTotals();
        } else {
            alert('Minimal harus ada 1 rincian biaya!');
        }
    }
});

// Attach calculate handlers
function attachCalculateHandlers() {
    document.querySelectorAll('.biaya-input, .jenis-select').forEach(el => {
        el.removeEventListener('input', calculateTotals);
        el.removeEventListener('change', calculateTotals);
        el.addEventListener('input', calculateTotals);
        el.addEventListener('change', calculateTotals);
    });
}

function calculateTotals() {
    let totalBbm = 0, totalTol = 0, totalPortir = 0, totalLain = 0;

    document.querySelectorAll('.biaya-item').forEach(item => {
        const jenis = item.querySelector('.jenis-select').value;
        const nominal = parseFloat(item.querySelector('.biaya-input').value) || 0;

        if (jenis === 'BBM') totalBbm += nominal;
        else if (jenis === 'Tol') totalTol += nominal;
        else if (jenis === 'Portir') totalPortir += nominal;
        else if (jenis === 'Lain-lain') totalLain += nominal;
    });

    document.getElementById('totalBbm').textContent = 'Rp ' + totalBbm.toLocaleString('id-ID');
    document.getElementById('totalTol').textContent = 'Rp ' + totalTol.toLocaleString('id-ID');
    document.getElementById('totalPortir').textContent = 'Rp ' + totalPortir.toLocaleString('id-ID');
    document.getElementById('totalLain').textContent = 'Rp ' + totalLain.toLocaleString('id-ID');

    const grandTotal = totalBbm + totalTol + totalPortir + totalLain;
    document.getElementById('grandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
}

// Init
attachCalculateHandlers();
</script>
