@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Pengajuan Perpanjangan SPD</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Perpanjangan</li>
        </ol>
    </nav>
</div>

<section class="section">
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Daftar Pengajuan Perpanjangan</h5>
                @can('permission', 'spd.create')
                <a href="{{ route('requester.perpanjangan.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Ajukan Perpanjangan
                </a>
                @endcan
            </div>

            @if($extensionList->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clock text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-3">Belum ada pengajuan perpanjangan</p>
                    @can('permission', 'spd.create')
                    <a href="{{ route('requester.perpanjangan.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Ajukan Perpanjangan
                    </a>
                    @endcan
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Surat Original</th>
                                <th>Surat Perpanjangan</th>
                                <th>Perpanjangan</th>
                                <th>Dana Perpanjangan</th>
                                <th>Status</th>
                                <th>Tanggal Ajukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($extensionList as $ext)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $ext->originalSurat->nomor_surat }}</span>
                                        <br><small class="text-muted">{{ Str::limit($ext->originalSurat->tempat_tujuan, 25) }}</small>
                                    </td>
                                    <td>
                                        @if($ext->status == 'approved')
                                            <span class="fw-semibold text-success">{{ $ext->nomor_surat_extension }}</span>
                                        @else
                                            <span class="text-muted">{{ $ext->nomor_surat_extension }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $ext->tanggal_kembali_original->translatedFormat('d M') }}</small>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                        <strong>{{ $ext->tanggal_kembali_baru->translatedFormat('d M Y') }}</strong>
                                        <br><span class="badge bg-info">+{{ $ext->durasi_tambahan_hari }} hari</span>
                                    </td>
                                    <td>
                                        @if((float) $ext->budget_tambahan > 0)
                                            Rp {{ number_format($ext->budget_tambahan, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted small">Menunggu input Admin GA</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ext->status == 'pending')
                                            <span class="badge bg-warning text-dark">Menunggu Approval</span>
                                        @elseif($ext->status == 'approved')
                                            <span class="badge bg-success">Disetujui</span>
                                            @if(!$ext->is_acknowledged)
                                                <br><small class="text-muted">Belum di-checklist</small>
                                            @endif
                                        @else
                                            <span class="badge bg-danger">Ditolak</span>
                                            @if($ext->rejection_reason)
                                                <br><small class="text-danger">{{ Str::limit($ext->rejection_reason, 30) }}</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        {{ $ext->created_at->translatedFormat('d M Y') }}
                                        <br><small class="text-muted">{{ $ext->created_at->format('H:i') }}</small>
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
