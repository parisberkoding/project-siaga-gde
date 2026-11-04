@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard Manajemen Pengguna',
    'description' => 'Jumlah akun, sebaran role, dan pengguna terbaru pada unit bisnis Anda.',
    'actions' => [
      ['label' => 'Daftar User', 'route' => 'users', 'icon' => 'bi bi-people', 'permission' => 'users.view', 'class' => 'gde-btn--primary'],
      ['label' => 'Tambah User', 'route' => 'users.create', 'icon' => 'bi bi-person-plus', 'permission' => 'users.create'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-people"></i></span>
      <div>
        <div class="gde-stat__label">Total Pengguna</div>
        <div class="gde-stat__value">{{ number_format($totalUser) }}</div>
        <div class="gde-stat__hint">Akun terdaftar</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-person-check"></i></span>
      <div>
        <div class="gde-stat__label">Akun Aktif</div>
        <div class="gde-stat__value">{{ number_format($userAktif) }}</div>
        <div class="gde-stat__hint">Boleh login</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--red">
      <span class="gde-stat__icon"><i class="bi bi-person-dash"></i></span>
      <div>
        <div class="gde-stat__label">Akun Nonaktif</div>
        <div class="gde-stat__value">{{ number_format($userNonaktif) }}</div>
        <div class="gde-stat__hint">Dinonaktifkan sementara</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-person-plus"></i></span>
      <div>
        <div class="gde-stat__label">Pengguna Baru</div>
        <div class="gde-stat__value">{{ number_format($userBaru) }}</div>
        <div class="gde-stat__hint">Dibuat bulan ini</div>
      </div>
    </div>
  </div>

  <div class="module-dash__grid module-dash__grid--2">
    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-shield-check"></i> Sebaran Role</div>
          <p class="gde-card__sub">Jumlah pengguna per role</p>
        </div>
      </div>
      <div class="gde-card__body">
        @php $maxRole = max(1, $roleCounts->max('users_count') ?? 1); @endphp
        @forelse ($roleCounts as $role)
          <div class="dash-bar">
            <div class="dash-bar__meta">
              <span>{{ $role->name }}</span>
              <b>{{ number_format($role->users_count) }}</b>
            </div>
            <div class="gde-progress gde-progress--blue">
              <span class="gde-progress__bar" style="width: {{ round($role->users_count / $maxRole * 100) }}%"></span>
            </div>
          </div>
        @empty
          <div class="dash-list__empty">Belum ada role.</div>
        @endforelse
      </div>
    </div>

    <div class="gde-card module-dash__card">
      <div class="gde-card__head">
        <div>
          <div class="gde-card__title"><i class="bi bi-diagram-3"></i> Sebaran Unit</div>
          <p class="gde-card__sub">Jumlah pengguna per unit bisnis</p>
        </div>
      </div>
      <div class="gde-card__body">
        @php $maxUnit = max(1, $unitCounts->max('users_count') ?? 1); @endphp
        @forelse ($unitCounts as $unit)
          <div class="dash-bar">
            <div class="dash-bar__meta">
              <span>{{ $unit->name }}{{ $unit->is_central ? ' (Pusat)' : '' }}</span>
              <b>{{ number_format($unit->users_count) }}</b>
            </div>
            <div class="gde-progress">
              <span class="gde-progress__bar" style="width: {{ round($unit->users_count / $maxUnit * 100) }}%"></span>
            </div>
          </div>
        @empty
          <div class="dash-list__empty">Belum ada unit.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="gde-card module-dash__card">
    <div class="gde-card__head">
      <div>
        <div class="gde-card__title"><i class="bi bi-person-lines-fill"></i> Pengguna Terbaru</div>
        <p class="gde-card__sub">6 akun terakhir yang dibuat</p>
      </div>
      @if (Auth::user()->hasPermission('users.view'))
        <a href="{{ route('users') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Lihat semua</a>
      @endif
    </div>
    <div class="gde-card__body">
      @if ($userTerbaru->isEmpty())
        <div class="dash-list__empty">Belum ada pengguna.</div>
      @else
        <div class="gde-table-wrap">
          <table class="gde-table gde-table--compact">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Unit</th>
                <th>Role</th>
                <th>Status</th>
                <th>Dibuat</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($userTerbaru as $row)
                <tr>
                  <td><strong>{{ $row->nama_lengkap }}</strong></td>
                  <td>{{ $row->username }}</td>
                  <td>{{ $row->unit?->name ?? 'Belum ada unit' }}</td>
                  <td>{{ $row->roles->pluck('name')->join(', ') ?: $row->role }}</td>
                  <td>
                    <span class="gde-badge {{ $row->is_active ? '' : 'gde-badge--red' }}">
                      {{ $row->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td>{{ $row->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection
