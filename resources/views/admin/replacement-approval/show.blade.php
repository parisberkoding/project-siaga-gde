@extends('templates.main')

@section('title', 'Review Permohonan Penggantian')

@section('content')

<style>
    .info-pair { margin-bottom: 6px; }
    .info-pair .info-label {
        font-size: 12px; color: #64748B;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .info-pair .info-value { font-weight: 600; color: #093D7D; }

    .condition-badge {
        display: inline-block; padding: 3px 10px;
        border-radius: 6px; font-size: 12px; font-weight: 600;
    }
    .condition-badge.ok     { background: #d1e7dd; color: #4A7A1C; }
    .condition-badge.ng     { background: #f8d7da; color: #C4142A; }
    .condition-badge.baik   { background: #d1e7dd; color: #4A7A1C; }
    .condition-badge.cukup  { background: #cff4fc; color: #0B50A1; }
    .condition-badge.kurang { background: #fff3cd; color: #997404; }
    .condition-badge.tidak  { background: #f8d7da; color: #C4142A; }

    .vprd-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 0;
    }
    .vprd-item:last-child { border-bottom: none; }

    .action-card {
        border: 2px solid #E4E9F0;
        border-radius: 12px;
        overflow: hidden;
    }
    .action-card .action-header {
        padding: 12px 16px;
        font-weight: 600;
    }
    .action-card .action-body { padding: 16px; }

    .action-card.approve { border-color: #4A7A1C; }
    .action-card.approve .action-header { background: #d1e7dd; color: #4A7A1C; }

    .action-card.reject { border-color: #C4142A; }
    .action-card.reject .action-header { background: #f8d7da; color: #C4142A; }

    .action-card.schedule { border-color: #0B50A1; }
    .action-card.schedule .action-header { background: #f0f2ff; color: #0B50A1; }

    .action-card.complete { border-color: #4A7A1C; }
    .action-card.complete .action-header { background: #d1e7dd; color: #4A7A1C; }

    /* Status Timeline (compact) */
    .status-flow {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .flow-step {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        background: #E4E9F0;
        color: #64748B;
    }
    .flow-step.active { background: #0B50A1; color: #fff; }
    .flow-step.done { background: #71A62F; color: #fff; }
    .flow-step.rejected { background: #ED1B2F; color: #fff; }
    .flow-arrow { color: #E4E9F0; font-size: 12px; }
</style>

<div class="pagetitle">
    <h1>Review Permohonan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.replacement-approval.index') }}">Approval</a></li>
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
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Status Flow --}}
@php
    $statusFlow = ['pending', 'approved', 'scheduled', 'in_service', 'completed'];
    $currentIndex = array_search($replacement->status, $statusFlow);
    if ($currentIndex === false) $currentIndex = -1;
    $flowLabels = [
        'pending' => 'Pending', 'approved' => 'Approved', 'scheduled' => 'Scheduled',
        'in_service' => 'In Service', 'completed' => 'Completed',
    ];
@endphp
<div class="status-flow">
    @foreach($statusFlow as $idx => $step)
        @php
            $stepClass = '';
            if ($replacement->status === 'rejected') {
                $stepClass = ($idx === 0) ? 'done' : (($step === 'approved') ? 'rejected' : '');
            } else {
                if ($idx < $currentIndex) $stepClass = 'done';
                elseif ($idx == $currentIndex) $stepClass = 'active';
            }
        @endphp
        <span class="flow-step {{ $stepClass }}">{{ $flowLabels[$step] }}</span>
        @if(!$loop->last)<span class="flow-arrow">→</span>@endif
    @endforeach

    @if($replacement->status === 'rejected')
        <span class="flow-arrow">→</span>
        <span class="flow-step rejected">Rejected</span>
    @endif
</div>

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════ --}}
        {{-- LEFT: Info + Items              --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-7">

            {{-- Info --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi Permohonan</h5>

                    <div class="row">
                        <div class="col-md-6">
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
                                <div class="info-label">Diajukan Oleh</div>
                                <div class="info-value">{{ $replacement->dispatcher->nama_lengkap ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pair">
                                <div class="info-label">Tanggal Pengajuan</div>
                                <div class="info-value">{{ $replacement->created_at->translatedFormat('d F Y, H:i') }}</div>
                            </div>
                            <div class="info-pair">
                                <div class="info-label">Driver Terakhir</div>
                                <div class="info-value">{{ $replacement->lastDriver->nama_lengkap ?? '-' }}</div>
                            </div>
                            <div class="info-pair">
                                <div class="info-label">Dari Pengecekan</div>
                                <div class="info-value">
                                    @php
                                        $checkDetail = $replacement->checkingDetail;
                                        $typeLabel = $checkDetail?->type === 'routine' ? 'Rutin' : 'Body';
                                    @endphp
                                    {{ $checkDetail->checking_detail_code ?? '-' }}
                                    <span class="badge bg-secondary">{{ $typeLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($replacement->approver)
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-pair">
                                <div class="info-label">Di-approve/reject Oleh</div>
                                <div class="info-value">{{ $replacement->approver->nama_lengkap ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pair">
                                <div class="info-label">Tanggal</div>
                                <div class="info-value">{{ $replacement->approved_at?->translatedFormat('d F Y, H:i') ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($replacement->rejection_reason)
                    <div class="alert alert-danger mt-2 mb-0">
                        <strong>Alasan Penolakan:</strong> {{ $replacement->rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Item List --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        Daftar Item
                        <span class="badge bg-secondary rounded-pill">{{ $replacement->replacementDetails->count() }}</span>
                    </h5>

                    @foreach($replacement->replacementDetails as $item)
                    <div class="vprd-item">
                        <div class="row align-items-center g-2">
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">{{ $loop->iteration }}</span>
                            </div>
                            <div class="col">
                                <strong>{{ $item->item_name }}</strong>
                                <br>
                                <span class="condition-badge {{ $item->condition_status }}">
                                    Kondisi: {{ ucfirst($item->condition_status) }}
                                </span>
                                @if($item->dispatcher_notes)
                                    <br><small class="text-muted fst-italic mt-1 d-inline-block">
                                        <i class="bi bi-chat-left-text me-1"></i>{{ $item->dispatcher_notes }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-muted">{{ $item->vprd_code }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════ --}}
        {{-- RIGHT: Actions                  --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-5">

            {{-- ── PENDING → Approve / Reject ── --}}
            @if($replacement->status === 'pending')
            <div class="action-card approve mb-3">
                <div class="action-header">
                    <i class="bi bi-check-circle me-1"></i> Approve Permohonan
                </div>
                <div class="action-body">
                    <form action="{{ route('admin.replacement-approval.approve', $replacement->vpr_code) }}" method="POST"
                          onsubmit="return confirm('Approve permohonan {{ $replacement->vpr_code }}?')">
                        @csrf
                        {{-- @method('PUT') --}}

                        <div class="mb-3">
                            <label for="desired_driver_id" class="form-label fw-semibold">
                                <i class="bi bi-person-gear me-1"></i> Driver untuk Service
                            </label>
                            <select name="desired_driver_id" id="desired_driver_id" class="form-select">
                                <option value="">— Belum ditentukan —</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                            {{ $replacement->desired_driver_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->nama_lengkap }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label fw-semibold">Catatan (opsional)</label>
                            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="2"
                                      placeholder="Catatan dari admin..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-lg me-1"></i> Approve
                        </button>
                    </form>
                </div>
            </div>

            <div class="action-card reject mb-3">
                <div class="action-header">
                    <i class="bi bi-x-circle me-1"></i> Tolak Permohonan
                </div>
                <div class="action-body">
                    <form action="{{ route('admin.replacement-approval.reject', $replacement->vpr_code) }}" method="POST"
                          onsubmit="return confirm('Tolak permohonan {{ $replacement->vpr_code }}?')">
                        @csrf
                        {{-- @method('PUT') --}}

                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label fw-semibold">
                                Alasan Penolakan <span class="text-danger">*</span>
                            </label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                                      placeholder="Jelaskan alasan penolakan..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-x-lg me-1"></i> Tolak
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ── APPROVED → Set Jadwal Service ── --}}
            @if($replacement->status === 'approved')
            <div class="action-card schedule mb-3">
                <div class="action-header">
                    <i class="bi bi-calendar-plus me-1"></i> Jadwalkan Service
                </div>
                <div class="action-body">
                    <form action="{{ route('admin.replacement-approval.schedule', $replacement->vpr_code) }}" method="POST"
                          onsubmit="return confirm('Set jadwal service untuk {{ $replacement->vpr_code }}?')">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Driver <span class="text-danger">*</span></label>
                            <select name="driver_id" class="form-select" required>
                                <option value="">— Pilih Driver —</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                            {{ $replacement->desired_driver_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->nama_lengkap }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Service <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" class="form-control"
                                   value="{{ now()->format('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lokasi Service <span class="text-danger">*</span></label>
                            <input type="text" name="service_location" class="form-control"
                                   placeholder="Nama bengkel / lokasi..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deadline Laporan <span class="text-danger">*</span></label>
                            <input type="date" name="deadline_date" class="form-control"
                                   value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                            <small class="text-muted">Batas waktu driver melaporkan hasil service</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calendar-check me-1"></i> Jadwalkan
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ── SCHEDULED/IN_SERVICE → Status Info ── --}}
            @if(in_array($replacement->status, ['scheduled', 'in_service', 'completed']))
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Info Service</h5>

                    @if($replacement->transaction)
                    @php $trx = $replacement->transaction; @endphp
                    <div class="info-pair">
                        <div class="info-label">Kode Transaksi</div>
                        <div class="info-value">{{ $trx->vprt_code }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Driver</div>
                        <div class="info-value">{{ $trx->driver->name ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Lokasi</div>
                        <div class="info-value fw-normal">{{ $trx->service_location ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Tanggal Service</div>
                        <div class="info-value">{{ $trx->service_date?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Deadline</div>
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

                    {{-- Rincian Biaya --}}
                    @if($trx->details && $trx->details->count() > 0)
                    <hr>
                    <h6 class="fw-bold mb-2">Rincian Biaya</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; @endphp
                                @foreach($trx->details as $trxItem)
                                @php $grandTotal += $trxItem->total_price ?? 0; @endphp
                                <tr>
                                    <td>{{ $trxItem->item_name }} <small class="text-muted">(x{{ $trxItem->quantity }})</small></td>
                                    <td class="text-end">Rp {{ number_format($trxItem->total_price ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="fw-bold">Grand Total</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    @if($trx->proof_photo)
                    <hr>
                    <h6 class="fw-bold mb-2">Bukti Service</h6>
                    <img src="{{ asset('storage/' . $trx->proof_photo) }}" alt="Bukti"
                         class="rounded" style="max-width: 100%; cursor: pointer;"
                         onclick="window.open(this.src, '_blank')">
                    @endif

                    @else
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-clock fs-3"></i>
                            <p class="mt-2 mb-0">Belum ada data transaksi service</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── IN_SERVICE → Tandai Selesai + biaya aktual (BUG-VPR-001) ── --}}
            @if($replacement->status === 'in_service')
            <div class="action-card complete mb-3">
                <div class="action-header">
                    <i class="bi bi-check2-circle me-1"></i> Tandai Selesai
                </div>
                <div class="action-body">
                    @if($replacement->transaction)
                    <form action="{{ route('admin.replacement-approval.complete', $replacement->vpr_code) }}" method="POST"
                          onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='<i class=\'bi bi-hourglass-split me-1\'></i> Memproses...';">
                        @csrf

                        <div class="mb-3">
                            <label for="actual_cost" class="form-label fw-semibold">
                                Biaya Aktual <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" min="0" name="actual_cost" id="actual_cost"
                                       class="form-control @error('actual_cost') is-invalid @enderror"
                                       value="{{ old('actual_cost', $replacement->transaction->total_amount) }}" required>
                            </div>
                            @error('actual_cost')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Biaya service yang benar-benar dikeluarkan (sesuai nota driver).</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check2-circle me-1"></i> Tandai Selesai
                        </button>
                    </form>
                    @else
                        <div class="text-center py-2 text-muted">
                            <i class="bi bi-clock fs-3"></i>
                            <p class="mt-2 mb-0">Driver belum melaporkan transaksi service, VPR belum bisa ditutup.</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Back button --}}
            <div class="card">
                <div class="card-body pt-3">
                    <a href="{{ route('admin.replacement-approval.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

        </div>

    </div>
</section>

@endsection
