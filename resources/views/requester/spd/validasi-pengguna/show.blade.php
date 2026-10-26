@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Validasi Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('lpj.pengguna.index') }}">Validasi Perjalanan</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </nav>
</div>

<section class="section">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Rincian perjalanan --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Rincian Perjalanan Dinas</h5>

                    <div class="row mb-2">
                        <div class="col-md-4 text-muted">Nomor Surat Tugas</div>
                        <div class="col-md-8"><strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 text-muted">Nomor LPJ</div>
                        <div class="col-md-8">{{ $laporan->lpj_number ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 text-muted">Tempat Tujuan</div>
                        <div class="col-md-8">{{ $laporan->suratSpd->tempat_tujuan ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 text-muted">Tanggal Perjalanan</div>
                        <div class="col-md-8">
                            @if($laporan->suratSpd)
                                {{ $laporan->suratSpd->tanggal_berangkat->translatedFormat('d F Y') }}
                                s/d
                                {{ $laporan->suratSpd->tanggal_kembali->translatedFormat('d F Y') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 text-muted">Pengemudi</div>
                        <div class="col-md-8">{{ $laporan->nama ?? $laporan->suratSpd->pengemudi_nama ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Daftar Pemakai (Penumpang)</div>
                        <div class="col-md-8">
                            @if($laporan->suratSpd && $laporan->suratSpd->details->isNotEmpty())
                                <ol class="mb-1 ps-3">
                                    @foreach($laporan->suratSpd->details as $d)
                                        <li>
                                            {{ $d->nama_lengkap }}
                                            @if($d->user_id == auth()->id())
                                                <span class="badge bg-primary">Anda</span>
                                            @endif
                                            @if(! $d->user_id)
                                                <small class="text-muted">(tanpa akun)</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Cukup <strong>satu</strong> dari penumpang di atas yang memvalidasi —
                                    setelah divalidasi, penumpang lain tidak perlu memvalidasi lagi.
                                </small>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-primary mb-3"><i class="bi bi-list-check"></i> Rincian Pengeluaran</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporan->details as $d)
                                    <tr>
                                        <td>{{ $d->tanggal ? $d->tanggal->translatedFormat('d M Y') : '-' }}</td>
                                        <td>{{ $d->tipe_penggunaan }}</td>
                                        <td class="text-end">Rp {{ number_format((float) $d->nominal, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Tidak ada rincian pengeluaran.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="2" class="text-end">Total Pengeluaran</th>
                                    <th class="text-end">Rp {{ number_format((float) $laporan->total_pengeluaran, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel validasi / tanda tangan --}}
        <div class="col-lg-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> Validasi Kebenaran Perjalanan</h6>
                </div>
                <div class="card-body">
                    @if($laporan->approved_by_pengguna)
                        <div class="alert alert-success py-2">
                            <i class="bi bi-check-circle"></i> Sudah divalidasi
                            <br><small>{{ optional($laporan->approved_pengguna_at)->translatedFormat('d F Y H:i') }}</small>
                        </div>

                        @if($qrSvg)
                            <div class="text-center border rounded p-3">
                                <div style="width:150px;height:150px;margin:0 auto;">{!! $qrSvg !!}</div>
                                <div class="small text-muted mt-2">QR Tanda Tangan Validasi</div>
                            </div>
                        @endif

                        <p class="small text-muted mt-3 mb-0">
                            Divalidasi oleh <strong>{{ $laporan->approverPengguna->nama_lengkap ?? '-' }}</strong>.
                            Menunggu validasi Divisi GA.
                        </p>
                    @else
                        <p class="small text-muted">
                            Sebagai <strong>User yang berangkat</strong>, nyatakan bahwa perjalanan dinas ini benar adanya
                            beserta rincian pengeluarannya.
                        </p>
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#validasiModal">
                            <i class="bi bi-pen"></i> Validasi &amp; Tanda Tangan
                        </button>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <a href="{{ route('lpj.pengguna.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal validasi --}}
@if(!$laporan->approved_by_pengguna)
<div class="modal fade" id="validasiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-pen"></i> Validasi Perjalanan Dinas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('lpj.pengguna.approve', $laporan) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-light border">
                        <strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong><br>
                        <small class="text-muted">
                            {{ $laporan->suratSpd->tempat_tujuan ?? '-' }} ·
                            Rp {{ number_format((float) $laporan->total_pengeluaran, 0, ',', '.') }}
                        </small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="pernyataan" value="1" id="pernyataanChk" required>
                        <label class="form-check-label" for="pernyataanChk">
                            <strong>Saya mengetahui bahwa perjalanan dinas ini benar adanya</strong> dengan rincian
                            sebagaimana terlampir pada laporan pertanggungjawaban ini.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Ya, Validasi &amp; Tanda Tangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
