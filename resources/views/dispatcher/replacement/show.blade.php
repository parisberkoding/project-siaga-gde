@extends('templates.main')

@section('title', 'Detail Permohonan Penggantian')

@section('content')

<style>
    .info-pair {
        margin-bottom: 6px;
    }
    .info-pair .info-label {
        font-size: 12px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-pair .info-value {
        font-weight: 600;
        color: #093D7D;
    }

    /* Status Timeline */
    .status-timeline {
        position: relative;
        padding-left: 28px;
    }
    .status-timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #E4E9F0;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 16px;
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot {
        position: absolute;
        left: -22px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #E4E9F0;
        background: #fff;
    }
    .timeline-dot.active {
        border-color: #0B50A1;
        background: #0B50A1;
    }
    .timeline-dot.completed {
        border-color: #4A7A1C;
        background: #71A62F;
    }
    .timeline-label {
        font-size: 13px;
        font-weight: 600;
    }
    .timeline-meta {
        font-size: 12px;
        color: #64748B;
    }

    /* Item list */
    .vprd-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 0;
    }
    .vprd-item:last-child { border-bottom: none; }

    .condition-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .condition-badge.ok     { background: #d1e7dd; color: #4A7A1C; }
    .condition-badge.ng     { background: #f8d7da; color: #C4142A; }
    .condition-badge.baik   { background: #d1e7dd; color: #4A7A1C; }
    .condition-badge.cukup  { background: #cff4fc; color: #0B50A1; }
    .condition-badge.kurang { background: #fff3cd; color: #997404; }
    .condition-badge.tidak  { background: #f8d7da; color: #C4142A; }

    /* Transaction card */
    .transaction-card {
        border: 2px solid #E4E9F0;
        border-radius: 10px;
        padding: 16px;
    }
    .transaction-card.completed { border-color: #4A7A1C; }
    .transaction-card.overdue { border-color: #C4142A; }
</style>

<div class="pagetitle">
    <h1>Detail Permohonan Penggantian</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.replacement.index') }}">Permohonan</a></li>
            <li class="breadcrumb-item active">{{ $replacement->vpr_code }}</li>
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

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════════ --}}
        {{-- LEFT: Info + Timeline               --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Info --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi Permohonan</h5>

                    <div class="info-pair">
                        <div class="info-label">Kode VPR</div>
                        <div class="info-value">{{ $replacement->vpr_code }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Kendaraan</div>
                        <div class="info-value">
                            {{ $replacement->nopol }}
                            <br><small class="text-muted fw-normal">{{ $replacement->vehicle->consumerial_name ?? '' }}</small>
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge {{ $replacement->getStatusBadgeClass() }} fs-6">
                                {{ $replacement->getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Diajukan Oleh</div>
                        <div class="info-value">{{ $replacement->dispatcher->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Tanggal Pengajuan</div>
                        <div class="info-value">{{ $replacement->created_at->translatedFormat('d F Y, H:i') }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Driver Terakhir</div>
                        <div class="info-value">{{ $replacement->lastDriver->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Driver untuk Service</div>
                        <div class="info-value">{{ $replacement->desiredDriver->nama_lengkap ?? 'Belum ditentukan' }}</div>
                    </div>

                    @if($replacement->approver)
                    <hr>
                    <div class="info-pair">
                        <div class="info-label">Di-approve Oleh</div>
                        <div class="info-value">{{ $replacement->approver->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Tanggal Approve</div>
                        <div class="info-value">{{ $replacement->approved_at?->translatedFormat('d F Y, H:i') ?? '-' }}</div>
                    </div>
                    @endif

                    @if($replacement->status === 'rejected' && $replacement->rejection_reason)
                    <hr>
                    <div class="info-pair">
                        <div class="info-label">Alasan Penolakan</div>
                        <div class="info-value text-danger fw-normal">{{ $replacement->rejection_reason }}</div>
                    </div>
                    @endif

                    {{-- Link ke pengecekan asal --}}
                    <hr>
                    <div class="info-pair">
                        <div class="info-label">Dari Pengecekan</div>
                        <div class="info-value">
                            @php
                                $checkDetail = $replacement->checkingDetail;
                                // dd($checkDetail)
                                $checkRoute = $checkDetail?->type === 'routine'
                                    ? route('dispatcher.daily-check.show', $checkDetail->checking_detail_code)
                                    : route('dispatcher.body-check.show', $checkDetail->checking_detail_code);
                            @endphp
                            <a href="{{ $checkRoute }}" class="text-decoration-none">
                                {{ $checkDetail->checking_detail_code ?? '-' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Timeline --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Status Tracking</h5>

                    @php
                        $statusFlow = ['pending', 'approved', 'scheduled', 'in_service', 'completed'];
                        $currentIndex = array_search($replacement->status, $statusFlow);
                        if ($currentIndex === false) $currentIndex = -1;

                        $timelineConfig = [
                            'pending'    => ['label' => 'Menunggu Approval',    'icon' => 'bi-hourglass-split'],
                            'approved'   => ['label' => 'Di-approve',           'icon' => 'bi-check-circle'],
                            'scheduled'  => ['label' => 'Dijadwalkan Service',  'icon' => 'bi-calendar-check'],
                            'in_service' => ['label' => 'Sedang di Service',    'icon' => 'bi-wrench'],
                            'completed'  => ['label' => 'Selesai',              'icon' => 'bi-check2-all'],
                        ];
                    @endphp

                    <div class="status-timeline">
                        @foreach($statusFlow as $idx => $step)
                        @php
                            $dotClass = '';
                            if ($idx < $currentIndex) $dotClass = 'completed';
                            elseif ($idx == $currentIndex) $dotClass = 'active';
                        @endphp
                        <div class="timeline-item">
                            <div class="timeline-dot {{ $dotClass }}"></div>
                            <div class="timeline-label {{ $dotClass ? '' : 'text-muted' }}">
                                <i class="bi {{ $timelineConfig[$step]['icon'] }} me-1"></i>
                                {{ $timelineConfig[$step]['label'] }}
                            </div>
                            @if($idx == $currentIndex)
                                <div class="timeline-meta">← Saat ini</div>
                            @endif
                        </div>
                        @endforeach

                        @if($replacement->status === 'rejected')
                        <div class="timeline-item">
                            <div class="timeline-dot" style="border-color: #C4142A; background: #ED1B2F;"></div>
                            <div class="timeline-label text-danger">
                                <i class="bi bi-x-circle me-1"></i> Ditolak
                            </div>
                            <div class="timeline-meta">← Saat ini</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-grid gap-2">
                        <a href="{{ route('dispatcher.replacement.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════ --}}
        {{-- RIGHT: Items + Transaction           --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Item List --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        Daftar Item Penggantian
                        <span class="badge bg-secondary rounded-pill">{{ $replacement->replacementDetails->count() }}</span>
                    </h5>

                    @forelse($replacement->replacementDetails as $detail_item)
                    <div class="vprd-item">
                        <div class="row align-items-center g-2">
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">{{ $loop->iteration }}</span>
                            </div>
                            <div class="col">
                                <strong>{{ $detail_item->item_name }}</strong>
                                <br>
                                <span class="condition-badge {{ $detail_item->condition_status }}">
                                    Kondisi: {{ ucfirst($detail_item->condition_status) }}
                                </span>
                                @if($detail_item->dispatcher_notes)
                                    <br><small class="text-muted fst-italic mt-1 d-inline-block">
                                        <i class="bi bi-chat-left-text me-1"></i>{{ $detail_item->dispatcher_notes }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-muted">{{ $detail_item->vprd_code }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted fs-1"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada item</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Transaction (kalau sudah ada) --}}
            @if($replacement->transaction)
            @php $trx = $replacement->transaction; @endphp
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-receipt me-1"></i> Laporan Service
                    </h5>

                    <div class="transaction-card {{ $trx->status === 'completed' ? 'completed' : ($trx->is_overdue ? 'overdue' : '') }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-pair">
                                    <div class="info-label">Kode Transaksi</div>
                                    <div class="info-value">{{ $trx->vprt_code }}</div>
                                </div>
                                <div class="info-pair">
                                    <div class="info-label">Driver</div>
                                    <div class="info-value">{{ $trx->driver->name ?? '-' }}</div>
                                </div>
                                <div class="info-pair">
                                    <div class="info-label">Lokasi Service</div>
                                    <div class="info-value fw-normal">{{ $trx->service_location ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-pair">
                                    <div class="info-label">Tanggal Service</div>
                                    <div class="info-value">{{ $trx->service_date?->translatedFormat('d F Y') ?? '-' }}</div>
                                </div>
                                <div class="info-pair">
                                    <div class="info-label">Deadline Laporan</div>
                                    <div class="info-value {{ $trx->is_overdue ? 'text-danger' : '' }}">
                                        {{ $trx->deadline_date?->translatedFormat('d F Y') ?? '-' }}
                                        @if($trx->is_overdue)
                                            <span class="badge bg-danger ms-1">Overdue</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="info-pair">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="badge {{ $trx->status === 'completed' ? 'bg-success' : ($trx->is_overdue ? 'bg-danger' : 'bg-warning text-dark') }}">
                                            {{ ucfirst($trx->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Transaction detail items --}}
                        @if($trx->details && $trx->details->count() > 0)
                        <hr>
                        <h6 class="fw-bold mb-2">Rincian Biaya</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $grandTotal = 0; @endphp
                                    @foreach($trx->details as $trxDetail)
                                    @php $grandTotal += $trxDetail->total_price ?? 0; @endphp
                                    <tr>
                                        <td>{{ $trxDetail->item_name }}</td>
                                        <td class="text-end">{{ $trxDetail->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($trxDetail->unit_price ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($trxDetail->total_price ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Grand Total</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif

                        {{-- Bukti foto --}}
                        @if($trx->proof_photo)
                        <hr>
                        <h6 class="fw-bold mb-2">Bukti Service</h6>
                        <img src="{{ asset('storage/' . $trx->proof_photo) }}" alt="Bukti Service"
                             class="rounded" style="max-width: 300px; cursor: pointer;"
                             onclick="window.open(this.src, '_blank')">
                        @endif
                    </div>

                </div>
        </div>
            @endif

        </div>

    </div>
</section>

@endsection
