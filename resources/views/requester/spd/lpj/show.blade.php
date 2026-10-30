@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Detail Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('requester.lpj.index') }}">Laporan LPJ</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        {{-- Detail LPJ --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">
                        <span>Laporan Pertanggungjawaban BBM</span>
                        @if($laporan->status == 'pending')
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Validasi</span>
                        @elseif($laporan->status == 'approved')
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                        @endif
                    </h5>

                    {{-- Warning Badges --}}
                    @if($laporan->is_late || $laporan->is_over_budget)
                        <div class="mb-3">
                            @if($laporan->is_late)
                                <span class="badge bg-danger me-1">
                                    <i class="bi bi-clock"></i> Terlambat {{ $laporan->days_late }} hari
                                </span>
                            @endif
                            @if($laporan->is_over_budget)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-exclamation-triangle"></i> Over Budget
                                </span>
                            @endif
                        </div>
                    @endif

                    <hr>

                    {{-- Info Surat Tugas --}}
                    <h6 class="text-primary mb-3"><i class="bi bi-file-earmark-text"></i> Informasi Surat Tugas</h6>
                    <div class="row mb-2">
                        <div class="col-4">Nomor Surat:</div>
                        <div class="col-8">
                            <strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong>
                            @if($laporan->suratSpd && $laporan->suratSpd->is_extension)
                                <span class="badge bg-info ms-1">Perpanjangan</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Tujuan:</div>
                        <div class="col-8">{{ $laporan->suratSpd->tempat_tujuan ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">Tanggal Perjalanan:</div>
                        <div class="col-8">
                            @if($laporan->suratSpd)
                                {{ $laporan->suratSpd->tanggal_berangkat->translatedFormat('d M Y') }} -
                                {{ $laporan->suratSpd->tanggal_kembali->translatedFormat('d M Y') }}
                                <span class="badge bg-secondary">{{ $laporan->suratSpd->lama_perjalanan_hari }} hari</span>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <hr>

                    {{-- Info Pelapor --}}
                    <h6 class="text-success mb-3"><i class="bi bi-person"></i> Informasi Pelapor</h6>
                    <div class="row mb-2">
                        <div class="col-4">Nama:</div>
                        <div class="col-8"><strong>{{ $laporan->nama }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Jabatan:</div>
                        <div class="col-8">{{ $laporan->jabatan }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">Kendaraan:</div>
                        <div class="col-8">
                            <span class="badge bg-dark">{{ $laporan->nomor_polisi }}</span>
                            {{ $laporan->vehicle->consumerial_name ?? '' }}
                        </div>
                    </div>

                    <hr>

                    {{-- Uraian --}}
                    <h6 class="text-info mb-3"><i class="bi bi-journal-text"></i> Uraian Perjalanan</h6>
                    <p>{{ $laporan->uraian }}</p>
                </div>
            </div>

            {{-- Detail Pengeluaran --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list-check"></i> Rincian Pengeluaran</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                 <tr>
                                     <th style="width: 50px;">No</th>
                                     <th>Tanggal</th>
                                     <th>Tipe</th>
                                     <th class="text-end">Nominal</th>
                                     <th>Struk</th>
                                     <th>Keterangan</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @forelse($laporan->details as $index => $detail)
                                     <tr>
                                         <td class="text-center">{{ $index + 1 }}</td>
                                         <td>{{ $detail->tanggal ? $detail->tanggal->translatedFormat('d M Y') : '-' }}</td>
                                         <td>
                                             @php
                                                 $badgeClass = match($detail->tipe_penggunaan) {
                                                     'BBM' => 'bg-primary',
                                                     'Tol' => 'bg-info',
                                                     'Portir' => 'bg-warning text-dark',
                                                     default => 'bg-secondary'
                                                 };
                                             @endphp
                                             <span class="badge {{ $badgeClass }}">{{ $detail->tipe_penggunaan }}</span>
                                             @if($detail->strukItem && $detail->strukItem->kategori_source === 'ai')
                                                 <span class="badge bg-info-subtle text-info" title="Dianalisis AI/OCR">
                                                     <i class="bi bi-stars"></i> AI
                                                 </span>
                                             @endif
                                         </td>
                                         <td class="text-end">Rp {{ number_format($detail->nominal, 0, ',', '.') }}</td>
                                         <td>
                                             @if($detail->strukItem && $detail->strukItem->path_image)
                                                 <a href="{{ asset('storage/' . $detail->strukItem->path_image) }}"
                                                    target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat struk">
                                                     <i class="bi bi-image"></i>
                                                 </a>
                                             @else
                                                 <span class="text-muted">-</span>
                                             @endif
                                         </td>
                                         <td>{{ $detail->keterangan ?? '-' }}</td>
                                     </tr>
                                 @empty
                                     <tr>
                                         <td colspan="6" class="text-center text-muted">Tidak ada data pengeluaran</td>
                                     </tr>
                                 @endforelse
                             </tbody>
                             <tfoot class="table-dark">
                                 <tr>
                                     <th colspan="3" class="text-end">Total Pengeluaran:</th>
                                     <th class="text-end">Rp {{ number_format($laporan->total_pengeluaran, 0, ',', '.') }}</th>
                                     <th colspan="2"></th>
                                 </tr>
                             </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Keuangan --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-calculator"></i> Ringkasan Keuangan</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td>Biaya BBM:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->total_biaya_bbm, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Biaya Tol:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->total_biaya_tol, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Biaya Portir:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->total_biaya_portir, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Biaya Lain-lain:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->total_biaya_lain, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-dark">
                                    <td><strong>Total:</strong></td>
                                    <td class="text-end"><strong>Rp {{ number_format($laporan->total_pengeluaran, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td>Uang Dari:</td>
                                    <td class="text-end">{{ $laporan->uang_dari ?? 'KAS' }}</td>
                                </tr>
                                <tr>
                                    <td>Jumlah Diberikan:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->jumlah_diberikan, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="{{ $laporan->pengembalian > 0 ? 'table-success' : '' }}">
                                    <td>Pengembalian:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->pengembalian, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="{{ $laporan->kekurangan > 0 ? 'table-warning' : '' }}">
                                    <td>Kekurangan:</td>
                                    <td class="text-end">Rp {{ number_format($laporan->kekurangan, 0, ',', '.') }}</td>
                                </tr>
                            </table>

                            {{-- Budget comparison --}}
                            @if($laporan->suratSpd)
                                <div class="alert {{ $laporan->is_over_budget ? 'alert-warning' : 'alert-info' }} py-2 mb-0">
                                    <small>
                                        <strong>Budget:</strong> Rp {{ number_format($laporan->suratSpd->total_budget, 0, ',', '.') }}<br>
                                        <strong>Terpakai:</strong> Rp {{ number_format($laporan->total_pengeluaran, 0, ',', '.') }}
                                        @if($laporan->is_over_budget)
                                            <span class="badge bg-warning text-dark">Over Budget</span>
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Riwayat Validasi --}}
            @if($laporan->status != 'pending')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clock-history"></i> Riwayat Validasi</h5>
                        <div class="alert {{ $laporan->status == 'approved' ? 'alert-success' : 'alert-danger' }} mb-0">
                            @if($laporan->status == 'approved')
                                <strong><i class="bi bi-check-circle"></i> Laporan Disetujui</strong><br>
                                <small>Oleh: {{ $laporan->approverPengguna->nama_lengkap ?? $laporan->approverPengguna->name ?? '-' }}</small><br>
                                <small>Pada: {{ $laporan->approved_pengguna_at ? $laporan->approved_pengguna_at->translatedFormat('d F Y H:i') : '-' }}</small>
                            @else
                                <strong><i class="bi bi-x-circle"></i> Laporan Ditolak</strong><br>
                                <small>Oleh: {{ $laporan->rejector->nama_lengkap ?? $laporan->rejector->name ?? '-' }}</small><br>
                                <small>Pada: {{ $laporan->rejected_at ? $laporan->rejected_at->translatedFormat('d F Y H:i') : '-' }}</small><br>
                                <strong>Alasan:</strong> {{ $laporan->rejection_reason }}
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Status Laporan</h6>

                    @if($laporan->status == 'pending')
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bi bi-hourglass-split"></i> Menunggu validasi dari Superadmin
                        </div>
                    @elseif($laporan->status == 'approved')
                        <div class="alert alert-success py-2 mb-0">
                            <i class="bi bi-check-circle"></i> Laporan telah disetujui
                        </div>
                    @else
                        <div class="alert alert-danger py-2 mb-0">
                            <i class="bi bi-x-circle"></i> Laporan ditolak
                            <br><small>{{ $laporan->rejection_reason }}</small>
                        </div>
                        @can('permission', 'lpj.update')
                        <a href="{{ route('requester.lpj.edit', $laporan) }}" class="btn btn-warning w-100 mt-2">
                            <i class="bi bi-pencil"></i> Revisi LPJ
                        </a>
                        @endcan
                    @endif
                </div>
            </div>

            {{-- Info Pengajuan --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-calendar"></i> Informasi Pengajuan</h6>

                    <div class="mb-2">
                        <small class="text-muted">Tanggal LPJ:</small><br>
                        <strong>{{ $laporan->tanggal_lpj ? $laporan->tanggal_lpj->translatedFormat('d M Y') : '-' }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Deadline:</small><br>
                        <strong class="{{ $laporan->is_late ? 'text-danger' : '' }}">
                            {{ $laporan->due_date ? $laporan->due_date->translatedFormat('d M Y') : '-' }}
                        </strong>
                        @if($laporan->is_late)
                            <span class="badge bg-danger">Terlambat</span>
                        @endif
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Diajukan pada:</small><br>
                        <strong>{{ $laporan->created_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted">Terakhir diupdate:</small><br>
                        <strong>{{ $laporan->updated_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>
                </div>
            </div>

            {{-- Catatan --}}
            @if($laporan->catatan)
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-journal-text"></i> Catatan</h6>
                        <p class="mb-0">{{ $laporan->catatan }}</p>
                    </div>
                </div>
            @endif

            {{-- Cetak Dokumen --}}
            @if($laporan->status == 'approved')
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-printer"></i> Cetak Dokumen</h6>
                    </div>
                    <div class="card-body">
                        @if(auth()->user()->hasPermission('lpj.export'))
                            <a href="{{ route('spd.laporan.export', $laporan) }}" target="_blank" class="btn btn-primary w-100">
                                <i class="bi bi-file-earmark-pdf"></i> Export SPD + LPJ
                            </a>
                        @else
                            {{-- BUG-RBAC-003: tanpa lpj.export, pemilik tetap bisa mencetak. --}}
                            <a href="{{ route('spd.laporan.print', $laporan) }}" target="_blank" class="btn btn-primary w-100">
                                <i class="bi bi-printer"></i> Cetak SPD + LPJ
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Back Button --}}
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('requester.lpj.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
