@extends('templates.main')

@section('title', 'Riwayat Transaksi Service')

@section('content')

<style>
    .trx-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all 0.15s;
    }
    .trx-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); color: inherit; }
    .trx-card.overdue { border-left: 4px solid #dc3545; }
    .trx-card.completed { border-left: 4px solid #198754; }
    .trx-card.in-progress { border-left: 4px solid #ffc107; }

    .trx-amount {
        font-size: 16px;
        font-weight: 700;
        color: #012970;
    }

    .proof-thumb {
        width: 50px; height: 50px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #dee2e6;
        cursor: pointer;
    }

    /* Lightbox */
    .lightbox-overlay {
        display: none;
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        align-items: center; justify-content: center;
        cursor: pointer;
    }
    .lightbox-overlay.active { display: flex; }
    .lightbox-overlay img {
        max-width: 92vw; max-height: 88vh;
        border-radius: 8px;
    }
</style>

<div class="pagetitle">
    <h1>Riwayat Transaksi Service</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Home</a></li>
            <li class="breadcrumb-item active">Transaksi Service</li>
        </ol>
    </nav>
</div>

{{-- Alert --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Summary --}}
<section class="section dashboard">
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Transaksi</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $transactions->total() }}</h6>
                            <span class="text-muted small pt-2">Semua transaksi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Total Pengeluaran</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 14px;">Rp {{ number_format($totalSpent ?? 0, 0, ',', '.') }}</h6>
                            <span class="text-muted small pt-2">Total biaya service</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section dashboard">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">

                    <h5 class="card-title">Daftar Transaksi</h5>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('requester.service-transaction.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Dari</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Sampai</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('requester.service-transaction.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Transaction Cards --}}
                    @forelse($transactions as $trx)
                    @php
                        $cardClass = 'in-progress';
                        if ($trx->replacement?->status === 'completed') $cardClass = 'completed';
                        elseif ($trx->is_overdue) $cardClass = 'overdue';
                    @endphp
                    <div class="trx-card {{ $cardClass }}">
                        <div class="row align-items-center g-2">
                            {{-- Photo Thumb --}}
                            <div class="col-auto">
                                @if($trx->proof_photo)
                                    <img src="{{ asset('storage/' . $trx->proof_photo) }}"
                                         alt="Bukti" class="proof-thumb"
                                         onclick="openLightbox('{{ asset('storage/' . $trx->proof_photo) }}')">
                                @else
                                    <div class="proof-thumb d-flex align-items-center justify-content-center" style="background: #f8f9fa;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="col">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong style="font-size: 13px;">{{ $trx->vprt_code }}</strong>
                                    @php
                                        $statusBadge = match($trx->replacement?->status) {
                                            'completed' => 'bg-success',
                                            'in_service' => 'bg-warning text-dark',
                                            'scheduled' => 'bg-info',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}" style="font-size: 10px;">
                                        {{ ucfirst(str_replace('_', ' ', $trx->replacement?->status ?? '-')) }}
                                    </span>
                                    @if($trx->is_overdue && $trx->replacement?->status !== 'completed')
                                        <span class="badge bg-danger" style="font-size: 10px;">Overdue</span>
                                    @endif
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-1">
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-truck me-1"></i>{{ $trx->replacement?->nopol ?? '-' }}
                                    </span>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($trx->service_location ?? '-', 25) }}
                                    </small>
                                </div>

                                {{-- Items --}}
                                @if($trx->details && $trx->details->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($trx->details->take(3) as $det)
                                    <span class="badge bg-light text-muted" style="font-size: 10px;">{{ $det->item_name }}</span>
                                    @endforeach
                                    @if($trx->details->count() > 3)
                                    <span class="badge bg-light text-muted" style="font-size: 10px;">
                                        +{{ $trx->details->count() - 3 }} lagi
                                    </span>
                                    @endif
                                </div>
                                @endif
                            </div>

                            {{-- Right: Amount + Date --}}
                            <div class="col-auto text-end">
                                <div class="trx-amount">
                                    Rp {{ number_format($trx->total_amount ?? 0, 0, ',', '.') }}
                                </div>
                                <small class="text-muted">
                                    {{ $trx->service_date?->format('d/m/Y') }}
                                </small>
                            </div>
                        </div>

                        {{-- Expandable Detail (toggle) --}}
                        @if($trx->details && $trx->details->count() > 0)
                        <div class="mt-2 pt-2 border-top" style="display: none;" id="detail-{{ $trx->id }}">
                            <table class="table table-sm mb-0" style="font-size: 12px;">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trx->details as $det)
                                    <tr>
                                        <td>{{ $det->item_name }}</td>
                                        <td class="text-center">{{ $det->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($det->unit_price ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($det->total_price ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-sm btn-link text-muted p-0 mt-1" style="font-size: 11px;"
                                onclick="toggleDetail({{ $trx->id }}, this)">
                            <i class="bi bi-chevron-down me-1"></i> Lihat rincian
                        </button>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="bi bi-receipt text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Belum ada transaksi service</p>
                        <a href="{{ route('requester.replacement-schedule.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="bi bi-calendar-check me-1"></i> Lihat Jadwal Service
                        </a>
                    </div>
                    @endforelse

                    @if($transactions->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $transactions->links() }}
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

{{-- Lightbox --}}
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="Bukti">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

function toggleDetail(id, btn) {
    const el = document.getElementById('detail-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i> Tutup rincian';
    } else {
        el.style.display = 'none';
        btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i> Lihat rincian';
    }
}
</script>

@endsection
