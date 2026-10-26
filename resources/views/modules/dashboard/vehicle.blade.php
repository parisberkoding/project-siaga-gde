@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard Kendaraan & Alat',
    'description' => 'Kesehatan armada, jadwal pengecekan, dan permohonan penggantian sparepart dalam satu ringkasan.',
    'actions' => [
      ['label' => 'Daftar Kendaraan', 'route' => 'vehicles', 'icon' => 'bi bi-truck', 'permission' => 'vehicle.view', 'class' => 'gde-btn--primary'],
      ['label' => 'Mulai Pemeriksaan', 'route' => 'dispatcher.daily-check.index', 'icon' => 'bi bi-clipboard-check', 'permission' => 'vehicle.checking'],
      ['label' => 'Permohonan VPR', 'route' => 'admin.replacement-approval.index', 'icon' => 'bi bi-tools', 'permission' => 'vehicle.sparepart'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-truck"></i></span>
      <div>
        <div class="gde-stat__label">Total Kendaraan & Alat</div>
        <div class="gde-stat__value">{{ number_format($totalKendaraan) }}</div>
        <div class="gde-stat__hint">{{ number_format($kendaraanAktif) }} aktif</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-calendar-x"></i></span>
      <div>
        <div class="gde-stat__label">Jadwal Terlambat</div>
        <div class="gde-stat__value">{{ number_format($jadwalOverdue) }}</div>
        <div class="gde-stat__hint">{{ number_format($jadwalHariIni) }} jatuh tempo hari ini</div>
      </div>
    </div>

    <div class="gde-stat {{ $vprMenunggu > 0 ? 'gde-stat--red' : '' }}">
      <span class="gde-stat__icon"><i class="bi bi-wrench-adjustable"></i></span>
      <div>
        <div class="gde-stat__label">VPR Menunggu</div>
        <div class="gde-stat__value">{{ number_format($vprMenunggu) }}</div>
        <div class="gde-stat__hint">{{ number_format($vprBerjalan) }} sedang diproses</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-clipboard2-check"></i></span>
      <div>
        <div class="gde-stat__label">Pengecekan Bulan Ini</div>
        <div class="gde-stat__value">{{ number_format($cekBulanIni) }}</div>
        <div class="gde-stat__hint">{{ number_format($cekHariIni) }} hari ini &middot; {{ number_format($temuanNg) }} temuan NG</div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-bar-chart-line"></i> Tren Pengecekan</div>
          <p class="gde-card__sub">Jumlah pengecekan kendaraan 6 bulan terakhir</p>
        </div>
      </div>
      <div class="gde-card__body">
        <div class="module-dash__chart"><canvas id="vehicleTrendChart"></canvas></div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-pie-chart"></i> Status VPR</div>
          <p class="gde-card__sub">Permohonan penggantian sparepart</p>
        </div>
      </div>
      <div class="gde-card__body">
        @php $totalVpr = max(1, collect($statusBars)->sum('total')); @endphp
        @foreach ($statusBars as $bar)
          <div class="dash-bar">
            <div class="dash-bar__meta">
              <span>{{ $bar['label'] }}</span>
              <b>{{ number_format($bar['total']) }}</b>
            </div>
            <div class="gde-progress {{ $bar['key'] === 'rejected' ? 'gde-progress--red' : ($bar['key'] === 'pending' ? 'gde-progress--yellow' : 'gde-progress--blue') }}">
              <span class="gde-progress__bar" style="width: {{ round($bar['total'] / $totalVpr * 100) }}%"></span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-calendar-event"></i> Jadwal Terdekat</div>
          <p class="gde-card__sub">Jatuh tempo dalam 7 hari ke depan</p>
        </div>
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($jadwalTerdekat as $schedule)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $schedule->vehicle?->consumerial_code ?? $schedule->schedule_code }}</span>
                <span class="dash-list__meta">{{ $schedule->vehicle?->consumerial_name ?? 'Kendaraan' }} &middot; {{ ucfirst($schedule->check_frequency) }}</span>
              </div>
              <span class="dash-list__value {{ $schedule->next_due_date?->isPast() ? 'text-danger' : '' }}">
                {{ $schedule->next_due_date?->translatedFormat('d M Y') ?? '-' }}
              </span>
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada jadwal dalam 7 hari ke depan.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-tools"></i> VPR Terbaru</div>
          <p class="gde-card__sub">Permohonan penggantian terakhir</p>
        </div>
        @if (Auth::user()->hasPermission('vehicle.sparepart'))
          <a href="{{ route('admin.replacement-approval.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($vprTerbaru as $vpr)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $vpr->vpr_code }}</span>
                <span class="dash-list__meta">
                  {{ $vpr->vehicle?->consumerial_code ?? 'Kendaraan' }}
                  &middot; {{ $vpr->created_at?->translatedFormat('d M Y') }}
                </span>
              </div>
              @php
                $vprBadge = match ($vpr->status) {
                    'pending' => 'gde-badge--yellow',
                    'rejected' => 'gde-badge--red',
                    'completed' => '',
                    default => 'gde-badge--blue',
                };
              @endphp
              <span class="gde-badge {{ $vprBadge }}">{{ $vpr->status_label ?? ucfirst(str_replace('_', ' ', $vpr->status)) }}</span>
              @if (Auth::user()->hasPermission('vehicle.sparepart'))
                <a href="{{ route('admin.replacement-approval.show', $vpr) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                  <i class="bi bi-eye"></i>
                </a>
              @endif
            </div>
          @empty
            <div class="dash-list__empty">Belum ada permohonan penggantian.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('vehicleTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: @json($trend['labels']),
        datasets: [{
          label: 'Pengecekan',
          data: @json($trend['values']),
          borderColor: '#71A62F',
          backgroundColor: 'rgba(141, 198, 63, .18)',
          fill: true,
          tension: .35,
          pointRadius: 3,
          pointBackgroundColor: '#71A62F',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, .18)' } },
          x: { grid: { display: false } },
        },
      },
    });
  });
</script>
@endpush
