@extends('templates.main')

@section('title', 'Validasi Laporan Harian SPD')

@section('content')
<div class="pagetitle">
    <h1><i class="bi bi-check2-square"></i> Validasi Seluruh Laporan untuk Membuat LPJ</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.index') }}">Pelaporan</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.spd.daily.index', $surat) }}">Laporan Harian</a></li>
            <li class="breadcrumb-item active">Validasi</li>
        </ol>
    </nav>
</div>

<section class="section">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-warning py-2">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Periksa kembali semua file di bawah.</strong>
        <small>Jika ada file yang salah kirim, hapus atau perbaiki nominalnya sebelum submit. Setelah LPJ dibuat, file tidak bisa diubah lagi.</small>
    </div>

    {{-- Semua file yang dikirim driver --}}
    @foreach($submitted as $report)
    <div class="card mb-3" id="reportCard-{{ $report->slug }}">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong><i class="bi bi-calendar3"></i> Laporan {{ $report->tanggal->translatedFormat('d M Y') }}</strong>
                <span class="small text-muted">
                    {{ $report->item_count }} item · Total <strong id="totalReport-{{ $report->slug }}">Rp {{ number_format($report->total_nominal, 0, ',', '.') }}</strong>
                </span>
            </div>
            @if($report->uraian)
                <small class="text-muted d-block mt-1"><i class="bi bi-chat-left-text"></i> {{ $report->uraian }}</small>
            @endif
        </div>
        <div class="card-body">
            @if($report->strukItems->isEmpty())
                <div class="text-center text-muted py-3 small">Tidak ada item pada laporan ini.</div>
            @else
            <div class="row g-2">
                @foreach($report->strukItems as $item)
                <div class="col-md-6">
                    <div class="card border-light shadow-sm h-100" data-slug="{{ $item->slug }}">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-shrink-0">
                                    <a href="{{ asset('storage/' . $item->path_image) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $item->path_image) }}" alt="Struk"
                                             style="max-height:80px; max-width:100px; border-radius:4px; object-fit:cover;">
                                    </a>
                                </div>
                                <div class="flex-grow-1 small">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge {{ $item->getKategoriBadgeClass() }}">{{ $item->struk_kategori }}</span>
                                        <strong class="text-dark">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="text-muted" style="font-size:0.7rem;">{{ $item->item_code }}</div>
                                    @if($item->keterangan)
                                        <div class="text-muted text-truncate" style="font-size:0.75rem;" title="{{ $item->keterangan }}">{{ $item->keterangan }}</div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-del-item" title="Hapus file">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <hr class="my-1">
                            <div class="d-flex gap-1 align-items-end">
                                <div class="flex-grow-1">
                                    <label class="small text-muted mb-0">Nominal (Rp)</label>
                                    <input type="number" class="form-control form-control-sm item-nominal" value="{{ (float) $item->nominal }}" min="0" step="0.01">
                                </div>
                                <div>
                                    <label class="small text-muted mb-0">Kategori</label>
                                    <select class="form-select form-select-sm item-kategori">
                                        <option value="BBM" {{ $item->struk_kategori === 'BBM' ? 'selected' : '' }}>BBM</option>
                                        <option value="Tol" {{ $item->struk_kategori === 'Tol' ? 'selected' : '' }}>Tol</option>
                                        <option value="Parkir" {{ $item->struk_kategori === 'Parkir' ? 'selected' : '' }}>Parkir</option>
                                        <option value="Lainnya" {{ $item->struk_kategori === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                        <option value="Tambahan" {{ $item->struk_kategori === 'Tambahan' ? 'selected' : '' }}>Tambahan</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-save-item">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach

    {{-- Form finalisasi LPJ --}}
    <form action="{{ route('pelaporan.spd.finalize', $surat) }}" method="POST" id="formFinalize">
        @csrf
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-journal-text"></i> Informasi LPJ (dikirim ke admin GA)</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Pelapor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama"
                               value="{{ old('nama', Auth::user()->nama_lengkap ?? Auth::user()->name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" name="jabatan" value="{{ old('jabatan', 'Pengemudi') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Uraian Perjalanan</label>
                    <textarea class="form-control" name="uraian" rows="2" placeholder="Ringkasan keseluruhan perjalanan dinas...">{{ old('uraian') }}</textarea>
                </div>

                <div class="alert alert-light border py-2 mb-3">
                    <small class="text-muted"><i class="bi bi-speedometer2"></i> Data tempuh kendaraan untuk dokumen cetak LPJ (opsional, boleh diisi admin GA nanti).</small>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Odometer sebelum SPD (km)</label>
                        <input type="number" class="form-control" name="odo_before" value="{{ old('odo_before') }}" min="0" step="0.01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Odometer setelah SPD (km)</label>
                        <input type="number" class="form-control" name="odo_after" value="{{ old('odo_after') }}" min="0" step="0.01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kebutuhan BBM (liter)</label>
                        <input type="number" class="form-control" name="kebutuhan_bbm_liter" value="{{ old('kebutuhan_bbm_liter') }}" min="0" step="0.01">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">Total LPJ:</span>
                        <strong class="text-success fs-5" id="totalFinal">Rp 0</strong>
                    </div>
                    <button type="submit" class="btn btn-success" id="btnSubmitLpj"
                            onclick="return confirm('Yakin submit sebagai LPJ? Seluruh laporan harian akan digabung & dikunci.')">
                        <i class="bi bi-check2-circle"></i> Submit sebagai LPJ
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
    @php
        $updateItemUrlBase = rtrim(str_replace('/__SLUG__', '', route('pelaporan.spd.daily.update-item', ['surat' => $surat, 'item' => '__SLUG__'])), '/');
    @endphp
    const updateItemUrlBase = @json($updateItemUrlBase);
    const csrfToken = @json(csrf_token());

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function recalcTotals() {
        let grand = 0;
        document.querySelectorAll('.card.mb-3[id^="reportCard-"]').forEach(reportCard => {
            let reportTotal = 0;
            reportCard.querySelectorAll('.card[data-slug]').forEach(card => {
                const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
                const kategori = card.querySelector('.item-kategori').value;
                if (kategori !== 'Tambahan') {
                    reportTotal += nominal;
                    grand += nominal;
                }
            });
            const slug = reportCard.id.replace('reportCard-', '');
            const el = document.getElementById('totalReport-' + slug);
            if (el) el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(reportTotal);
        });
        document.getElementById('totalFinal').textContent = formatRupiah(grand);
    }

    async function saveItem(card) {
        const slug = card.dataset.slug;
        const nominal = card.querySelector('.item-nominal').value;
        const kategori = card.querySelector('.item-kategori').value;
        try {
            const res = await fetch(updateItemUrlBase + '/' + slug, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ nominal: nominal, struk_kategori: kategori })
            });
            const data = await res.json();
            if (!data.success) alert(data.message || 'Gagal menyimpan perubahan.');
            recalcTotals();
        } catch (e) {
            alert('Error koneksi.');
        }
    }

    async function deleteItem(card) {
        if (!confirm('Hapus file ini dari laporan?')) return;
        const slug = card.dataset.slug;
        try {
            const res = await fetch(updateItemUrlBase + '/' + slug, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                card.closest('.col-md-6').remove();
                recalcTotals();
            } else {
                alert(data.message || 'Gagal menghapus item.');
            }
        } catch (e) {
            alert('Error koneksi.');
        }
    }

    document.querySelectorAll('.card[data-slug]').forEach(card => {
        card.querySelector('.btn-save-item').addEventListener('click', () => saveItem(card));
        card.querySelector('.btn-del-item').addEventListener('click', () => deleteItem(card));
        card.querySelector('.item-nominal').addEventListener('input', recalcTotals);
        card.querySelector('.item-kategori').addEventListener('change', recalcTotals);
    });

    recalcTotals();
</script>
@endsection
