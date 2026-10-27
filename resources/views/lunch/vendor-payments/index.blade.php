@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Data Pengeluaran & Pembayaran Vendor</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Vendor</li>
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

    {{-- ── Ringkasan ───────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['total_billed'], 0, ',', '.') }}</h4>
                <small class="text-muted">Total Tagihan</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</h4>
                <small class="text-muted">Sudah Dibayar</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold text-danger mb-0">Rp {{ number_format($summary['total_remaining'], 0, ',', '.') }}</h4>
                <small class="text-muted">Sisa Tagihan</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <h4 class="fw-bold {{ $summary['overdue'] > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ $summary['overdue'] }}</h4>
                <small class="text-muted">Invoice Jatuh Tempo</small>
            </div></div>
        </div>
    </div>

    {{-- ── Filter & aksi ───────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('lunch.vendor-payments.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" name="period_start" value="{{ $periodStart }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="period_end" value="{{ $periodEnd }}" class="form-control form-control-sm">
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
                        @foreach(['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'partial' => 'Dibayar Sebagian', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>

            <hr class="my-3">

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="bi bi-receipt me-1"></i>Generate Invoice
                </button>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vendorModal" id="btnAddVendor">
                    <i class="bi bi-shop me-1"></i>Tambah Vendor
                </button>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#vendorList">
                    <i class="bi bi-list-ul me-1"></i>Daftar Vendor ({{ $vendors->count() }})
                </button>
            </div>

            <div class="collapse mt-3" id="vendorList">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Vendor</th><th>Unit / Site</th><th>Harga / Porsi</th><th>Status</th><th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendors as $vendor)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $vendor->name }}
                                        @if($vendor->contact_person)<br><small class="text-muted">{{ $vendor->contact_person }} · {{ $vendor->phone }}</small>@endif
                                    </td>
                                    <td>
                                        <small>
                                            {{ $vendor->site?->name ?? 'Semua site' }}
                                            @if($vendor->unit)<br>{{ $vendor->unit->name }}@endif
                                        </small>
                                    </td>
                                    <td>
                                        @if($vendor->price_per_portion)
                                            Rp {{ number_format((float) $vendor->price_per_portion, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">belum diisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $vendor->is_active ? 'success' : 'secondary' }}">
                                            {{ $vendor->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary edit-vendor"
                                                data-bs-toggle="modal" data-bs-target="#vendorModal"
                                                data-id="{{ $vendor->id }}"
                                                data-name="{{ $vendor->name }}"
                                                data-unit="{{ $vendor->unit_id }}"
                                                data-site="{{ $vendor->site_id }}"
                                                data-contact="{{ $vendor->contact_person }}"
                                                data-phone="{{ $vendor->phone }}"
                                                data-price="{{ $vendor->price_per_portion }}"
                                                data-notes="{{ $vendor->notes }}"
                                                data-active="{{ $vendor->is_active ? 1 : 0 }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('lunch.vendor-payments.vendors.destroy', $vendor) }}" class="d-inline"
                                              onsubmit="return confirm('Hapus / nonaktifkan vendor {{ $vendor->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada vendor. Tambahkan vendor + harga per porsi dulu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Invoice ─────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header bg-white">
            <strong><i class="bi bi-receipt-cutoff me-1"></i>Invoice Vendor</strong>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Site</th>
                            <th>Periode</th>
                            <th class="text-center">Porsi</th>
                            <th class="text-end">Tagihan</th>
                            <th class="text-end">Dibayar</th>
                            <th class="text-end">Sisa</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->vendor?->name ?? '-' }}</td>
                                <td><small>{{ $invoice->site?->name ?? '-' }}</small></td>
                                <td><small>{{ $invoice->period_start?->format('d/m/Y') }} – {{ $invoice->period_end?->format('d/m/Y') }}</small></td>
                                <td class="text-center">{{ $invoice->total_portion }}</td>
                                <td class="text-end">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($invoice->paidAmount(), 0, ',', '.') }}</td>
                                <td class="text-end {{ $invoice->remainingAmount() > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                                    @if($invoice->isOverdue())
                                        <span class="badge bg-danger">Jatuh tempo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('lunch.vendor-payments.show', $invoice) }}" class="btn btn-sm btn-outline-primary" title="Detail & pembayaran">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(in_array($invoice->status, ['draft', 'submitted'], true))
                                        <form method="POST" action="{{ route('lunch.vendor-payments.approve', $invoice) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" title="Setujui"><i class="bi bi-check2"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                Belum ada invoice pada periode ini. Klik <strong>Generate Invoice</strong>.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $invoices->links('pagination::bootstrap-5') }}
        </div>
    </div>

</section>

{{-- ── Modal generate invoice ─────────────────────────────────── --}}
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.vendor-payments.generate') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Generate Invoice dari Distribusi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Invoice dihitung dari total porsi distribusi × harga per porsi vendor.
                    Nominal masih bisa dikoreksi manual setelah invoice dibuat.
                </p>
                <div class="mb-2">
                    <label class="form-label">Periode mulai</label>
                    <input type="date" name="period_start" value="{{ $periodStart }}" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Periode selesai</label>
                    <input type="date" name="period_end" value="{{ $periodEnd }}" class="form-control" required>
                </div>
                <div class="mb-1">
                    <label class="form-label">Site (opsional)</label>
                    <select name="site_id" class="form-select">
                        <option value="">Semua site di unit saya</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal vendor ──────────────────────────────────────────── --}}
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="vendorForm" action="{{ route('lunch.vendor-payments.vendors.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="vendorMethod" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorModalTitle">Tambah Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Nama vendor <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="vendorName" class="form-control" required maxlength="150">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Unit</label>
                        <select name="unit_id" id="vendorUnit" class="form-select">
                            <option value="">— Semua unit —</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Site</label>
                        <select name="site_id" id="vendorSite" class="form-select">
                            <option value="">— Semua site di unit —</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" data-unit="{{ $site->unit_id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <label class="form-label">Kontak</label>
                        <input type="text" name="contact_person" id="vendorContact" class="form-control" maxlength="150">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="phone" id="vendorPhone" class="form-control" maxlength="50">
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Harga per porsi</label>
                    <input type="number" name="price_per_portion" id="vendorPrice" class="form-control" min="0" step="100">
                </div>
                <div class="mb-2">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" id="vendorNotes" class="form-control" rows="2" maxlength="1000"></textarea>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" id="vendorActive" value="1" checked>
                    <label class="form-check-label" for="vendorActive">Aktif</label>
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
const vendorForm = document.getElementById('vendorForm');
const vendorModalTitle = document.getElementById('vendorModalTitle');
const vendorMethod = document.getElementById('vendorMethod');

document.getElementById('btnAddVendor').addEventListener('click', () => {
    vendorForm.action = '{{ route('lunch.vendor-payments.vendors.store') }}';
    vendorMethod.value = 'POST';
    vendorModalTitle.textContent = 'Tambah Vendor';
    vendorForm.reset();
    document.getElementById('vendorActive').checked = true;
});

document.querySelectorAll('.edit-vendor').forEach((btn) => {
    btn.addEventListener('click', () => {
        vendorForm.action = '{{ url('lunch/vendor-payments/vendors') }}/' + btn.dataset.id;
        vendorMethod.value = 'PUT';
        vendorModalTitle.textContent = 'Edit Vendor';
        document.getElementById('vendorName').value = btn.dataset.name || '';
        document.getElementById('vendorUnit').value = btn.dataset.unit || '';
        document.getElementById('vendorSite').value = btn.dataset.site || '';
        document.getElementById('vendorContact').value = btn.dataset.contact || '';
        document.getElementById('vendorPhone').value = btn.dataset.phone || '';
        document.getElementById('vendorPrice').value = btn.dataset.price || '';
        document.getElementById('vendorNotes').value = btn.dataset.notes || '';
        document.getElementById('vendorActive').checked = btn.dataset.active === '1';
    });
});

// Site mengikuti unit.
const vendorUnit = document.getElementById('vendorUnit');
const vendorSite = document.getElementById('vendorSite');
vendorUnit.addEventListener('change', () => {
    const unitId = vendorUnit.value;
    Array.from(vendorSite.options).forEach((opt) => {
        if (!opt.value) return;
        const match = !unitId || String(opt.dataset.unit) === String(unitId);
        opt.hidden = !match;
        opt.disabled = !match;
    });
    if (vendorSite.selectedOptions[0]?.disabled) vendorSite.value = '';
});
</script>
@endpush
