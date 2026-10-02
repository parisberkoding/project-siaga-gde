@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>SPD Berjalan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">SPD Berjalan</li>
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
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-car-front"></i> Surat Tugas yang Harus Dilaporkan</h5>

            <div class="alert alert-info py-2">
                <i class="bi bi-info-circle me-1"></i>
                <small>
                    Daftar di bawah ini adalah surat tugas yang <strong>sudah disetujui</strong> dan
                    <strong>belum dibuat laporannya</strong>. Klik <strong>Lapor &amp; Validasi SPD</strong> pada
                    surat yang bersangkutan untuk mengunggah struk dan menyelesaikan LPJ.
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Surat Tugas</th>
                            <th>Jadwal</th>
                            <th>Kendaraan</th>
                            <th>Laporan Harian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suratList as $surat)
                        <tr>
                            <td>
                                <strong>{{ $surat->nomor_surat }}</strong>
                                @if($surat->is_extension)
                                    <span class="badge bg-secondary">Perpanjangan</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $surat->tempat_tujuan }}</small>
                            </td>
                            <td>
                                <small>
                                    {{ $surat->tanggal_berangkat->translatedFormat('l, d F Y') }} s/d<br>
                                    {{ $surat->tanggal_kembali->translatedFormat('l, d F Y') }}
                                </small>
                            </td>
                            <td><small>{{ $surat->vehicle?->consumerial_code ?? '-' }}</small></td>
                            <td>
                                @if($surat->daily_reports_available)
                                    <span class="badge bg-success">{{ $surat->daily_submitted_count }} hari terkirim</span>
                                    <small class="text-muted d-block">· {{ $surat->daily_item_count }} item</small>
                                    @if($surat->has_draft_daily)
                                        <span class="badge bg-warning text-dark">ada draft</span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-muted">belum ada</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('pelaporan.spd.validasi', $surat) }}" class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="bi bi-calendar3"></i> Lapor &amp; Validasi SPD
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-check2-circle" style="font-size:2rem;"></i><br>
                                Tidak ada surat SPD yang perlu dilaporkan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
