@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard RBAC & Hak Akses',
    'description' => 'Kesehatan konfigurasi role, permission, dan akun yang belum memiliki role.',
    'actions' => [
      ['label' => 'Kelola Role', 'route' => 'superadmin.rbac.index', 'icon' => 'bi bi-shield-lock', 'permission' => 'rbac.roles', 'class' => 'gde-btn--primary'],
      ['label' => 'Tambah Role', 'route' => 'superadmin.rbac.create', 'icon' => 'bi bi-plus-circle', 'permission' => 'rbac.roles'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-shield-lock"></i></span>
      <div>
        <div class="gde-stat__label">Total Role</div>
        <div class="gde-stat__value">{{ number_format($totalRole) }}</div>
        <div class="gde-stat__hint">Role aktif & sistem</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-key"></i></span>
      <div>
        <div class="gde-stat__label">Total Permission</div>
        <div class="gde-stat__value">{{ number_format($totalPermission) }}</div>
        <div class="gde-stat__hint">Hak akses terdaftar</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-collection"></i></span>
      <div>
        <div class="gde-stat__label">Grup Permission</div>
        <div class="gde-stat__value">{{ number_format($totalGroup) }}</div>
        <div class="gde-stat__hint">Pengelompokan modul</div>
      </div>
    </div>

    <div class="gde-stat {{ $userTanpaRole > 0 ? 'gde-stat--red' : '' }}">
      <span class="gde-stat__icon"><i class="bi bi-person-exclamation"></i></span>
      <div>
        <div class="gde-stat__label">User Tanpa Role</div>
        <div class="gde-stat__value">{{ number_format($userTanpaRole) }}</div>
        <div class="gde-stat__hint">Perlu ditinjau</div>
      </div>
    </div>
  </div>

  <div class="gde-card module-dash__card">
    <div class="gde-card__head">
      <div>
        <div class="gde-card__title"><i class="bi bi-table"></i> Ringkasan Role</div>
        <p class="gde-card__sub">Jumlah pengguna dan permission pada setiap role</p>
      </div>
      @if (Auth::user()->hasPermission('rbac.roles'))
        <a href="{{ route('superadmin.rbac.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Kelola Role</a>
      @endif
    </div>
    <div class="gde-card__body">
      @if ($roles->isEmpty())
        <div class="dash-list__empty">Belum ada role.</div>
      @else
        <div class="gde-table-wrap">
          <table class="gde-table gde-table--compact">
            <thead>
              <tr>
                <th>Role</th>
                <th>Kode</th>
                <th>Pengguna</th>
                <th>Permission</th>
                <th>Unit</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($roles as $role)
                <tr>
                  <td><strong>{{ $role->name }}</strong></td>
                  <td><code>{{ $role->code }}</code></td>
                  <td>{{ number_format($role->users_count) }}</td>
                  <td>{{ number_format($role->permissions_count) }}</td>
                  <td>{{ $role->unit?->name ?? 'Global' }}</td>
                  <td class="text-end">
                    @if (Auth::user()->hasPermission('rbac.roles'))
                      <a href="{{ route('superadmin.rbac.edit', $role->id) }}" class="gde-btn gde-btn--ghost gde-btn--xs">
                        <i class="bi bi-pencil"></i> Ubah
                      </a>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <div class="gde-card module-dash__card">
    <div class="gde-card__head">
      <div>
        <div class="gde-card__title"><i class="bi bi-clock-history"></i> Role Terbaru</div>
        <p class="gde-card__sub">5 role yang terakhir dibuat</p>
      </div>
    </div>
    <div class="gde-card__body">
      <div class="dash-list">
        @forelse ($roleTerbaru as $role)
          <div class="dash-list__row">
            <div class="dash-list__main">
              <span class="dash-list__title">{{ $role->name }}</span>
              <span class="dash-list__meta">{{ $role->description ?: 'Tanpa deskripsi' }}</span>
            </div>
            <span class="dash-list__value">{{ $role->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
          </div>
        @empty
          <div class="dash-list__empty">Belum ada role.</div>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection
