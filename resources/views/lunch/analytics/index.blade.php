@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Lunch Analytics</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Analytics</li>
        </ol>
    </nav>
</div>

<section class="section">

    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" action="{{ route('lunch.analytics.index') }}" class="d-flex gap-2">
                <select name="days" class="form-select" onchange="this.form.submit()">
                    @foreach([7, 14, 30, 60] as $option)
                        <option value="{{ $option }}" @selected($days == $option)>{{ $option }} hari terakhir</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Learning progress --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold text-success mb-0">{{ $learning['resolved'] }}</h4>
                <small class="text-muted">Transaksi Dikenali</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold {{ $learning['unresolved'] > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ $learning['unresolved'] }}</h4>
                <small class="text-muted">Belum Dikenali</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold text-primary mb-0">{{ $learning['avgConfidence'] ?: '—' }}</h4>
                <small class="text-muted">Rata-rata Confidence</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body py-2">
                <small class="text-muted d-block mb-1">Metode resolusi:</small>
                @forelse($learning['byMethod'] as $method => $total)
                    <span class="badge bg-light text-dark border me-1">{{ $method }}: {{ $total }}</span>
                @empty
                    <span class="text-muted small">Belum ada transaksi.</span>
                @endforelse
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Akurasi trend --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Akurasi Forecast Harian</h5>
                    <canvas id="accuracyChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- Variance per site --}}
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Forecast vs Aktual per Site</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Site</th><th class="text-center">Forecast</th><th class="text-center">Aktual</th><th class="text-center">Variance</th></tr></thead>
                            <tbody>
                                @foreach($variance as $v)
                                <tr>
                                    <td>{{ $v['site'] }}</td>
                                    <td class="text-center">{{ $v['forecast'] }}</td>
                                    <td class="text-center">{{ $v['actual'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $v['variance'] == 0 ? 'success' : ($v['variance'] > 0 ? 'info' : 'warning') }}">
                                            {{ $v['variance'] > 0 ? '+' : '' }}{{ $v['variance'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Site trend --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Tren Kehadiran per Site</h5>
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- Top override --}}
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top Manual Override</h5>
                    @if($topOverrides->isEmpty())
                        <p class="text-muted mb-0">Belum ada manual override pada periode ini.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($topOverrides as $o)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ $o->employee?->employee_name ?? '-' }}
                                <span class="badge bg-primary rounded-pill">{{ $o->total }}x</span>
                            </li>
                            @endforeach
                        </ul>
                        <small class="text-muted">Sering di-override = kandidat perbaikan default site.</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@push('scripts')
<script>
const accuracyTrend = @json($accuracyTrend);
const siteTrend     = @json($siteTrend);
const siteCodes     = @json($sites->pluck('code'));

new Chart(document.getElementById('accuracyChart'), {
    type: 'line',
    data: {
        labels: accuracyTrend.map(r => r.date),
        datasets: [{
            label: 'Akurasi (%)',
            data: accuracyTrend.map(r => r.accuracy),
            borderColor: '#4154f1',
            backgroundColor: 'rgba(65, 84, 241, .1)',
            tension: .3,
            spanGaps: true,
            fill: true,
        }],
    },
    options: { scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } },
});

const palette = ['#4154f1', '#2eca6a', '#ff771d', '#e03444'];
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: siteTrend.map(r => r.date),
        datasets: siteCodes.map((code, i) => ({
            label: code,
            data: siteTrend.map(r => r[code] ?? 0),
            backgroundColor: palette[i % palette.length],
            stack: 'total',
        })),
    },
    options: { scales: { x: { stacked: true }, y: { stacked: true } } },
});
</script>
@endpush
