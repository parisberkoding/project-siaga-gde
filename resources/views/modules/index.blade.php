@extends('templates.main')

@php
  // Halaman All Modules adalah "rumah" lintas modul: sidebar modul tidak
  // ditampilkan supaya user memilih modul secara sadar lebih dulu.
  $hideSidebar = true;
@endphp

@section('content')
<div class="modules-home">

  <div class="modules-home__head">
    <div>
      <span class="gde-badge gde-badge--blue modules-home__eyebrow">
        <i class="bi bi-grid-1x2 me-1"></i> Pusat Kendali
      </span>
      <h1>All Modules</h1>
      <p>
        Selamat datang, <strong>{{ $user->nama_lengkap }}</strong>.
        Pilih satu modul untuk mulai bekerja. Sidebar dan menu akan mengikuti
        modul yang Anda pilih.
      </p>
    </div>

    <div class="modules-home__account">
      <div class="modules-home__account-row">
        <span class="modules-home__account-label">Unit</span>
        <strong>{{ $unit?->name ?? 'Unregistered' }}</strong>
      </div>
      <div class="modules-home__account-row">
        <span class="modules-home__account-label">Hak Akses</span>
        <strong>{{ $isSuperadmin ? 'Superaddmin' : 'Unit' }}</strong>
      </div>
      <div class="modules-home__account-actions">
        <a href="{{ route('profile') }}" class="gde-btn gde-btn--outline gde-btn--sm">
          <i class="bi bi-person-circle"></i> Profil Saya
        </a>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="gde-btn gde-btn--ghost gde-btn--sm">
            <i class="bi bi-box-arrow-right"></i> Keluar
          </button>
        </form>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="gde-alert gde-alert--success modules-home__alert">
      <span class="gde-alert__icon"><i class="bi bi-check-circle"></i></span>
      <div class="gde-alert__body">{{ session('success') }}</div>
    </div>
  @endif

  @if (session('warning'))
    <div class="gde-alert gde-alert--warning modules-home__alert">
      <span class="gde-alert__icon"><i class="bi bi-exclamation-triangle"></i></span>
      <div class="gde-alert__body">{{ session('warning') }}</div>
    </div>
  @endif

  @if (session('error'))
    <div class="gde-alert gde-alert--danger modules-home__alert">
      <span class="gde-alert__icon"><i class="bi bi-x-circle"></i></span>
      <div class="gde-alert__body">{{ session('error') }}</div>
    </div>
  @endif

  @if ($modules->isEmpty())
    <div class="gde-card modules-home__empty">
      <div class="gde-card__body">
        <div class="gde-empty">
          <i class="bi bi-inboxes"></i>
          <h3>Belum ada modul yang bisa dibuka</h3>
          <p>
            Akun Anda belum memiliki permission pada modul mana pun, atau semua
            modul untuk unit Anda sedang dinonaktifkan. Hubungi Superadmin Pusat
            untuk penyesuaian hak akses.
          </p>
          <a href="{{ route('profile') }}" class="gde-btn gde-btn--primary">
            <i class="bi bi-person-circle"></i> Buka Profil
          </a>
        </div>
      </div>
    </div>
  @else
    <div class="modules-home__hint">
      <i class="bi bi-info-circle"></i>
      Anda bekerja di dalam satu modul pada satu waktu. Untuk berpindah modul,
      kembali ke halaman <strong> Modul Siaga </strong> ini.
    </div>

    <div class="modules-home__grid">
      @foreach ($modules as $module)
        @php
          // Variasi warna aksen kartu, berulang tiap 4 modul.
          $tone = ['blue', 'green', 'yellow', 'red'][$loop->index % 4];
        @endphp

        <form method="POST" action="{{ route('modules.activate', $module) }}" class="modules-card-form">
          @csrf
          <button type="submit" class="modules-card modules-card--{{ $tone }}" title="Buka modul {{ $module->name }}">
            <span class="modules-card__icon">
              <i class="{{ $module->icon ?: 'bi bi-grid' }}"></i>
            </span>
            <span class="modules-card__content">
              <span class="modules-card__title">{{ $module->name }}</span>
              <span class="modules-card__foot">
                <span class="modules-card__cta">
                  Buka Modul <i class="bi bi-arrow-right"></i>
                </span>
                @if ($module->is_always_on)
                  <span class="gde-badge gde-badge--gray">Selalu aktif</span>
                @endif
              </span>
            </span>
          </button>
        </form>
      @endforeach
    </div>
  @endif

  <p class="modules-home__footer">
    <i class="bi bi-shield-check"></i>
    Modul yang tampil sudah disaring berdasarkan permission role Anda dan
    modul yang diaktifkan untuk unit {{ $unit?->name ?? 'Anda' }}.
  </p>

</div>
@endsection
