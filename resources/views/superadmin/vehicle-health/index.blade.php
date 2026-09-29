@extends('templates.main')

@section('title', 'Kondisi Kendaraan')

@section('content')

<style>
    .health-card {
        border: 1px solid #E4E9F0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .health-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: inherit; transform: translateY(-2px); }

    .health-card .card-top {
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .health-card .card-bottom {
        padding: 8px 16px 12px;
        background: #F8FAFC;
        font-size: 12px;
    }

    .health-score {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 800;
        flex-shrink: 0;
    }
    .health-score.excellent { background: #d1e7dd; color: #4A7A1C; border: 3px solid #71A62F; }
    .health-score.good      { background: #cff4fc; color: #0B50A1; border: 3px solid #0B50A1; }
    .health-score.warning   { background: #fff3cd; color: #997404; border: 3px solid #D8CE3A; }
    .health-score.critical  { background: #f8d7da; color: #C4142A; border: 3px solid #ED1B2F; }

    .mini-bar {
        height: 6px;
        border-radius: 3px;
        background: #E4E9F0;
        overflow: hidden;
        flex-grow: 1;
    }
    .mini-bar .fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    .check-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .check-dot.ok       { background: #71A62F; }
    .check-dot.due      { background: #D8CE3A; }
    .check-dot.overdue  { background: #ED1B2F; }
    .check-dot.none     { background: #E4E9F0; }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #64748B;
        margin-right: 12px;
    }

    .fleet-stat {
        text-align: center;
        padding: 16px;
    }
    .fleet-stat .stat-number {
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
    }
    .fleet-stat .stat-label {
        font-size: 12px;
        color: #64748B;
        margin-top: 4px;
    }
</style>

<div class="pagetitle d-flex flex-wrap align-items-start justify-content-between gap-2">
    <div>
        <h1>Vehicle Health Matrix</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard.bisnis') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Vehicle Health</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->role === 'superadmin')
        <div class="mt-2">
            <a href="{{ route('superadmin.barcode.download-zip') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-file-zip me-1"></i> Download Semua Barcode (ZIP)
            </a>
        </div>
    @endif
</div>

{{-- Fleet Summary --}}
<section class="section">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fleet-stat">
                    <div class="stat-number" style="color: #4A7A1C;">{{ $fleetStats['excellent'] ?? 0 }}</div>
                    <div class="stat-label">Excellent (≥85%)</div>
                    <div class="mini-bar mt-2">
                        <div class="fill" style="width: {{ $fleetStats['total'] > 0 ? round(($fleetStats['excellent'] ?? 0) / $fleetStats['total'] * 100) : 0 }}%; background: #71A62F;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fleet-stat">
                    <div class="stat-number" style="color: #0B50A1;">{{ $fleetStats['good'] ?? 0 }}</div>
                    <div class="stat-label">Good (70-84%)</div>
                    <div class="mini-bar mt-2">
                        <div class="fill" style="width: {{ $fleetStats['total'] > 0 ? round(($fleetStats['good'] ?? 0) / $fleetStats['total'] * 100) : 0 }}%; background: #0B50A1;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fleet-stat">
                    <div class="stat-number" style="color: #D8CE3A;">{{ $fleetStats['warning'] ?? 0 }}</div>
                    <div class="stat-label">Warning (50-69%)</div>
                    <div class="mini-bar mt-2">
                        <div class="fill" style="width: {{ $fleetStats['total'] > 0 ? round(($fleetStats['warning'] ?? 0) / $fleetStats['total'] * 100) : 0 }}%; background: #D8CE3A;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card mb-2">
                <div class="fleet-stat">
                    <div class="stat-number" style="color: #C4142A;">{{ $fleetStats['critical'] ?? 0 }}</div>
                    <div class="stat-label">Critical (&lt;50%)</div>
                    <div class="mini-bar mt-2">
                        <div class="fill" style="width: {{ $fleetStats['total'] > 0 ? round(($fleetStats['critical'] ?? 0) / $fleetStats['total'] * 100) : 0 }}%; background: #ED1B2F;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center pt-3 mb-3">
                        <h5 class="card-title mb-0 pb-0">Kondisi Kendaraan</h5>
                        <div>
                            <div class="legend-item"><span class="check-dot ok"></span> OK / Selesai</div>
                            <div class="legend-item"><span class="check-dot due"></span> Due / Jadwal</div>
                            <div class="legend-item"><span class="check-dot overdue"></span> Overdue</div>
                            <div class="legend-item"><span class="check-dot none"></span> Belum Ada</div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('superadmin.vehicle-health.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Status Kesehatan</label>
                                <select name="health" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="excellent" {{ request('health') == 'excellent' ? 'selected' : '' }}>Excellent (≥85%)</option>
                                    <option value="good" {{ request('health') == 'good' ? 'selected' : '' }}>Good (70-84%)</option>
                                    <option value="warning" {{ request('health') == 'warning' ? 'selected' : '' }}>Warning (50-69%)</option>
                                    <option value="critical" {{ request('health') == 'critical' ? 'selected' : '' }}>Critical (&lt;50%)</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Urutkan</label>
                                <select name="sort" class="form-select form-select-sm">
                                    <option value="score_asc" {{ request('sort') == 'score_asc' ? 'selected' : '' }}>Score Terendah</option>
                                    <option value="score_desc" {{ request('sort') == 'score_desc' ? 'selected' : '' }}>Score Tertinggi</option>
                                    <option value="nopol" {{ request('sort') == 'nopol' ? 'selected' : '' }}>Nopol A-Z</option>
                                    <option value="last_check" {{ request('sort') == 'last_check' ? 'selected' : '' }}>Terakhir Dicek</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                    <a href="{{ route('superadmin.vehicle-health.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Vehicle Grid --}}
                    <div class="row g-3">
                        @forelse($vehicles as $v)
                        @php
                            $score = $v->health_score ?? 0;
                            $scoreClass = 'critical';
                            if ($score >= 85) $scoreClass = 'excellent';
                            elseif ($score >= 70) $scoreClass = 'good';
                            elseif ($score >= 50) $scoreClass = 'warning';

                            $barColor = match($scoreClass) {
                                'excellent' => '#71A62F',
                                'good' => '#0B50A1',
                                'warning' => '#D8CE3A',
                                'critical' => '#ED1B2F',
                            };
                        @endphp
                        <div class="col-lg-4 col-md-6 col-12">
                            <a href="{{ route('superadmin.vehicle-health.show', $v->consumerial_code) }}" class="health-card">
                                <div class="card-top">
                                    <div>
                                        <strong style="font-size: 15px;">{{ $v->consumerial_code }}</strong>
                                        <br><small class="text-muted">{{ $v->consumerial_name ?? '' }}</small>

                                        {{-- Schedule Dots --}}
                                        <div class="d-flex gap-2 mt-2">
                                            @php
                                                $schedules = $v->checkingSchedules ?? collect();
                                                $freqMap = $schedules->keyBy('check_frequency');
                                            @endphp
                                            @foreach(['daily' => 'H', 'weekly' => 'M', 'monthly' => 'B', 'body' => 'Bd'] as $freq => $label)
                                                @php
                                                    $sch = $freqMap[$freq] ?? null;
                                                    $dotClass = 'none';
                                                    if ($sch) {
                                                        $dotClass = match($sch->status) {
                                                            'overdue' => 'overdue',
                                                            'due' => 'due',
                                                            'completed' => 'ok',
                                                            default => 'none',
                                                        };
                                                    }
                                                @endphp
                                                <div class="text-center">
                                                    <span class="check-dot {{ $dotClass }}"></span>
                                                    <br><span style="font-size: 9px; color: #64748B;">{{ $label }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Score Circle --}}
                                    <div class="health-score {{ $scoreClass }}">{{ round($score) }}</div>
                                </div>

                                <div class="card-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="mini-bar">
                                            <div class="fill" style="width: {{ $score }}%; background: {{ $barColor }};"></div>
                                        </div>
                                        <span style="font-size: 11px; color: {{ $barColor }}; font-weight: 600;">{{ round($score) }}%</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span>
                                            @if($v->active_replacement_count > 0)
                                                <span class="badge bg-warning text-dark" style="font-size: 10px;">
                                                    <i class="bi bi-tools"></i> {{ $v->active_replacement_count }} VPR aktif
                                                </span>
                                            @endif
                                        </span>
                                        <span class="text-muted">
                                            Cek terakhir: {{ $v->last_checked_at ? $v->last_checked_at->diffForHumans() : 'Belum' }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @empty
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-truck text-muted fs-1"></i>
                            <p class="text-muted mt-2">Tidak ada data kendaraan</p>
                        </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
