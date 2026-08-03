@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Validasi Surat Tugas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Validasi Surat</li>
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
        <div class="col-xxl-4 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Menunggu Validasi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pending_count }}</h6>
                            <span class="text-muted small pt-2">Surat pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $approved_count }}</h6>
                            <span class="text-muted small pt-2">Surat disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Ditolak</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $rejected_count }}</h6>
                            <span class="text-muted small pt-2">Surat ditolak</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card with Tabs --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-file-earmark-text"></i> Daftar Surat Tugas Perjalanan Dinas
            </h5>

            {{-- Tabs --}}
            <ul class="nav nav-tabs nav-tabs-bordered" id="suratTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative" id="pending-tab" data-bs-toggle="tab"
                            data-bs-target="#pending" type="button" role="tab">
                        <i class="bi bi-clock-history"></i> Menunggu Validasi
                        @if($pending_count > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $pending_count }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approved-tab" data-bs-toggle="tab"
                            data-bs-target="#approved" type="button" role="tab">
                        <i class="bi bi-check-circle"></i> Disetujui
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rejected-tab" data-bs-toggle="tab"
                            data-bs-target="#rejected" type="button" role="tab">
                        <i class="bi bi-x-circle"></i> Ditolak
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content pt-3" id="suratTabsContent">
                {{-- Pending Tab --}}
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    @if($pending_list->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 mb-0">Tidak ada surat yang menunggu validasi</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat</th>
                                        <th>Pengaju</th>
                                        <th>Tujuan</th>
                                        <th>Tanggal</th>
                                        <th>Kendaraan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pending_list as $surat)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $surat->nomor_surat }}</span>
                                                @if($surat->is_extension)
                                                    <br><span class="badge bg-info">Perpanjangan</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $surat->pengemudi_nama }}
                                                <br><small class="text-muted">{{ $surat->user->email ?? '' }}</small>
                                            </td>
                                            <td>{{ Str::limit($surat->tempat_tujuan, 30) }}</td>
                                            <td>
                                                {{ $surat->tanggal_berangkat->format('d M Y') }} - {{ $surat->tanggal_kembali->format('d M Y') }}
                                                <br><span class="badge bg-secondary">{{ $surat->lama_perjalanan_hari }} hari</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-dark">{{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</span>
                                                <br><small>{{ $surat->vehicle->consumerial_name ?? '' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('surat.superadmin.show', $surat->id) }}"
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Detail & Validasi
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Approved Tab --}}
                <div class="tab-pane fade" id="approved" role="tabpanel">
                    @if($approved_list->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Belum ada surat yang disetujui</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis</th>
                                        <th>No. Surat</th>
                                        <th>Pengaju</th>
                                        <th>Tujuan</th>
                                        <th>Tanggal</th>
                                        <th>Deadline LPJ</th>
                                        <th>Disetujui</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approved_list as $surat)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">Surat Tugas</span>
                                                @if($surat->is_extension)
                                                    <br><span class="badge bg-info mt-1">Perpanjangan</span>
                                                @endif
                                                <br>
                                                @php
                                                    $lpj = $surat->lpjPenggantian;
                                                @endphp
                                                @if($lpj)
                                                    @if($lpj->status == 'approved')
                                                        <span class="badge bg-success mt-1">LPJ Disetujui</span>
                                                    @elseif($lpj->status == 'pending')
                                                        <span class="badge bg-warning text-dark mt-1">LPJ Menunggu</span>
                                                    @elseif($lpj->status == 'rejected')
                                                        <span class="badge bg-danger mt-1">LPJ Ditolak</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary mt-1">LPJ Belum</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $surat->nomor_surat }}</span>
                                            </td>
                                            <td>{{ $surat->pengemudi_nama }}</td>
                                            <td>{{ Str::limit($surat->tempat_tujuan, 25) }}</td>
                                            <td>{{ $surat->tanggal_berangkat->format('d M') }} - {{ $surat->tanggal_kembali->format('d M Y') }}</td>
                                            <td>
                                                @if($surat->lpj_deadline)
                                                    @if($surat->isLpjDeadlineOverdue() && !$surat->hasLpj())
                                                        <span class="text-danger">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            {{ $surat->lpj_deadline->format('d M Y') }}
                                                        </span>
                                                    @else
                                                        {{ $surat->lpj_deadline->format('d M Y') }}
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }}
                                                <br><small class="text-muted">{{ $surat->approved_at->format('d M Y H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route('surat.superadmin.show', $surat->id) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('surat.superadmin.print', $surat->id) }}"
                                                       class="btn btn-sm btn-outline-success" target="_blank" title="Cetak">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Rejected Tab --}}
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    @if($rejected_list->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Tidak ada surat yang ditolak</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat</th>
                                        <th>Pengaju</th>
                                        <th>Tujuan</th>
                                        <th>Ditolak Oleh</th>
                                        <th>Alasan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rejected_list as $surat)
                                        <tr>
                                            <td><span class="fw-semibold">{{ $surat->nomor_surat }}</span></td>
                                            <td>{{ $surat->pengemudi_nama }}</td>
                                            <td>{{ Str::limit($surat->tempat_tujuan, 25) }}</td>
                                            <td>
                                                {{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }}
                                                <br><small class="text-muted">{{ optional($surat->rejected_at)->format('d M Y') ?? '-' }}</small>
                                            </td>
                                            
                                            <td>{{ Str::limit($surat->rejection_reason, 40) }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('surat.superadmin.show', $surat->id) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
