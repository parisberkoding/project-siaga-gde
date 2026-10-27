@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard SPD & LPJ',
    'description' => 'Ringkasan surat tugas, perjalanan yang sedang berjalan, dan LPJ yang menunggu tindakan.',
    'actions' => [
      ['label' => 'Buat Surat (Shortcut)', 'route' => 'surat.superadmin.create-shortcut', 'icon' => 'bi bi-plus-circle', 'permission' => 'spd.manage', 'class' => 'gde-btn--primary'],
      ['label' => 'Validasi Surat', 'route' => 'surat.superadmin.index', 'icon' => 'bi bi-check2-square', 'permission' => 'spd.manage'],
      ['label' => 'Validasi LPJ', 'route' => 'lpj.superadmin.index', 'icon' => 'bi bi-receipt', 'permission' => 'lpj.validate'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-envelope-paper"></i></span>
      <div>
        <div class="gde-stat__label">Surat Bulan Ini</div>
        <div class="gde-stat__value">{{ number_format($suratBulanIni) }}</div>
        <div class="gde-stat__hint">Dibuat sejak {{ now()->startOfMonth()->translatedFormat('d M Y') }}</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-hourglass-split"></i></span>
      <div>
        <div class="gde-stat__label">Menunggu Validasi</div>
        <div class="gde-stat__value">{{ number_format($menungguValidasi) }}</div>
        <div class="gde-stat__hint">Surat berstatus pending</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-signpost-2"></i></span>
      <div>
        <div class="gde-stat__label">Perjalanan Berjalan</div>
        <div class="gde-stat__value">{{ number_format($perjalananBerjalan) }}</div>
        <div class="gde-stat__hint">Berangkat s.d. kembali hari ini</div>
      </div>
    </div>

    <div class="gde-stat {{ $lpjMenunggu > 0 ? 'gde-stat--red' : '' }}">
      <span class="gde-stat__icon"><i class="bi bi-receipt-cutoff"></i></span>
      <div>
        <div class="gde-stat__label">LPJ Menunggu</div>
        <div class="gde-stat__value">{{ number_format($lpjMenunggu) }}</div>
        <div class="gde-stat__hint">{{ number_format($lpjTerlambat) }} LPJ terlambat</div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-bar-chart-line"></i> Tren Surat Tugas</div>
          <p class="gde-card__sub">Jumlah surat dibuat dalam 6 bulan terakhir</p>
        </div>
      </div>
      <div class="gde-card__body">
        <div class="module-dash__chart"><canvas id="spdTrendChart"></canvas></div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-pie-chart"></i> Status Surat</div>
          <p class="gde-card__sub">Distribusi seluruh surat pada unit Anda</p>
        </div>
      </div>
      <div class="gde-card__body">
        @php $totalStatus = max(1, collect($statusBars)->sum('total')); @endphp
        @foreach ($statusBars as $bar)
          <div class="dash-bar">
            <div class="dash-bar__meta">
              <span>{{ $bar['label'] }}</span>
              <b>{{ number_format($bar['total']) }}</b>
            </div>
            <div class="gde-progress {{ $bar['key'] === 'rejected' ? 'gde-progress--red' : ($bar['key'] === 'pending' ? 'gde-progress--yellow' : 'gde-progress--blue') }}">
              <span class="gde-progress__bar" style="width: {{ round($bar['total'] / $totalStatus * 100) }}%"></span>
            </div>
          </div>
        @endforeach

        <div class="d-flex flex-wrap gap-2 mt-3">
          <span class="gde-chip"><i class="bi bi-wallet2"></i> Budget disetujui <b>Rp {{ number_format((float) $totalBudget, 0, ',', '.') }}</b></span>
          <span class="gde-chip"><i class="bi bi-arrow-repeat"></i> Perpanjangan pending <b>{{ number_format($perpanjanganMenunggu) }}</b></span>
        </div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-clock-history"></i> Surat Terbaru</div>
          <p class="gde-card__sub">6 surat tugas terakhir</p>
        </div>
        @if (Auth::user()->hasPermission('spd.manage'))
          <a href="{{ route('surat.superadmin.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($suratTerbaru as $surat)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $surat->nomor_surat }}</span>
                <span class="dash-list__meta">
                  {{ $surat->pengemudi_nama }}
                  &middot; {{ $surat->tanggal_berangkat?->translatedFormat('d M Y') }}
                </span>
              </div>
              <span class="gde-badge {{ $surat->status === 'approved' ? '' : ($surat->status === 'pending' ? 'gde-badge--yellow' : ($surat->status === 'rejected' ? 'gde-badge--red' : 'gde-badge--gray')) }}">
                {{ ucfirst($surat->status) }}
              </span>
              @if (Auth::user()->hasPermission('spd.manage'))
                <a href="{{ route('surat.superadmin.show', $surat) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                  <i class="bi bi-eye"></i>
                </a>
              @elseif (Auth::user()->hasPermission('spd.view'))
                <a href="{{ route('requester.surat.show', $surat) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                  <i class="bi bi-eye"></i>
                </a>
              @endif
            </div>
          @empty
            <div class="dash-list__empty">Belum ada surat tugas.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-exclamation-triangle"></i> LPJ Perlu Perhatian</div>
          <p class="gde-card__sub">Menunggu validasi atau melewati tenggat</p>
        </div>
        @if (Auth::user()->hasPermission('lpj.validate'))
          <a href="{{ route('lpj.superadmin.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($lpjPerluPerhatian as $lpj)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $lpj->lpj_number }}</span>
                <span class="dash-list__meta">{{ $lpj->nama }} &middot; {{ $lpj->tanggal_lpj?->translatedFormat('d M Y') }}</span>
              </div>
              @if ($lpj->is_late)
                <span class="gde-badge gde-badge--red">Terlambat {{ $lpj->days_late }} hari</span>
              @else
                <span class="gde-badge gde-badge--yellow">{{ ucfirst($lpj->status) }}</span>
              @endif
              @if (Auth::user()->hasPermission('lpj.validate'))
                <a href="{{ route('lpj.superadmin.show', $lpj) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                  <i class="bi bi-eye"></i>
                </a>
              @elseif (Auth::user()->hasPermission('lpj.view'))
                <a href="{{ route('requester.lpj.show', $lpj) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                  <i class="bi bi-eye"></i>
                </a>
              @endif
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada LPJ yang perlu ditindaklanjuti.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  @if ($perjalananAktif->isNotEmpty())
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-geo-alt"></i> Perjalanan Sedang Berjalan</div>
          <p class="gde-card__sub">Pemantauan surat yang sedang aktif hari ini</p>
        </div>
      </div>
      <div class="gde-card__body">
        <div class="gde-table-wrap">
          <table class="gde-table gde-table--compact">
            <thead>
              <tr>
                <th>Nomor Surat</th>
                <th>Pengemudi</th>
                <th>Tujuan</th>
                <th>Berangkat</th>
                <th>Kembali</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($perjalananAktif as $surat)
                <tr>
                  <td><strong>{{ $surat->nomor_surat }}</strong></td>
                  <td>{{ $surat->pengemudi_nama }}</td>
                  <td>{{ \Illuminate\Support\Str::limit($surat->tempat_tujuan, 42) }}</td>
                  <td>{{ $surat->tanggal_berangkat?->translatedFormat('d M Y') }}</td>
                  <td>{{ $surat->tanggal_kembali?->translatedFormat('d M Y') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('spdTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: @json($trend['labels']),
        datasets: [{
          label: 'Surat tugas',
          data: @json($trend['values']),
          backgroundColor: 'rgba(11, 80, 161, .75)',
          borderRadius: 6,
          maxBarThickness: 34,
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
