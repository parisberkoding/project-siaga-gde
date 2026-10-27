@extends('templates.main')

@section('title', $title)

@section('content')
@php
    // Label & warna badge status permohonan BBM.
    $bbmStatusLabel = fn ($status) => match ($status) {
        'pending' => 'Menunggu Approval',
        'pending_vehicle_approval' => 'Menunggu Kendaraan',
        'approved' => 'Disetujui',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        default => ucfirst((string) $status),
    };

    $bbmStatusBadge = fn ($status) => match ($status) {
        'pending', 'pending_vehicle_approval' => 'gde-badge--yellow',
        'approved' => 'gde-badge--blue',
        'rejected' => 'gde-badge--red',
        default => 'gde-badge--gray',
    };
@endphp

<div class="module-dash">

  <div class="module-dash__head">
    <div class="module-dash__identity">
      <span class="module-dash__icon"><i class="bi bi-person-badge"></i></span>
      <div>
        <span class="module-dash__eyebrow">Ruang Kerja Driver</span>
        <h1>Dashboard Driver</h1>
        <p>
          Ringkasan pekerjaan Anda sendiri: permohonan BBM, perjalanan dinas &amp; LPJ,
          serta pengecekan kendaraan. Hanya data milik akun Anda yang ditampilkan —
          dashboard analitik seluruh unit tetap ada di sisi superadmin/GA.
        </p>
      </div>
    </div>

    <div class="module-dash__actions">
      @if (Auth::user()->hasPermission('bbm.request'))
        <a href="{{ route('requester-index') }}" class="gde-btn gde-btn--primary gde-btn--sm">
          <i class="bi bi-plus-circle"></i> Permohonan BBM
        </a>
      @endif
      @if (Auth::user()->hasPermission('spd.create'))
        <a href="{{ route('requester.surat.create') }}" class="gde-btn gde-btn--outline-primary gde-btn--sm">
          <i class="bi bi-envelope-paper"></i> Surat Tugas
        </a>
      @endif
      @if (Auth::user()->hasPermission('vehicle.acknowledge'))
        <a href="{{ route('requester.checking-acknowledgement.index') }}" class="gde-btn gde-btn--outline-primary gde-btn--sm">
          <i class="bi bi-clipboard-check"></i> Pengecekan
        </a>
      @endif
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-fuel-pump"></i></span>
      <div>
        <div class="gde-stat__label">Permohonan BBM Aktif</div>
        <div class="gde-stat__value">{{ number_format($bbmStats['aktif']) }}</div>
        <div class="gde-stat__hint">
          {{ number_format($bbmStats['menunggu']) }} menunggu &middot;
          {{ number_format($bbmStats['disetujui']) }} disetujui
        </div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-signpost-2"></i></span>
      <div>
        <div class="gde-stat__label">SPD Berjalan</div>
        <div class="gde-stat__value">{{ number_format($spdBerjalanCount) }}</div>
        <div class="gde-stat__hint">Surat disetujui yang belum punya LPJ</div>
      </div>
    </div>

    <div class="gde-stat {{ $spdTerlambatCount > 0 ? 'gde-stat--yellow' : '' }}">
      <span class="gde-stat__icon"><i class="bi bi-hourglass-split"></i></span>
      <div>
        <div class="gde-stat__label">LPJ Lewat Tenggat</div>
        <div class="gde-stat__value">{{ number_format($spdTerlambatCount) }}</div>
        <div class="gde-stat__hint">Form tetap terbuka &mdash; segera dilaporkan</div>
      </div>
    </div>

    <div class="gde-stat {{ $ackPendingCount > 0 ? 'gde-stat--red' : '' }}">
      <span class="gde-stat__icon"><i class="bi bi-clipboard2-pulse"></i></span>
      <div>
        <div class="gde-stat__label">Pengecekan Menunggu</div>
        <div class="gde-stat__value">{{ number_format($ackPendingCount) }}</div>
        <div class="gde-stat__hint">Perlu acknowledge Anda</div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-fuel-pump"></i> Permohonan BBM Saya</div>
          <p class="gde-card__sub">
            Permohonan yang masih berjalan &middot; {{ number_format($bbmStats['bulan_ini']) }} dibuat bulan ini
          </p>
        </div>
        @if (Auth::user()->hasPermission('bbm.view'))
          <a href="{{ route('requester-history') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Riwayat</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($bbmAktif as $bbm)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $bbm->request_number }}</span>
                <span class="dash-list__meta">
                  {{ $bbm->request_date?->translatedFormat('d M Y') }}
                  @if ($bbm->vehicle)
                    &middot; {{ $bbm->vehicle->consumerial_name }}
                  @endif
                  @if ($bbm->bill_amounts)
                    &middot; Rp {{ number_format((float) $bbm->bill_amounts, 0, ',', '.') }}
                  @endif
                </span>
              </div>
              <span class="gde-badge {{ $bbmStatusBadge($bbm->status) }}">{{ $bbmStatusLabel($bbm->status) }}</span>
              <a href="{{ route('requester-show', $bbm->id) }}" class="gde-btn gde-btn--ghost gde-btn--xs" title="Lihat detail">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada permohonan BBM yang sedang berjalan.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-signpost-2"></i> SPD Berjalan</div>
          <p class="gde-card__sub">Perjalanan dinas yang belum Anda laporkan (LPJ)</p>
        </div>
        <a href="{{ route('requester.spd.berjalan') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($spdBerjalan as $surat)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $surat->nomor_surat }}</span>
                <span class="dash-list__meta">
                  {{ \Illuminate\Support\Str::limit($surat->tempat_tujuan, 34) }}
                  &middot; {{ $surat->tanggal_berangkat?->translatedFormat('d M') }}
                  &ndash; {{ $surat->tanggal_kembali?->translatedFormat('d M Y') }}
                </span>
              </div>

              @if ($surat->isLpjDeadlineOverdue())
                <span class="gde-badge gde-badge--red">Telat {{ $surat->lpj_overdue_days }} hari</span>
              @elseif ($surat->lpj_deadline_efektif)
                <span class="gde-badge gde-badge--yellow">
                  Tenggat {{ $surat->lpj_deadline_efektif->translatedFormat('d M') }}
                </span>
              @else
                <span class="gde-badge gde-badge--gray">Belum ada LPJ</span>
              @endif

              <a href="{{ route('requester.spd.berjalan') }}" class="gde-btn gde-btn--ghost gde-btn--xs" title="Laporkan">
                <i class="bi bi-receipt"></i>
              </a>
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada SPD yang menunggu pelaporan.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-clipboard2-check"></i> Pengecekan Kendaraan</div>
          <p class="gde-card__sub">Hasil pemeriksaan dispatcher yang menunggu acknowledge Anda</p>
        </div>
        @if (Auth::user()->hasPermission('vehicle.acknowledge'))
          <a href="{{ route('requester.checking-acknowledgement.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($ackPending as $check)
            @php
              $vehicleName = $check->vehicleChecking?->vehicle?->consumerial_name;
            @endphp
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $vehicleName ?: 'Kendaraan' }}</span>
                <span class="dash-list__meta">
                  {{ $check->getTypeLabel() }}
                  &middot; Dicek {{ $check->checked_at?->translatedFormat('d M Y H:i') }}
                  @if ($check->dispatcher)
                    oleh {{ $check->dispatcher->nama_lengkap }}
                  @endif
                </span>
              </div>
              <span class="gde-badge gde-badge--red">Belum di-ack</span>
              <a href="{{ route('requester.checking-acknowledgement.show', $check) }}"
                 class="gde-btn gde-btn--ghost gde-btn--xs" title="Acknowledge">
                <i class="bi bi-check2-square"></i>
              </a>
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada pengecekan yang menunggu acknowledge.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-wrench-adjustable"></i> Jadwal Service Saya</div>
          <p class="gde-card__sub">Permohonan penggantian sparepart (VPR) yang di-assign ke Anda</p>
        </div>
        <a href="{{ route('requester.replacement-schedule.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($vprAktif as $vpr)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $vpr->vpr_code }}</span>
                <span class="dash-list__meta">
                  {{ $vpr->nopol ?: ($vpr->vehicle?->consumerial_name ?? 'Kendaraan') }}
                  @if ($vpr->scheduled_date)
                    &middot; {{ $vpr->scheduled_date->translatedFormat('d M Y') }}
                  @endif
                  @if ($vpr->service_location)
                    &middot; {{ $vpr->service_location }}
                  @endif
                </span>
              </div>
              <span class="gde-badge {{ $vpr->status === 'in_service' ? 'gde-badge--blue' : 'gde-badge--yellow' }}">
                {{ ucwords(str_replace('_', ' ', $vpr->status)) }}
              </span>
              <a href="{{ route('requester.replacement-schedule.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs" title="Detail jadwal">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          @empty
            <div class="dash-list__empty">Tidak ada jadwal service untuk Anda saat ini.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
