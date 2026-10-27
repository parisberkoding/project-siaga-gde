@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Custom Purchase</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Custom Purchase</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $summary['count'] }}</h4>
                <small class="text-muted">Custom Purchase Bulan Ini</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['total'], 0, ',', '.') }}</h4>
                <small class="text-muted">Total Biaya</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $summary['draft'] }}</h4>
                <small class="text-muted">Menunggu Persetujuan</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-dark mb-0">{{ $summary['replaces'] }}</h4>
                <small class="text-muted">Menggantikan Katering</small>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('lunch.custom-purchases.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Bulan</label>
                    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Site</label>
                    <select name="site_id" class="form-select form-select-sm">
                        <option value="">Semua site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected($siteFilter === $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        @foreach(['draft' => 'Draft', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <button type="button" class="btn btn-sm btn-primary" id="btnAddPurchase">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Custom Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong><i class="bi bi-bag-heart me-1"></i>Daftar Custom Purchase</strong></div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Site</th>
                            <th class="text-center">Item</th>
                            <th class="text-end">Total</th>
                            <th>Ganti Katering</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td><small>{{ $purchase->purchase_date?->format('d/m/Y') }}</small></td>
                                <td class="fw-semibold">
                                    {{ $purchase->title }}
                                    @if($purchase->vendor_name)<br><small class="text-muted">{{ $purchase->vendor_name }}</small>@endif
                                </td>
                                <td><small>{{ $purchase->site?->name }}</small></td>
                                <td class="text-center">{{ $purchase->items->count() }}</td>
                                <td class="text-end">Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    @if($purchase->replaces_catering)
                                        <span class="badge bg-warning text-dark">Ya</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $purchase->statusBadgeClass() }}">{{ $purchase->statusLabel() }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if($purchase->status === 'draft')
                                        <form method="POST" action="{{ route('lunch.custom-purchases.approve', $purchase) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" title="Setujui"><i class="bi bi-check2"></i></button>
                                        </form>
                                    @endif
                                    @if(in_array($purchase->status, ['draft', 'approved'], true))
                                        <form method="POST" action="{{ route('lunch.custom-purchases.complete', $purchase) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" title="Selesai"><i class="bi bi-flag"></i></button>
                                        </form>
                                    @endif
                                    @if($purchase->status !== 'cancelled')
                                        <form method="POST" action="{{ route('lunch.custom-purchases.cancel', $purchase) }}" class="d-inline"
                                              onsubmit="return confirm('Batalkan custom purchase ini? Katering akan dihitung normal lagi.')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning" title="Batalkan"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    @endif
                                    <button class="btn btn-sm btn-outline-primary edit-purchase"
                                            data-bs-toggle="modal" data-bs-target="#purchaseModal"
                                            data-id="{{ $purchase->id }}"
                                            data-date="{{ $purchase->purchase_date?->toDateString() }}"
                                            data-title="{{ $purchase->title }}"
                                            data-site="{{ $purchase->site_id }}"
                                            data-vendor="{{ $purchase->vendor_name }}"
                                            data-description="{{ $purchase->description }}"
                                            data-status="{{ $purchase->status }}"
                                            data-replaces="{{ $purchase->replaces_catering ? 1 : 0 }}"
                                            data-items="{{ json_encode($purchase->items->map(fn ($i) => ['item_name' => $i->item_name, 'qty' => (float) $i->qty, 'unit' => $i->unit, 'unit_price' => (float) $i->unit_price])) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('lunch.custom-purchases.destroy', $purchase) }}" class="d-inline"
                                          onsubmit="return confirm('Hapus custom purchase ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">
                                Belum ada custom purchase pada bulan ini.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $purchases->links('pagination::bootstrap-5') }}
        </div>
    </div>

</section>

{{-- ── Modal custom purchase ─────────────────────────────────── --}}
<div class="modal fade" id="purchaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" id="purchaseForm" action="{{ route('lunch.custom-purchases.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="purchaseMethod" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalTitle">Tambah Custom Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" id="purchaseDate" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Site <span class="text-danger">*</span></label>
                        <select name="site_id" id="purchaseSite" class="form-select" required>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->unit?->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" id="purchaseStatus" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="approved">Disetujui</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="mb-2 mt-2">
                    <label class="form-label">Judul <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="purchaseTitle" class="form-control" required maxlength="150"
                           placeholder="Contoh: Perayaan HUT / Apresiasi Pegawai">
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Vendor / Supplier</label>
                        <input type="text" name="vendor_name" id="purchaseVendor" class="form-control" maxlength="150">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="replaces_catering" value="0">
                            <input class="form-check-input" type="checkbox" name="replaces_catering" id="purchaseReplaces" value="1" checked>
                            <label class="form-check-label" for="purchaseReplaces">
                                Gantikan katering reguler pada tanggal + site ini
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-2 mt-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="purchaseDescription" class="form-control" rows="2" maxlength="2000"></textarea>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small">Rincian Item</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Nama Item</th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 90px;">Satuan</th>
                                <th style="width: 140px;">Harga</th>
                                <th style="width: 140px;" class="text-end">Subtotal</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total</th>
                                <th class="text-end" id="itemsTotal">Rp 0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Jika "Gantikan katering" dicentang, porsi vendor pada tanggal + site ini otomatis menjadi 0
                    sehingga tidak terjadi tagihan ganda.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const purchaseForm = document.getElementById('purchaseForm');
const purchaseMethod = document.getElementById('purchaseMethod');
const purchaseModalTitle = document.getElementById('purchaseModalTitle');
const itemsBody = document.querySelector('#itemsTable tbody');
const itemsTotal = document.getElementById('itemsTotal');

function addItemRow(item = {}) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[][item_name]" class="form-control form-control-sm" value="${escapeAttr(item.item_name)}" maxlength="150"></td>
        <td><input type="number" name="items[][qty]" class="form-control form-control-sm qty" value="${item.qty ?? 1}" min="0" step="0.01"></td>
        <td><input type="text" name="items[][unit]" class="form-control form-control-sm" value="${escapeAttr(item.unit)}" maxlength="30" placeholder="porsi"></td>
        <td><input type="number" name="items[][unit_price]" class="form-control form-control-sm price" value="${item.unit_price ?? 0}" min="0" step="100"></td>
        <td class="text-end subtotal">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x"></i></button></td>`;
    itemsBody.appendChild(tr);
    bindRow(tr);
    recalcTotal();
}

function bindRow(tr) {
    tr.querySelectorAll('.qty, .price').forEach((input) => {
        input.addEventListener('input', () => { recalcRow(tr); recalcTotal(); });
    });
    tr.querySelector('.remove-item').addEventListener('click', () => {
        tr.remove();
        recalcTotal();
    });
    recalcRow(tr);
}

function recalcRow(tr) {
    const qty = parseFloat(tr.querySelector('.qty').value || 0);
    const price = parseFloat(tr.querySelector('.price').value || 0);
    const subtotal = qty * price;
    tr.querySelector('.subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
}

function recalcTotal() {
    let total = 0;
    itemsBody.querySelectorAll('tr').forEach((tr) => {
        total += parseFloat(tr.querySelector('.qty').value || 0) * parseFloat(tr.querySelector('.price').value || 0);
    });
    itemsTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function escapeAttr(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.getElementById('addItem').addEventListener('click', () => addItemRow());

document.getElementById('btnAddPurchase').addEventListener('click', () => {
    purchaseForm.action = '{{ route('lunch.custom-purchases.store') }}';
    purchaseMethod.value = 'POST';
    purchaseModalTitle.textContent = 'Tambah Custom Purchase';
    purchaseForm.reset();
    itemsBody.innerHTML = '';
    addItemRow();
    document.getElementById('purchaseReplaces').checked = true;
});

document.querySelectorAll('.edit-purchase').forEach((btn) => {
    btn.addEventListener('click', () => {
        purchaseForm.action = '{{ url('lunch/custom-purchases') }}/' + btn.dataset.id;
        purchaseMethod.value = 'PUT';
        purchaseModalTitle.textContent = 'Edit Custom Purchase';
        document.getElementById('purchaseDate').value = btn.dataset.date || '';
        document.getElementById('purchaseSite').value = btn.dataset.site || '';
        document.getElementById('purchaseTitle').value = btn.dataset.title || '';
        document.getElementById('purchaseVendor').value = btn.dataset.vendor || '';
        document.getElementById('purchaseDescription').value = btn.dataset.description || '';
        document.getElementById('purchaseStatus').value = btn.dataset.status || 'draft';
        document.getElementById('purchaseReplaces').checked = btn.dataset.replaces === '1';

        itemsBody.innerHTML = '';
        let items = [];
        try { items = JSON.parse(btn.dataset.items || '[]'); } catch (e) { items = []; }
        if (!items.length) items = [{}];
        items.forEach((item) => addItemRow(item));
    });
});

// Baris awal untuk modal tambah.
addItemRow();
</script>
@endpush
