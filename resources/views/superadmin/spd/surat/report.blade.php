@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Laporan SPD</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item active">Laporan SPD</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    {{-- Stats --}}
    <div class="row mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Surat Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $totalApproved }}</h6>
                            <span class="text-muted small pt-2">Surat SPD</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Total Budget</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ps-3">
                            <h6>Rp {{ number_format($totalBudget, 0, ',', '.') }}</h6>
                            <span class="text-muted small pt-2">Anggaran</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card success-card">
                <div class="card-body">
                    <h5 class="card-title">Total Realisasi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ps-3">
                            <h6>Rp {{ number_format($totalSpending, 0, ',', '.') }}</h6>
                            <span class="text-muted small pt-2">LPJ Disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card <?= $totalBudget > 0 && $totalSpending > $totalBudget ? 'danger-card' : 'info-card' ?>">
                <div class="card-body">
                    <h5 class="card-title">LPJ Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $totalLpj }}</h6>
                            <span class="text-muted small pt-2">Laporan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Surat --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Daftar Surat SPD</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Surat</th>
                            <th>Driver</th>
                            <th>Tujuan</th>
                            <th>Tanggal</th>
                            <th class="text-end">Budget</th>
                            <th class="text-end">Realisasi</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suratList as $surat)
                            @php
                                $budgetTotal = $surat->total_budget ?? 0;
                                $realisasi = $surat->lpjPenggantian ? $surat->lpjPenggantian->total_pengeluaran : 0;
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $surat->nomor_surat }}</span>
                                    @if($surat->is_extension)
                                        <br><span class="badge bg-info">Perpanjangan</span>
                                    @endif
                                </td>
                                <td>{{ $surat->pengemudi_nama }}</td>
                                <td>{{ Str::limit($surat->tempat_tujuan, 20) }}</td>
                                <td>{{ $surat->tanggal_berangkat->format('d/m') }} - {{ $surat->tanggal_kembali->format('d/m/y') }}</td>
                                <td class="text-end">Rp {{ number_format($budgetTotal, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($realisasi, 0, ',', '.') }}</td>
                                <td>
                                    @if($surat->status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($surat->status == 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($surat->status == 'completed')
                                        <span class="badge bg-primary">Selesai</span>
                                    @else
                                        <span class="badge bg-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('surat.superadmin.show', $surat) }}"
                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($surat->status == 'approved')
                                        <a href="{{ route('surat.superadmin.print', $surat) }}"
                                           class="btn btn-sm btn-outline-success" target="_blank" title="Cetak">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada data SPD</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $suratList->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
