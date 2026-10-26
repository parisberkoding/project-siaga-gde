@extends('templates.main')

@php
    // Route detail mengikuti KONTEKS halaman (bukan hanya permission), agar user
    // yang punya banyak peran (mis. sekaligus User berangkat & Divisi GA) tidak
    // nyasar ke tahap validasi yang lain.
    $showRoute = request()->routeIs('lpj.pengguna.*')
        ? 'lpj.pengguna.show'
        : 'lpj.superadmin.show';
@endphp

@section('content')
<div class="pagetitle">
    <h1>Validasi Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Validasi LPJ</li>
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

    {{-- Notice LPJ belum disubmit & sudah lewat batas 3 hari.
         Muncul untuk GA (semua surat di unitnya) dan untuk User yang berangkat
         (surat yang ia ikuti). Overdue hanya penanda — form LPJ driver tetap
         terbuka, jadi ini pengingat untuk mengejar, bukan status gagal. --}}
    @php $menungguOverdue = $suratMenungguOverdue ?? collect(); @endphp
    @if($menungguOverdue->isNotEmpty())
        <div class="alert alert-danger">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle fs-4"></i>
                <div class="flex-grow-1">
                    <strong>{{ $menungguOverdue->count() }} surat tugas belum dibuatkan LPJ &amp; sudah lewat batas 3 hari.</strong>
                    <div class="small mt-1">
                        Batas submit = tanggal kembali SPD + 3 hari. Form LPJ driver <strong>tidak ditutup</strong> —
                        LPJ tetap bisa masuk kapan saja dan akan tercatat terlambat.
                    </div>

                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-borderless mb-0 align-middle">
                            <tbody>
                                @foreach($menungguOverdue as $suratOverdue)
                                    <tr>
                                        <td class="ps-0">
                                            <span class="fw-semibold">{{ $suratOverdue->nomor_surat }}</span>
                                            <span class="text-muted small">— {{ $suratOverdue->pengemudi_nama }}</span>
                                        </td>
                                        <td class="text-muted small">{{ $suratOverdue->tempat_tujuan }}</td>
                                        <td class="text-nowrap small">
                                            Kembali {{ $suratOverdue->tanggal_kembali?->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="text-nowrap">
                                            @include('partials.lpj-deadline-badge', ['surat' => $suratOverdue, 'compact' => true])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card warning-card">
                <div class="card-body">
                    <h5 class="card-title">Menunggu Validasi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['pending'] }}</h6>
                            <span class="text-muted small pt-2">Perlu divalidasi</span>
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
                            <span class="text-muted small pt-2">Total disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card danger-card">
                <div class="card-body">
                    <h5 class="card-title">Terlambat</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $stats['late'] }}</h6>
                            <span class="text-muted small pt-2">Submission terlambat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Bulan Ini</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 0.85rem;">Rp {{ number_format($total_approved_amount, 0, ',', '.') }}</h6>
                            <span class="text-muted small pt-2">Total pengeluaran</span>
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
                <i class="bi bi-file-earmark-check"></i> Daftar Laporan Pertanggungjawaban
            </h5>

            {{-- Tabs --}}
            <ul class="nav nav-tabs nav-tabs-bordered" id="lpjTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative" id="pending-tab" data-bs-toggle="tab"
                            data-bs-target="#pending" type="button" role="tab">
                        Menunggu Validasi
                        @if($stats['pending'] > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $stats['pending'] }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approved-tab" data-bs-toggle="tab"
                            data-bs-target="#approved" type="button" role="tab">
                        Disetujui
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rejected-tab" data-bs-toggle="tab"
                            data-bs-target="#rejected" type="button" role="tab">
                        Ditolak
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content pt-3" id="lpjTabsContent">
                {{-- Pending Tab --}}
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    @if($pending_list->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 mb-0">Tidak ada LPJ yang menunggu validasi</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Tugas</th>
                                        <th>Driver</th>
                                        <th>Tujuan</th>
                                        <th>Tanggal LPJ</th>
                                        <th class="text-end">Total Biaya</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pending_list as $lpj)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</span>
                                                @if($lpj->suratSpd && $lpj->suratSpd->is_extension)
                                                    <br><span class="badge bg-info">Perpanjangan</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $lpj->nama }}
                                                <br><small class="text-muted">{{ $lpj->jabatan }}</small>
                                            </td>
                                            <td>{{ Str::limit($lpj->suratSpd->tempat_tujuan ?? '-', 25) }}</td>
                                            <td>
                                                {{ $lpj->tanggal_lpj ? $lpj->tanggal_lpj->translatedFormat('d M Y') : '-' }}
                                                @if($lpj->is_late)
                                                    <br><span class="badge bg-danger">Terlambat {{ $lpj->days_late }} hari</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold">Rp {{ number_format($lpj->total_pengeluaran, 0, ',', '.') }}</span>
                                                @if($lpj->is_over_budget)
                                                    <br><span class="badge bg-warning text-dark">Over Budget</span>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-warning text-dark">Menunggu</span></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route($showRoute, $lpj) }}"
                                                       class="btn btn-sm btn-primary" title="Detail & Validasi">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </a>
                                                    <a href="{{ route('spd.laporan.export', $lpj) }}"
                                                       class="btn btn-sm btn-outline-primary" target="_blank" title="Cetak SPD + LPJ (1 bundel: tabel, spending, lampiran)">
                                                        <i class="bi bi-file-earmark-pdf"></i>
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

                {{-- Approved Tab --}}
                <div class="tab-pane fade" id="approved" role="tabpanel">
                    @if($approved_list->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Belum ada LPJ yang disetujui</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Tugas</th>
                                        <th>Driver</th>
                                        <th>Tujuan</th>
                                        <th class="text-end">Total Biaya</th>
                                        <th>Disetujui</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approved_list as $lpj)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</span>
                                                @if($lpj->suratSpd && $lpj->suratSpd->is_extension)
                                                    <br><span class="badge bg-info">Perpanjangan</span>
                                                @endif
                                            </td>
                                            <td>{{ $lpj->nama }}</td>
                                            <td>{{ Str::limit($lpj->suratSpd->tempat_tujuan ?? '-', 25) }}</td>
                                            <td class="text-end">
                                                <span class="fw-bold">Rp {{ number_format($lpj->total_pengeluaran, 0, ',', '.') }}</span>
                                            </td>
                                            <td>
                                                {{ $lpj->approverPengguna->nama_lengkap ?? $lpj->approverPengguna->name ?? '-' }}
                                                <br><small class="text-muted">{{ $lpj->approved_pengguna_at ? $lpj->approved_pengguna_at->translatedFormat('d M Y H:i') : '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route($showRoute, $lpj) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('spd.laporan.export', $lpj) }}"
                                                       class="btn btn-sm btn-outline-success" target="_blank" title="Cetak SPD + LPJ (1 bundel: Ringkasan Penggunaan, Lembar Persetujuan, Lampiran Penggunaan Dana)">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                    @can('permission', 'lpj.pencairan')
                                                        <a href="{{ route('spd.laporan.pemberian-dana', $lpj) }}"
                                                           class="btn btn-sm btn-outline-primary" target="_blank" title="Cetak Lembar Lampiran Pemberian Dana (khusus GA)">
                                                            <i class="bi bi-cash-stack"></i>
                                                        </a>
                                                    @endcan
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
                            <p class="text-muted mb-0">Tidak ada LPJ yang ditolak</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Surat Tugas</th>
                                        <th>Driver</th>
                                        <th class="text-end">Total Biaya</th>
                                        <th>Ditolak Oleh</th>
                                        <th>Alasan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rejected_list as $lpj)
                                        <tr>
                                            <td><span class="fw-semibold">{{ $lpj->suratSpd->nomor_surat ?? '-' }}</span></td>
                                            <td>{{ $lpj->nama }}</td>
                                            <td class="text-end">Rp {{ number_format($lpj->total_pengeluaran, 0, ',', '.') }}</td>
                                            <td>
                                                {{ $lpj->rejector->nama_lengkap ?? $lpj->rejector->name ?? '-' }}
                                                <br><small class="text-muted">{{ $lpj->rejected_at ? $lpj->rejected_at->translatedFormat('d M Y H:i') : '-' }}</small>
                                            </td>
                                            <td>{{ Str::limit($lpj->rejection_reason, 40) }}</td>
                                            <td class="text-center">
                                                <a href="{{ route($showRoute, $lpj) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="bi bi-eye"></i>
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
