@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Surat Tugas Perjalanan Dinas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Surat Tugas</li>
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
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Surat</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['total'] }}</h6>
                            <span class="text-muted small pt-2">Semua surat tugas</span>
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
                            <span class="text-muted small pt-2">Surat disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Perlu LPJ</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['need_lpj'] }}</h6>
                            <span class="text-muted small pt-2">Belum laporan</span>
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
                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text"></i> Daftar Surat Tugas</h5>
                <a href="{{ route('requester.surat.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Buat Surat Baru
                </a>
            </div>

            @if($suratList->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-3">Belum ada surat tugas</p>
                    <a href="{{ route('requester.surat.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Buat Surat Pertama
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Surat</th>
                                <th>Tujuan</th>
                                <th>Tanggal</th>
                                <th>Kendaraan</th>
                                <th>Status</th>
                                <th>LPJ</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suratList as $surat)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $surat->nomor_surat }}</span>
                                        @if($surat->is_extension)
                                            <br><span class="badge bg-info">Perpanjangan</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($surat->tempat_tujuan, 30) }}</td>
                                    <td>
                                        {{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}
                                        <br><span class="badge bg-secondary">{{ $surat->lama_perjalanan_hari }} hari</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">{{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</span>
                                    </td>
                                    <td>
                                        @if($surat->status == 'pending')
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        @elseif($surat->status == 'approved')
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif($surat->status == 'completed')
                                            <span class="badge bg-primary">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($surat->status == 'approved')
                                            @if($surat->lpjPenggantian)
                                                @if($surat->lpjPenggantian->status == 'pending')
                                                    <span class="badge bg-warning text-dark">LPJ Pending</span>
                                                @elseif($surat->lpjPenggantian->status == 'approved')
                                                    <span class="badge bg-success">LPJ Approved</span>
                                                @else
                                                    <span class="badge bg-danger">LPJ Ditolak</span>
                                                @endif
                                            @else
                                                @if($surat->isLpjDeadlineOverdue())
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-exclamation-triangle"></i> Terlambat!
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Belum LPJ</span>
                                                    @if($surat->lpj_deadline)
                                                        <br><small class="text-muted">Deadline: {{ $surat->lpj_deadline->translatedFormat('d M') }}</small>
                                                    @endif
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            {{-- <a href="{{ route('requester.surat.show', $surat) }}"
                                               class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a> --}}
                                            @if($surat->status == 'approved' && !$surat->lpjPenggantian)
                                                <a href="{{ route('requester.lpj.create', $surat) }}"
                                                   class="btn btn-sm btn-outline-success" title="Buat LPJ">
                                                    <i class="bi bi-file-earmark-plus"></i>
                                                </a>
                                            @endif
                                            @if($surat->status == 'approved' && !$surat->is_extension)
                                                <a href="{{ route('requester.perpanjangan.create') }}?surat_id={{ $surat->id }}"
                                                   class="btn btn-sm btn-outline-info" title="Ajukan Perpanjangan">
                                                    <i class="bi bi-clock-history"></i>
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
