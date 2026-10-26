@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard Pengaturan & Data Master',
    'description' => 'Kondisi unit bisnis, modul yang aktif, tahap fitur, dan data master yang dipakai aplikasi.',
    'actions' => [
      ['label' => 'Unit Bisnis', 'route' => 'superadmin.units.index', 'icon' => 'bi bi-diagram-3', 'permission' => 'settings.manage', 'class' => 'gde-btn--primary'],
      ['label' => 'Site / Lokasi', 'route' => 'superadmin.sites.index', 'icon' => 'bi bi-geo-alt', 'permission' => 'settings.manage'],
      ['label' => 'Harga BBM', 'route' => 'master-gasoline.index', 'icon' => 'bi bi-fuel-pump', 'permission' => 'settings.view'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-diagram-3"></i></span>
      <div>
        <div class="gde-stat__label">Unit Bisnis</div>
        <div class="gde-stat__value">{{ number_format($totalUnit) }}</div>
        <div class="gde-stat__hint">{{ number_format($totalSite) }} site terdaftar</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-grid-3x3-gap"></i></span>
      <div>
        <div class="gde-stat__label">Modul Aktif</div>
        <div class="gde-stat__value">{{ number_format($totalModul) }}</div>
        <div class="gde-stat__hint">Terdaftar di aplikasi</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-toggle2-on"></i></span>
      <div>
        <div class="gde-stat__label">Fitur Production</div>
        <div class="gde-stat__value">{{ number_format($fiturProduction) }}</div>
        <div class="gde-stat__hint">{{ number_format($fiturDevelopment) }} masih development</div>
      </div>
    </div>

    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-person-badge"></i></span>
      <div>
        <div class="gde-stat__label">Pemetaan SSO</div>
        <div class="gde-stat__value">{{ number_format($totalSsoMapping) }}</div>
        <div class="gde-stat__hint">Jabatan → role (aktif)</div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-grid-3x3-gap"></i> Modul & Akses Unit</div>
          <p class="gde-card__sub">Jumlah unit yang mengaktifkan tiap modul</p>
        </div>
      </div>
      <div class="gde-card__body">
        <div class="gde-table-wrap">
          <table class="gde-table gde-table--compact">
            <thead>
              <tr>
                <th>Modul</th>
                <th>Permission</th>
                <th>Unit Aktif</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($modules as $row)
                <tr>
                  <td>
                    <strong><i class="{{ $row->icon ?: 'bi bi-grid' }} me-1"></i> {{ $row->name }}</strong>
                  </td>
                  <td>{{ number_format($row->permissions_count) }}</td>
                  <td>{{ number_format($row->units_count) }}</td>
                  <td>
                    @if ($row->is_always_on)
                      <span class="gde-badge gde-badge--blue">Selalu aktif</span>
                    @elseif ($row->is_active)
                      <span class="gde-badge">Aktif</span>
                    @else
                      <span class="gde-badge gde-badge--gray">Nonaktif</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-diagram-3"></i> Unit & Site</div>
          <p class="gde-card__sub">Jumlah site dan pengguna per unit</p>
        </div>
        @if (Auth::user()->hasPermission('settings.manage'))
          <a href="{{ route('superadmin.units.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Kelola</a>
        @endif
      </div>
      <div class="gde-card__body">
        <div class="dash-list">
          @forelse ($units as $unit)
            <div class="dash-list__row">
              <div class="dash-list__main">
                <span class="dash-list__title">{{ $unit->name }}</span>
                <span class="dash-list__meta">{{ $unit->code }}{{ $unit->is_central ? ' · Pusat' : '' }}</span>
              </div>
              <span class="gde-badge gde-badge--blue">{{ number_format($unit->sites_count) }} site</span>
              <span class="gde-badge gde-badge--gray">{{ number_format($unit->users_count) }} user</span>
            </div>
          @empty
            <div class="dash-list__empty">Belum ada unit.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="gde-card module-dash__card">
    <div class="gde-card__head">
      <div>
        <div class="gde-card__title"><i class="bi bi-fuel-pump"></i> Master Harga BBM</div>
        <p class="gde-card__sub">Data harga bahan bakar terakhir yang tersinkron</p>
      </div>
      @if (Auth::user()->hasPermission('settings.view'))
        <a href="{{ route('master-gasoline.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat Master</a>
      @endif
    </div>
    <div class="gde-card__body">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="gde-chip"><i class="bi bi-database"></i> <b>{{ number_format($totalHargaBbm) }}</b> baris harga</span>
        <span class="gde-chip">
          <i class="bi bi-clock-history"></i>
          Update terakhir:
          <b>{{ $hargaTerakhir?->updated_at?->translatedFormat('d M Y H:i') ?? 'belum ada' }}</b>
        </span>
      </div>
    </div>
  </div>

</div>
@endsection
