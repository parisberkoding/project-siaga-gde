@extends('templates.main')

@section('title', 'Pelaporan BBM & SPD')

@section('content')
<div class="pagetitle">
    <h1><i class="bi bi-journal-text"></i> Pelaporan BBM & SPD</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item active">Pelaporan</li>
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

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Mode selector --}}
    <div class="row g-3 mb-4">
        @if($spdMode)
        <div class="col-md-6">
            <button type="button" class="card w-100 text-start border-primary shadow-sm mode-card" data-mode="spd" id="modeSpdCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-file-earmark-text text-primary fs-3"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">Laporan SPD (LPJ)</h5>
                        <small class="text-muted">Laporkan pengeluaran dalam satu halaman: input struk (maks 5 gambar/kirim) → segmentasi draft/final → validasi untuk membuat LPJ</small>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </button>
        </div>
        @endif
        <div class="col-md-6">
            <button type="button" class="card w-100 text-start border shadow-sm mode-card" data-mode="operational" id="modeOpCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                        <i class="bi bi-fuel-pump text-success fs-3"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">BBM Operasional</h5>
                        <small class="text-muted">Laporkan pengisian BBM harian dengan upload struk + AI</small>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </button>
        </div>
    </div>

    @if(!$spdMode)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        Tidak ada surat tugas SPD yang perlu dilaporkan saat ini. Anda hanya bisa membuat LPJ untuk surat yang sudah disetujui dan belum ada laporannya.
    </div>
    @endif

    {{-- ═══════════════ MODE SPD ═══════════════ --}}
    @if($spdMode)
    <div id="panel-spd" style="display:none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Surat Tugas SPD</h5>

                <div class="alert alert-info py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    <small>Laporkan pengeluaran lewat <strong>satu halaman validasi</strong>: struk selama SPD berjalan tersimpan otomatis sebagai <strong>draft</strong>; setelah SPD selesai cukup bulk-upload sisa struk (maks 5 gambar per kirim, bisa 5 lagi dst) lalu <strong>"Submit sebagai LPJ"</strong>.</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Surat Tugas</th>
                                <th>Jadwal</th>
                                <th>Kendaraan</th>
                                <th>Laporan Harian</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suratList as $surat)
                            <tr>
                                <td>
                                    <strong>{{ $surat->nomor_surat }}</strong>
                                    @if($surat->is_extension)
                                        <span class="badge bg-secondary">Perpanjangan</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $surat->tempat_tujuan }}</small>
                                </td>
                                <td>
                                    <small>
                                        {{ $surat->tanggal_berangkat->translatedFormat('l, d F Y') }} s/d<br>
                                        {{ $surat->tanggal_kembali->translatedFormat('l, d F Y') }}
                                    </small>
                                </td>
                                <td><small>{{ $surat->vehicle?->consumerial_code ?? '-' }}</small></td>
                                <td>
                                    @if($surat->daily_reports_available)
                                        <span class="badge bg-success">{{ $surat->daily_submitted_count }} hari terkirim</span>
                                        <small class="text-muted d-block">· {{ $surat->daily_item_count }} item</small>
                                        @if($surat->has_draft_daily)
                                            <span class="badge bg-warning text-dark">ada draft</span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted">belum ada</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('pelaporan.spd.validasi', $surat) }}" class="btn btn-sm btn-outline-primary mb-1">
                                        <i class="bi bi-calendar3"></i> Lapor & Validasi SPD
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size:2rem;"></i><br>
                                    Tidak ada surat SPD yang perlu dilaporkan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ MODE OPERASIONAL ═══════════════ --}}
    <div id="panel-operational" style="display:none;">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-fuel-pump"></i> Laporan BBM Operasional</h5>

                        @if($activePermohonan->isEmpty())
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">Tidak ada permohonan BBM yang disetujui dan belum dilaporkan.</p>
                            </div>
                        @else
                        <form action="{{ route('pelaporan.operational.store') }}" method="POST" id="formOperational" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Permohonan BBM <span class="text-danger">*</span></label>
                                <select class="form-select" name="permohonan_id" id="selectPermohonan" required>
                                    <option value="">-- Pilih Permohonan --</option>
                                    @foreach($activePermohonan as $p)
                                        <option value="{{ $p->id }}"
                                                data-vehicle="{{ $p->vehicle?->consumerial_code }}"
                                                data-plate="{{ $p->vehicle?->consumerial_code }}"
                                                data-bill="{{ $p->bill_amounts }}"
                                                data-gasoline="{{ $p->gasoline_type }}"
                                                data-budget="{{ $p->vehicle?->detail?->nominal_full_tank }}">
                                            {{ $p->request_number }} - {{ $p->vehicle?->consumerial_code }} ({{ $p->gasoline_type }}) - Rp {{ number_format($p->bill_amounts, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="infoPermohonan"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Foto Struk / Tambahan <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="foto_files[]" id="fotoFilesOp"
                                       accept="image/jpeg,image/png,image/webp" multiple required>
                                <small class="text-muted">Upload 1 foto atau banyak foto sekaligus (struk BBM, odometer, dashboard, dll). AI akan menganalisis tiap foto.</small>
                            </div>

                            {{-- Preview & hasil AI per file --}}
                            <div id="aiResultsOp" class="d-flex flex-column gap-2 mb-3"></div>

                            {{-- Validasi struk vs permohonan --}}
                            <div id="strukValidationAlertOp" class="alert alert-danger py-2 small d-none">
                                <strong><i class="bi bi-exclamation-triangle"></i> Struk berbeda.</strong>
                                <span id="strukValidationMsgOp"></span>
                            </div>

                            {{-- Peringatan API limit --}}
                            <div id="aiLimitAlertOp" class="alert alert-warning py-2 small d-none">
                                <strong><i class="bi bi-exclamation-triangle"></i> Layanan AI (Gemini) sedang mencapai batas kuota.</strong>
                                Silakan isi data struk secara manual pada foto yang ditandai, lalu kirim laporan seperti biasa.
                            </div>

                            {{-- Panel rekap manual (muncul hanya saat ada foto yang perlu diisi manual) --}}
                            <div id="manualRekapOp" class="border border-warning rounded p-2 mb-3 d-none bg-warning-subtle">
                                <div class="small fw-semibold mb-1"><i class="bi bi-pencil-square"></i> Rekap Laporan — koreksi/isi manual</div>
                                <div class="row g-2" id="manualRekapFieldsOp"></div>
                            </div>

                            {{-- Field auto-filled (hidden, dari AI) --}}
                            <input type="hidden" name="purchase_date" id="purchaseDateOp"
                                   value="{{ old('purchase_date', now()->format('Y-m-d')) }}">
                            <input type="hidden" name="volume_bbm" id="volumeOp" value="{{ old('volume_bbm') }}" required>
                            <input type="hidden" name="nominal" id="nominalOp" value="{{ old('nominal') }}">
                            {{-- Hidden fields per-file (diisi JS) --}}
                            <div id="fileFieldsWrap"></div>

                            <div id="aiStatusOp" class="small mb-2"></div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-send"></i> Kirim Laporan BBM
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi</h6>
                        <ul class="small text-muted mb-0">
                            <li>Pilih permohonan yang sudah disetujui</li>
                            <li>Upload foto struk & tambahan (odometer, dashboard) — AI otomatis mengkategorikan</li>
                            <li>Foto odometer/dashboard = <span class="badge bg-warning text-dark">Tambahan</span> (bukan struk)</li>
                            <li>Periksa hasil AI, lalu klik <strong>Kirim Laporan</strong></li>
                            <li>Bila AI limit/gagal analisis, <strong>isi manual</strong> pada foto yang ditandai kuning — laporan tetap bisa dikirim</li>
                            <li>1 permohonan = 1 laporan BBM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    const analyzeUrl = @json(route('pelaporan.analyze-struk'));
    const csrfToken = @json(csrf_token());
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    // Label & warna badge per kategori struk
    const kategoriLabels = { 'BBM': 'Struk BBM', 'Tol': 'Struk Tol', 'Parkir': 'Struk Parkir', 'Lainnya': 'Struk Lainnya', 'Tambahan': 'Tambahan' };
    const kategoriBadges = { 'BBM': 'bg-danger', 'Tol': 'bg-primary', 'Parkir': 'bg-info', 'Lainnya': 'bg-secondary', 'Tambahan': 'bg-warning text-dark' };

    // ── Mode switching (persist di URL supaya refresh tidak lompat) ──
    const cards = document.querySelectorAll('.mode-card');
    const panelSpd = document.getElementById('panel-spd');
    const panelOp = document.getElementById('panel-operational');

    function activateMode(mode, persistUrl = true) {
        const wantSpd = mode === 'spd';
        if (panelSpd) panelSpd.style.display = wantSpd ? 'block' : 'none';
        if (panelOp) panelOp.style.display = wantSpd ? 'none' : 'block';

        cards.forEach(c => {
            const active = c.dataset.mode === mode;
            c.classList.toggle('border-primary', active);
            c.classList.toggle('shadow-sm', active);
        });

        if (persistUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('mode', mode);
            history.replaceState(null, '', url);
        }
    }

    cards.forEach(c => c.addEventListener('click', () => activateMode(c.dataset.mode)));

    // Baca mode dari URL (?mode=operational) supaya refresh tetap di pilihan user
    const urlMode = new URLSearchParams(window.location.search).get('mode');
    if (urlMode === 'operational' || (urlMode === 'spd' && panelSpd)) {
        activateMode(urlMode, false);
    } else {
        activateMode(panelSpd ? 'spd' : 'operational', false);
    }

    // ── Operasional: dropdown info ──
    const selectPermohonan = document.getElementById('selectPermohonan');
    if (selectPermohonan) {
        selectPermohonan.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const info = document.getElementById('infoPermohonan');
            if (!opt.value) { info.textContent = ''; return; }
            const budget = opt.dataset.budget ? formatRupiah(opt.dataset.budget) : 'belum diisi';
            info.innerHTML = `Kendaraan: <strong>${opt.dataset.vehicle || '-'}</strong> • Jenis BBM: <strong>${opt.dataset.gasoline || '-'}</strong> • Budget full tank: <strong>${budget}</strong>`;
        });
    }

    // ── Pre-select permohonan & paksa mode operasional dari ?permohonan_id= ──
    const permohonanId = new URLSearchParams(window.location.search).get('permohonan_id');
    if (permohonanId) {
        activateMode('operational', false);
        if (selectPermohonan) {
            const presetOpt = selectPermohonan.querySelector(`option[value="${permohonanId}"]`);
            if (presetOpt) {
                selectPermohonan.value = permohonanId;
                selectPermohonan.dispatchEvent(new Event('change'));
            } else {
                const info = document.getElementById('infoPermohonan');
                if (info) info.innerHTML = '<span class="text-warning">Permohonan ini sudah dilaporkan atau belum disetujui. Pilih permohonan lain di bawah.</span>';
            }
        }
    }

    // ── Operasional: multi-file upload + batch AI analysis ──
    const fotoFilesOp = document.getElementById('fotoFilesOp');
    // State per indeks file:
    // { status:'ai_ok'|'manual', kategori, nominal, volume, tanggal, platNomor, el:{...} }
    let fileState = {};
    let strukValidationFailed = false;

    function num(v) { const n = parseFloat(v); return isNaN(n) ? 0 : n; }

    function effKategori(s) { return s.status === 'ai_ok' ? (s.kategori || 'Lainnya') : (s.kategori || ''); }

    function setMainField(id, value) {
        const el = document.getElementById(id);
        if (!el || el.dataset.userEdited === '1') return;
        el.value = value;
    }

    function recomputeTotals() {
        let totalNominal = 0, totalVolume = 0, mainTanggal = null;

        Object.values(fileState).forEach(s => {
            if (!s) return;
            const kat = effKategori(s);
            if (!kat) return;
            if (kat === 'BBM') {
                totalNominal += num(s.nominal);
                totalVolume += num(s.volume);
                if (!mainTanggal && s.tanggal) mainTanggal = s.tanggal;
            } else if (kat !== 'Tambahan') {
                totalNominal += num(s.nominal);
            }
        });

        setMainField('nominalOp', totalNominal > 0 ? totalNominal : '');
        setMainField('volumeOp', totalVolume > 0 ? totalVolume : '');

        if (mainTanggal) {
            const d = new Date(mainTanggal);
            if (!isNaN(d)) setMainField('purchaseDateOp', d.toISOString().slice(0, 10));
        }
    }

    // Pindahkan field utama (tanggal/volume/nominal) dari hidden ke panel rekap
    // yang terlihat — hanya saat ada foto yang harus diisi manual.
    function buildManualRekap() {
        const panel = document.getElementById('manualRekapOp');
        const wrap = document.getElementById('manualRekapFieldsOp');
        if (!panel || !wrap || wrap.childElementCount > 0) return;
        panel.classList.remove('d-none');

        const specs = [
            { id: 'purchaseDateOp', label: 'Tanggal Pengisian', type: 'date', required: true },
            { id: 'volumeOp', label: 'Volume BBM (liter)', type: 'number', step: '0.01', min: '0', required: true },
            { id: 'nominalOp', label: 'Nominal Total (Rp)', type: 'number', min: '0', step: '1', required: false },
        ];
        specs.forEach(sp => {
            const el = document.getElementById(sp.id);
            el.type = sp.type;
            el.className = 'form-control form-control-sm';
            if (sp.step) el.step = sp.step;
            if (sp.min !== undefined) el.min = sp.min;
            const col = document.createElement('div');
            col.className = 'col-12 col-md-4';
            col.innerHTML = `<label class="form-label small mb-1">${sp.label} ${sp.required ? '<span class="text-danger">*</span>' : ''}</label>`;
            col.appendChild(el);
            wrap.appendChild(col);

            el.addEventListener('input', () => { el.dataset.userEdited = '1'; });
        });
    }

    function syncHiddenFields(fieldsWrap) {
        fieldsWrap.innerHTML = '';
        Object.keys(fileState).sort((a, b) => a - b).forEach(i => {
            const s = fileState[i];
            if (!s) return;
            const kat = effKategori(s);
            const nom = s.status === 'ai_ok' ? (s.nominal || 0) : (s.nominal === '' ? '' : s.nominal);
            const ket = s.status === 'ai_ok' ? (s.text || '').substring(0, 200) : '';
            const meta = JSON.stringify({
                kategori: kat,
                metadata: {
                    tanggal: s.tanggal || null,
                    nominal: nom === '' ? null : num(nom),
                    volume: num(s.volume) || null,
                    plat_nomor: s.platNomor || null,
                },
                text: s.status === 'ai_ok' ? (s.text || '') : '',
                source: s.status,
            });
            const div = document.createElement('div');
            div.innerHTML =
                `<input type="hidden" name="struk_kategoris[${i}]" value="${kat}">` +
                `<input type="hidden" name="nominals[${i}]" value="${nom}">` +
                `<input type="hidden" name="keterangan_s[${i}]" value="${ket.replace(/"/g, '&quot;')}">` +
                `<input type="hidden" name="kategori_sources[${i}]" value="${s.status === 'ai_ok' ? 'ai' : 'manual'}">` +
                `<input type="hidden" name="metadata_s[${i}]" value='${meta.replace(/'/g, '&#39;')}'>`;
            fieldsWrap.appendChild(div);
        });
    }

    function renderManualInputs(card, idx) {
        const detailEl = card.querySelector('.file-ai-detail');
        detailEl.innerHTML = `
            <div class="row g-1 mt-1">
                <div class="col-12 col-md-5">
                    <select class="form-select form-select-sm m-kategori">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="BBM">Struk BBM</option>
                        <option value="Tol">Struk Tol</option>
                        <option value="Parkir">Struk Parkir</option>
                        <option value="Lainnya">Struk Lainnya</option>
                        <option value="Tambahan">Tambahan (bukan struk)</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <input type="number" min="0" step="1" class="form-control form-control-sm m-nominal" placeholder="Nominal (Rp)">
                </div>
                <div class="col-6 col-md-2 m-volume-wrap d-none">
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm m-volume" placeholder="Liter">
                </div>
                <div class="col-6 offset-md-2 col-md-2">
                    <input type="date" class="form-control form-control-sm m-tanggal" title="Tanggal transaksi">
                </div>
            </div>`;

        const st = fileState[idx];
        st.el = {
            kategori: detailEl.querySelector('.m-kategori'),
            nominal: detailEl.querySelector('.m-nominal'),
            volume: detailEl.querySelector('.m-volume'),
            tanggal: detailEl.querySelector('.m-tanggal'),
            volWrap: detailEl.querySelector('.m-volume-wrap'),
        };

        st.el.kategori.addEventListener('change', function() {
            st.kategori = this.value;
            const isBbm = this.value === 'BBM';
            st.el.volWrap.classList.toggle('d-none', !isBbm);
            if (!isBbm) st.volume = '';
            recomputeTotals();
        });
        st.el.nominal.addEventListener('input', function() { st.nominal = this.value; recomputeTotals(); });
        st.el.volume.addEventListener('input', function() { st.volume = this.value; recomputeTotals(); });
        st.el.tanggal.addEventListener('change', function() { st.tanggal = this.value; recomputeTotals(); });
    }

    if (fotoFilesOp) {
        fotoFilesOp.addEventListener('change', async function() {
            const files = Array.from(this.files);
            const container = document.getElementById('aiResultsOp');
            const status = document.getElementById('aiStatusOp');
            const fieldsWrap = document.getElementById('fileFieldsWrap');

            // Wajib pilih permohonan dulu agar validasi struk bisa dijalankan.
            if (selectPermohonan && !selectPermohonan.value) {
                this.value = '';
                status.textContent = 'Pilih Permohonan BBM terlebih dahulu sebelum upload struk.';
                status.className = 'small text-danger';
                return;
            }

            // Reset
            container.innerHTML = '';
            fieldsWrap.innerHTML = '';
            status.textContent = '';
            status.className = 'small';
            fileState = {};
            strukValidationFailed = false;
            document.getElementById('aiLimitAlertOp').classList.add('d-none');
            const valAlertReset = document.getElementById('strukValidationAlertOp');
            if (valAlertReset) valAlertReset.classList.add('d-none');

            if (files.length === 0) return;

            // Buat card preview untuk setiap file
            files.forEach((file, idx) => {
                const card = document.createElement('div');
                card.className = 'card border-light shadow-sm';
                card.id = 'fileCard' + idx;
                card.innerHTML = `
                    <div class="card-body p-2">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-shrink-0">
                                <img class="file-preview-img" style="max-height:70px; max-width:90px; border-radius:4px; object-fit:cover;" alt="Preview">
                            </div>
                            <div class="flex-grow-1 small">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge file-ai-badge bg-secondary">-</span>
                                    <strong class="file-ai-nominal text-dark">Rp 0</strong>
                                    <span class="file-ai-status text-muted ms-1"></span>
                                </div>
                                <div class="file-ai-detail text-muted" style="font-size:0.75rem;"></div>
                                <details class="mt-1">
                                    <summary class="text-muted" style="font-size:0.7rem;">OCR</summary>
                                    <pre class="file-ai-text small mt-1 mb-0" style="white-space:pre-wrap; overflow-wrap:break-word; word-break:break-word; max-height:80px; overflow-y:auto; background:#F8FAFC; padding:4px; border-radius:3px; font-size:0.7rem;"></pre>
                                </details>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);

                // Preview image
                const reader = new FileReader();
                const imgEl = card.querySelector('.file-preview-img');
                reader.onload = e => { imgEl.src = e.target.result; };
                reader.readAsDataURL(file);
            });

            // Kirim semua file ke server (batch)
            status.textContent = `Menganalisis ${files.length} foto...`;
            status.className = 'small text-primary';

            const fd = new FormData();
            files.forEach(f => fd.append('images[]', f));
            if (selectPermohonan && selectPermohonan.value) {
                fd.append('permohonan_id', selectPermohonan.value);
            }

            try {
                const res = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd });

                if (res.status === 429) {
                    status.textContent = 'Terlalu banyak permintaan analisis (rate limit). Tunggu ±1 menit lalu upload ulang, atau lanjutkan dengan pengisian manual.';
                    status.className = 'small text-danger';
                    return;
                }

                let data;
                try {
                    data = await res.json();
                } catch (e) {
                    status.textContent = 'Respons server tidak valid. Coba lagi.';
                    status.className = 'small text-danger';
                    return;
                }

                if (!(data.success && data.results)) {
                    status.textContent = 'Gagal: ' + (data.message || 'analisis tidak tersedia');
                    status.className = 'small text-danger';
                    return;
                }

                let hasApiLimit = !!data.has_api_limit;
                let hasApiError = false;

                data.results.forEach((r, idx) => {
                    const card = document.getElementById('fileCard' + idx);
                    if (!card) return;

                    const statusOk = r.status === 'ok';

                    if (statusOk) {
                        const kategori = r.kategori || 'Lainnya';
                        const metadata = r.metadata || {};
                        const text = r.text || '';

                        fileState[idx] = {
                            status: 'ai_ok',
                            kategori,
                            nominal: metadata.nominal || 0,
                            volume: metadata.volume || 0,
                            tanggal: metadata.tanggal || '',
                            platNomor: metadata.plat_nomor || '',
                            text,
                            el: null,
                        };

                        // Badge & nominal
                        const badge = card.querySelector('.file-ai-badge');
                        badge.textContent = kategoriLabels[kategori] || kategori;
                        badge.className = 'badge file-ai-badge ' + (kategoriBadges[kategori] || 'bg-secondary');
                        card.querySelector('.file-ai-nominal').textContent = formatRupiah(metadata.nominal || 0);

                        // Detail
                        const detailEl = card.querySelector('.file-ai-detail');
                        let detailParts = [];
                        if (metadata.volume) detailParts.push(metadata.volume + ' L');
                        if (metadata.harga_per_liter) detailParts.push('@ ' + formatRupiah(metadata.harga_per_liter) + '/L');
                        detailEl.textContent = detailParts.join(' • ') || '-';

                        // OCR text
                        card.querySelector('.file-ai-text').textContent = text || '(tanpa teks)';

                        // Status
                        const statusEl = card.querySelector('.file-ai-status');
                        statusEl.textContent = '✓';
                        statusEl.className = 'file-ai-status text-success ms-1';
                    } else {
                        // api_limit / api_error → mode isi manual untuk foto ini
                        if (r.status === 'api_limit') hasApiLimit = true; else hasApiError = true;

                        fileState[idx] = { status: 'manual', kategori: '', nominal: '', volume: '', tanggal: '', platNomor: '', text: '', el: null };

                        card.classList.remove('border-light');
                        card.classList.add('needs-manual', 'border-warning');

                        const badge = card.querySelector('.file-ai-badge');
                        badge.textContent = r.status === 'api_limit' ? 'AI Limit — Isi Manual' : 'Gagal Analisis — Isi Manual';
                        badge.className = 'badge file-ai-badge bg-warning text-dark';
                        card.querySelector('.file-ai-nominal').textContent = '';
                        card.querySelector('.file-ai-text').textContent = '';

                        const statusEl = card.querySelector('.file-ai-status');
                        statusEl.textContent = '⚠';
                        statusEl.className = 'file-ai-status text-warning ms-1';

                        renderManualInputs(card, idx);
                    }
                });

                syncHiddenFields(fieldsWrap);
                recomputeTotals();

                // ── Validasi kecocokan struk vs permohonan (nominal & plat) ──
                if (data.validation) {
                    const valAlert = document.getElementById('strukValidationAlertOp');
                    const valMsg = document.getElementById('strukValidationMsgOp');
                    strukValidationFailed = !data.validation.valid;
                    if (strukValidationFailed) {
                        if (valMsg) valMsg.textContent = ' ' + (data.validation.message || 'Harap upload struk yang sebenarnya.');
                        if (valAlert) valAlert.classList.remove('d-none');
                        Object.entries(fileState).forEach(([idx, s]) => {
                            if (s && effKategori(s) === 'BBM') {
                                const c = document.getElementById('fileCard' + idx);
                                if (c) { c.classList.remove('border-light'); c.classList.add('border-danger'); }
                            }
                        });
                    } else if (valAlert) {
                        valAlert.classList.add('d-none');
                    }
                }

                if (hasApiLimit || hasApiError) {
                    const alertEl = document.getElementById('aiLimitAlertOp');
                    alertEl.innerHTML = hasApiLimit
                        ? '<strong><i class="bi bi-exclamation-triangle"></i> Layanan AI (Gemini) sedang mencapai batas kuota.</strong> Silakan isi data secara manual pada foto yang ditandai kuning, lalu kirim laporan seperti biasa.'
                        : '<strong><i class="bi bi-exclamation-triangle"></i> Sebagian foto gagal dianalisis AI.</strong> Silakan lengkapi data secara manual pada foto yang ditandai.';
                    alertEl.classList.remove('d-none');
                    buildManualRekap();
                }

                const manualCount = Object.values(fileState).filter(s => s.status === 'manual').length;
                if (manualCount > 0) {
                    status.textContent = `${data.results.length - manualCount}/${data.results.length} foto dianalisis AI • ${manualCount} foto perlu diisi manual.`;
                    status.className = 'small text-warning';
                } else {
                    status.textContent = `✓ ${data.results.length} foto dianalisis. Periksa hasil lalu kirim laporan.`;
                    status.className = 'small text-success';
                }
            } catch (e) {
                status.textContent = 'Error koneksi — periksa jaringan lalu coba lagi.';
                status.className = 'small text-danger';
            }
        });
    }

    // ── Operasional: blokir submit bila ada foto yang belum diisi manual ──
    const formOperational = document.getElementById('formOperational');
    if (formOperational) {
        formOperational.addEventListener('submit', function(e) {
            // Blokir submit bila validasi struk vs permohonan gagal.
            if (strukValidationFailed) {
                e.preventDefault();
                const msg = document.getElementById('strukValidationMsgOp');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Struk Berbeda',
                        text: (msg && msg.textContent) ? msg.textContent.trim() : 'Harap upload struk yang sebenarnya.',
                        confirmButtonText: 'OK',
                    });
                }
                return;
            }

            const problems = [];

            Object.entries(fileState).forEach(([idx, s]) => {
                if (!s || s.status !== 'manual') return;
                const n = parseInt(idx, 10) + 1;
                if (!s.kategori) {
                    problems.push(`Foto ${n}: pilih kategori struk.`);
                    if (s.el && s.el.kategori) s.el.kategori.classList.add('is-invalid');
                } else {
                    if (s.el && s.el.kategori) s.el.kategori.classList.remove('is-invalid');
                }
                if (s.kategori && s.kategori !== 'Tambahan' && (s.nominal === '' || s.nominal === null)) {
                    problems.push(`Foto ${n}: isi nominal.`);
                    if (s.el && s.el.nominal) s.el.nominal.classList.add('is-invalid');
                } else {
                    if (s.el && s.el.nominal) s.el.nominal.classList.remove('is-invalid');
                }
            });

            const volumeOp = document.getElementById('volumeOp');
            const purchaseDateOp = document.getElementById('purchaseDateOp');
            if (!((parseFloat(volumeOp && volumeOp.value) || 0) > 0)) {
                problems.push('Volume BBM wajib diisi (minimal 0.01 liter).');
                if (volumeOp) volumeOp.classList.add('is-invalid');
            } else if (volumeOp) {
                volumeOp.classList.remove('is-invalid');
            }
            if (!purchaseDateOp || !purchaseDateOp.value) {
                problems.push('Tanggal pengisian wajib diisi.');
                if (purchaseDateOp) purchaseDateOp.classList.add('is-invalid');
            } else if (purchaseDateOp) {
                purchaseDateOp.classList.remove('is-invalid');
            }

            if (problems.length > 0) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lengkapi Data Manual',
                        html: '<div style="text-align:left">' + problems.map(p => '&bull; ' + p).join('<br>') + '</div>',
                        confirmButtonText: 'OK, saya isi',
                    });
                }
            }
        });
    }
});
</script>
@endsection
