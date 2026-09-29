@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Detail Surat Tugas</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('requester.surat.index') }}">Surat Tugas</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        {{-- Detail Surat --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">
                        <span>Surat Tugas Perjalanan Dinas</span>
                        @if($surat->status == 'pending')
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Validasi</span>
                        @elseif($surat->status == 'approved')
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                        @elseif($surat->status == 'completed')
                            <span class="badge bg-primary"><i class="bi bi-check-all"></i> Selesai</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                        @endif
                    </h5>

                    {{-- Extension Badge --}}
                    @if($surat->is_extension)
                        <div class="alert alert-info py-2 mb-3">
                            <i class="bi bi-info-circle"></i> Ini adalah <strong>Surat Perpanjangan</strong> dari:
                            <a href="{{ route('requester.surat.show', $surat->parentSurat) }}">{{ $surat->parentSurat->nomor_surat }}</a>
                        </div>
                    @endif

                    {{-- Nomor Surat --}}
                    <div class="row mb-3">
                        <div class="col-4"><strong>Nomor Surat:</strong></div>
                        <div class="col-8"><span class="fs-5 fw-bold text-primary">{{ $surat->nomor_surat }}</span></div>
                    </div>

                    <hr>

                    {{-- Pemberi Tugas --}}
                    <h6 class="text-primary mb-3"><i class="bi bi-person-badge"></i> Pemberi Tugas</h6>
                    <div class="row mb-2">
                        <div class="col-4">Nama:</div>
                        <div class="col-8"><strong>{{ $surat->pemberi_tugas_nama }}</strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">Jabatan:</div>
                        <div class="col-8">{{ $surat->pemberi_tugas_jabatan }}</div>
                    </div>

                    <hr>

                    {{-- Informasi Perjalanan --}}
                    <h6 class="text-success mb-3"><i class="bi bi-geo-alt"></i> Informasi Perjalanan</h6>
                    <div class="row mb-2">
                        <div class="col-4">Nama Pengemudi:</div>
                        <div class="col-8"><strong>{{ $surat->pengemudi_nama }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Tempat Tujuan:</div>
                        <div class="col-8">{{ $surat->tempat_tujuan }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Melayani Bidang:</div>
                        <div class="col-8">{{ $surat->melayani_bidang }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Kendaraan:</div>
                        <div class="col-8">
                            <span class="badge bg-dark">{{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</span>
                            {{ $surat->vehicle->consumerial_name ?? '' }}
                        </div>
                    </div>

                    <hr>

                    {{-- Daftar Pemakai --}}
                    <h6 class="text-info mb-3"><i class="bi bi-people"></i> Daftar Nama Pemakai ({{ $surat->jumlah_pemakai }} orang)</h6>
                    @if($surat->details->isNotEmpty())
                        <ol class="mb-3">
                            @foreach($surat->details as $detail)
                                <li>{{ $detail->nama_lengkap }}</li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-muted">Tidak ada data</p>
                    @endif

                    <hr>

                    {{-- Jadwal --}}
                    <h6 class="text-warning mb-3"><i class="bi bi-calendar-check"></i> Jadwal Perjalanan</h6>
                    <div class="row mb-2">
                        <div class="col-4">Tanggal Berangkat:</div>
                        <div class="col-8"><strong>{{ $surat->tanggal_berangkat->translatedFormat('d F Y') }}</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">Tanggal Kembali:</div>
                        <div class="col-8"><strong>{{ $surat->tanggal_kembali->translatedFormat('d F Y') }}</strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">Lama Perjalanan:</div>
                        <div class="col-8"><span class="badge bg-info">{{ $surat->lama_perjalanan_hari }} hari</span></div>
                    </div>

                    <hr>

                    {{-- Budget --}}
                    <h6 class="text-success mb-3"><i class="bi bi-cash-stack"></i> Informasi Budget</h6>
                    <div class="row mb-2">
                        <div class="col-4">Budget Per Hari:</div>
                        <div class="col-8"><strong>Rp {{ number_format($surat->budget_per_hari, 0, ',', '.') }}</strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">Total Budget:</div>
                        <div class="col-8"><span class="fs-5 fw-bold text-success">Rp {{ number_format($surat->total_budget, 0, ',', '.') }}</span></div>
                    </div>

                    {{-- Catatan --}}
                    @if($surat->catatan)
                        <hr>
                        <h6 class="mb-3"><i class="bi bi-journal-text"></i> Catatan</h6>
                        <p class="text-muted">{{ $surat->catatan }}</p>
                    @endif

                    {{-- Riwayat Validasi --}}
                    @if($surat->status == 'rejected')
                        <hr>
                        <div class="alert alert-danger mb-0">
                            <strong><i class="bi bi-x-circle"></i> Surat Ditolak</strong><br>
                            <small>Oleh: {{ $surat->rejector->nama_lengkap ?? $surat->rejector->name ?? '-' }}</small><br>
                            <small>Pada: {{ $surat->rejected_at->translatedFormat('d F Y H:i') }}</small><br>
                            <strong>Alasan:</strong> {{ $surat->rejection_reason }}
                        </div>
                    @elseif($surat->status == 'approved' || $surat->status == 'completed')
                        <hr>
                        <div class="alert alert-success mb-0">
                            <strong><i class="bi bi-check-circle"></i> Surat Disetujui</strong><br>
                            <small>Oleh: {{ $surat->approver->nama_lengkap ?? $surat->approver->name ?? '-' }}</small><br>
                            <small>Pada: {{ $surat->approved_at->translatedFormat('d F Y H:i') }}</small>
                            @if($surat->lpj_deadline)
                                <br><small class="text-muted">Deadline LPJ: {{ $surat->lpj_deadline->translatedFormat('d M Y') }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Perpanjangan --}}
            @if($surat->childExtensions && $surat->childExtensions->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-diagram-3"></i> Surat Perpanjangan</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nomor Surat</th>
                                        <th>Tanggal Kembali Baru</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($surat->childExtensions as $ext)
                                        <tr>
                                            <td>{{ $ext->nomor_surat }}</td>
                                            <td>{{ $ext->tanggal_kembali->translatedFormat('d M Y') }}</td>
                                            <td>
                                                @if($ext->status == 'pending')
                                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                                @elseif($ext->status == 'approved')
                                                    <span class="badge bg-success">Disetujui</span>
                                                @else
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('requester.surat.show', $ext) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            @if($surat->status == 'approved')
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-lightning"></i> Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        @if(!$surat->lpjPenggantian)
                            @can('permission', 'lpj.create')
                            <a href="{{ route('requester.lpj.create', $surat) }}" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-file-earmark-plus"></i> Buat Laporan LPJ
                            </a>
                            @endcan
                        @endif

                        @if(!$surat->is_extension)
                            @can('permission', 'spd.create')
                            <a href="{{ route('requester.perpanjangan.create') }}?surat_id={{ $surat->id }}" class="btn btn-info w-100 mb-2">
                                <i class="bi bi-clock-history"></i> Ajukan Perpanjangan
                            </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif

            {{-- Status LPJ --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-file-earmark-check"></i> Status LPJ</h6>
                    @if($surat->status != 'approved' && $surat->status != 'completed')
                        <div class="alert alert-secondary py-2 mb-0">
                            <small><i class="bi bi-info-circle"></i> Surat belum disetujui</small>
                        </div>
                    @elseif($surat->lpjPenggantian)
                        <div class="alert {{ $surat->lpjPenggantian->status == 'approved' ? 'alert-success' : ($surat->lpjPenggantian->status == 'rejected' ? 'alert-danger' : 'alert-warning') }} py-2 mb-0">
                            @if($surat->lpjPenggantian->status == 'approved')
                                <i class="bi bi-check-circle"></i> LPJ sudah disetujui
                            @elseif($surat->lpjPenggantian->status == 'rejected')
                                <i class="bi bi-x-circle"></i> LPJ ditolak
                            @else
                                <i class="bi bi-hourglass-split"></i> LPJ menunggu validasi
                            @endif
                            <br><a href="{{ route('requester.lpj.show', $surat->lpjPenggantian) }}" class="small">Lihat LPJ →</a>
                        </div>
                    @else
                        <div class="alert {{ $surat->isLpjDeadlineOverdue() ? 'alert-danger' : 'alert-warning' }} py-2 mb-0">
                            @if($surat->isLpjDeadlineOverdue())
                                <i class="bi bi-exclamation-triangle"></i> <strong>LPJ TERLAMBAT!</strong>
                            @else
                                <i class="bi bi-clock"></i> LPJ belum dibuat
                            @endif
                            @if($surat->lpj_deadline)
                                <br><small>Deadline: {{ $surat->lpj_deadline->translatedFormat('d M Y') }}</small>
                                @if(!$surat->isLpjDeadlineOverdue())
                                    <br><small class="text-muted">{{ $surat->getDaysUntilLpjDeadline() }} hari lagi</small>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info Pengajuan --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi Pengajuan</h6>
                    <div class="mb-2">
                        <small class="text-muted">Diajukan pada:</small><br>
                        <strong>{{ $surat->created_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Terakhir diupdate:</small><br>
                        <strong>{{ $surat->updated_at->translatedFormat('d F Y H:i') }}</strong>
                    </div>
                </div>
            </div>

            {{-- Back Button --}}
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('requester.surat.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
