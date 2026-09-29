@extends('templates.main')

@section('title', 'Laporan Keuangan Service Kendaraan')

@section('content')

<style>
    .fin-stat {
        text-align: center;
        padding: 18px 12px;
    }
    .fin-stat .stat-number {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
    }
    .fin-stat .stat-label {
        font-size: 11px;
        color: #64748B;
        margin-top: 4px;
    }
    .fin-stat .stat-sub {
        font-size: 11px;
        margin-top: 2px;
    }
    .fin-stat .stat-sub.up   { color: #C4142A; }
    .fin-stat .stat-sub.down { color: #4A7A1C; }
    .fin-stat .stat-sub.flat { color: #64748B; }

    .period-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .period-nav .btn-nav {
        width: 32px; height: 32px;
        border-radius: 50%;
        border: 1px solid #E4E9F0;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
        cursor: pointer;
        color: #64748B;
    }
    .period-nav .btn-nav:hover { background: #F8FAFC; }
    .period-nav .period-label {
        font-weight: 700;
        font-size: 15px;
        color: #093D7D;
        min-width: 160px;
        text-align: center;
    }

    .export-btn-group .btn {
        border-radius: 8px;
        font-weight: 600;
    }

    .table-financial th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        border-bottom: 2px solid #E4E9F0;
    }
    .table-financial td { vertical-align: middle; }
    .table-financial .row-total {
        background: #F8FAFC;
        font-weight: 700;
    }

    .top-spender {
        display: flex;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .top-spender:last-child { border-bottom: none; }
    .top-spender .rank {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 12px;
        flex-shrink: 0;
        margin-right: 10px;
    }
    .top-spender .rank.gold   { background: #fff3cd; color: #997404; }
    .top-spender .rank.silver { background: #E4E9F0; color: #64748B; }
    .top-spender .rank.bronze { background: #f8d7da; color: #997404; }
    .top-spender .rank.normal { background: #F8FAFC; color: #64748B; }
</style>

<div class="pagetitle">
    <h1>Laporan Keuangan Service</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan Keuangan</li>
        </ol>
    </nav>
</div>

{{-- Period Navigation + Export --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="period-nav">
        <a href="{{ route('superadmin.financial-report.index', ['month' => $prevMonth]) }}" class="btn-nav">
            <i class="bi bi-chevron-left"></i>
        </a>
        <span class="period-label">{{ $currentMonthLabel }}</span>
        <a href="{{ route('superadmin.financial-report.index', ['month' => $nextMonth]) }}" class="btn-nav">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="export-btn-group d-flex gap-2">
        <a href="{{ route('superadmin.financial-report.export', ['month' => request('month', now()->format('Y-m')), 'format' => 'excel']) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
        </a>
        <a href="{{ route('superadmin.financial-report.export', ['month' => request('month', now()->format('Y-m')), 'format' => 'pdf']) }}"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<section class="section">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fin-stat">
                    <div class="stat-number" style="color: #0B50A1;">
                        Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="stat-label">Total Biaya Bulan Ini</div>
                    @php
                        $costDiff = ($summary['cost_change_pct'] ?? 0);
                    @endphp
                    <div class="stat-sub {{ $costDiff > 0 ? 'up' : ($costDiff < 0 ? 'down' : 'flat') }}">
                        @if($costDiff > 0)
                            <i class="bi bi-arrow-up"></i> +{{ $costDiff }}% vs bulan lalu
                        @elseif($costDiff < 0)
                            <i class="bi bi-arrow-down"></i> {{ $costDiff }}% vs bulan lalu
                        @else
                            — Sama dengan bulan lalu
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fin-stat">
                    <div class="stat-number" style="color: #4A7A1C;">{{ $summary['completed_count'] ?? 0 }}</div>
                    <div class="stat-label">Service Selesai</div>
                    <div class="stat-sub flat">dari {{ $summary['total_vpr'] ?? 0 }} VPR total</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fin-stat">
                    <div class="stat-number" style="color: #D8CE3A;">{{ $summary['total_items'] ?? 0 }}</div>
                    <div class="stat-label">Item Diganti</div>
                    <div class="stat-sub flat">
                        Avg: Rp {{ number_format($summary['avg_cost_per_item'] ?? 0, 0, ',', '.') }}/item
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fin-stat">
                    <div class="stat-number" style="color: #C4142A;">{{ $summary['overdue_count'] ?? 0 }}</div>
                    <div class="stat-label">Overdue</div>
                    <div class="stat-sub flat">
                        Avg: Rp {{ number_format($summary['avg_cost_per_trx'] ?? 0, 0, ',', '.') }}/transaksi
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="row">

        {{-- ═══════════════════════════════ --}}
        {{-- LEFT: Charts                    --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Monthly Trend Chart --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Tren Biaya Service (6 Bulan)</h5>
                    <canvas id="costTrendChart" height="220"></canvas>
                </div>
            </div>

            {{-- Cost by Vehicle --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Biaya per Kendaraan — {{ $currentMonthLabel }}</h5>
                    <canvas id="vehicleCostChart" height="200"></canvas>
                </div>
            </div>

            {{-- Detailed Table --}}
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0 pb-0">Rincian Transaksi</h5>
                        {{-- Filter --}}
                        <form method="GET" action="{{ route('superadmin.financial-report.index') }}" class="d-flex gap-1">
                            <input type="hidden" name="month" value="{{ request('month', now()->format('Y-m')) }}">
                            <select name="vehicle_id" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                                <option value="">Semua Kendaraan</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->consumerial_code }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-financial table-hover">
                            <thead>
                                <tr>
                                    <th>Kode VPRT</th>
                                    <th>Kendaraan</th>
                                    <th>Lokasi</th>
                                    <th>Driver</th>
                                    <th class="text-center">Items</th>
                                    <th>Tgl Service</th>
                                    <th class="text-end">Biaya</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td>
                                        <strong style="font-size: 13px;">{{ $trx->vprt_code }}</strong>
                                        <br><small class="text-muted">{{ $trx->vpr_code }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $trx->replacement->nopol ?? '-' }}</strong>
                                        <br><small class="text-muted">{{ $trx->replacement->vehicle->consumerial_name ?? '' }}</small>
                                    </td>
                                    <td style="max-width: 140px;">
                                        <span title="{{ $trx->service_location }}">{{ Str::limit($trx->service_location ?? '-', 20) }}</span>
                                    </td>
                                    <td>{{ $trx->driver->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $trx->details->count() }}</span>
                                    </td>
                                    <td>{{ $trx->service_date?->format('d/m/Y') }}</td>
                                    <td class="text-end fw-bold" style="white-space: nowrap;">
                                        Rp {{ number_format($trx->total_amount ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $st = $trx->replacement?->status ?? 'unknown';
                                            $stBadge = match($st) {
                                                'completed' => 'bg-success',
                                                'in_service' => 'bg-warning text-dark',
                                                'scheduled' => 'bg-info',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $stBadge }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</span>
                                        @if($trx->is_overdue && $st !== 'completed')
                                            <br><span class="badge bg-danger" style="font-size: 9px;">Overdue</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        Tidak ada transaksi pada periode ini
                                    </td>
                                </tr>
                                @endforelse

                                @if($transactions->count() > 0)
                                <tr class="row-total">
                                    <td colspan="6" class="text-end">Grand Total:</td>
                                    <td class="text-end" style="white-space: nowrap; font-size: 15px;">
                                        Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator && $transactions->hasPages())
                    <div class="d-flex justify-content-center mt-2">
                        {{ $transactions->links() }}
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════ --}}
        {{-- RIGHT: Insights                 --}}
        {{-- ═══════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Top Spenders (Kendaraan) --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-trophy me-1 text-warning"></i>
                        Top Biaya per Kendaraan
                    </h5>

                    @forelse($topVehicles as $idx => $tv)
                    <div class="top-spender">
                        @php
                            $rankClass = match($idx) {
                                0 => 'gold',
                                1 => 'silver',
                                2 => 'bronze',
                                default => 'normal',
                            };
                        @endphp
                        <div class="rank {{ $rankClass }}">{{ $idx + 1 }}</div>
                        <div class="flex-grow-1">
                            <strong style="font-size: 13px;">{{ $tv->nopol }}</strong>
                            <br><small class="text-muted">{{ $tv->trx_count }} transaksi</small>
                        </div>
                        <div class="text-end">
                            <strong style="font-size: 13px; color: #0B50A1;">
                                Rp {{ number_format($tv->total_cost ?? 0, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-2 mb-0">Tidak ada data</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Items Replaced --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">
                        <i class="bi bi-tools me-1 text-secondary"></i>
                        Item Paling Sering Diganti
                    </h5>

                    @forelse($topItems as $item)
                    <div class="d-flex justify-content-between align-items-center py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span style="font-size: 13px;">{{ $item->part_name }}</span>
                        <div class="text-end">
                            <span class="badge bg-secondary">{{ $item->total_qty }}x</span>
                            <br><small class="text-muted">
                                Rp {{ number_format($item->total_cost ?? 0, 0, ',', '.') }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-2 mb-0">Tidak ada data</p>
                    @endforelse
                </div>
            </div>

            {{-- Cost Distribution Pie --}}
            <div class="card">
                <div class="card-body pt-3">
                    <h5 class="card-title">Distribusi Biaya per Kendaraan</h5>
                    <canvas id="costPieChart" height="220"></canvas>
                </div>
            </div>

            {{-- Export Card (Prominent) --}}
            <div class="card" style="border: 2px solid #71A62F;">
                <div class="card-body pt-3 text-center">
                    <h5 class="card-title" style="color: #4A7A1C;">
                        <i class="bi bi-download me-1"></i> Export Laporan
                    </h5>
                    <p class="text-muted mb-3" style="font-size: 13px;">
                        Download laporan lengkap termasuk: ringkasan biaya, rincian transaksi, biaya per kendaraan, dan item paling sering diganti.
                    </p>

                    <div class="d-grid gap-2">
                        <a href="{{ route('superadmin.financial-report.export', [
                                'month' => request('month', now()->format('Y-m')),
                                'format' => 'excel'
                            ]) }}"
                           class="btn btn-success">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                            Download Excel (.xlsx)
                        </a>
                        <a href="{{ route('superadmin.financial-report.export', [
                                'month' => request('month', now()->format('Y-m')),
                                'format' => 'pdf'
                            ]) }}"
                           class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i>
                            Download PDF
                        </a>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Periode: {{ $currentMonthLabel }}
                        @if(request('vehicle_id'))
                            | Kendaraan: {{ $vehicles->firstWhere('id', request('vehicle_id'))?->consumerial_code ?? '-' }}
                        @endif
                    </small>
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
// ══════════════════════════════════════════
// Cost Trend (6 bulan)
// ══════════════════════════════════════════
const trendCtx = document.getElementById('costTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($trendLabels ?? []) !!},
        datasets: [
            {
                label: 'Biaya Service',
                data: {!! json_encode($trendCosts ?? []) !!},
                backgroundColor: 'rgba(11,80,161,0.7)',
                borderColor: '#0B50A1',
                borderWidth: 1,
                borderRadius: 4,
                order: 2,
            },
            {
                label: 'Jumlah Transaksi',
                data: {!! json_encode($trendCounts ?? []) !!},
                type: 'line',
                borderColor: '#D8CE3A',
                backgroundColor: 'rgba(255,193,7,0.1)',
                pointBackgroundColor: '#D8CE3A',
                pointRadius: 5,
                yAxisID: 'y1',
                order: 1,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'Rp ' + (v / 1000000).toFixed(1) + 'jt' },
                title: { display: true, text: 'Biaya (Rp)' }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'Jumlah' },
                ticks: { stepSize: 1 }
            }
        },
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        if (ctx.dataset.yAxisID === 'y1') return ctx.dataset.label + ': ' + ctx.parsed.y;
                        return ctx.dataset.label + ': Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});

// ══════════════════════════════════════════
// Cost by Vehicle (Horizontal Bar)
// ══════════════════════════════════════════
const vehCtx = document.getElementById('vehicleCostChart').getContext('2d');
new Chart(vehCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($vehicleCostLabels ?? []) !!},
        datasets: [{
            label: 'Biaya',
            data: {!! json_encode($vehicleCostValues ?? []) !!},
            backgroundColor: [
                '#0B50A1', '#2eca6e', '#ff771d', '#0B50A1',
                '#6f42c1', '#ED1B2F', '#D8CE3A', '#20c997',
                '#fd7e14', '#6610f2'
            ],
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: { ticks: { callback: v => 'Rp ' + (v / 1000000).toFixed(1) + 'jt' } }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.parsed.x.toLocaleString('id-ID')
                }
            }
        }
    }
});

// ══════════════════════════════════════════
// Cost Distribution Pie
// ══════════════════════════════════════════
const pieCtx = document.getElementById('costPieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($vehicleCostLabels ?? []) !!},
        datasets: [{
            data: {!! json_encode($vehicleCostValues ?? []) !!},
            backgroundColor: [
                '#0B50A1', '#2eca6e', '#ff771d', '#0B50A1',
                '#6f42c1', '#ED1B2F', '#D8CE3A', '#20c997',
                '#fd7e14', '#6610f2'
            ],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 10 } } },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                        return ctx.label + ': Rp ' + ctx.parsed.toLocaleString('id-ID') + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});
</script>

@endsection
