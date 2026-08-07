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
                        <small class="text-muted">Laporkan pengeluaran perjalanan dinas (BBM, Tol, Parkir, dll) dengan upload struk + AI</small>
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
        <form action="{{ route('pelaporan.spd.store') }}" method="POST" id="formSpd" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg-8">
                    {{-- Pilih Surat --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Pilih Surat Tugas</h5>
                            <div class="mb-3">
                                <label class="form-label">Surat Tugas yang Dilaporkan <span class="text-danger">*</span></label>
                                <select class="form-select" name="surat_spd_id" id="selectSuratSpd" required>
                                    <option value="">-- Pilih Surat Tugas --</option>
                                    @foreach($suratForDropdown as $item)
                                        <option value="{{ $item['id'] }}"
                                                data-tujuan="{{ $suratList->where('id', $item['id'])->first()->tempat_tujuan ?? '' }}"
                                                data-berangkat="{{ $suratList->where('id', $item['id'])->first()->tanggal_berangkat->format('Y-m-d') ?? '' }}"
                                                data-kembali="{{ $suratList->where('id', $item['id'])->first()->tanggal_kembali->format('Y-m-d') ?? '' }}"
                                                data-budget="{{ $suratList->where('id', $item['id'])->first()->budget_per_hari ?? 0 }}"
                                                data-total-budget="{{ $suratList->where('id', $item['id'])->first()->total_budget ?? 0 }}"
                                                data-vehicle="{{ $suratList->where('id', $item['id'])->first()->vehicle->consumerial_code ?? '' }}">
                                            {{ $item['label'] }} - {{ $item['sublabel'] }}
                                            @if($item['is_overdue']) (TERLAMBAT!) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="infoSuratSpd" class="alert alert-info" style="display:none;"></div>
                        </div>
                    </div>

                    {{-- Informasi Pelapor --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-person"></i> Informasi Pelapor</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Pelapor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama"
                                           value="{{ old('nama', Auth::user()->nama_lengkap ?? Auth::user()->name) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="jabatan" value="{{ old('jabatan', 'Pengemudi') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Uraian Perjalanan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="uraian" rows="3" required
                                          placeholder="Jelaskan singkat kegiatan perjalanan dinas...">{{ old('uraian') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Rincian Pengeluaran --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0"><i class="bi bi-list-check"></i> Rincian Pengeluaran</h5>
                                <button type="button" class="btn btn-primary btn-sm" id="btnAddItemSpd">
                                    <i class="bi bi-plus-lg"></i> Tambah Item
                                </button>
                            </div>

                            <div class="alert alert-info py-2">
                                <i class="bi bi-stars me-1"></i>
                                <small>Upload foto struk untuk setiap item — AI akan mengisi kategori & nominal otomatis.</small>
                            </div>

                            <div id="itemsContainerSpd" class="d-flex flex-column gap-3"></div>

                            <div id="itemsEmptySpd" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                                <p class="mb-0 mt-2 small">Belum ada item. Klik "Tambah Item" untuk mulai.</p>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between align-items-center px-1">
                                <span class="fw-bold">Total Pengeluaran:</span>
                                <span id="totalPengeluaranSpd" class="fw-bold text-primary fs-5">Rp 0</span>
                            </div>
                            <div class="alert alert-warning py-2 mt-3" id="alertOverBudgetSpd" style="display:none;">
                                <i class="bi bi-exclamation-triangle"></i> Total pengeluaran melebihi budget!
                            </div>
                        </div>
                    </div>

                    {{-- Keuangan --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-cash-stack"></i> Informasi Keuangan</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Uang Dari</label>
                                    <input type="text" class="form-control" name="uang_dari" value="{{ old('uang_dari', 'KAS') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Diberikan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="jumlah_diberikan" id="jumlahDiberikanSpd"
                                               value="{{ old('jumlah_diberikan', 0) }}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="alert alert-success py-2 mb-0">
                                        <small>Pengembalian:</small>
                                        <br><strong id="pengembalianSpd">Rp 0</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small>Kekurangan:</small>
                                        <br><strong id="kekuranganSpd">Rp 0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-journal-text"></i> Catatan (Opsional)</h5>
                            <textarea class="form-control" name="catatan" rows="2">{{ old('catatan') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-calculator"></i> Ringkasan</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-3">
                                <tr>
                                    <td class="text-muted">Total Budget:</td>
                                    <td class="text-end"><strong id="summaryBudgetSpd">-</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Pengeluaran:</td>
                                    <td class="text-end"><strong id="summaryPengeluaranSpd">Rp 0</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">BBM:</td>
                                    <td class="text-end" id="summaryBBMSpd">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tol:</td>
                                    <td class="text-end" id="summaryTolSpd">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Parkir:</td>
                                    <td class="text-end" id="summaryParkirSpd">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Lainnya:</td>
                                    <td class="text-end" id="summaryLainSpd">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><span class="badge bg-warning text-dark" style="font-size:0.65rem;">Tambahan</span></td>
                                    <td class="text-end text-muted" id="summaryTambahanSpd" style="font-size:0.85rem;">0 foto</td>
                                </tr>
                            </table>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-send"></i> Kirim Laporan SPD
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
                                <small class="text-muted">Upload 1 foto atau nhiều foto sekaligus (struk BBM, odometoo, dashboard, dll). AI akan menganalisis tiap foto.</small>
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
                            <li>Foto odometoo/dashboard = <span class="badge bg-warning text-dark">Tambahan</span> (bukan struk)</li>
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
    const budgetsPerSurat = @json($budgetsPerSurat ?? []);
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    // Label & warna badge per kategori struk
    const kategoriLabels = { 'BBM': 'Struk BBM', 'Tol': 'Struk Tol', 'Parkir': 'Struk Parkir', 'Lainnya': 'Struk Lainnya', 'Tambahan': 'Tambahan' };
    const kategoriBadges = { 'BBM': 'bg-danger', 'Tol': 'bg-primary', 'Parkir': 'bg-info', 'Lainnya': 'bg-secondary', 'Tambahan': 'bg-warning text-dark' };
    function kategoriLabel(k) { return kategoriLabels[k] || ('Struk ' + (k || '-')); }
    function kategoriBadgeClass(k) { return kategoriBadges[k] || 'bg-secondary'; }

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

    // ── SPD items ──
    let itemCount = 0;
    let totalBudgetSpd = 0;

    function addItemSpd(tanggal = '') {
        itemCount++;
        const container = document.getElementById('itemsContainerSpd');
        const card = document.createElement('div');
        card.className = 'card item-card border shadow-sm';
        card.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="item-number badge bg-primary rounded-pill">#${itemCount}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <label class="form-label small text-muted mb-1">Tanggal</label>
                        <input type="date" class="form-control form-control-sm item-tanggal"
                               name="items[${itemCount}][tanggal]" value="${tanggal}" required>
                    </div>
                    <div class="col-6 col-md-8">
                        <label class="form-label small text-muted mb-1">Foto Struk <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm item-struk" name="items[${itemCount}][struk]"
                               accept="image/jpeg,image/png,image/webp" required>
                    </div>
                </div>

                <div class="mt-2 item-struk-preview" style="display:none;">
                    <img class="img-thumbnail" style="max-height:90px;" alt="Pratinjau">
                </div>

                <div class="item-ai-result mt-2" style="display:none;">
                    <span class="badge item-ai-badge bg-secondary">-</span>
                    <strong class="item-ai-nominal text-dark ms-1">Rp 0</strong>
                </div>
                <span class="item-ai-status small text-muted"></span>

                <input type="hidden" class="item-tipe" name="items[${itemCount}][struk_kategori]" value="">
                <input type="hidden" class="item-nominal" name="items[${itemCount}][nominal]" value="0">
                <input type="hidden" class="item-kategori-source" name="items[${itemCount}][kategori_source]" value="manual">
                <input type="hidden" class="item-metadata-json" name="items[${itemCount}][metadata_json]" value="">
                <input type="hidden" class="item-keterangan" name="items[${itemCount}][keterangan]" value="">
            </div>
        `;
        container.appendChild(card);
        document.getElementById('itemsEmptySpd').style.display = 'none';

        card.querySelector('.item-tanggal').addEventListener('change', calculateSpd);
        card.querySelector('.item-struk').addEventListener('change', function() { handleStrukSpd(this, card); });
        card.querySelector('.btn-remove-item').addEventListener('click', function() {
            card.remove();
            calculateSpd();
            if (container.querySelectorAll('.item-card').length === 0) {
                document.getElementById('itemsEmptySpd').style.display = 'block';
            }
        });
    }

    function handleStrukSpd(input, card) {
        const file = input.files[0];
        const preview = card.querySelector('.item-struk-preview');
        const status = card.querySelector('.item-ai-status');
        if (!file) {
            preview.style.display = 'none';
            preview.querySelector('img').src = '';
            status.textContent = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => { preview.querySelector('img').src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
        // Auto-analisis AI setelah file dipilih
        analyzeStrukSpd(card);
    }

    async function analyzeStrukSpd(card) {
        const input = card.querySelector('.item-struk');
        const file = input.files[0];
        if (!file) return;
        const status = card.querySelector('.item-ai-status');
        status.textContent = 'Menganalisis struk...';
        status.className = 'item-ai-status small text-primary';

        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                const kategori = data.kategori;
                const metadata = data.metadata || {};
                const text = data.text || '';

                // Isi hidden fields
                card.querySelector('.item-tipe').value = kategori;
                card.querySelector('.item-kategori-source').value = 'ai';
                card.querySelector('.item-metadata-json').value = JSON.stringify({ kategori, metadata, text });
                card.querySelector('.item-nominal').value = (metadata.nominal && metadata.nominal > 0) ? metadata.nominal : 0;
                card.querySelector('.item-keterangan').value = text ? text.substring(0, 200) : '';

                // Tampilkan hasil AI (read-only) untuk validasi
                const badge = card.querySelector('.item-ai-badge');
                badge.textContent = kategoriLabel(kategori);
                badge.className = 'badge item-ai-badge ' + kategoriBadgeClass(kategori);
                card.querySelector('.item-ai-nominal').textContent = formatRupiah(metadata.nominal || 0);
                card.querySelector('.item-ai-result').style.display = 'block';

                status.textContent = '✓ AI mendeteksi: ' + kategoriLabel(kategori) + '. Periksa hasil lalu simpan.';
                status.className = 'item-ai-status small text-success';
                calculateSpd();
            } else {
                status.textContent = 'Gagal: ' + (data.message || 'tersedia');
                status.className = 'item-ai-status small text-danger';
            }
        } catch (e) {
            status.textContent = 'Error koneksi';
            status.className = 'item-ai-status small text-danger';
        }
    }

    function calculateSpd() {
        let total = 0, bbm = 0, tol = 0, parkir = 0, lain = 0, tambahanCount = 0;
        document.querySelectorAll('#itemsContainerSpd .item-card').forEach(card => {
            const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
            const tipe = card.querySelector('.item-tipe').value;
            const subtotalEl = card.querySelector('.item-subtotal');
            if (subtotalEl) subtotalEl.textContent = formatRupiah(nominal);
            total += nominal;
            if (tipe === 'BBM') bbm += nominal;
            else if (tipe === 'Tol') tol += nominal;
            else if (tipe === 'Parkir') parkir += nominal;
            else if (tipe === 'Tambahan') { tambahanCount++; /* no nominal */ }
            else lain += nominal;
        });
        document.getElementById('totalPengeluaranSpd').textContent = formatRupiah(total);
        document.getElementById('summaryPengeluaranSpd').textContent = formatRupiah(total);
        document.getElementById('summaryBBMSpd').textContent = formatRupiah(bbm);
        document.getElementById('summaryTolSpd').textContent = formatRupiah(tol);
        document.getElementById('summaryParkirSpd').textContent = formatRupiah(parkir);
        document.getElementById('summaryLainSpd').textContent = formatRupiah(lain);
        document.getElementById('summaryTambahanSpd').textContent = tambahanCount + ' foto';

        const over = total > totalBudgetSpd;
        document.getElementById('alertOverBudgetSpd').style.display = over ? 'block' : 'none';

        const diberikan = parseFloat(document.getElementById('jumlahDiberikanSpd').value) || 0;
        const selisih = diberikan - total;
        document.getElementById('pengembalianSpd').textContent = formatRupiah(selisih >= 0 ? selisih : 0);
        document.getElementById('kekuranganSpd').textContent = formatRupiah(selisih < 0 ? Math.abs(selisih) : 0);
    }

    // Surat info SPD
    const selectSuratSpd = document.getElementById('selectSuratSpd');
    if (selectSuratSpd) {
        selectSuratSpd.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const info = document.getElementById('infoSuratSpd');
            if (!opt.value) { info.style.display = 'none'; totalBudgetSpd = 0; document.getElementById('summaryBudgetSpd').textContent = '-'; return; }
            info.innerHTML = `
                <div class="row">
                    <div class="col-md-6"><small class="text-muted">Tujuan:</small><br><strong>${opt.dataset.tujuan || '-'}</strong></div>
                    <div class="col-md-6"><small class="text-muted">Kendaraan:</small><br><strong>${opt.dataset.vehicle || '-'}</strong></div>
                </div>
                <hr class="my-2">
                <div class="row">
                    <div class="col-md-4"><small class="text-muted">Jadwal:</small><br><span>${opt.dataset.berangkat || '-'} s/d ${opt.dataset.kembali || '-'}</span></div>
                    <div class="col-md-4"><small class="text-muted">Budget/Hari:</small><br><span>${formatRupiah(opt.dataset.budget)}</span></div>
                    <div class="col-md-4"><small class="text-muted">Total Budget:</small><br><strong class="text-success">${formatRupiah(opt.dataset.totalBudget)}</strong></div>
                </div>`;
            info.style.display = 'block';
            totalBudgetSpd = parseFloat(opt.dataset.totalBudget) || 0;
            document.getElementById('summaryBudgetSpd').textContent = formatRupiah(totalBudgetSpd);
            calculateSpd();
        });
    }

    const btnAddItemSpd = document.getElementById('btnAddItemSpd');
    if (btnAddItemSpd) btnAddItemSpd.addEventListener('click', () => addItemSpd());

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

    document.getElementById('jumlahDiberikanSpd')?.addEventListener('input', calculateSpd);

    // Init: row pertama SPD jika ada
    if (btnAddItemSpd) addItemSpd();
});
</script>
@endsection
