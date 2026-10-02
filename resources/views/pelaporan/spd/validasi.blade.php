@extends('templates.main')

{{-- ═══════════════════════════════════════════════════════════════════════
     FLOW BARU: SATU HALAMAN PELAPORAN SPD.
     Sebelumnya (FLOW LAMA) halaman ini hanya menampilkan laporan harian yang
     sudah "submitted" untuk divalidasi di akhir periode SPD.
     Sekarang halaman ini menjadi satu-satunya view pelaporan SPD:
       - Input struk secara BULK (maks 5 gambar per kirim, kirim lagi 5 dst).
       - Item di-SEGMENTASI:
           ▶ DRAFT  : input saat periode SPD berjalan / belum difinalisasi.
           ▶ FINAL  : dari laporan harian submitted (flow lama) — siap digabung.
       - Edit / hapus item + form "Submit sebagai LPJ" tanpa perlu form tambahan.
     ───────────────────────────────────────────────────────────────────────
     SEMUA item (draft + submitted) akan digabung saat finalize.
--}}
@section('title', 'Pelaporan & Validasi SPD')

@section('content')
<div class="pagetitle">
    <h1><i class="bi bi-check2-square"></i> Validasi Seluruh Laporan untuk Membuat LPJ</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('pelaporan.index') }}">Pelaporan</a></li>
            <li class="breadcrumb-item active">Validasi Seluruh Laporan untuk Membuat LPJ</li>
        </ol>
    </nav>
</div>

<section class="section">
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
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

    <div class="alert {{ $spdActive ? 'alert-primary' : 'alert-success' }} py-2">
        <i class="bi {{ $spdActive ? 'bi-clock-history' : 'bi-check2-circle' }} me-1"></i>
        @if($spdActive)
            <strong>Periode SPD masih berjalan.</strong>
            <small>Semua struk yang di-upload tersimpan sebagai <strong>DRAFT</strong> (belum tervalidasi / belum finalisasi).
                Finalisasi menjadi LPJ baru bisa dilakukan setelah tanggal kembali lewat.</small>
        @else
            <strong>SPD sudah selesai.</strong>
            <small>Bulk-upload struk sisa (maks 5 gambar per kirim, bisa 5 lagi 5 lagi dst), lalu
                <strong>"Submit sebagai LPJ"</strong> untuk langsung finalisasi. Bila belum mau final, biarkan sebagai draft.</small>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- ═══════════ INPUT STRUK (FLOW BARU: BULK, MAKS 5/KIRIM) ═══════════ --}}
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-cloud-upload"></i> Input Struk / Bukti Pengeluaran</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Pilih gambar struk (maksimal <strong>5</strong> per kirim)</label>
                            <input type="file" class="form-control form-control-sm" id="bulkFile" multiple
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Setiap foto dianalisis AI — kategori, nominal, <strong>dan tanggal transaksi dibaca otomatis dari struk</strong>,
                                lalu item dikelompokkan sesuai tanggalnya (seamless, tanpa pilih tanggal manual). Tersimpan sebagai <strong>draft</strong> —
                                aman dari refresh. Bila lebih dari 5, kirim lagi batch berikutnya.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnBulkUpload">
                            <i class="bi bi-upload"></i> Upload Struk
                        </button>
                        <span id="bulkFileCount" class="small text-muted"></span>
                        <div id="uploadStatus" class="small ms-auto"></div>
                    </div>

                    {{-- Foto gagal dianalisis AI (mis. kuota Gemini habis) → isi manual --}}
                    <div id="manualPendingWrap" class="mt-3 d-none">
                        <div class="alert alert-warning py-2 small mb-2">
                            <strong><i class="bi bi-exclamation-triangle"></i> Beberapa foto gagal dianalisis AI.</strong>
                            Lengkapi datanya secara manual di bawah, lalu klik <em>Simpan Manual</em> — foto tetap masuk draft.
                        </div>
                        <div id="manualPendingList" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>

            {{-- ═══════════ SEGMENTASI DRAFT (belum final) ═══════════ --}}
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-pencil-square"></i> Draft — disimpan sementara, belum final</strong>
                    <span class="small" id="draftSummary"></span>
                </div>
                <div class="card-body">
                    {{-- Draft dikelompokkan otomatis berdasarkan tanggal dari AI --}}
                    <div id="draftItemsContainer" class="d-flex flex-column gap-3">
                        @php
                            $draftGrouped = $draftItems->groupBy(fn ($i) => $i->tanggal ? $i->tanggal->format('Y-m-d') : 'tanpa-tanggal');
                        @endphp
                        @foreach($draftGrouped as $dateKey => $groupItems)
                            @php
                                $groupFinancial = $groupItems->where('struk_kategori', '!=', 'Tambahan');
                            @endphp
                            <div class="border rounded p-2" data-date-group="{{ $dateKey }}">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <strong><i class="bi bi-calendar3 me-1"></i>
                                        @if($dateKey === 'tanpa-tanggal')
                                            Tanpa tanggal
                                        @else
                                            {{ \Illuminate\Support\Carbon::parse($dateKey)->translatedFormat('l, d F Y') }}
                                        @endif
                                    </strong>
                                    <span class="small text-muted group-meta">
                                        {{ $groupItems->count() }} item · Total Rp {{ number_format($groupFinancial->sum('nominal'), 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="row g-2">
                                    @foreach($groupItems as $item)
                                    <div class="col-12">
                                        @include('pelaporan.spd.partials.struk-item-card', ['item' => $item])
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="draftEmpty" class="text-center text-muted py-4 {{ $draftItems->isNotEmpty() ? 'd-none' : '' }}">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mb-0 mt-2 small">Belum ada item draft. Upload struk di atas — item langsung tersimpan sebagai draft.</p>
                    </div>
                </div>
            </div>

            {{-- ═══════════ SEGMENTASI FINAL / Submitted (flow lama) ═══════════ --}}
            @if($submitted->isNotEmpty())
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-check2-all"></i> Terkirim — siap digabung menjadi LPJ</strong>
                    <span class="small">{{ $submittedItems->count() }} item</span>
                </div>
                <div class="card-body">
                    @foreach($submitted as $report)
                    <div class="card mb-3 bg-light" id="reportCard-{{ $report->slug }}">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <strong><i class="bi bi-calendar3"></i> {{ $report->tanggal->translatedFormat('l, d F Y') }}</strong>
                            <span class="small text-muted">
                                {{ $report->item_count }} item · Total <strong id="totalReport-{{ $report->slug }}">Rp {{ number_format($report->total_nominal, 0, ',', '.') }}</strong>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @foreach($report->strukItems as $item)
                                <div class="col-12">
                                    @include('pelaporan.spd.partials.struk-item-card', ['item' => $item])
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- ═══════════ FORM FINALISASI LPJ ═══════════ --}}
            @if(! $spdActive)
            <form action="{{ route('pelaporan.spd.finalize', $surat) }}" method="POST" id="formFinalize"
                  class="card border-success sticky-top" style="top:1rem;">
                @csrf
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-journal-text"></i> Finalisasi LPJ</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small py-2">
                        <i class="bi bi-info-circle"></i> Semua item <strong>draft</strong> dan <strong>terkirim</strong> di
                        atas akan digabung menjadi satu LPJ dan dikunci untuk direview admin GA.
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nama Pelapor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama"
                               value="{{ old('nama', Auth::user()->nama_lengkap ?? Auth::user()->name) }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" name="jabatan" value="{{ old('jabatan', 'Pengemudi') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Uraian Perjalanan</label>
                        <textarea class="form-control" name="uraian" rows="2" placeholder="Ringkasan keseluruhan perjalanan dinas...">{{ old('uraian') }}</textarea>
                    </div>

                    <div class="alert alert-light border py-2 mb-2">
                        <small class="text-muted"><i class="bi bi-speedometer2"></i> Data tempuh kendaraan untuk dokumen cetak LPJ (opsional, boleh diisi admin GA nanti).</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Odometer sebelum SPD (km)</label>
                        <input type="number" class="form-control" name="odo_before" value="{{ old('odo_before') }}" min="0" step="0.01">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Odometer setelah SPD (km)</label>
                        <input type="number" class="form-control" name="odo_after" value="{{ old('odo_after') }}" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kebutuhan BBM (liter)</label>
                        <input type="number" class="form-control" name="kebutuhan_bbm_liter" value="{{ old('kebutuhan_bbm_liter') }}" min="0" step="0.01">
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <span class="text-muted">Total LPJ: <strong class="text-success fs-5" id="totalFinal">Rp 0</strong></span>
                        <button type="submit" class="btn btn-success w-100" id="btnSubmitLpj"
                                onclick="return confirm('Yakin submit sebagai LPJ? Seluruh item (draft + terkirim) akan digabung & dikunci.')">
                            <i class="bi bi-check2-circle"></i> Submit sebagai LPJ
                        </button>
                    </div>
                </div>
            </form>
            @else
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-lock"></i> Finalisasi LPJ</h6>
                </div>
                <div class="card-body text-center text-muted py-4">
                    <i class="bi bi-clock-history" style="font-size:2.5rem;"></i>
                    <p class="mt-2 mb-0">SPD masih berjalan.<br>
                        <small>Laporan otomatis tersimpan sebagai draft. Finalisasi menjadi LPJ menunggu setelah <strong>{{ $surat->tanggal_kembali->translatedFormat('d M Y') }}</strong>.</small>
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<script>
    @php
        $updateItemUrlBase = rtrim(str_replace('/__SLUG__', '', route('pelaporan.spd.daily.update-item', ['surat' => $surat, 'item' => '__SLUG__'])), '/');
    @endphp
    const bulkUploadUrl = @json(route('pelaporan.spd.daily.upload-items', $surat));
    const updateItemUrlBase = @json($updateItemUrlBase);
    const csrfToken = @json(csrf_token());
    const maxBulkUpload = {{ \App\Http\Controllers\SpdDailyReportController::MAX_BULK_UPLOAD }};

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    const kategoriBadges = { 'BBM': 'bg-danger', 'Tol': 'bg-primary', 'Parkir': 'bg-info', 'Hotel + Makan': 'bg-success', 'Lainnya': 'bg-secondary', 'Tambahan': 'bg-warning text-dark' };
    const kategoriOptions = ['BBM', 'Tol', 'Parkir', 'Hotel + Makan', 'Lainnya', 'Tambahan'];

    // Badge hasil perbandingan plat struk vs plat kendaraan SPD (dari DB).
    function renderPlateBadge(item) {
        if (item.plat_cocok === undefined || item.plat_cocok === null) return '';
        if (item.plat_cocok === true) {
            return `<span class="badge text-bg-success" title="Plat kendaraan SPD tercocokkan dengan struk.">
                        <i class="bi bi-shield-check"></i> Plat cocok ${(item.plat_nomor || '').toUpperCase()}</span>`;
        }
        if (item.plat_cocok === false) {
            return `<span class="badge text-bg-danger" title="Plat pada struk berbeda dari plat kendaraan SPD (${(item.plat_kendaraan || '')}).">
                        <i class="bi bi-shield-exclamation"></i> Plat beda</span>`;
        }
        return `<span class="badge text-bg-light border text-muted" title="Nomor polisi tidak terbaca pada struk / tidak dapat dibandingkan.">
                    <i class="bi bi-shield"></i> Plat tidak terbaca</span>`;
    }

    function recalcTotals() {
        let grand = 0;
        document.querySelectorAll('.card[data-slug]').forEach(card => {
            const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
            const kategori = card.querySelector('.item-kategori').value;
            if (kategori !== 'Tambahan') grand += nominal;
        });
        // Draft dikelompokkan per tanggal (dari AI) — perbarui count & total tiap grup
        document.querySelectorAll('[data-date-group]').forEach(group => {
            const items = Array.from(group.querySelectorAll('.card[data-slug]'));
            let groupTotal = 0;
            items.forEach(card => {
                const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
                const kategori = card.querySelector('.item-kategori').value;
                if (kategori !== 'Tambahan') groupTotal += nominal;
            });
            const meta = group.querySelector('.group-meta');
            if (meta) meta.textContent = items.length + ' item · Total ' + formatRupiah(groupTotal);
        });
        document.querySelectorAll('.card[id^="reportCard-"]').forEach(reportCard => {
            let reportTotal = 0;
            reportCard.querySelectorAll('.card[data-slug]').forEach(card => {
                const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
                const kategori = card.querySelector('.item-kategori').value;
                if (kategori !== 'Tambahan') reportTotal += nominal;
            });
            const slug = reportCard.id.replace('reportCard-', '');
            const el = document.getElementById('totalReport-' + slug);
            if (el) el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(reportTotal);
        });
        const totalEl = document.getElementById('totalFinal');
        if (totalEl) totalEl.textContent = formatRupiah(grand);
        updateDraftSummary();
    }

    function updateDraftSummary() {
        const count = document.querySelectorAll('#draftItemsContainer .card[data-slug]').length;
        let total = 0;
        document.querySelectorAll('#draftItemsContainer .card[data-slug]').forEach(card => {
            const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
            const kategori = card.querySelector('.item-kategori').value;
            if (kategori !== 'Tambahan') total += nominal;
        });
        const el = document.getElementById('draftSummary');
        if (el) el.textContent = count + ' item · Rp ' + new Intl.NumberFormat('id-ID').format(total);
        const empty = document.getElementById('draftEmpty');
        if (empty) empty.classList.toggle('d-none', count > 0);
    }

    async function saveItem(card) {
        const slug = card.dataset.slug;
        const nominal = card.querySelector('.item-nominal').value;
        const kategori = card.querySelector('.item-kategori').value;
        const keterangan = card.querySelector('.item-keterangan').value;
        try {
            const res = await fetch(updateItemUrlBase + '/' + slug, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ nominal: nominal, struk_kategori: kategori, keterangan: keterangan })
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Gagal menyimpan perubahan.');
                return false;
            }
            flashAutosave(card);
            recalcTotals();
            return true;
        } catch (e) {
            alert('Error koneksi.');
            return false;
        }
    }

    // Auto-save: perubahan nominal/keterangan disimpan otomatis (debounce) agar
    // TIDAK HILANG saat refresh — tanpa harus menekan tombol simpan.
    const saveDebounceMap = {};
    function saveItemDebounced(card) {
        const slug = card.dataset.slug;
        clearTimeout(saveDebounceMap[slug]);
        saveDebounceMap[slug] = setTimeout(() => saveItem(card), 900);
    }

    function flashAutosave(card) {
        const el = card.querySelector('.autosave-status');
        if (!el) return;
        el.classList.remove('d-none');
        clearTimeout(el._autosaveTimer);
        el._autosaveTimer = setTimeout(() => el.classList.add('d-none'), 1600);
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
                const col = card.closest('.col-12');
                if (col) col.remove();
                else card.remove();
                recalcTotals();
            } else {
                alert(data.message || 'Gagal menghapus item.');
            }
        } catch (e) {
            alert('Error koneksi.');
        }
    }

    function bindItemCard(card) {
        if (!card) return;
        card.querySelector('.btn-save-item').addEventListener('click', () => saveItem(card));
        card.querySelector('.btn-del-item').addEventListener('click', () => deleteItem(card));
        card.querySelector('.item-nominal').addEventListener('input', () => saveItemDebounced(card));
        card.querySelector('.item-kategori').addEventListener('change', () => saveItem(card));
        card.querySelector('.item-keterangan').addEventListener('input', () => saveItemDebounced(card));
    }

    function buildItemCardHtml(item) {
        const badge = kategoriBadges[item.kategori] || 'bg-secondary';
        const options = kategoriOptions.map(k =>
            `<option value="${k}" ${k === item.kategori ? 'selected' : ''}>${k}</option>`).join('');
        const tanggal = item.tanggal ? item.tanggal.split('T')[0] : null;
        const tanggalLabel = tanggal
            ? new Date(tanggal + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
            : '';
        return `
            <div class="card border-0 gde-struk" data-slug="${item.slug}">
                <button type="button" class="btn btn-sm btn-outline-danger gde-struk__del btn-del-item" title="Hapus file" aria-label="Hapus">
                    <i class="bi bi-trash"></i>
                </button>
                <a href="${item.url}" target="_blank" rel="noopener" class="gde-struk__thumb" title="Lihat struk">
                    <img src="${item.url}" alt="Struk ${item.item_code}" loading="lazy">
                </a>
                <div class="gde-struk__summary">
                    <div class="gde-struk__row">
                        <span class="badge ${badge}">${item.kategori}</span>
                        <span class="gde-struk__nominal">${formatRupiah(item.nominal)}</span>
                    </div>
                    <div class="gde-struk__row mt-2">
                        <span class="gde-struk__code" title="${item.item_code}">${item.item_code}</span>
                        ${tanggalLabel ? `<span class="gde-struk__date"><i class="bi bi-calendar3"></i> ${tanggalLabel}</span>` : ''}
                        ${renderPlateBadge(item)}
                    </div>
                    ${item.keterangan ? `<div class="gde-struk__ket mt-2" title="${item.keterangan}">${item.keterangan}</div>` : ''}
                </div>
                <div class="gde-struk__edit">
                    <div class="gde-struk__edit-grid">
                        <div>
                            <label class="form-label">Nominal (Rp)</label>
                            <input type="number" class="form-control form-control-sm item-nominal" value="${item.nominal}" min="0" step="0.01" inputmode="decimal">
                        </div>
                        <div>
                            <label class="form-label">Kategori</label>
                            <select class="form-select form-select-sm item-kategori">${options}</select>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm gde-btn--block btn-save-item" title="Simpan perubahan">
                            <i class="bi bi-check2"></i> Simpan
                        </button>
                    </div>
                    <div>
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control form-control-sm item-keterangan" value="${item.keterangan || ''}" maxlength="500" placeholder="cth: pengisian BBM di Patuha">
                    </div>
                    <div class="gde-struk__autosave autosave-status d-none">
                        <i class="bi bi-check-circle"></i> Tersimpan otomatis
                    </div>
                </div>
            </div>`;
    }

    // ── Draft dikelompokkan otomatis per tanggal hasil AI (seamless) ──
    function dateGroupKeyOf(item) {
        return item.tanggal ? item.tanggal.split('T')[0] : 'tanpa-tanggal';
    }

    function formatDateLabel(key) {
        if (key === 'tanpa-tanggal') return 'Tanpa tanggal';
        const d = new Date(key + 'T00:00:00');
        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    function ensureDateGroup(key) {
        const container = document.getElementById('draftItemsContainer');
        let group = container.querySelector('[data-date-group="' + key + '"]');
        if (!group) {
            group = document.createElement('div');
            group.className = 'border rounded p-2';
            group.dataset.dateGroup = key;
            group.innerHTML = `
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <strong><i class="bi bi-calendar3 me-1"></i> ${formatDateLabel(key)}</strong>
                    <span class="small text-muted group-meta">0 item · Total ${formatRupiah(0)}</span>
                </div>
                <div class="row g-2"></div>`;
            let inserted = false;
            container.querySelectorAll(':scope > [data-date-group]').forEach(existing => {
                if (!inserted && existing.dataset.dateGroup > key) {
                    container.insertBefore(group, existing);
                    inserted = true;
                }
            });
            if (!inserted) container.appendChild(group);
        }
        return group.querySelector('.row.g-2');
    }

    function appendItemCard(item) {
        const row = ensureDateGroup(dateGroupKeyOf(item));
        const col = document.createElement('div');
        col.className = 'col-12';
        col.innerHTML = buildItemCardHtml(item);
        row.appendChild(col);
        bindItemCard(col.querySelector('.card[data-slug]'));
        flashAutosave(col.querySelector('.card[data-slug]'));
        recalcTotals();
    }

    // ── Bulk upload: pisahkan file menjadi batch maksimal 5 per kirim ──
    const bulkFile = document.getElementById('bulkFile');
    const btnBulkUpload = document.getElementById('btnBulkUpload');
    const uploadStatus = document.getElementById('uploadStatus');

    if (bulkFile) {
        bulkFile.addEventListener('change', () => {
            const count = bulkFile.files.length;
            document.getElementById('bulkFileCount').textContent = count
                ? count + ' file dipilih (' + Math.ceil(count / maxBulkUpload) + '× kirim @' + maxBulkUpload + ')'
                : '';
            if (count > maxBulkUpload) {
                uploadStatus.textContent = 'Terlalu banyak dalam 1 kirim — akan dikirim per ' + maxBulkUpload + ' gambar.';
                uploadStatus.className = 'small text-warning';
            } else {
                uploadStatus.textContent = '';
            }
        });

    // ── Foto gagal analisis AI → kartu isi-manual (tetap tersimpan sbg draft) ──
    let manualSeq = 0;

    async function postStrukBatch(formData) {
        const res = await fetch(bulkUploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        });
        if (res.status === 429) return { error: 'Terlalu banyak permintaan — tunggu ±1 menit lalu coba lagi.' };
        try { return await res.json(); } catch (e) { return { error: 'Respons server tidak valid.' }; }
    }

    function refreshManualWrap() {
        const wrap = document.getElementById('manualPendingWrap');
        const list = document.getElementById('manualPendingList');
        if (wrap && list && !list.children.length) wrap.classList.add('d-none');
    }

    function queueManualEntry(file, detail) {
        const wrap = document.getElementById('manualPendingWrap');
        const list = document.getElementById('manualPendingList');
        if (!wrap || !list) return;
        wrap.classList.remove('d-none');

        const card = document.createElement('div');
        card.className = 'card border-warning shadow-sm';
        const reasonBadge = detail.reason === 'api_limit' ? 'AI Limit — Isi Manual' : 'Gagal Analisis — Isi Manual';
        card.innerHTML = `
            <div class="card-body p-2 d-flex gap-2">
                <div class="flex-shrink-0">
                    <img class="mp-preview" alt="Preview" style="max-height:70px; max-width:90px; border-radius:4px; object-fit:cover; background:#F8FAFC;">
                </div>
                <div class="flex-grow-1 small">
                    <span class="badge bg-warning text-dark">${reasonBadge}</span>
                    <span class="text-muted" style="font-size:0.7rem;">${detail.message || ''}</span>
                    <div class="row g-1 mt-1">
                        <div class="col-6 col-md-3">
                            <select class="form-select form-select-sm mp-kategori">
                                <option value="">-- Kategori --</option>
                                ${kategoriOptions.map(k => `<option value="${k}">${k}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <input type="number" min="0" step="1" class="form-control form-control-sm mp-nominal" placeholder="Nominal (Rp)">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm mp-tanggal">
                        </div>
                        <div class="col-6 col-md-4">
                            <input type="text" maxlength="500" class="form-control form-control-sm mp-keterangan" placeholder="Keterangan (opsional)">
                        </div>
                    </div>
                    <div class="d-flex gap-1 mt-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-warning mp-save"><i class="bi bi-check2"></i> Simpan Manual</button>
                        <button type="button" class="btn btn-sm btn-outline-primary mp-retry"><i class="bi bi-arrow-clockwise"></i> Coba Analisis Ulang</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mp-discard">Buang</button>
                    </div>
                </div>
            </div>`;
        list.appendChild(card);

        if (file) {
            const reader = new FileReader();
            reader.onload = e => { const im = card.querySelector('.mp-preview'); if (im) im.src = e.target.result; };
            reader.readAsDataURL(file);
        }

        card.querySelector('.mp-save').addEventListener('click', async () => {
            if (!file) { alert('File tidak tersedia untuk dikirim ulang.'); return; }
            const kategori = card.querySelector('.mp-kategori').value;
            const nominalEl = card.querySelector('.mp-nominal');
            if (!kategori) { alert('Pilih kategori terlebih dahulu.'); return; }
            if (kategori !== 'Tambahan' && nominalEl.value === '') { alert('Isi nominal terlebih dahulu.'); return; }

            const fd = new FormData();
            fd.append('images[]', file);
            fd.append('manual_data[0][kategori]', kategori);
            if (nominalEl.value !== '') fd.append('manual_data[0][nominal]', nominalEl.value);
            const tgl = card.querySelector('.mp-tanggal').value;
            if (tgl) fd.append('manual_data[0][tanggal]', tgl);
            const ket = card.querySelector('.mp-keterangan').value.trim();
            if (ket) fd.append('manual_data[0][keterangan]', ket);

            card.querySelector('.mp-save').disabled = true;
            const data = await postStrukBatch(fd);
            card.querySelector('.mp-save').disabled = false;

            if (data.error) { alert(data.error); return; }
            if (data.items && data.items.length) {
                (data.items || []).forEach(item => appendItemCard(item));
                card.remove();
                refreshManualWrap();
                uploadStatus.textContent = '✓ Foto disimpan manual sebagai draft.';
                uploadStatus.className = 'small text-success';
            } else {
                alert(data.message || 'Gagal menyimpan. Coba lagi.');
            }
        });

        card.querySelector('.mp-retry').addEventListener('click', async () => {
            if (!file) { alert('File tidak tersedia.'); return; }
            const fd = new FormData();
            fd.append('images[]', file);

            const btn = card.querySelector('.mp-retry');
            btn.disabled = true;
            uploadStatus.textContent = 'Menganalisis ulang 1 foto...';
            uploadStatus.className = 'small text-primary';

            const data = await postStrukBatch(fd);
            btn.disabled = false;

            if (data.error) { uploadStatus.textContent = data.error; uploadStatus.className = 'small text-danger'; return; }
            if (data.items && data.items.length) {
                (data.items || []).forEach(item => appendItemCard(item));
                card.remove();
                refreshManualWrap();
                uploadStatus.textContent = '✓ Analisis ulang berhasil.';
                uploadStatus.className = 'small text-success';
            } else {
                uploadStatus.textContent = 'Analisis masih gagal — silakan isi manual.';
                uploadStatus.className = 'small text-warning';
            }
        });

        card.querySelector('.mp-discard').addEventListener('click', () => {
            card.remove();
            refreshManualWrap();
        });
    }

    btnBulkUpload.addEventListener('click', async () => {
        const files = Array.from(bulkFile.files);
        if (!files.length) {
            alert('Pilih minimal 1 gambar struk terlebih dahulu.');
            return;
        }

        // Tanggal transaksi dibaca otomatis oleh AI — tidak dikirim manual lagi.
        const batches = [];
        for (let i = 0; i < files.length; i += maxBulkUpload) {
            batches.push(files.slice(i, i + maxBulkUpload));
        }

        btnBulkUpload.disabled = true;
        for (let b = 0; b < batches.length; b++) {
            const batch = batches[b];
            const fd = new FormData();
            batch.forEach((f, i) => fd.append('images[]', f));

            uploadStatus.textContent = 'Mengupload batch ' + (b + 1) + '/' + batches.length + ' & menganalisis ' + batch.length + ' foto...';
            uploadStatus.className = 'small text-primary';

            try {
                const res = await fetch(bulkUploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: fd
                });

                if (res.status === 429) {
                    uploadStatus.textContent = 'Terlalu banyak permintaan (rate limit). Tunggu ±1 menit lalu kirim ulang batch ini.';
                    uploadStatus.className = 'small text-danger';
                    break;
                }

                let data;
                try { data = await res.json(); } catch (e) { data = null; }

                if (!data) {
                    uploadStatus.textContent = 'Batch ' + (b + 1) + ': respons server tidak valid.';
                    uploadStatus.className = 'small text-danger';
                    break;
                }

                (data.items || []).forEach(item => appendItemCard(item));

                const failedDetails = data.failed_details || [];
                failedDetails.forEach(det => {
                    const fileIdx = (det.index || 1) - 1;
                    queueManualEntry(batch[fileIdx] || null, det);
                });

                if (data.has_api_limit) {
                    uploadStatus.textContent = '⚠ Kuota AI (Gemini) habis sementara — lengkapi foto yang gagal lewat form manual di atas.';
                    uploadStatus.className = 'small text-warning';
                } else if (data.success) {
                    uploadStatus.textContent = '✓ ' + (data.message || ('Batch ' + (b + 1) + ' berhasil.'));
                    uploadStatus.className = 'small ' + ((data.failed || []).length ? 'text-warning' : 'text-success');
                } else {
                    uploadStatus.textContent = 'Batch ' + (b + 1) + ' gagal: ' + (data.message || 'terjadi kesalahan.');
                    uploadStatus.className = 'small text-danger';
                    break;
                }
            } catch (e) {
                uploadStatus.textContent = 'Error koneksi saat batch ' + (b + 1) + '.';
                uploadStatus.className = 'small text-danger';
                break;
            }
        }
        btnBulkUpload.disabled = false;
        bulkFile.value = '';
        document.getElementById('bulkFileCount').textContent = '';
    });
    }

    // Bind handler edit/hapus utk item yang sudah dirender server
    document.querySelectorAll('.card[data-slug]').forEach(card => bindItemCard(card));

    recalcTotals();
</script>
@endsection