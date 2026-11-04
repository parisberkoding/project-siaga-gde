@extends('templates.main')

{{--
    Lampiran Pemberian Dana Perjalanan Dinas — kelola baris pencairan.
    Hanya bisa diakses oleh Divisi General Affairs (permission `lpj.pencairan`).

    Setiap perubahan (tambah/ubah/hapus) otomatis menyinkronkan
    `jumlah_diberikan` pada LPJ dengan total pencairan.
--}}

@section('content')
<div class="pagetitle">
    <h1>Lampiran Pemberian Dana Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('lpj.superadmin.index') }}">Validasi LPJ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('lpj.superadmin.show', $laporan) }}">{{ $laporan->lpj_number }}</a></li>
            <li class="breadcrumb-item active">Pemberian Dana</li>
        </ol>
    </nav>
</div>

<section class="section">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            {{-- Ringkasan --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-cash-stack"></i> Ringkasan Dana</h5>

                    <div class="row small">
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>No. Surat Dinas</td>
                                    <td class="text-end"><strong>{{ $surat->nomor_surat ?? '-' }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Kendaraan</td>
                                    <td class="text-end">{{ $laporan->kendaraan_label }}</td>
                                </tr>
                                <tr>
                                    <td>Uang Dari</td>
                                    <td class="text-end">{{ $laporan->uang_dari ?? 'KAS' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>Balance Awal (total pencairan)</td>
                                    <td class="text-end"><strong>Rp {{ number_format($total, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Pengeluaran</td>
                                    <td class="text-end">Rp {{ number_format((float) $laporan->total_pengeluaran, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="{{ ($total - (float) $laporan->total_pengeluaran) < 0 ? 'table-warning' : 'table-success' }}">
                                    <td>Balance Akhir</td>
                                    <td class="text-end">
                                        <strong>Rp {{ number_format($total - (float) $laporan->total_pengeluaran, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <a href="{{ route('spd.laporan.pemberian-dana', $laporan) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer"></i> Cetak Lembar Pemberian Dana
                        </a>
                        <a href="{{ route('lpj.superadmin.show', $laporan) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Detail LPJ
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tabel pencairan --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list-ul"></i> Rincian Pencairan</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Nomor Referensi Perbankan</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Bentuk Pencairan</th>
                                    <th>Sumber</th>
                                    <th>Rek. Tujuan</th>
                                    <th style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pencairans as $index => $pencairan)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $pencairan->tanggal ? $pencairan->tanggal->translatedFormat('d M Y') : '-' }}</td>
                                        <td>{{ $pencairan->bank_reference ?: '-' }}</td>
                                        <td class="text-end">Rp {{ number_format((float) $pencairan->nominal, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge {{ $pencairan->isTransfer() ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ $pencairan->bentuk_pencairan_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($pencairan->isPerpanjangan())
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-arrow-repeat"></i> Perpanjangan
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark border">{{ $pencairan->sumber_label }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $pencairan->isTransfer() ? ($pencairan->rek_tujuan ?: '-') : '-' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#editPencairan{{ $pencairan->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('lpj.superadmin.pencairan.destroy', [$laporan, $pencairan]) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus baris pencairan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada data pencairan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($pencairans->isNotEmpty())
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Grand Total</th>
                                        <th class="text-end">Rp {{ number_format($total, 0, ',', '.') }}</th>
                                        <th colspan="4"></th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form tambah --}}
        <div class="col-lg-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Tambah Pencairan</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('lpj.superadmin.pencairan.store', $laporan) }}" method="POST">
                        @csrf

                        <div class="mb-2">
                            <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm"
                                   value="{{ old('tanggal', now()->toDateString()) }}" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control form-control-sm"
                                   value="{{ old('nominal') }}" min="0" step="0.01" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Bentuk Pencairan <span class="text-danger">*</span></label>
                            <select name="bentuk_pencairan" class="form-select form-select-sm" required
                                    onchange="toggleTransferFields(this, 'add')">
                                <option value="Tunai" {{ old('bentuk_pencairan') === 'Tunai' ? 'selected' : '' }}>Tunai</option>
                                <option value="Transfer" {{ old('bentuk_pencairan') === 'Transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                        </div>

                        <div class="mb-2 transfer-field-add d-none">
                            <label class="form-label small mb-1">Nomor Referensi Perbankan</label>
                            <input type="text" name="bank_reference" class="form-control form-control-sm"
                                   value="{{ old('bank_reference') }}" maxlength="255">
                        </div>

                        <div class="mb-2 transfer-field-add d-none">
                            <label class="form-label small mb-1">Rekening Tujuan</label>
                            <input type="text" name="rek_tujuan" class="form-control form-control-sm"
                                   value="{{ old('rek_tujuan') }}" maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm"
                                   value="{{ old('catatan') }}" maxlength="255">
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-save"></i> Simpan Pencairan
                        </button>
                        <small class="text-muted d-block mt-2">
                            Total pencairan akan disinkronkan ke <em>Jumlah Diberikan</em> pada LPJ.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal edit per baris --}}
@foreach($pencairans as $pencairan)
    <div class="modal fade" id="editPencairan{{ $pencairan->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('lpj.superadmin.pencairan.update', [$laporan, $pencairan]) }}" method="POST" class="modal-content">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title">Ubah Pencairan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                               value="{{ $pencairan->tanggal ? $pencairan->tanggal->format('Y-m-d') : '' }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-1">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal" class="form-control form-control-sm"
                               value="{{ (float) $pencairan->nominal }}" min="0" step="0.01" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-1">Bentuk Pencairan <span class="text-danger">*</span></label>
                        <select name="bentuk_pencairan" class="form-select form-select-sm" required
                                onchange="toggleTransferFields(this, 'edit-{{ $pencairan->id }}')">
                            <option value="Tunai" {{ $pencairan->bentuk_pencairan_label === 'Tunai' ? 'selected' : '' }}>Tunai</option>
                            <option value="Transfer" {{ $pencairan->bentuk_pencairan_label === 'Transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>

                    <div class="mb-2 transfer-field-edit-{{ $pencairan->id }} {{ $pencairan->isTransfer() ? '' : 'd-none' }}">
                        <label class="form-label small mb-1">Nomor Referensi Perbankan</label>
                        <input type="text" name="bank_reference" class="form-control form-control-sm"
                               value="{{ $pencairan->bank_reference }}" maxlength="255">
                    </div>

                    <div class="mb-2 transfer-field-edit-{{ $pencairan->id }} {{ $pencairan->isTransfer() ? '' : 'd-none' }}">
                        <label class="form-label small mb-1">Rekening Tujuan</label>
                        <input type="text" name="rek_tujuan" class="form-control form-control-sm"
                               value="{{ $pencairan->rek_tujuan }}" maxlength="255">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm"
                               value="{{ $pencairan->catatan }}" maxlength="255">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<script>
    function toggleTransferFields(select, scope) {
        const wrapper = select.closest('form');
        const isTransfer = select.value === 'Transfer';

        // Tampilkan/sembunyikan input transfer pada form terkait.
        wrapper.querySelectorAll('.transfer-field-add, [class*="transfer-field-edit-"]').forEach(function (el) {
            el.classList.toggle('d-none', !isTransfer);
        });

        wrapper.querySelectorAll('input[name="bank_reference"], input[name="rek_tujuan"]').forEach(function (el) {
            el.required = isTransfer;
        });
    }

    // Inisialisasi tampilan field transfer saat halaman dimuat.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[name="bentuk_pencairan"]').forEach(function (select) {
            toggleTransferFields(select, 'init');
        });
    });
</script>
@endsection
