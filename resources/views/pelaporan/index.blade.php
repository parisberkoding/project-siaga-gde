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
                        <small class="text-muted">Laporkan pengeluaran per hari selama perjalanan dinas, lalu validasi untuk membuat LPJ</small>
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
                    <small>Laporkan pengeluaran <strong>per hari</strong> selama perjalanan dinas (laporan harian tersimpan otomatis, tidak hilang saat refresh). Setelah SPD selesai, klik <strong>"Validasi Seluruh Laporan untuk Membuat LPJ"</strong> untuk direview & dikirim sebagai LPJ.</small>
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
                                        {{ $surat->tanggal_berangkat->format('d M Y') }} s/d<br>
                                        {{ $surat->tanggal_kembali->format('d M Y') }}
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
                                    <a href="{{ route('pelaporan.spd.daily.index', $surat) }}" class="btn btn-sm btn-outline-primary mb-1">
                                        <i class="bi bi-calendar3"></i> Kelola Laporan Harian
                                    </a>
                                    @if($surat->can_finalize)
                                        <div>
                                            <a href="{{ route('pelaporan.spd.validasi', $surat) }}" class="btn btn-sm btn-success">
                                                <i class="bi bi-check2-square"></i> Validasi Seluruh Laporan untuk Membuat LPJ
                                            </a>
                                        </div>
                                    @endif
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

    // ── Operasional: multi-file upload + batch AI analysis ──
    const fotoFilesOp = document.getElementById('fotoFilesOp');
    if (fotoFilesOp) {
        fotoFilesOp.addEventListener('change', async function() {
            const files = Array.from(this.files);
            const container = document.getElementById('aiResultsOp');
            const status = document.getElementById('aiStatusOp');
            const fieldsWrap = document.getElementById('fileFieldsWrap');

            // Reset
            container.innerHTML = '';
            fieldsWrap.innerHTML = '';
            status.textContent = '';
            status.className = 'small';

            if (files.length === 0) return;

            // Buat card preview untuk setiap file
            const results = [];
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
                                    <pre class="file-ai-text small mt-1 mb-0" style="white-space:pre-wrap; max-height:80px; overflow-y:auto; background:#f8f9fa; padding:4px; border-radius:3px; font-size:0.7rem;"></pre>
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

            try {
                const res = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd });
                const data = await res.json();

                if (data.success && data.results) {
                    // Isi total nominal dari struk BBM
                    let totalNominal = 0;
                    let totalVolume = 0;
                    let mainTanggal = null;

                    data.results.forEach((r, idx) => {
                        const card = document.getElementById('fileCard' + idx);
                        if (!card) return;
                        const kategori = r.kategori || 'Lainnya';
                        const metadata = r.metadata || {};
                        const text = r.text || '';

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

                        // Hidden fields
                        const idxStr = idx.toString();
                        fieldsWrap.innerHTML += `
                            <input type="hidden" name="struk_kategoris[${idxStr}]" value="${kategori}">
                            <input type="hidden" name="nominals[${idxStr}]" value="${metadata.nominal || 0}">
                            <input type="hidden" name="keterangan_s[${idxStr}]" value="${text ? text.substring(0, 200) : ''}">
                            <input type="hidden" name="metadata_s[${idxStr}]" value='${JSON.stringify({ kategori, metadata, text })}'>
                        `;

                        // Accumulate
                        if (kategori === 'BBM') {
                            totalNominal += metadata.nominal || 0;
                            totalVolume += metadata.volume || 0;
                            if (metadata.tanggal && !mainTanggal) mainTanggal = metadata.tanggal;
                        } else if (kategori !== 'Tambahan') {
                            totalNominal += metadata.nominal || 0;
                        }
                    });

                    // Auto-fill main hidden fields (BBM pertama)
                    if (totalNominal > 0) {
                        document.getElementById('nominalOp').value = totalNominal;
                    }
                    if (totalVolume > 0) {
                        document.getElementById('volumeOp').value = totalVolume;
                    }
                    if (mainTanggal) {
                        const d = new Date(mainTanggal);
                        if (!isNaN(d)) {
                            document.getElementById('purchaseDateOp').value = d.toISOString().slice(0, 10);
                        }
                    }

                    status.textContent = `✓ ${data.results.length} foto dianalisis. Periksa hasil lalu kirim laporan.`;
                    status.className = 'small text-success';
                } else {
                    status.textContent = 'Gagal: ' + (data.message || 'analisis tidak tersedia');
                    status.className = 'small text-danger';
                }
            } catch (e) {
                status.textContent = 'Error koneksi';
                status.className = 'small text-danger';
            }
        });
    }
});
</script>
@endsection
