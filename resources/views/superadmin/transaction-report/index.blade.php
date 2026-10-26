@extends('templates.main')

@section('title', 'Laporan Transaksi Sparepart')

@section('content')

<style>
    .trx-detail-card {
        border: 1px solid #E4E9F0;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .trx-detail-card.overdue  { border-left: 4px solid #ED1B2F; }
    .trx-detail-card.completed { border-left: 4px solid #71A62F; }
    .trx-detail-card.active   { border-left: 4px solid #D8CE3A; }

    .trx-header {
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        cursor: pointer;
    }
    .trx-header:hover { background: #fafbfc; }

    .trx-expand {
        padding: 0 16px 14px;
        border-top: 1px dashed #E4E9F0;
        display: none;
    }
    .trx-expand.show { display: block; }

    .proof-thumb-sm {
        width: 60px; height: 60px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #E4E9F0;
        cursor: pointer;
    }

    .items-table th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #64748B;
        border-bottom: 1px solid #E4E9F0;
        padding: 6px 8px;
    }
    .items-table td {
        padding: 6px 8px;
        font-size: 13px;
    }

    .stat-mini {
        background: #F8FAFC;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }
    .stat-mini .stat-val {
        font-size: 20px;
        font-weight: 800;
        line-height: 1;
    }
    .stat-mini .stat-lbl {
        font-size: 11px;
        color: #64748B;
        margin-top: 4px;
    }

    .timeline-flow {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        flex-wrap: wrap;
    }
    .timeline-flow .step {
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
    }
    .timeline-flow .step.done { background: #d1e7dd; color: #4A7A1C; }
    .timeline-flow .step.current { background: #cff4fc; color: #0B50A1; }
    .timeline-flow .step.pending { background: #E4E9F0; color: #64748B; }
    .timeline-flow .arrow { color: #E4E9F0; }

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
    <h1>Laporan Transaksi Sparepart</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Transaksi Sparepart</li>
        </ol>
    </nav>
</div>

{{-- Summary Stats --}}
<div class="row mb-3">
    <div class="col-lg-2 col-4">
        <div class="stat-mini">
            <div class="stat-val" style="color: #0B50A1;">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-lbl">Total Transaksi</div>
        </div>
    </div>
    <div class="col-lg-2 col-4">
        <div class="stat-mini">
            <div class="stat-val" style="color: #4A7A1C;">{{ $stats['completed'] ?? 0 }}</div>
            <div class="stat-lbl">Selesai</div>
        </div>
    </div>
    <div class="col-lg-2 col-4">
        <div class="stat-mini">
            <div class="stat-val" style="color: #C4142A;">{{ $stats['overdue'] ?? 0 }}</div>
            <div class="stat-lbl">Overdue</div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="stat-mini">
            <div class="stat-val" style="color: #0B50A1; font-size: 16px;">
                Rp {{ number_format($stats['total_cost'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="stat-lbl">Total Biaya</div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="stat-mini">
            <div class="stat-val" style="color: #64748B; font-size: 16px;">
                Rp {{ number_format($stats['avg_cost'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="stat-lbl">Rata-rata / Transaksi</div>
        </div>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">

                    {{-- Filter Bar --}}
                    <form method="GET" action="{{ route('superadmin.transaction-report.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->consumerial_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Driver</label>
                                <select name="driver_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($drivers as $d)
                                        <option value="{{ $d->id }}" {{ request('driver_id') == $d->id ? 'selected' : '' }}>
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="in_service" {{ request('status') == 'in_service' ? 'selected' : '' }}>Dalam Service</option>
                                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Dari</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Sampai</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('superadmin.transaction-report.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <h5 class="card-title">
                        Detail Transaksi
                        <span class="badge bg-secondary rounded-pill">{{ $transactions->total() }}</span>
                    </h5>

                    {{-- Transaction Cards (expandable) --}}
                    @forelse($transactions as $trx)
                    @php
                        $vprStatus = $trx->replacement?->status ?? 'unknown';
                        $cardClass = 'active';
                        if ($vprStatus === 'completed') $cardClass = 'completed';
                        elseif ($trx->is_overdue) $cardClass = 'overdue';

                        // Status flow
                        $allStatuses = ['pending','approved','scheduled','in_service','completed'];
                        $currentIdx = array_search($vprStatus, $allStatuses);
                    @endphp
                    <div class="trx-detail-card {{ $cardClass }}">

                        {{-- Header (clickable) --}}
                        <div class="trx-header" onclick="toggleExpand({{ $trx->id }})">
                            <div class="d-flex align-items-start gap-3">
                                {{-- Photo --}}
                                @if($trx->proof_photo)
                                <img src="{{ asset('storage/' . $trx->proof_photo) }}"
                                     class="proof-thumb-sm"
                                     onclick="event.stopPropagation(); openLightbox('{{ asset('storage/' . $trx->proof_photo) }}')"
                                     alt="Bukti">
                                @else
                                <div class="proof-thumb-sm d-flex align-items-center justify-content-center" style="background: #F8FAFC;">
                                    <i class="bi bi-image text-muted fs-5"></i>
                                </div>
                                @endif

                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong>{{ $trx->vprt_code }}</strong>
                                        @php
                                            $stBadge = match($vprStatus) {
                                                'completed' => 'bg-success',
                                                'in_service' => 'bg-warning text-dark',
                                                'scheduled' => 'bg-info',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $stBadge }}" style="font-size: 10px;">
                                            {{ ucfirst(str_replace('_', ' ', $vprStatus)) }}
                                        </span>
                                        @if($trx->is_overdue && $vprStatus !== 'completed')
                                            <span class="badge bg-danger" style="font-size: 10px;">Overdue</span>
                                        @endif
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-truck me-1"></i>{{ $trx->replacement->nopol ?? '-' }}
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>{{ $trx->driver->name ?? '-' }}
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($trx->service_location, 25) }}
                                        </small>
                                    </div>

                                    {{-- Status Flow --}}
                                    <div class="timeline-flow">
                                        @foreach($allStatuses as $sIdx => $s)
                                        @php
                                            $stepClass = 'pending';
                                            if ($sIdx < $currentIdx) $stepClass = 'done';
                                            elseif ($sIdx == $currentIdx) $stepClass = 'current';
                                        @endphp
                                        @if($sIdx > 0)
                                            <span class="arrow">→</span>
                                        @endif
                                        <span class="step {{ $stepClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="text-end flex-shrink-0">
                                <strong style="font-size: 16px; color: #093D7D;">
                                    Rp {{ number_format($trx->total_amount ?? 0, 0, ',', '.') }}
                                </strong>
                                <br><small class="text-muted">{{ $trx->service_date?->format('d/m/Y') }}</small>
                                <br><i class="bi bi-chevron-down text-muted" id="chevron-{{ $trx->id }}"></i>
                            </div>
                        </div>

                        {{-- Expandable Detail --}}
                        <div class="trx-expand" id="expand-{{ $trx->id }}">

                            <div class="row g-3 mt-1">
                                {{-- Left: Items --}}
                                <div class="col-md-7">
                                    <h6 class="fw-bold mb-2" style="font-size: 13px;">Rincian Item</h6>
                                    <table class="table items-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Item</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Harga</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($trx->details as $det)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $det->part_name ?? $det->item_name ?? '-' }}</td>
                                                <td class="text-center">{{ $det->quantity }}</td>
                                                <td class="text-end">Rp {{ number_format($det->unit_price ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end fw-bold">Rp {{ number_format($det->total_price ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="5" class="text-center text-muted">Tidak ada detail</td></tr>
                                            @endforelse
                                        </tbody>
                                        @if($trx->details->count() > 0)
                                        <tfoot>
                                            <tr style="background: #F8FAFC;">
                                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                                <td class="text-end fw-bold" style="color: #0B50A1;">
                                                    Rp {{ number_format($trx->details->sum('total_price'), 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>

                                    {{-- Total Mismatch Warning --}}
                                    @php
                                        $detailSum = $trx->details->sum('total_price');
                                        $hasMismatch = abs(($trx->total_amount ?? 0) - $detailSum) > 0.01 && $detailSum > 0;
                                    @endphp
                                    @if($hasMismatch)
                                    <div class="alert alert-warning py-1 px-3 mt-2 mb-0" style="font-size: 12px;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Total header (Rp {{ number_format($trx->total_amount, 0, ',', '.') }})
                                        berbeda dengan total detail (Rp {{ number_format($detailSum, 0, ',', '.') }})
                                    </div>
                                    @endif
                                </div>

                                {{-- Right: Info --}}
                                <div class="col-md-5">
                                    <h6 class="fw-bold mb-2" style="font-size: 13px;">Informasi</h6>
                                    <table class="table table-sm mb-0" style="font-size: 12px;">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Kode VPR</td>
                                            <td>
                                                <a href="{{ route('admin.replacement-approval.show', $trx->vpr_code) }}"
                                                   class="text-primary text-decoration-none">{{ $trx->vpr_code }}</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Kendaraan</td>
                                            <td>{{ $trx->replacement->nopol ?? '-' }}
                                                <br><small class="text-muted">{{ $trx->replacement->vehicle->consumerial_name ?? '' }}</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Driver</td>
                                            <td>{{ $trx->driver->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Dispatcher</td>
                                            <td>{{ $trx->replacement->dispatcher->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Lokasi</td>
                                            <td>{{ $trx->service_location ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tgl Service</td>
                                            <td>{{ $trx->service_date?->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Deadline</td>
                                            <td class="{{ $trx->is_overdue ? 'text-danger fw-bold' : '' }}">
                                                {{ $trx->deadline_date?->format('d/m/Y') }}
                                                @if($trx->is_overdue && $vprStatus !== 'completed')
                                                    <span class="badge bg-danger" style="font-size: 9px;">Overdue</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Dilaporkan</td>
                                            <td>{{ $trx->reported_at?->translatedFormat('d/m/Y H:i') ?? '-' }}</td>
                                        </tr>
                                    </table>

                                    {{-- Proof Photo --}}
                                    @if($trx->proof_photo)
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Bukti Struk/Nota:</small>
                                        <img src="{{ asset('storage/' . $trx->proof_photo) }}"
                                             style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px; border: 1px solid #E4E9F0; cursor: pointer;"
                                             onclick="openLightbox('{{ asset('storage/' . $trx->proof_photo) }}')"
                                             alt="Bukti">
                                    </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="bi bi-receipt text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada transaksi ditemukan</p>
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
function toggleExpand(id) {
    const el = document.getElementById('expand-' + id);
    const chevron = document.getElementById('chevron-' + id);
    if (el.classList.contains('show')) {
        el.classList.remove('show');
        chevron.className = 'bi bi-chevron-down text-muted';
    } else {
        el.classList.add('show');
        chevron.className = 'bi bi-chevron-up text-primary';
    }
}

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
</script>

@endsection
