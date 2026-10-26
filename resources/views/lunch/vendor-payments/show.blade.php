@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Detail Invoice Vendor</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item"><a href="{{ route('lunch.vendor-payments.index') }}">Pengeluaran Vendor</a></li>
            <li class="breadcrumb-item active">Invoice #{{ $invoice->id }}</li>
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

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Invoice {{ $invoice->vendor?->name }}</strong>
                    <span class="badge bg-{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="35%">Site</th><td>{{ $invoice->site?->name }} ({{ $invoice->site?->code }})</td></tr>
                        <tr><th>Periode</th><td>{{ $invoice->period_start?->format('d/m/Y') }} – {{ $invoice->period_end?->format('d/m/Y') }}</td></tr>
                        <tr><th>Total porsi</th><td>{{ $invoice->total_portion }}</td></tr>
                        <tr><th>Harga / porsi</th><td>Rp {{ number_format((float) $invoice->price_per_portion, 0, ',', '.') }}</td></tr>
                        <tr><th>Hitung otomatis</th><td>Rp {{ number_format((float) $invoice->calculated_amount, 0, ',', '.') }}</td></tr>
                        <tr><th>Nominal final</th><td class="fw-bold">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td></tr>
                        <tr><th>Sudah dibayar</th><td class="text-success">Rp {{ number_format($invoice->paidAmount(), 0, ',', '.') }}</td></tr>
                        <tr><th>Sisa</th><td class="fw-bold {{ $invoice->remainingAmount() > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}
                        </td></tr>
                        <tr><th>Jatuh tempo</th><td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td></tr>
                        <tr><th>Catatan</th><td>{{ $invoice->notes ?? '—' }}</td></tr>
                    </table>

                    <hr>

                    <form method="POST" action="{{ route('lunch.vendor-payments.update', $invoice) }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small">Nominal final</label>
                            <input type="number" name="total_amount" value="{{ (float) $invoice->total_amount }}" class="form-control form-control-sm" min="0" step="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Jatuh tempo</label>
                            <input type="date" name="due_date" value="{{ $invoice->due_date?->toDateString() }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                @foreach(['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'cancelled' => 'Dibatalkan'] as $value => $label)
                                    <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2">{{ $invoice->notes }}</textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-sm btn-primary">Simpan Perubahan</button>
                            @if(in_array($invoice->status, ['draft', 'submitted'], true))
                                <button type="submit" form="approveForm" class="btn btn-sm btn-success">Setujui Invoice</button>
                            @endif
                        </div>
                    </form>
                    <form method="POST" action="{{ route('lunch.vendor-payments.approve', $invoice) }}" id="approveForm" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white"><strong>Riwayat Pembayaran</strong></div>
                <div class="card-body pt-2">
                    @forelse($invoice->payments as $payment)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                                <small class="text-muted">{{ $payment->paid_at?->format('d/m/Y') }}</small>
                            </div>
                            <small class="text-muted">
                                {{ $payment->methodLabel() }}
                                @if($payment->reference) · {{ $payment->reference }} @endif
                                @if($payment->creator) · oleh {{ $payment->creator->nama_lengkap }} @endif
                            </small>
                            @if($payment->proof_path)
                                <div><a href="{{ \Illuminate\Support\Facades\Storage::url($payment->proof_path) }}" target="_blank" class="small">Lihat bukti</a></div>
                            @endif
                            <form method="POST" action="{{ route('lunch.vendor-payments.payments.destroy', $payment) }}" class="mt-1"
                                  onsubmit="return confirm('Hapus pembayaran ini?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada pembayaran.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-white"><strong>Catat Pembayaran</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('lunch.vendor-payments.payments.store', $invoice) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Nominal</label>
                            <input type="number" name="amount" class="form-control form-control-sm" min="1" step="100"
                                   value="{{ (float) $invoice->remainingAmount() }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Tanggal bayar</label>
                            <input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Metode</label>
                            <select name="method" class="form-select form-select-sm">
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Tunai</option>
                                <option value="giro">Giro / Cek</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Referensi</label>
                            <input type="text" name="reference" class="form-control form-control-sm" maxlength="100">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Bukti (jpg/png/pdf)</label>
                            <input type="file" name="proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Catatan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <button class="btn btn-sm btn-success w-100">Simpan Pembayaran</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection
