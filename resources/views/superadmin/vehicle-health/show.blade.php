@extends('templates.main')

@section('title', 'Detail Kesehatan Kendaraan')

@section('content')

<style>
    .info-pair { margin-bottom: 8px; }
    .info-pair .info-label {
        font-size: 11px; color: #64748B;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .info-pair .info-value { font-weight: 600; color: #093D7D; }

    .score-ring {
        width: 120px; height: 120px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-direction: column;
        margin: 0 auto 16px;
    }
    .score-ring.excellent { background: linear-gradient(135deg, #d1e7dd, #a3cfbb); }
    .score-ring.good      { background: linear-gradient(135deg, #cff4fc, #9eeaf9); }
    .score-ring.warning   { background: linear-gradient(135deg, #fff3cd, #ffe69c); }
    .score-ring.critical  { background: linear-gradient(135deg, #f8d7da, #f1aeb5); }
    .score-ring .score-number {
        font-size: 32px; font-weight: 800; line-height: 1;
    }
    .score-ring .score-label {
        font-size: 12px; font-weight: 600;
    }

    .schedule-row {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        gap: 10px;
    }
    .schedule-row:last-child { border-bottom: none; }

    .freq-badge {
        width: 40px; height: 40px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px;
        flex-shrink: 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        padding-bottom: 16px;
    }
    .timeline-item::before {
        content: '';
        position: absolute; left: 8px; top: 24px; bottom: 0;
        width: 2px; background: #E4E9F0;
    }
    .timeline-item:last-child::before { display: none; }
    .timeline-item .timeline-dot {
        width: 18px; height: 18px;
        border-radius: 50%;
        position: absolute; left: 0; top: 4px;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #E4E9F0;
    }
    .timeline-item .timeline-dot.routine { background: #0B50A1; box-shadow: 0 0 0 2px #0B50A1; }
    .timeline-item .timeline-dot.body { background: #64748B; box-shadow: 0 0 0 2px #64748B; }

    .replacement-mini {
        border: 1px solid #E4E9F0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }
</style>

<div class="pagetitle">
    <h1>Detail Kesehatan Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('superadmin.vehicle-health.index') }}">Vehicle Health</a></li>
            <li class="breadcrumb-item active">{{ $vehicle->consumerial_code }}</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════ --}}
        {{-- LEFT: Vehicle Info + Score      --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Score --}}
            <div class="card">
                <div class="card-body pt-3 text-center">
                    @php
                        $score = $vehicle->health_score ?? 0;
                        $scoreClass = 'critical';
                        $scoreLabel = 'Critical';
                        if ($score >= 85) { $scoreClass = 'excellent'; $scoreLabel = 'Excellent'; }
                        elseif ($score >= 70) { $scoreClass = 'good'; $scoreLabel = 'Good'; }
                        elseif ($score >= 50) { $scoreClass = 'warning'; $scoreLabel = 'Warning'; }
                    @endphp
                    <div class="score-ring {{ $scoreClass }}">
                        <div class="score-number">{{ round($score) }}</div>
                        <div class="score-label">{{ $scoreLabel }}</div>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $vehicle->consumerial_code }}</h5>
                    <small class="text-muted">{{ $vehicle->consumerial_name ?? '' }}</small>
                </div>
            </div>

            {{-- Vehicle Info --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Informasi Kendaraan</h5>
                    <div class="info-pair">
                        <div class="info-label">Nopol</div>
                        <div class="info-value">{{ $vehicle->consumerial_code }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Nama / Tipe</div>
                        <div class="info-value">{{ $vehicle->consumerial_name ?? '-' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Total Pengecekan</div>
                        <div class="info-value">{{ $totalCheckings ?? 0 }} kali</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Terakhir Dicek</div>
                        <div class="info-value">{{ $lastChecked?->translatedFormat('d F Y, H:i') ?? 'Belum pernah' }}</div>
                    </div>
                    <div class="info-pair">
                        <div class="info-label">Total Biaya Service</div>
                        <div class="info-value" style="color: #0B50A1;">
                            Rp {{ number_format($totalServiceCost ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    {{-- Download Barcode --}}
                    @if(in_array(auth()->user()->role, ['superadmin', 'dispatcher', 'admin']) && $vehicle->barcode)
                        <div class="mt-3">
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('dispatcher.barcode.download', $vehicle->id) }}"
                                   class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-download me-1"></i> Download Barcode
                                </a>
                            @else
                                <a href="{{ route('superadmin.barcode.download', $vehicle->id) }}"
                                   class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-download me-1"></i> Download Barcode
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Schedule Status --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Jadwal Pengecekan</h5>

                    @php
                        $freqConfig = [
                            'daily'   => ['label' => 'Harian',   'short' => 'H',  'color' => '#0B50A1'],
                            'weekly'  => ['label' => 'Mingguan', 'short' => 'M',  'color' => '#0B50A1'],
                            'monthly' => ['label' => 'Bulanan',  'short' => 'B',  'color' => '#6610f2'],
                            'body'    => ['label' => 'Body',     'short' => 'Bd', 'color' => '#64748B'],
                        ];
                    @endphp

                    @foreach($schedules as $sch)
                    @php
                        $cfg = $freqConfig[$sch->check_frequency] ?? ['label' => $sch->check_frequency, 'short' => '?', 'color' => '#64748B'];
                        $statusBadge = match($sch->status) {
                            'overdue' => 'bg-danger',
                            'due' => 'bg-warning text-dark',
                            'completed' => 'bg-success',
                            default => 'bg-secondary',
                        };
                        $statusLabel = match($sch->status) {
                            'overdue' => 'Overdue',
                            'due' => 'Due',
                            'completed' => 'OK',
                            default => '-',
                        };
                    @endphp
                    <div class="schedule-row">
                        <div class="freq-badge" style="background: {{ $cfg['color'] }}20; color: {{ $cfg['color'] }};">
                            {{ $cfg['short'] }}
                        </div>
                        <div class="flex-grow-1">
                            <strong style="font-size: 13px;">{{ $cfg['label'] }}</strong>
                            <br><small class="text-muted">
                                Next: {{ $sch->next_due_date?->format('d/m/Y') ?? '-' }}
                            </small>
                        </div>
                        <div>
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            @if($sch->assignedDispatcher)
                                <br><small class="text-muted">{{ $sch->assignedDispatcher->name }}</small>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    @if($schedules->isEmpty())
                    <p class="text-muted text-center py-2 mb-0">Belum ada jadwal</p>
                    @endif
                </div>
            </div>

            {{-- Back --}}
            <a href="{{ route('superadmin.vehicle-health.index') }}" class="btn btn-outline-secondary w-100 mb-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Matrix
            </a>
        </div>

        {{-- ═══════════════════════════════ --}}
        {{-- RIGHT: Charts + History         --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Health Trend Chart --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Tren Kondisi (6 Bulan Terakhir)</h5>
                    <canvas id="healthTrendChart" height="200"></canvas>
                </div>
            </div>

            {{-- Checking Result Distribution --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body pt-3">
                            <h5 class="card-title">Routine Check Results</h5>
                            <canvas id="routineChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body pt-3">
                            <h5 class="card-title">Body Check Results</h5>
                            <canvas id="bodyChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Checkings Timeline --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        Riwayat Pengecekan Terakhir
                        <span class="badge bg-secondary rounded-pill">{{ $recentCheckings->count() }}</span>
                    </h5>

                    @forelse($recentCheckings as $check)
                    <div class="timeline-item">
                        <div class="timeline-dot {{ $check->type }}"></div>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong style="font-size: 13px;">
                                    {{ $check->checking_detail_code }}
                                </strong>
                                @if($check->type === 'routine')
                                    <span class="badge bg-primary" style="font-size: 10px;">
                                        {{ $check->getSubTypeLabel() }}
                                    </span>
                                @else
                                    <span class="badge bg-dark" style="font-size: 10px;">Body</span>
                                @endif
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>{{ $check->dispatcher->nama_lengkap ?? '-' }}
                                    &nbsp;|&nbsp;
                                    <i class="bi bi-person-badge me-1"></i>{{ $check->currentDriver->nama_lengkap ?? '-' }}
                                </small>
                                <br>
                                {{-- Mini Results --}}
                                @if($check->type === 'routine')
                                    <span class="badge bg-success" style="font-size: 10px;">OK {{ $check->ok_count }}</span>
                                    <span class="badge bg-danger" style="font-size: 10px;">NG {{ $check->ng_count }}</span>
                                @else
                                    @php $results = $check->itemResults; @endphp
                                    <span class="badge" style="font-size:10px;background:#d1e7dd;color:#4A7A1C;">B{{ $results->where('status','baik')->count() }}</span>
                                    <span class="badge" style="font-size:10px;background:#cff4fc;color:#0B50A1;">C{{ $results->where('status','cukup')->count() }}</span>
                                    <span class="badge" style="font-size:10px;background:#fff3cd;color:#997404;">K{{ $results->where('status','kurang')->count() }}</span>
                                    <span class="badge" style="font-size:10px;background:#f8d7da;color:#C4142A;">T{{ $results->where('status','tidak')->count() }}</span>
                                @endif
                                @if($check->driver_acknowledged)
                                    <span class="badge bg-light text-success" style="font-size: 10px;">
                                        <i class="bi bi-check"></i> Ack
                                    </span>
                                @endif
                            </div>
                            <small class="text-muted text-end flex-shrink-0">
                                {{ $check->checked_at?->format('d/m/Y') }}
                                <br>{{ $check->checked_at?->format('H:i') }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3 mb-0">Belum ada riwayat pengecekan</p>
                    @endforelse
                </div>
            </div>

            {{-- Active Replacements --}}
            @if($activeReplacements->count() > 0)
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-tools text-warning me-1"></i>
                        Permohonan Penggantian Aktif
                    </h5>

                    @foreach($activeReplacements as $vpr)
                    <div class="replacement-mini">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('admin.replacement-approval.show', $vpr->vpr_code) }}"
                                   class="fw-bold text-primary text-decoration-none">
                                    {{ $vpr->vpr_code }}
                                </a>
                                <span class="badge {{ $vpr->getStatusBadgeClass() }}">{{ $vpr->getStatusLabel() }}</span>
                                <br>
                                <small class="text-muted">
                                    {{ $vpr->replacementDetails->count() }} item
                                    &nbsp;|&nbsp;
                                    {{ $vpr->created_at?->format('d/m/Y') }}
                                </small>
                            </div>
                            @if($vpr->transaction)
                            <div class="text-end">
                                <strong>Rp {{ number_format($vpr->transaction->total_amount ?? 0, 0, ',', '.') }}</strong>
                                <br><small class="text-muted">{{ $vpr->transaction->service_location ?? '-' }}</small>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Trip Analysis --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-graph-up-arrow text-primary me-1"></i>Analisis Trip & Efisiensi BBM</span>
                        <span class="badge bg-secondary rounded-pill">{{ $tripAnalyses->count() }} Trip</span>
                    </h5>

                    @if($tripAnalyses->isEmpty())
                        <p class="text-muted text-center py-3 mb-0">Belum ada data trip analysis untuk kendaraan ini</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle" style="font-size:12px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Driver</th>
                                    <th class="text-center">Jarak (KM)</th>
                                    <th class="text-center">BBM (L)</th>
                                    <th class="text-center">Efisiensi</th>
                                    <th class="text-center">Deviasi</th>
                                    <th class="text-center">Range Usage</th>
                                    <th class="text-center">Alert</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tripAnalyses as $trip)
                                @php
                                    $driver = $trip->transactionProofPrevious->permohonan->requester ?? null;
                                    $deviasi = $trip->efficiency_deviation_percent ?? 0;
                                    $deviColor = $deviasi >= 0 ? 'text-success' : ($deviasi >= -20 ? 'text-warning' : 'text-danger');
                                    $alertBadge = match($trip->alert_level) {
                                        'critical' => 'bg-danger',
                                        'warning'  => 'bg-warning text-dark',
                                        default    => 'bg-success',
                                    };
                                    $alertLabel = match($trip->alert_level) {
                                        'critical' => 'Kritis',
                                        'warning'  => 'Perlu Perhatian',
                                        default    => 'Normal',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $driver->nama_lengkap ?? '-' }}</span>
                                        @if($trip->validated_at)
                                            <br><small class="text-success"><i class="bi bi-check-circle"></i> Tervalidasi</small>
                                        @else
                                            <br><small class="text-muted">Belum divalidasi</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">{{ number_format($trip->diff_odo ?? 0) }}</span>
                                    </td>
                                    <td class="text-center">{{ number_format($trip->fuel_volume ?? 0, 1) }}</td>
                                    <td class="text-center">
                                        <span class="{{ $deviColor }} fw-semibold">
                                            {{ number_format($trip->actual_efficiency ?? 0, 2) }} km/L
                                        </span>
                                        <br><small class="text-muted">(optimal: {{ number_format($trip->expected_efficiency ?? 0, 2) }} km/L)</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ $deviColor }} fw-bold">
                                            {{ number_format($deviasi, 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php $rangeUsage = $trip->range_usage_percent ?? 0; @endphp
                                        <div style="font-size:11px;">
                                            <span class="{{ $rangeUsage < 60 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                {{ number_format($rangeUsage, 0) }}%
                                            </span>
                                            @if($trip->is_premature_refuel)
                                                <br><small class="text-danger">Terlalu Cepat!</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $alertBadge }}" style="font-size:10px;">{{ $alertLabel }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</section>

{{-- Chart.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"
     integrity="sha384-bs/nf9FbdNouRbMiFcrcZfLXYPKiPaGVGplVbv7dLGECccEXDW+S3zjqSKR5ZEaD"
     crossorigin="anonymous"></script>
<script>
// Health Trend (6 bulan)
const trendCtx = document.getElementById('healthTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($trendLabels ?? []) !!},
        datasets: [{
            label: 'Health Score',
            data: {!! json_encode($trendScores ?? []) !!},
            borderColor: '#0B50A1',
            backgroundColor: 'rgba(11,80,161,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 5,
            pointBackgroundColor: '#0B50A1',
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
        },
        plugins: { legend: { display: false } }
    }
});

// Routine Distribution
const routineCtx = document.getElementById('routineChart').getContext('2d');
new Chart(routineCtx, {
    type: 'doughnut',
    data: {
        labels: ['OK', 'NG'],
        datasets: [{
            data: [{{ $routineStats['ok'] ?? 0 }}, {{ $routineStats['ng'] ?? 0 }}],
            backgroundColor: ['#71A62F', '#ED1B2F'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// Body Distribution
const bodyCtx = document.getElementById('bodyChart').getContext('2d');
new Chart(bodyCtx, {
    type: 'doughnut',
    data: {
        labels: ['Baik', 'Cukup', 'Kurang', 'Tidak'],
        datasets: [{
            data: [
                {{ $bodyStats['baik'] ?? 0 }},
                {{ $bodyStats['cukup'] ?? 0 }},
                {{ $bodyStats['kurang'] ?? 0 }},
                {{ $bodyStats['tidak'] ?? 0 }}
            ],
            backgroundColor: ['#71A62F', '#0B50A1', '#D8CE3A', '#ED1B2F'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});
</script>

@endsection
