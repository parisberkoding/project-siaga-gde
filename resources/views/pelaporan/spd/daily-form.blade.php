{{-- ═══════════════════════════════════════════════════════════════════════
     ⚠️ VIEW INI DIHIDE / TIDAK DIPAKAI (FLOW BARU).
     FLOW LAMA : form input laporan harian (1 file per upload) + tombol kirim per
                 hari untuk di-validasi di akhir periode SPD.
     PENGGANTI : halaman validasi (pelaporan.spd.validasi) menampung bulk upload
                 image (maks 5 gambar per kirim) langsung tersimpan sebagai
                 draft + finalisasi. File ini sengaja TIDAK dihapus.
     ───────────────────────────────────────────────────────────────────────
--}}
@extends('templates.main')

@section('title', $daily->exists ? 'Edit Laporan Harian' : 'Tambah Laporan Harian')

@section('content')
<div class="pagetitle">
    <h1><i class="bi bi-calendar-plus"></i> {{ $daily->exists ? 'Edit Laporan Harian' : 'Tambah Laporan Harian' }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.index') }}">Pelaporan</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.spd.daily.index', $surat) }}">Laporan Harian</a></li>
            <li class="breadcrumb-item active">Form</li>
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

    <form action="{{ $daily->exists ? route('pelaporan.spd.daily.update', [$surat, $daily]) : route('pelaporan.spd.daily.store', $surat) }}"
          method="POST" id="formDaily" enctype="multipart/form-data">
        @csrf
        @if($daily->exists)
            @method('PUT')
        @endif

        <input type="hidden" name="surat_spd_id" value="{{ $surat->id }}">

        <div class="row">
            <div class="col-lg-8">
                {{-- Info surat --}}
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <strong>{{ $surat->nomor_surat }}</strong> — {{ $surat->tempat_tujuan }}
                            · {{ $surat->tanggal_berangkat->translatedFormat('l, d F Y') }} s/d {{ $surat->tanggal_kembali->translatedFormat('l, d F Y') }}
                        </small>
                    </div>
                </div>

                {{-- Tanggal & uraian --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-calendar3"></i> Informasi Laporan</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tanggal Laporan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" id="tanggalDaily"
                                       value="{{ old('tanggal', $daily->tanggal ? $daily->tanggal->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                       min="{{ $surat->tanggal_berangkat->format('Y-m-d') }}"
                                       max="{{ $surat->tanggal_kembali->format('Y-m-d') }}" required>
                                <div class="form-text">Hari ini: {{ now()->translatedFormat('l, d F Y') }}</div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Uraian Kegiatan Hari Ini</label>
                                <textarea class="form-control" name="uraian" rows="2" placeholder="Ringkasan kegiatan pada tanggal ini (opsional)">{{ old('uraian', $daily->uraian) }}</textarea>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea class="form-control" name="catatan" rows="1" placeholder="Catatan tambahan...">{{ old('catatan', $daily->catatan) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-list-check"></i> Item Pengeluaran / Foto</h6>
                        <div class="alert alert-info py-2">
                            <i class="bi bi-stars me-1"></i>
                            <small>Setiap foto yang kamu pilih langsung tersimpan (draft server-side) — <strong>refresh tidak menghilangkan data</strong>. AI mengisi kategori & nominal otomatis.</small>
                        </div>

                        <div id="itemsContainer" class="d-flex flex-column gap-2">
                            @if($daily->exists)
                                @foreach($daily->strukItems as $item)
                                <div class="card border-light shadow-sm item-card" data-slug="{{ $item->slug }}">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('storage/' . $item->path_image) }}" alt="Struk"
                                                     style="max-height:70px; max-width:90px; border-radius:4px; object-fit:cover;">
                                            </div>
                                            <div class="flex-grow-1 small">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge {{ $item->getKategoriBadgeClass() }}">{{ \App\Models\StrukItem::KATEGORI_PREFIX[$item->struk_kategori] ?? $item->struk_kategori }} · {{ $item->struk_kategori }}</span>
                                                    <strong class="text-dark">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</strong>
                                                </div>
                                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->item_code }}</div>
                                                @if($item->keterangan)
                                                    <div class="text-muted text-truncate" style="font-size:0.75rem;">{{ $item->keterangan }}</div>
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>

                        <div id="itemsEmpty" class="text-center text-muted py-4 {{ $daily->exists && $daily->strukItems->isNotEmpty() ? 'd-none' : '' }}">
                            <i class="bi bi-inbox" style="font-size:2rem;"></i>
                            <p class="mb-0 mt-2 small">Belum ada item. Pilih file di bawah untuk menambahkan.</p>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Tambah Foto / Struk</label>
                            <input type="file" class="form-control" id="itemFile" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Upload satu-satu. AI akan menganalisis tiap foto.</div>
                        </div>

                        <div id="uploadStatus" class="small mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-calculator"></i> Kirim Laporan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Total item: <strong id="itemCountTotal">{{ $daily->exists ? $daily->strukItems->count() : 0 }}</strong></small>
                        </div>
                        <div class="alert alert-light border small py-2 mb-3">
                            <i class="bi bi-info-circle"></i> Laporan disimpan otomatis sebagai draft saat kamu upload file. Klik kirim untuk mengunci & mengirim laporan harian ini.
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-send"></i> Kirim Laporan Harian
                        </button>
                        <a href="{{ route('pelaporan.spd.daily.index', $surat) }}" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
    @php
        $deleteItemUrlBase = rtrim(str_replace('/__SLUG__', '', route('pelaporan.spd.daily.delete-item', ['surat' => $surat, 'item' => '__SLUG__'])), '/');
    @endphp
    const uploadItemUrl = @json(route('pelaporan.spd.daily.upload-item', $surat));
    const deleteItemUrlBase = @json($deleteItemUrlBase);
    const csrfToken = @json(csrf_token());
    const suratId = @json($surat->id);
    const itemFile = document.getElementById('itemFile');

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    const kategoriBadges = { 'BBM': 'bg-danger', 'Tol': 'bg-primary', 'Parkir': 'bg-info', 'Lainnya': 'bg-secondary', 'Tambahan': 'bg-warning text-dark' };

    function refreshEmpty() {
        const has = document.querySelectorAll('#itemsContainer .item-card').length > 0;
        document.getElementById('itemsEmpty').classList.toggle('d-none', has);
        document.getElementById('itemCountTotal').textContent = document.querySelectorAll('#itemsContainer .item-card').length;
    }

    function appendItemCard(item) {
        const container = document.getElementById('itemsContainer');
        const card = document.createElement('div');
        card.className = 'card border-light shadow-sm item-card';
        card.dataset.slug = item.slug;
        card.innerHTML = `
            <div class="card-body p-2">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-shrink-0">
                        <img src="${item.url}" alt="Struk" style="max-height:70px; max-width:90px; border-radius:4px; object-fit:cover;">
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge ${kategoriBadges[item.kategori] || 'bg-secondary'}">${item.kategori}</span>
                            <strong class="text-dark">${formatRupiah(item.nominal)}</strong>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">${item.item_code}</div>
                        ${item.keterangan ? `<div class="text-muted text-truncate" style="font-size:0.75rem;">${item.keterangan}</div>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
        card.querySelector('.btn-delete-item').addEventListener('click', () => deleteItem(card, item.slug));
        refreshEmpty();
    }

    async function deleteItem(card, slug) {
        if (!confirm('Hapus item/foto ini?')) return;
        try {
            const res = await fetch(deleteItemUrlBase + '/' + slug, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                card.remove();
                refreshEmpty();
            } else {
                alert(data.message || 'Gagal menghapus item.');
            }
        } catch (e) {
            alert('Error koneksi.');
        }
    }

    document.querySelectorAll('.btn-delete-item').forEach(btn => {
        const card = btn.closest('.item-card');
        btn.addEventListener('click', () => deleteItem(card, card.dataset.slug));
    });

    if (itemFile) {
        itemFile.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            const tanggal = document.getElementById('tanggalDaily').value || new Date().toISOString().slice(0, 10);
            const status = document.getElementById('uploadStatus');
            status.textContent = 'Mengupload & menganalisis foto...';
            status.className = 'small text-primary';

            const fd = new FormData();
            fd.append('surat_spd_id', suratId);
            fd.append('tanggal', tanggal);
            fd.append('struk', file);

            try {
                const res = await fetch(uploadItemUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    appendItemCard(data.item);
                    status.textContent = '✓ Foto tersimpan (draft server-side).';
                    status.className = 'small text-success';
                } else {
                    status.textContent = 'Gagal: ' + (data.message || 'terjadi kesalahan.');
                    status.className = 'small text-danger';
                }
            } catch (e) {
                status.textContent = 'Error koneksi.';
                status.className = 'small text-danger';
            }

            this.value = '';
        });
    }

    refreshEmpty();
</script>
@endsection
