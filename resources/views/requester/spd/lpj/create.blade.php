@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Buat Laporan Pertanggungjawaban</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item">SPD</li>
            <li class="breadcrumb-item"><a href="{{ route('requester.lpj.index') }}">Laporan LPJ</a></li>
            <li class="breadcrumb-item active">Buat Laporan</li>
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

    @if($suratList->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 mb-3">Tidak ada surat tugas yang perlu dilaporkan</p>
                <p class="text-muted small">Anda hanya bisa membuat LPJ untuk surat yang sudah disetujui dan belum ada laporannya.</p>
                <a href="" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    @else
        <form action="{{ route('requester.lpj.store') }}" method="POST" id="formLpj">
            @csrf
            <div class="row">
                {{-- Form Utama --}}
                <div class="col-lg-8">
                    {{-- Pilih Surat --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Pilih Surat Tugas</h5>

                            <div class="mb-3">
                                <label class="form-label">Surat Tugas yang Dilaporkan <span class="text-danger">*</span></label>
                                <select class="form-select @error('surat_spd_id') is-invalid @enderror"
                                        name="surat_spd_id" required id="selectSurat">
                                    <option value="">-- Pilih Surat Tugas --</option>
                                    @foreach($suratForDropdown as $item)
                                        <option value="{{ $item['id'] }}"
                                                data-tujuan="{{ $suratList->where('id', $item['id'])->first()->tempat_tujuan ?? '' }}"
                                                data-berangkat="{{ $suratList->where('id', $item['id'])->first()->tanggal_berangkat->format('Y-m-d') ?? '' }}"
                                                data-kembali="{{ $suratList->where('id', $item['id'])->first()->tanggal_kembali->format('Y-m-d') ?? '' }}"
                                                data-budget="{{ $suratList->where('id', $item['id'])->first()->budget_per_hari ?? 0 }}"
                                                data-total-budget="{{ $suratList->where('id', $item['id'])->first()->total_budget ?? 0 }}"
                                                data-lama="{{ $suratList->where('id', $item['id'])->first()->lama_perjalanan_hari ?? 0 }}"
                                                data-vehicle="{{ $suratList->where('id', $item['id'])->first()->vehicle->consumerial_code ?? '' }}"
                                                {{ (old('surat_spd_id') == $item['id'] || request('surat_id') == $item['id']) ? 'selected' : '' }}>
                                            {{ $item['label'] }} - {{ $item['sublabel'] }}
                                            @if($item['is_overdue'])
                                                (TERLAMBAT!)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('surat_spd_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Info Surat Terpilih --}}
                            <div id="infoSurat" class="alert alert-info" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Tujuan:</small><br>
                                        <strong id="infoTujuan">-</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Kendaraan:</small><br>
                                        <strong id="infoVehicle">-</strong>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Jadwal:</small><br>
                                        <span id="infoJadwal">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Budget/Hari:</small><br>
                                        <span id="infoBudget">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Total Budget:</small><br>
                                        <strong id="infoTotalBudget" class="text-success">-</strong>
                                    </div>
                                </div>
                                {{-- Budget per Tanggal --}}
                                {{-- <div id="budgetPerTanggal"></div> --}}
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
                                           name="nama" value="{{ old('nama', Auth::user()->nama_lengkap ?? Auth::user()->name) }}" required>
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('jabatan') is-invalid @enderror"
                                           name="jabatan" value="{{ old('jabatan', 'Pengemudi') }}" required>
                                    @error('jabatan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Uraian Perjalanan <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('uraian') is-invalid @enderror"
                                          name="uraian" rows="3" required
                                          placeholder="Jelaskan secara singkat kegiatan perjalanan dinas...">{{ old('uraian') }}</textarea>
                                @error('uraian')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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

                            {{-- Card list item pengeluaran (mobile friendly) --}}
                            <div id="itemsContainer" class="d-flex flex-column gap-3">
                                {{-- Dynamic cards will be added here --}}
                            </div>

                            {{-- Empty state --}}
                            <div id="itemsEmpty" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                                <p class="mb-0 mt-2 small">Belum ada item pengeluaran. Klik "Tambah Item" untuk mulai.</p>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center px-1">
                                <span class="fw-bold">Total Pengeluaran:</span>
                                <span id="totalPengeluaran" class="fw-bold text-primary fs-5">Rp 0</span>
                            </div>

                            {{-- Over Budget Alert per Tanggal --}}
                            <div class="alert alert-warning py-2 mt-3" id="alertOverBudget" style="display: none;">
                                <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Total pengeluaran melebihi budget!
                            </div>

                            {{-- Detail Over Budget per Tanggal --}}
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
                                           value="{{ old('uang_dari', 'KAS') }}" placeholder="Contoh: KAS, Bon Sementara">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Diberikan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="jumlah_diberikan"
                                               id="jumlahDiberikan" value="{{ old('jumlah_diberikan', 0) }}" min="0">
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
                                      placeholder="Catatan tambahan jika ada...">{{ old('catatan') }}</textarea>
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

                            {{-- Budget per Tanggal Summary --}}
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

                            <button type="submit" class="btn btn-success w-100 mb-2" id="btnSubmit">
                                <i class="bi bi-send"></i> Kirim Laporan
                            </button>

                            <a href="{{ route('requester.lpj.index') }}" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-left"></i> Batal
                            </a>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-info-circle"></i> Informasi</h6>
                            <ul class="small text-muted mb-0">
                                <li>Pastikan semua pengeluaran sudah dicatat</li>
                                <li>Pengeluaran akan dicocokkan dengan budget per tanggal</li>
                                <li>Item yang melebihi budget harian akan ditandai</li>
                                <li>Laporan akan divalidasi oleh Superadmin</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</section>

{{-- Pass budget data ke JS --}}
<script>
    const budgetsPerSurat = @json($budgetsPerSurat ?? []);
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectSurat = document.getElementById('selectSurat');
    const itemsContainer = document.getElementById('itemsContainer');
    const totalPengeluaranEl = document.getElementById('totalPengeluaran');
    const jumlahDiberikan = document.getElementById('jumlahDiberikan');
    const infoSurat = document.getElementById('infoSurat');

    let itemCount = 0;
    let totalBudget = 0;
    let currentBudgetMap = {}; // { '2026-02-09': 1400000, ... }

    // Format rupiah
    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    }

    // Format tanggal untuk display
    function formatTanggal(dateStr) {
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        const d = new Date(dateStr);
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    // Add new item card
    function addItemRow(tanggal = '', tipe = 'BBM', nominal = '', keterangan = '') {
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

                <div class="mt-2">
                    <label class="form-label small text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][keterangan]"
                           value="${keterangan}" placeholder="Contoh: SPBU Pertamina, hotel menginap 1 malam, tukang parkir, dll">
                </div>
            </div>
        `;
        itemsContainer.appendChild(card);

        document.getElementById('itemsEmpty').style.display = 'none';

        // Event listeners
        card.querySelector('.item-nominal').addEventListener('input', calculateTotal);
        card.querySelector('.item-tipe').addEventListener('change', calculateTotal);
        card.querySelector('.item-tanggal').addEventListener('change', calculateTotal);
        card.querySelector('.btn-remove-item').addEventListener('click', function() {
            card.remove();
            renumberRows();
            calculateTotal();
            if (itemsContainer.querySelectorAll('.item-card').length === 0) {
                document.getElementById('itemsEmpty').style.display = 'block';
            }
        });
    }

    // Renumber cards after deletion
    function renumberRows() {
        const cards = itemsContainer.querySelectorAll('.item-card');
        cards.forEach((card, index) => {
            const badge = card.querySelector('.item-number');
            if (badge) badge.textContent = '#' + (index + 1);
        });
    }

    // Calculate total + check over budget per tanggal
    function calculateTotal() {
        let total = 0;
        let hotel = 0, bbm = 0, tol = 0, Parkir = 0, lain = 0;

        // Group pengeluaran per tanggal
        const spendPerDate = {};

        itemsContainer.querySelectorAll('.item-card').forEach(card => {
            const nominalInput = card.querySelector('.item-nominal');
            const tipeSelect = card.querySelector('.item-tipe');
            const tanggalInput = card.querySelector('.item-tanggal');

            if (!nominalInput || !tipeSelect || !tanggalInput) return;

            const nominal = parseFloat(nominalInput.value) || 0;
            const tipe = tipeSelect.value;
            const tanggal = tanggalInput.value;

            // Update subtotal display pada card
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

            // Group by tanggal
            if (tanggal) {
                spendPerDate[tanggal] = (spendPerDate[tanggal] || 0) + nominal;
            }
        });

        // Update totals
        totalPengeluaranEl.textContent = formatRupiah(total);
        document.getElementById('summaryPengeluaran').textContent = formatRupiah(total);
        document.getElementById('summaryHotel').textContent = formatRupiah(hotel);
        document.getElementById('summaryBBM').textContent = formatRupiah(bbm);
        document.getElementById('summaryTol').textContent = formatRupiah(tol);
        document.getElementById('summaryParkir').textContent = formatRupiah(Parkir);
        document.getElementById('summaryLain').textContent = formatRupiah(lain);

        // Check total over budget
        if (totalBudget > 0) {
            const selisih = totalBudget - total;
            document.getElementById('rowSelisih').style.display = 'table-row';
            document.getElementById('summarySelisih').textContent = formatRupiah(Math.abs(selisih));
            document.getElementById('summarySelisih').className = selisih >= 0 ? 'text-success' : 'text-danger';
            document.getElementById('summarySelisih').textContent = (selisih >= 0 ? '' : '-') + formatRupiah(Math.abs(selisih));

            document.getElementById('alertOverBudget').style.display = selisih < 0 ? 'block' : 'none';
            if (selisih < 0) {
                document.getElementById('alertOverBudget').innerHTML =
                    '<i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Total pengeluaran melebihi total budget sebesar <strong>' +
                    formatRupiah(Math.abs(selisih)) + '</strong>';
            }
        }

        // ✅ Check over budget PER TANGGAL
        const overBudgetWarnings = [];
        let dateStatusHtml = '';
        const hasBudgetData = Object.keys(currentBudgetMap).length > 0;

        if (hasBudgetData) {
            // Kumpulkan semua tanggal (dari budget + dari pengeluaran)
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

                // Progress bar width (cap at 100% visual)
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

            // Show summary per tanggal di sidebar
            document.getElementById('summaryBudgetPerTanggal').style.display = 'block';
            document.getElementById('summaryDateStatus').innerHTML = dateStatusHtml;
        } else {
            document.getElementById('summaryBudgetPerTanggal').style.display = 'none';
        }

        // Show/hide over budget detail alert
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

        // Highlight cards yang over budget
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

        // Calculate pengembalian/kekurangan
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

    // Update surat info when selection changes
    function updateSuratInfo() {
        const selected = selectSurat.options[selectSurat.selectedIndex];

        if (!selected.value) {
            infoSurat.style.display = 'none';
            totalBudget = 0;
            currentBudgetMap = {};
            document.getElementById('summaryBudget').textContent = '-';
            document.getElementById('rowSelisih').style.display = 'none';
            document.getElementById('summaryBudgetPerTanggal').style.display = 'none';
            document.getElementById('alertOverBudget').style.display = 'none';
            document.getElementById('alertOverBudgetDetail').style.display = 'none';
            return;
        }

        // Update basic info
        document.getElementById('infoTujuan').textContent = selected.dataset.tujuan;
        document.getElementById('infoVehicle').textContent = selected.dataset.vehicle;
        document.getElementById('infoJadwal').textContent =
            formatTanggal(selected.dataset.berangkat) + ' s/d ' + formatTanggal(selected.dataset.kembali);
        document.getElementById('infoBudget').textContent = formatRupiah(selected.dataset.budget) + '/hari';
        document.getElementById('infoTotalBudget').textContent = formatRupiah(selected.dataset.totalBudget);

        totalBudget = parseFloat(selected.dataset.totalBudget) || 0;
        document.getElementById('summaryBudget').textContent = formatRupiah(totalBudget);

        // ✅ Load budget per tanggal
        const suratId = selected.value;
        const budgets = budgetsPerSurat[suratId] || [];

        // Build budget map
        currentBudgetMap = {};
        budgets.forEach(b => {
            currentBudgetMap[b.tanggal] = parseFloat(b.nominal);
        });

        // Render budget per tanggal table
        let budgetHtml = '';
        if (budgets.length > 0) {
            budgetHtml += '<hr class="my-2">';
            budgetHtml += '<small class="text-muted fw-bold"><i class="bi bi-calendar-check"></i> Budget per Tanggal:</small>';
            budgetHtml += '<table class="table table-sm table-bordered mt-1 mb-0">';
            budgetHtml += '<thead class="table-light"><tr><th>Tanggal</th><th class="text-end">Budget</th><th>Catatan</th></tr></thead><tbody>';

            let totalFromBudgets = 0;
            budgets.forEach(b => {
                totalFromBudgets += parseFloat(b.nominal);
                budgetHtml += `<tr>
                    <td>${formatTanggal(b.tanggal)}</td>
                    <td class="text-end">${formatRupiah(b.nominal)}</td>
                    <td><small class="text-muted">${b.catatan || '-'}</small></td>
                </tr>`;
            });

            budgetHtml += `<tr class="table-light fw-bold">
                <td>Total</td>
                <td class="text-end">${formatRupiah(totalFromBudgets)}</td>
                <td></td>
            </tr>`;
            budgetHtml += '</tbody></table>';
        }

        document.getElementById('budgetPerTanggal').innerHTML = budgetHtml;
        infoSurat.style.display = 'block';

        calculateTotal();
    }

    // Event listeners
    selectSurat.addEventListener('change', updateSuratInfo);
    jumlahDiberikan.addEventListener('input', calculateTotal);

    document.getElementById('btnAddItem').addEventListener('click', function() {
        addItemRow();
    });

    // Form submit validation
    document.getElementById('formLpj').addEventListener('submit', function(e) {
        const rows = itemsContainer.querySelectorAll('.item-card');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 item pengeluaran!');
            return false;
        }

        // Check if any nominal is 0
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

    // Initialize
    addItemRow(); // Add first row
    updateSuratInfo(); // Load info if pre-selected
});
</script>

@endsection
