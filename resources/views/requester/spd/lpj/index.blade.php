@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Laporan LPJ</li>
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

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total LPJ</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['total'] }}</h6>
                            <span class="text-muted small pt-2">Semua laporan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['pending'] }}</h6>
                            <span class="text-muted small pt-2">Menunggu validasi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['approved'] }}</h6>
                            <span class="text-muted small pt-2">Laporan disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Ditolak</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['rejected'] }}</h6>
                            <span class="text-muted small pt-2">Laporan ditolak</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-check"></i> Daftar Laporan Pertanggungjawaban</h5>
                <!-- <a href="{{ route('requester.surat.index') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Buat Laporan Baru
                </a> -->
            </div>

            @if($laporanList->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-3">Belum ada laporan pertanggungjawaban</p>
                    <a href="{{ route('requester.surat.index') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Buat Laporan Pertama
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Surat Tugas</th>
                                <th>Tujuan</th>
                                <th>Kendaraan</th>
                                <th class="text-end">Total Pengeluaran</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($laporanList as $index => $lpj)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $lpj->suratSpd->nomor_surat ?? '-' }}</strong>
                                    @if($lpj->suratSpd && $lpj->suratSpd->is_extension)
                                        <br><span class="badge bg-info">Perpanjangan</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($lpj->suratSpd->tempat_tujuan ?? '-', 25) }}</td>
                                <td>
                                    <span class="badge bg-dark">{{ $lpj->nomor_polisi }}</span>
                                </td>
                                <td class="text-end">
                                    <strong>Rp {{ number_format($lpj->total_pengeluaran, 0, ',', '.') }}</strong>
                                    @if($lpj->is_over_budget)
                                        <br><span class="badge bg-warning text-dark">Over Budget</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lpj->status == 'pending')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @elseif($lpj->status == 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-danger">Ditolak</span>
                                    @endif
                                    @if($lpj->is_late)
                                        <br><span class="badge bg-danger">Terlambat</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $lpj->created_at->translatedFormat('d M Y') }}
                                    <br><small class="text-muted">{{ $lpj->created_at->format('H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('requester.lpj.show', $lpj) }}"
                                           class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('spd.laporan.export', $lpj) }}"
                                           class="btn btn-sm btn-outline-primary" target="_blank" title="Cetak SPD + LPJ (1 bundel: tabel, spending, lampiran)">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                        @if($lpj->status == 'rejected')
                                            <a href="{{ route('requester.lpj.edit', $lpj) }}"
                                               class="btn btn-sm btn-outline-warning" title="Revisi LPJ">
                                                <i class="bi bi-pencil"></i> Revisi
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
