{{-- ═══════════════════════════════════════════════════════════════════════
     ⚠️ VIEW INI DIHIDE / TIDAK DIPAKAI (FLOW BARU).
     FLOW LAMA : daftar laporan harian yang harus diinput satu-per-satu per hari,
                 lalu dibuka lagi saat validasi di akhir periode SPD.
     PENGGANTI : SATU halaman validasi (pelaporan.spd.validasi) menampung input
                 struk (bulk, maks 5/kirim) + segmentasi draft/final + finalisasi.
     Controller index() kini redirect ke pelaporan.spd.validasi. File ini
     sengaja TIDAK dihapus agar tidak menghilangkan view lama.
     ───────────────────────────────────────────────────────────────────────
--}}
@extends('templates.main')

@section('title', 'Laporan Harian SPD')

@section('content')
<div class="pagetitle">
    <h1><i class="bi bi-calendar3"></i> Laporan Harian SPD</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.index') }}">Pelaporan</a></li>
            <li class="breadcrumb-item active">Laporan Harian</li>
        </ol>
    </nav>
</div>

<section class="section">
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

    {{-- Info surat --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-file-earmark-text text-primary" style="font-size:1.4rem; line-height:1.2;"></i>
                        <div>
                            <h5 class="card-title mb-0 fw-bold" style="word-break:break-word;">{{ $surat->nomor_surat }}</h5>
                            <div class="text-muted small">
                                <i class="bi bi-geo-alt me-1"></i>{{ $surat->tempat_tujuan }}
                                @if($surat->is_extension) <span class="badge bg-secondary ms-1">Perpanjangan</span> @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <span class="badge text-bg-light border py-2">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $surat->tanggal_berangkat->translatedFormat('d F Y') }} s/d
                            {{ $surat->tanggal_kembali->translatedFormat('d F Y') }}
                        </span>
                        <span class="badge text-bg-light border py-2">
                            <i class="bi bi-truck me-1"></i>{{ $surat->vehicle?->consumerial_code ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aksi cepat --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @if($draft)
            <a href="{{ route('pelaporan.spd.daily.edit', [$surat, $draft]) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square"></i> Lanjutkan Draft ({{ $draft->tanggal->translatedFormat('d M Y') }})
            </a>
        @else
            <a href="{{ route('pelaporan.spd.daily.create', $surat) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Laporan Harian
            </a>
        @endif

        @if($surat->tanggal_kembali->isPast() && $reports->contains('status', 'submitted'))
            <a href="{{ route('pelaporan.spd.validasi', $surat) }}" class="btn btn-success">
                <i class="bi bi-check2-square"></i> Validasi Seluruh Laporan untuk Membuat LPJ
            </a>
        @endif

        <a href="{{ route('pelaporan.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    {{-- Daftar laporan harian --}}
    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Daftar Laporan Harian</h6>

            @if($reports->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
                    <p class="mt-2 mb-0">Belum ada laporan harian. Klik "Tambah Laporan Harian" untuk mulai melaporkan pengeluaran hari ini.</p>
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th>Uraian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                        <tr>
                            <td><strong>{{ $report->tanggal->translatedFormat('l, d F Y') }}</strong></td>
                            <td>
                                <span class="badge {{ $report->status_badge_class }}">{{ $report->status_label }}</span>
                                @if($report->submitted_at)
                                    <small class="text-muted d-block">{{ $report->submitted_at->translatedFormat('l, d F Y · H:i') }}</small>
                                @endif
                            </td>
                            <td>{{ $report->item_count }}</td>
                            <td>Rp {{ number_format($report->total_nominal, 0, ',', '.') }}</td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($report->uraian, 50) }}</td>
                            <td>
                                @if($report->isDraft())
                                    <a href="{{ route('pelaporan.spd.daily.edit', [$surat, $report]) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('pelaporan.spd.daily.destroy', [$surat, $report]) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus draft laporan harian ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">Terkirim — menunggu validasi LPJ</span>
                                @endif
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
