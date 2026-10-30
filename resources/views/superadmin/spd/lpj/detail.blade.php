@extends('templates.main')

{{--



--}}

@section('content')
@php
    // Halaman detail yang sama dipakai antrean GA dan antrean Management.
    // Tautan kembali harus mengikuti konteks agar peran Management tanpa
    // permission `lpj.validate` tidak mendapat 403.
    $isManagementContext = request()->routeIs('lpj.management.*');
    $indexRoute = $isManagementContext ? 'lpj.management.index' : 'lpj.superadmin.index';
@endphp
<div class="pagetitle">
    <h1>Detail Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}">Validasi LPJ</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        {{-- Detail LPJ (Left Column) --}}
        <div class="col-lg-8">
            {{-- Header Card --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">
                        <span>Laporan Pertanggungjawaban BBM</span>
                        {{-- Status Badge --}}
                        @if($laporan->status == 'pending')
                            <span class="badge bg-warning text-dark fs-6">
                                <i class="bi bi-clock-history"></i> Menunggu Validasi
                            </span>
                        @elseif($laporan->status == 'approved')
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle"></i> Disetujui
                            </span>
                        @else
                            <span class="badge bg-danger fs-6">
                                <i class="bi bi-x-circle"></i> Ditolak
                            </span>
                        @endif
                    </h5>

                    {{-- Badges untuk status khusus --}}
                    <div class="mb-3">
                        @if($laporan->is_late)
                            <span class="badge bg-danger me-1">
                                <i class="bi bi-clock"></i> Terlambat {{ $laporan->days_late }} hari
                            </span>
                        @endif
                        @if($laporan->is_over_budget)
                            <span class="badge bg-warning text-dark me-1">
                                <i class="bi bi-exclamation-triangle"></i> Over Budget
                            </span>
                        @endif
                        @if($laporan->suratSpd && $laporan->suratSpd->is_extension)
                            <span class="badge bg-info">
                                <i class="bi bi-diagram-3"></i> Perpanjangan
                            </span>
                        @endif
                    </div>

                    <hr>

                    {{-- Info Surat Tugas --}}
                    <h6 class="text-primary mb-3"><i class="bi bi-file-earmark-text"></i> Informasi Surat Tugas</h6>
                    <div class="row mb-2">
                        <div class="col-md-4">Nomor Surat:</div>
                        <div class="col-md-8">
                            <strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong>
                            @if($laporan->suratSpd && $laporan->suratSpd->is_extension && $laporan->suratSpd->parentSurat)
                                <br><small class="text-muted">Perpanjangan dari: {{ $laporan->suratSpd->parentSurat->nomor_surat }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Tujuan:</div>
                        <div class="col-md-8">{{ $laporan->suratSpd->tempat_tujuan ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Tanggal Perjalanan:</div>
                        <div class="col-md-8">
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
                        <div class="col-md-4">Nama:</div>
                        <div class="col-md-8"><strong>{{ $laporan->nama }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Jabatan:</div>
                        <div class="col-md-8">{{ $laporan->jabatan }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Kendaraan:</div>
                        <div class="col-md-8">
                            <span class="badge bg-dark">{{ $laporan->nomor_polisi }}</span>
                            {{ $laporan->vehicle->consumerial_name ?? '' }}
                        </div>
                    </div>

                    <hr>

                    {{-- Uraian --}}
                    <h6 class="text-info mb-3"><i class="bi bi-journal-text"></i> Uraian Perjalanan</h6>
                    <p>{{ $laporan->uraian }}</p>

                    <hr>

                    {{-- Tanggal & Deadline --}}
                    <h6 class="text-warning mb-3"><i class="bi bi-calendar"></i> Tanggal Pengajuan</h6>
                    <div class="row mb-2">
                        <div class="col-md-4">Tanggal LPJ:</div>
                        <div class="col-md-8">{{ $laporan->tanggal_lpj ? $laporan->tanggal_lpj->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">Deadline:</div>
                        <div class="col-md-8">
                            @if($laporan->due_date)
                                <span class="{{ $laporan->is_late ? 'text-danger' : '' }}">
                                    {{ $laporan->due_date->translatedFormat('d M Y') }}
                                </span>
                                @if($laporan->is_late)
                                    <span class="badge bg-danger">Terlambat {{ $laporan->days_late }} hari</span>
                                @endif
                            @else
                                -
                            @endif
                        </div>
                    </div>
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
                                            <span class="badge {{ $detail->getTipeBadgeClass() }}">
                                                {{ $detail->tipe_penggunaan }}
                                            </span>
                                            @if($detail->strukItem && $detail->strukItem->kategori_source === 'ai')
                                                <span class="badge bg-info-subtle text-info" title="Dianalisis AI/OCR">
                                                    <i class="bi bi-stars"></i> AI
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">Rp {{ number_format($detail->nominal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($detail->strukItem && $detail->strukItem->path_image)
                                                <div>
                                                    <a href="{{ asset('storage/' . $detail->strukItem->path_image) }}"
                                                       target="_blank" title="Klik untuk perbesar">
                                                        <img src="{{ asset('storage/' . $detail->strukItem->path_image) }}"
                                                             alt="{{ $detail->strukItem->item_code }}"
                                                             style="height: 60px; width: auto; max-width: 120px; object-fit: cover; border: 1px solid #E4E9F0; border-radius: 6px; cursor: zoom-in;"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <span class="d-none btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-image"></i> Lihat struk
                                                        </span>
                                                    </a>
                                                    <small class="d-block text-muted font-monospace mt-1"
                                                           style="font-size: 0.7rem;">{{ $detail->strukItem->item_code }}</small>
                                                </div>
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
                                    <td><strong>Total Pengeluaran:</strong></td>
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

                            {{-- Budget Analysis --}}
                            @if($laporan->suratSpd)
                                <div class="alert {{ $laporan->is_over_budget ? 'alert-warning' : 'alert-info' }} py-2 mb-0">
                                    <small>
                                        <strong>Budget per hari:</strong> Rp {{ number_format($laporan->budget_per_hari, 0, ',', '.') }}<br>
                                        <strong>Rata-rata per hari:</strong>
                                        Rp {{ number_format($laporan->total_pengeluaran / $laporan->suratSpd->lama_perjalanan_hari, 0, ',', '.') }}
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

            {{-- Edit Data Keuangan (admin GA / keuangan) --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-pencil-square"></i> Data Keuangan (untuk orang keuangan / GA)</h5>
                    <form action="{{ route('lpj.superadmin.keuangan', $laporan) }}" method="POST" class="row g-2">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Uang Dari</label>
                            <input type="text" class="form-control form-control-sm" name="uang_dari" value="{{ $laporan->uang_dari ?? 'KAS' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Jumlah Diberikan (Rp)</label>
                            <input type="number" class="form-control form-control-sm" name="jumlah_diberikan" value="{{ (float) $laporan->jumlah_diberikan }}" min="0" step="0.01">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-check2"></i> Simpan Data Keuangan
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Pengembalian/kekurangan dihitung otomatis dari jumlah yang diberikan dikurangi total pengeluaran.</small>
                        </div>
                    </form>

                    @can('permission', 'lpj.pencairan')
                        <hr>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong class="small">Pencairan Dana (Tunai / Transfer)</strong><br>
                                <small class="text-muted">
                                    Kelola rincian pencairan &mdash; totalnya otomatis menjadi Jumlah Diberikan.
                                </small>
                            </div>
                            <a href="{{ route('lpj.superadmin.pencairan.index', $laporan) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-cash-stack"></i> Kelola Pencairan
                            </a>
                        </div>
                    @endcan
                </div>
            </div>

            {{-- Riwayat Validasi --}}
            @if($laporan->status != 'pending')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clock-history"></i> Riwayat Validasi</h5>
                        <div class="alert {{ $laporan->status == 'approved' ? 'alert-success' : 'alert-danger' }} mb-0">
                            @if($laporan->status == 'approved')
                                <strong><i class="bi bi-check-circle"></i> Disetujui</strong>
                                @if($laporan->approverManagement)
                                    oleh <strong>{{ $laporan->approverManagement->nama_lengkap ?? $laporan->approverManagement->name ?? '-' }}</strong> (Management)<br>
                                    <small>{{ $laporan->approved_management_at ? $laporan->approved_management_at->translatedFormat('d F Y H:i') : '-' }}</small>
                                @else
                                    {{-- LPJ lama yang sudah final sebelum alur 3 tahap --}}
                                    oleh <strong>{{ $laporan->approverPengguna->nama_lengkap ?? $laporan->approverPengguna->name ?? '-' }}</strong><br>
                                    <small>{{ $laporan->approved_pengguna_at ? $laporan->approved_pengguna_at->translatedFormat('d F Y H:i') : '-' }}</small>
                                @endif
                            @else
                                <strong><i class="bi bi-x-circle"></i> Ditolak</strong> oleh
                                <strong>{{ $laporan->rejector->nama_lengkap ?? $laporan->rejector->name ?? '-' }}</strong><br>
                                <small>{{ $laporan->rejected_at ? $laporan->rejected_at->translatedFormat('d F Y H:i') : '-' }}</small><br>
                                <strong>Alasan:</strong> {{ $laporan->rejection_reason }}
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Action Panel (Right Column) --}}
        <div class="col-lg-4">
            {{-- Progress Validasi (3 Tahap) --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-diagram-3"></i> Progress Validasi</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-person-check"></i> User (Penumpang)</span>
                            {!! $laporan->approved_by_pengguna
                                ? '<span class="badge bg-success">Disetujui</span>'
                                : '<span class="badge bg-secondary">Menunggu</span>' !!}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-shield-check"></i> Divisi GA</span>
                            {!! $laporan->approved_by_divisi_ga
                                ? '<span class="badge bg-success">Disetujui</span>'
                                : '<span class="badge bg-secondary">Menunggu</span>' !!}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-person-badge"></i> Management (Final)</span>
                            {!! $laporan->approved_by_management
                                ? '<span class="badge bg-success">Disetujui</span>'
                                : '<span class="badge bg-secondary">Menunggu</span>' !!}
                        </li>
                    </ul>
                    <small class="text-muted d-block mt-2">
                        Tiga tanda tangan ini dicatat terpisah. LPJ baru berstatus
                        <strong>Disetujui</strong> setelah User, Divisi GA, dan Management
                        menyelesaikan validasinya.
                    </small>
                </div>
            </div>

            {{-- Validasi Actions --}}
            @if($laporan->status == 'pending')
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-shield-check"></i> Aksi Validasi</h6>
                    </div>
                    <div class="card-body">
                        {{-- Warning for late/over budget --}}
                        @if($laporan->is_late || $laporan->is_over_budget)
                            <div class="alert alert-warning py-2 mb-3">
                                <small>
                                    <i class="bi bi-exclamation-triangle"></i> Perhatian:
                                    @if($laporan->is_late)
                                        <br>• LPJ diajukan terlambat {{ $laporan->days_late }} hari
                                    @endif
                                    @if($laporan->is_over_budget)
                                        <br>• Pengeluaran melebihi budget harian
                                    @endif
                                </small>
                            </div>
                        @endif

                        {{-- Tahap 1: User (penumpang) — tidak strict, tidak menunggu tahap lain --}}
                        @if(auth()->user()->hasPermission('spd.validate_trip') && !$laporan->approved_by_pengguna)
                            <form action="{{ route('lpj.pengguna.approve', $laporan) }}" method="POST" class="mb-2">
                                @csrf
                                <input type="hidden" name="pernyataan" value="1">
                                <button type="submit" class="btn btn-success w-100"
                                        onclick="return confirm('Saya mengetahui bahwa perjalanan dinas ini benar adanya. Validasi sebagai User (penumpang)?');">
                                    <i class="bi bi-person-check"></i> Validasi sebagai User (Penumpang)
                                </button>
                            </form>
                        @endif

                        {{-- Tahap 2: Divisi GA — tidak strict (boleh lebih dulu) --}}
                        @if(auth()->user()->hasPermission('lpj.validate') && !$laporan->approved_by_divisi_ga)
                            <form action="{{ route('lpj.superadmin.approve-ga', $laporan) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100"
                                        onclick="return confirm('Validasi sebagai Divisi GA (admin/supervisor/manager)?');">
                                    <i class="bi bi-shield-check"></i> Validasi sebagai Divisi GA
                                </button>
                            </form>
                        @endif

                        {{-- Tahap 3: Management — final, setelah User & Divisi GA --}}
                        @if(auth()->user()->hasPermission('lpj.validate_management') && !$laporan->approved_by_management)
                            @if($laporan->approved_by_pengguna && $laporan->approved_by_divisi_ga)
                                <form action="{{ route('lpj.management.approve', $laporan) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100"
                                            onclick="return confirm('Menyetujui LPJ sebagai Management (validasi final)? Dokumen akan siap dicetak.');">
                                        <i class="bi bi-person-badge"></i> Validasi Final sebagai Management
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-info py-2 mb-2">
                                    <small>
                                        <i class="bi bi-info-circle"></i>
                                        Validasi final Management menunggu validasi User &amp; Divisi GA selesai.
                                    </small>
                                </div>
                            @endif
                        @endif

                        {{-- Tolak (Divisi GA) --}}
                        @if(auth()->user()->hasPermission('lpj.validate'))
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle"></i> Tolak LPJ
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Print Button --}}
            @if($laporan->status == 'approved')
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-printer"></i> Cetak Dokumen</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('spd.laporan.export', $laporan) }}" target="_blank" class="btn btn-primary w-100">
                            <i class="bi bi-file-earmark-pdf"></i> Cetak SPD + LPJ
                        </a>
                        @can('permission', 'lpj.pencairan')
                            <a href="{{ route('spd.laporan.pemberian-dana', $laporan) }}" target="_blank" class="btn btn-outline-primary w-100 mt-2">
                                <i class="bi bi-cash-stack"></i> Cetak Lembar Pemberian Dana (GA)
                            </a>
                        @endcan
                    </div>
                </div>
            @endif

            {{-- Info Pengajuan --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi Pengajuan</h6>

                    <div class="mb-2">
                        <small class="text-muted">Diajukan oleh:</small><br>
                        <strong>{{ $laporan->user->nama_lengkap ?? $laporan->user->name ?? '-' }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Tanggal Pengajuan:</small><br>
                        <strong>{{ $laporan->created_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted">Terakhir Update:</small><br>
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

            {{-- Back Button --}}
            <div class="card">
                <div class="card-body">
                    <a href="{{ route($indexRoute) }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Setujui LPJ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('lpj.superadmin.approve', $laporan) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Anda yakin ingin menyetujui LPJ ini?</p>
                    <div class="alert alert-light border mb-3">
                        <strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong><br>
                        <small class="text-muted">{{ $laporan->nama }} - Rp {{ number_format($laporan->total_pengeluaran, 0, ',', '.') }}</small>
                    </div>
                    <div class="alert alert-info py-2 mb-0">
                        <small><i class="bi bi-info-circle"></i> Surat tugas akan otomatis ditandai <strong>Selesai</strong>.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Ya, Setujui LPJ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Tolak LPJ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('lpj.superadmin.reject', $laporan) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Anda yakin ingin menolak LPJ ini?</p>
                    <div class="alert alert-light border mb-3">
                        <strong>{{ $laporan->suratSpd->nomor_surat ?? '-' }}</strong><br>
                        <small class="text-muted">{{ $laporan->nama }} - Rp {{ number_format($laporan->total_pengeluaran, 0, ',', '.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="rejection_reason" rows="4"
                                  placeholder="Jelaskan alasan penolakan..." required minlength="10"></textarea>
                        <div class="form-text">Minimal 10 karakter</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Ya, Tolak LPJ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
