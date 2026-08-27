@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Revisi Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('requester.lpj.index') }}">Laporan LPJ</a></li>
            <li class="breadcrumb-item active">Revisi LPJ</li>
        </ol>
    </nav>
</div>

<section class="section">
    {{-- Alert Messages --}}
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

    {{-- Rejection Info Banner --}}
    <div class="alert alert-danger">
        <i class="bi bi-x-circle"></i> <strong>LPJ Ditolak:</strong>
        <br>{{ $laporan->rejection_reason }}
        <br><small class="text-muted">Silakan perbaiki laporan di bawah lalu ajukan ulang.</small>
    </div>

    <form action="{{ route('requester.lpj.update', $laporan) }}" method="POST" id="formLpj" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            {{-- Form Utama --}}
            <div class="col-lg-8">
                {{-- Surat Tugas (read-only) --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Surat Tugas</h5>
                        <div class="alert alert-secondary">
                            <strong>{{ $surat->nomor_surat }}</strong><br>
                            <small>{{ $surat->tempat_tujuan }} | {{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}</small>
                            <br><small>Kendaraan: {{ $surat->vehicle->consumerial_code ?? '' }}</small>
                        </div>
                    </div>
                </div>

                {{-- Informasi Pelapor --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person"></i> Informasi Pelapor</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Pelapor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama') is-invalid @enderror"
                                       name="nama" value="{{ old('nama', $laporan->nama) }}" required>
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('jabatan') is-invalid @enderror"
                                       name="jabatan" value="{{ old('jabatan', $laporan->jabatan) }}" required>
                                @error('jabatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Uraian Perjalanan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('uraian') is-invalid @enderror"
                                      name="uraian" rows="3" required
                                      placeholder="Jelaskan secara singkat kegiatan perjalanan dinas...">{{ old('uraian', $laporan->uraian) }}</textarea>
                            @error('uraian')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Informasi Tempuh Kendaraan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-speedometer2"></i> Informasi Tempuh Kendaraan</h5>
                        <small class="text-muted d-block mb-3">Data odometer & kebutuhan BBM untuk dokumen cetak LPJ (opsional).</small>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Odometer sebelum SPD (km)</label>
                                <input type="number" class="form-control" name="odo_before"
                                       id="odoBefore" value="{{ old('odo_before', $laporan->odo_before) }}" min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Odometer setelah SPD (km)</label>
                                <input type="number" class="form-control" name="odo_after"
                                       id="odoAfter" value="{{ old('odo_after', $laporan->odo_after) }}" min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kebutuhan BBM (liter)</label>
                                <input type="number" class="form-control" name="kebutuhan_bbm_liter"
                                       id="kebutuhanBbm" value="{{ old('kebutuhan_bbm_liter', $laporan->kebutuhan_bbm_liter) }}" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="alert alert-light border py-2 mb-0">
                            <small class="text-muted">Jarak tempuh otomatis dihitung: <strong id="jarakTempuh">-</strong></small>
                        </div>
                    </div>
                </div>

                {{-- Detail Pengeluaran --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><i class="bi bi-list-check"></i> Rincian Pengeluaran</h5>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAddItem">
                                <i class="bi bi-plus-lg"></i> Tambah Item
                            </button>
                        </div>

                        <div id="itemsContainer" class="d-flex flex-column gap-3">
                        </div>

                        <div id="itemsEmpty" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                            <p class="mb-0 mt-2 small">Belum ada item pengeluaran. Klik "Tambah Item" untuk mulai.</p>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center px-1">
                            <span class="fw-bold">Total Pengeluaran:</span>
                            <span id="totalPengeluaran" class="fw-bold text-primary fs-5">Rp 0</span>
                        </div>

                        <div class="alert alert-warning py-2 mt-3" id="alertOverBudget" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Total pengeluaran melebihi budget!
                        </div>

                        <div class="alert alert-danger py-2 mt-2" id="alertOverBudgetDetail" style="display: none;">
                            <i class="bi bi-exclamation-octagon"></i> <strong>Over Budget per Tanggal:</strong>
                            <ul class="mb-0 mt-1" id="overBudgetList"></ul>
                        </div>
                    </div>
                </div>

                {{-- Informasi Keuangan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-cash-stack"></i> Informasi Keuangan</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Uang Dari</label>
                                <input type="text" class="form-control" name="uang_dari"
                                       value="{{ old('uang_dari', $laporan->uang_dari ?? 'KAS') }}" placeholder="Contoh: KAS, Bon Sementara">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jumlah Diberikan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="jumlah_diberikan"
                                           id="jumlahDiberikan" value="{{ old('jumlah_diberikan', $laporan->jumlah_diberikan ?? 0) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success py-2 mb-0">
                                    <small>Pengembalian:</small>
                                    <br><strong id="pengembalian">Rp 0</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning py-2 mb-0">
                                    <small>Kekurangan:</small>
                                    <br><strong id="kekurangan">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-journal-text"></i> Catatan (Opsional)</h5>
                        <textarea class="form-control" name="catatan" rows="2"
                                  placeholder="Catatan tambahan jika ada...">{{ old('catatan', $laporan->catatan) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sidebar Right --}}
            <div class="col-lg-4">
                {{-- Ringkasan --}}
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-calculator"></i> Ringkasan</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted">Total Budget:</td>
                                <td class="text-end"><strong id="summaryBudget">-</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Pengeluaran:</td>
                                <td class="text-end"><strong id="summaryPengeluaran">Rp 0</strong></td>
                            </tr>
                            <tr id="rowSelisih" style="display: none;">
                                <td class="text-muted">Selisih:</td>
                                <td class="text-end"><strong id="summarySelisih">-</strong></td>
                            </tr>
                        </table>

                        <hr>

                        <div id="summaryBudgetPerTanggal" style="display: none;">
                            <small class="text-muted fw-bold">Status per Tanggal:</small>
                            <div id="summaryDateStatus" class="mt-1 mb-3"></div>
                            <hr>
                        </div>

                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted">Hotel:</td>
                                <td class="text-end"><span id="summaryHotel">Rp 0</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">BBM:</td>
                                <td class="text-end"><span id="summaryBBM">Rp 0</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Parkir:</td>
                                <td class="text-end"><span id="summaryParkir">Rp 0</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tol:</td>
                                <td class="text-end"><span id="summaryTol">Rp 0</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Lain-lain:</td>
                                <td class="text-end"><span id="summaryLain">Rp 0</span></td>
                            </tr>
                        </table>

                        <button type="submit" class="btn btn-warning w-100 mb-2" id="btnSubmit">
                            <i class="bi bi-send"></i> Ajukan Ulang LPJ
                        </button>

                        <a href="{{ route('requester.lpj.show', $laporan) }}" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
                    </div>
                </div>

                {{-- Info --}}
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi</h6>
                        <ul class="small text-muted mb-0">
                            <li>Perbaiki data sesuai alasan penolakan</li>
                            <li>Pastikan semua pengeluaran sudah dicatat</li>
                            <li>Pengeluaran akan dicocokkan dengan budget per tanggal</li>
                            <li>Item yang melebihi budget harian akan ditandai</li>
                            <li>Laporan akan divalidasi ulang oleh Superadmin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

{{-- Pass budget data ke JS --}}
<script>
    const budgetsPerSurat = @json($budgetsPerSurat ?? []);
    const analyzeUrl = @json(route('pelaporan.analyze-struk'));
    const csrfToken = @json(csrf_token());
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('itemsContainer');
    const totalPengeluaranEl = document.getElementById('totalPengeluaran');
    const jumlahDiberikan = document.getElementById('jumlahDiberikan');

    let itemCount = 0;
    let totalBudget = 0;
    let currentBudgetMap = {};

    // Pre-populated items from existing LPJ
    @php
        $existingItems = $laporan->details->map(fn($d) => [
            'tanggal' => $d->tanggal ? $d->tanggal->format('Y-m-d') : '',
            'tipe_penggunaan' => $d->tipe_penggunaan,
            'nominal' => $d->nominal,
            'keterangan' => $d->keterangan,
        ])->values();
    @endphp
    const existingItems = @json($existingItems);

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    }

    function formatTanggal(dateStr) {
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        const d = new Date(dateStr);
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function addItemRow(tanggal = '', tipe = 'BBM', nominal = '', keterangan = '', strukKategori = '', kategoriSource = 'manual', metadataJson = '', hasStruk = false) {
        itemCount++;
        const card = document.createElement('div');
        card.className = 'card item-card border shadow-sm';
        card.id = `row-${itemCount}`;
        card.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="item-number badge bg-primary rounded-pill">#${itemCount}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-row="${itemCount}">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>

                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">Tanggal</label>
                        <input type="date" class="form-control form-control-sm item-tanggal" name="items[${itemCount}][tanggal]"
                               value="${tanggal}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">Kategori</label>
                        <select class="form-select form-select-sm item-tipe" name="items[${itemCount}][tipe_penggunaan]" required>
                            <option value="Hotel" ${tipe === 'Hotel' ? 'selected' : ''}>Hotel</option>
                            <option value="BBM" ${tipe === 'BBM' ? 'selected' : ''}>BBM</option>
                            <option value="Parkir" ${tipe === 'Parkir' ? 'selected' : ''}>Parkir</option>
                            <option value="Tol" ${tipe === 'Tol' ? 'selected' : ''}>Tol</option>
                            <option value="Lain-lain" ${tipe === 'Lain-lain' ? 'selected' : ''}>Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">Nominal (Rp)</label>
                        <input type="number" class="form-control form-control-sm item-nominal" name="items[${itemCount}][nominal]"
                               value="${nominal}" min="0" required placeholder="0">
                    </div>
                    <div class="col-6 col-md-3 d-flex align-items-end">
                        <div class="item-total-display w-100 text-end small text-muted">
                            <span class="d-block">Subtotal</span>
                            <strong class="item-subtotal text-dark">Rp 0</strong>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-1 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small text-muted mb-1">Foto Struk <small>(kosongkan bila struk lama tetap dipakai)</small></label>
                        <input type="file" class="form-control form-control-sm item-struk" name="items[${itemCount}][struk]"
                               accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-md-4 d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary item-analyze" disabled>
                            <i class="bi bi-stars"></i> Analisis AI
                        </button>
                        <span class="item-ai-status small text-muted"></span>
                    </div>
                </div>
                <div class="mt-2 item-struk-preview" style="display:none;">
                    <img class="img-thumbnail" alt="Pratinjau struk" style="max-height:90px;">
                </div>

                {{-- Field AI/struk (hidden) --}}
                <input type="hidden" class="item-struk-kategori" name="items[${itemCount}][struk_kategori]" value="${strukKategori}">
                <input type="hidden" class="item-kategori-source" name="items[${itemCount}][kategori_source]" value="${kategoriSource}">
                <input type="hidden" class="item-metadata-json" name="items[${itemCount}][metadata_json]" value="${metadataJson}">

                <div class="mt-2">
                    <label class="form-label small text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][keterangan]"
                           value="${keterangan}" placeholder="Contoh: SPBU Pertamina, hotel menginap 1 malam, tukang parkir, dll">
                </div>
            </div>
        `;
        itemsContainer.appendChild(card);

        document.getElementById('itemsEmpty').style.display = 'none';

        card.querySelector('.item-nominal').addEventListener('input', calculateTotal);
        card.querySelector('.item-tipe').addEventListener('change', calculateTotal);
        card.querySelector('.item-tanggal').addEventListener('change', calculateTotal);
        card.querySelector('.item-struk').addEventListener('change', function() { handleStrukFile(this, card); });
        card.querySelector('.item-analyze').addEventListener('click', function() { analyzeStruk(card); });
        card.querySelector('.btn-remove-item').addEventListener('click', function() {
            card.remove();
            renumberRows();
            calculateTotal();
            if (itemsContainer.querySelectorAll('.item-card').length === 0) {
                document.getElementById('itemsEmpty').style.display = 'block';
            }
        });
    }

    // Handle pemilihan file struk: preview + aktifkan tombol AI
    function handleStrukFile(input, card) {
        const file = input.files[0];
        const preview = card.querySelector('.item-struk-preview');
        const btn = card.querySelector('.item-analyze');
        const status = card.querySelector('.item-ai-status');
        if (!file) {
            preview.style.display = 'none';
            preview.querySelector('img').src = '';
            btn.disabled = true;
            status.textContent = '';
            status.className = 'item-ai-status small text-muted';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => { preview.querySelector('img').src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
        btn.disabled = false;
        status.textContent = 'Klik AI utk isi otomatis';
        status.className = 'item-ai-status small text-primary';
    }

    // Kirim struk ke AI/OCR
    async function analyzeStruk(card) {
        const input = card.querySelector('.item-struk');
        const file = input.files[0];
        if (!file) return;
        const btn = card.querySelector('.item-analyze');
        const status = card.querySelector('.item-ai-status');
        btn.disabled = true;
        status.textContent = 'Menganalisis...';
        status.className = 'item-ai-status small text-primary';

        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd });

            if (res.status === 429) {
                status.textContent = '⚠ Terlalu banyak permintaan analisis (rate limit). Silakan isi data secara manual.';
                status.className = 'item-ai-status small text-warning';
                return;
            }

            const data = await res.json();
            if (data.success && data.status === 'ok') {
                const kategori = data.kategori;
                const metadata = data.metadata || {};
                const text = data.text || '';
                const tipeMap = { 'BBM': 'BBM', 'Tol': 'Tol', 'Parkir': 'Parkir', 'Lainnya': 'Lain-lain' };

                card.querySelector('.item-struk-kategori').value = kategori;
                card.querySelector('.item-kategori-source').value = 'ai';
                card.querySelector('.item-metadata-json').value = JSON.stringify({ kategori, metadata, text });

                const tipeSelect = card.querySelector('.item-tipe');
                if (tipeSelect.value === 'BBM' || tipeSelect.value === 'Hotel' || !tipeSelect.value) {
                    tipeSelect.value = tipeMap[kategori] || 'Lain-lain';
                }
                if (metadata.nominal && !(parseFloat(card.querySelector('.item-nominal').value) > 0)) {
                    card.querySelector('.item-nominal').value = metadata.nominal;
                }
                if (text && !card.querySelector('input[name*="[keterangan]"]').value.trim()) {
                    card.querySelector('input[name*="[keterangan]"]').value = text.substring(0, 200);
                }
                status.textContent = '✓ ' + kategori + (metadata.nominal ? ' • ' + formatRupiah(metadata.nominal) : '');
                status.className = 'item-ai-status small text-success';
                calculateTotal();
            } else if (data.success && data.status === 'api_limit') {
                card.querySelector('.item-kategori-source').value = 'manual';
                status.textContent = '⚠ Kuota AI (Gemini) habis sementara — silakan isi data secara manual.';
                status.className = 'item-ai-status small text-warning';
            } else if (data.success) {
                card.querySelector('.item-kategori-source').value = 'manual';
                status.textContent = '⚠ Analisis AI gagal membaca struk — silakan isi data secara manual.';
                status.className = 'item-ai-status small text-warning';
            } else {
                status.textContent = 'Gagal: ' + (data.message || 'analisis tidak tersedia');
                status.className = 'item-ai-status small text-danger';
            }
        } catch (e) {
            status.textContent = 'Error koneksi — coba analisis ulang, atau isi data secara manual.';
            status.className = 'item-ai-status small text-danger';
        } finally {
            btn.disabled = false;
        }
    }

    function renumberRows() {
        const cards = itemsContainer.querySelectorAll('.item-card');
        cards.forEach((card, index) => {
            const badge = card.querySelector('.item-number');
            if (badge) badge.textContent = '#' + (index + 1);
        });
    }

    function calculateTotal() {
        let total = 0;
        let hotel = 0, bbm = 0, tol = 0, Parkir = 0, lain = 0;
        const spendPerDate = {};

        itemsContainer.querySelectorAll('.item-card').forEach(card => {
            const nominalInput = card.querySelector('.item-nominal');
            const tipeSelect = card.querySelector('.item-tipe');
            const tanggalInput = card.querySelector('.item-tanggal');

            if (!nominalInput || !tipeSelect || !tanggalInput) return;

            const nominal = parseFloat(nominalInput.value) || 0;
            const tipe = tipeSelect.value;
            const tanggal = tanggalInput.value;

            const subtotalEl = card.querySelector('.item-subtotal');
            if (subtotalEl) subtotalEl.textContent = formatRupiah(nominal);

            total += nominal;

            switch(tipe) {
                case 'Hotel': hotel += nominal; break;
                case 'BBM': bbm += nominal; break;
                case 'Tol': tol += nominal; break;
                case 'Parkir': Parkir += nominal; break;
                case 'Lain-lain': lain += nominal; break;
            }

            if (tanggal) {
                spendPerDate[tanggal] = (spendPerDate[tanggal] || 0) + nominal;
            }
        });

        totalPengeluaranEl.textContent = formatRupiah(total);
        document.getElementById('summaryPengeluaran').textContent = formatRupiah(total);
        document.getElementById('summaryHotel').textContent = formatRupiah(hotel);
        document.getElementById('summaryBBM').textContent = formatRupiah(bbm);
        document.getElementById('summaryTol').textContent = formatRupiah(tol);
        document.getElementById('summaryParkir').textContent = formatRupiah(Parkir);
        document.getElementById('summaryLain').textContent = formatRupiah(lain);

        if (totalBudget > 0) {
            const selisih = totalBudget - total;
            document.getElementById('rowSelisih').style.display = 'table-row';
            document.getElementById('summarySelisih').textContent = (selisih >= 0 ? '' : '-') + formatRupiah(Math.abs(selisih));
            document.getElementById('summarySelisih').className = selisih >= 0 ? 'text-success' : 'text-danger';

            document.getElementById('alertOverBudget').style.display = selisih < 0 ? 'block' : 'none';
            if (selisih < 0) {
                document.getElementById('alertOverBudget').innerHTML =
                    '<i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Total pengeluaran melebihi total budget sebesar <strong>' +
                    formatRupiah(Math.abs(selisih)) + '</strong>';
            }
        }

        const overBudgetWarnings = [];
        let dateStatusHtml = '';
        const hasBudgetData = Object.keys(currentBudgetMap).length > 0;

        if (hasBudgetData) {
            const allDates = new Set([
                ...Object.keys(currentBudgetMap),
                ...Object.keys(spendPerDate)
            ]);

            const sortedDates = Array.from(allDates).sort();

            sortedDates.forEach(tanggal => {
                const budgetHari = currentBudgetMap[tanggal] || 0;
                const spent = spendPerDate[tanggal] || 0;
                const selisih = budgetHari - spent;
                const persen = budgetHari > 0 ? Math.round((spent / budgetHari) * 100) : (spent > 0 ? 999 : 0);

                let badgeClass, statusText;
                if (spent === 0) {
                    badgeClass = 'bg-light text-muted';
                    statusText = 'Belum ada';
                } else if (selisih >= 0) {
                    badgeClass = 'bg-success';
                    statusText = 'OK';
                } else {
                    badgeClass = 'bg-danger';
                    statusText = 'OVER ' + formatRupiah(Math.abs(selisih));
                    overBudgetWarnings.push({
                        tanggal: tanggal,
                        budget: budgetHari,
                        spent: spent,
                        over: Math.abs(selisih)
                    });
                }

                const progressWidth = Math.min(persen, 100);
                const progressColor = persen > 100 ? 'bg-danger' : (persen > 80 ? 'bg-warning' : 'bg-success');

                dateStatusHtml += `
                    <div class="mb-2 small">
                        <div class="d-flex justify-content-between">
                            <span>${formatTanggal(tanggal)}</span>
                            <span class="badge ${badgeClass}">${statusText}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                            <span>${formatRupiah(spent)} / ${formatRupiah(budgetHari)}</span>
                            <span>${persen}%</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar ${progressColor}" style="width: ${progressWidth}%"></div>
                        </div>
                    </div>
                `;
            });

            document.getElementById('summaryBudgetPerTanggal').style.display = 'block';
            document.getElementById('summaryDateStatus').innerHTML = dateStatusHtml;
        } else {
            document.getElementById('summaryBudgetPerTanggal').style.display = 'none';
        }

        const alertDetail = document.getElementById('alertOverBudgetDetail');
        const overBudgetList = document.getElementById('overBudgetList');

        if (overBudgetWarnings.length > 0) {
            let listHtml = '';
            overBudgetWarnings.forEach(w => {
                listHtml += `<li><strong>${formatTanggal(w.tanggal)}</strong>: ${formatRupiah(w.spent)} / ${formatRupiah(w.budget)} — over <strong>${formatRupiah(w.over)}</strong></li>`;
            });
            overBudgetList.innerHTML = listHtml;
            alertDetail.style.display = 'block';
        } else {
            alertDetail.style.display = 'none';
        }

        itemsContainer.querySelectorAll('.item-card').forEach(card => {
            const tanggalInput = card.querySelector('.item-tanggal');
            if (!tanggalInput) return;
            const tanggal = tanggalInput.value;
            const budgetHari = currentBudgetMap[tanggal] || 0;
            const spentHari = spendPerDate[tanggal] || 0;

            if (tanggal && budgetHari > 0 && spentHari > budgetHari) {
                card.classList.add('border-danger');
                card.classList.remove('border');
            } else {
                card.classList.remove('border-danger');
                card.classList.add('border');
            }
        });

        const diberikan = parseFloat(jumlahDiberikan.value) || 0;
        const selisihKeuangan = diberikan - total;

        if (selisihKeuangan >= 0) {
            document.getElementById('pengembalian').textContent = formatRupiah(selisihKeuangan);
            document.getElementById('kekurangan').textContent = formatRupiah(0);
        } else {
            document.getElementById('pengembalian').textContent = formatRupiah(0);
            document.getElementById('kekurangan').textContent = formatRupiah(Math.abs(selisihKeuangan));
        }
    }

    document.getElementById('btnAddItem').addEventListener('click', function() {
        addItemRow();
    });

    formLpj.addEventListener('submit', function(e) {
        const rows = itemsContainer.querySelectorAll('.item-card');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 item pengeluaran!');
            return false;
        }
        let hasZero = false;
        rows.forEach(card => {
            const nominal = parseFloat(card.querySelector('.item-nominal').value) || 0;
            if (nominal <= 0) hasZero = true;
        });
        if (hasZero) {
            if (!confirm('Ada item dengan nominal Rp 0. Lanjutkan submit?')) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Initialize: load budget from attached surat
    @if($surat)
        totalBudget = {{ $surat->total_budget ?? 0 }};
        document.getElementById('summaryBudget').textContent = formatRupiah(totalBudget);
        const suratBudgets = budgetsPerSurat[{{ $surat->id }}] || [];
        currentBudgetMap = {};
        suratBudgets.forEach(b => {
            currentBudgetMap[b.tanggal] = parseFloat(b.nominal);
        });
    @endif

    // Load existing items
    existingItems.forEach(item => {
        const struk = item.strukItem || null;
        addItemRow(
            item.tanggal,
            item.tipe_penggunaan,
            item.nominal,
            item.keterangan,
            struk ? struk.struk_kategori : '',
            struk ? struk.kategori_source : 'manual',
            struk && struk.metadata_json ? struk.metadata_json.replace(/"/g, '&quot;') : '',
            !!struk
        );
    });

    jumlahDiberikan.addEventListener('input', calculateTotal);
    calculateTotal();

    // Tempuh kendaraan: hitung jarak otomatis
    const odoBefore = document.getElementById('odoBefore');
    const odoAfter = document.getElementById('odoAfter');
    const jarakTempuh = document.getElementById('jarakTempuh');

    function hitungJarak() {
        const sebelum = parseFloat(odoBefore?.value) || 0;
        const sesudah = parseFloat(odoAfter?.value) || 0;
        if (jarakTempuh) {
            jarakTempuh.textContent = (sebelum > 0 && sesudah > 0)
                ? new Intl.NumberFormat('id-ID').format(sesudah - sebelum) + ' km'
                : '-';
        }
    }
    odoBefore?.addEventListener('input', hitungJarak);
    odoAfter?.addEventListener('input', hitungJarak);
    hitungJarak();
});
</script>

@endsection
