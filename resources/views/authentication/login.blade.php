<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>{{ $title }}</title>
  <meta content="Masuk ke SIAGA — Sistem Informasi Administrasi General Affairs PT Geo Dipa Energi (Persero)" name="description">

  <!-- Favicons -->
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">

  <!-- Ikon (lokal, tanpa CDN) -->
  <link href="{{ asset('gde-ui-templates/icons/bootstrap-icons.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — halaman berdiri sendiri: tanpa shell aplikasi & tanpa jembatan NiceAdmin -->
  @include('partials.gde-head', ['gdeApp' => false, 'gdeCompat' => false])
</head>

<body>

  <main class="gde-auth">

    {{-- ── Panel merek ── --}}
    <section class="gde-auth__brand" aria-label="Tentang SIAGA">
      @include('partials.geothermal-scene')

      <div class="gde-auth__brand-top">
        <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo.png') }}" alt="Geo Dipa Energi">
        <span>PT Geo Dipa Energi (Persero)</span>
      </div>

      <div class="gde-auth__intro">
        <span class="gde-auth__eyebrow">SIAGA</span>
        <h1 class="gde-auth__headline">Layanan General Affairs,<br><em>satu pintu.</em></h1>
        <p class="gde-auth__lead">
          Sistem Informasi Administrasi General Affairs — permohonan, persetujuan,
          hingga pelaporan dalam satu platform.
        </p>
        <ul class="gde-auth__modules">
          <li><i class="bi bi-fuel-pump"></i> BBM</li>
          <li><i class="bi bi-briefcase"></i> Perjalanan Dinas</li>
          <li><i class="bi bi-car-front"></i> Kendaraan</li>
          <li><i class="bi bi-cup-hot"></i> Makan Siang</li>
        </ul>
      </div>

      <div class="gde-auth__brand-foot">&copy; {{ date('Y') }} PT Geo Dipa Energi (Persero)</div>
    </section>

    {{-- ── Panel formulir ── --}}
    <section class="gde-auth__panel">
      <div class="gde-auth__card">

        <h2 class="gde-auth__title">Selamat datang</h2>
        <p class="gde-auth__sub">Masuk dengan akun SIAGA Anda untuk melanjutkan.</p>

        <!-- Alert Messages untuk error umum dan success -->
        @if(session('success'))
          <div class="gde-alert gde-alert--success" role="status">
            <i class="bi bi-check-circle-fill gde-alert__icon"></i>
            <div class="gde-alert__body">{{ session('success') }}</div>
          </div>
        @endif

        @if(session('error'))
          <div class="gde-alert gde-alert--danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill gde-alert__icon"></i>
            <div class="gde-alert__body">{{ session('error') }}</div>
          </div>
        @endif
        <!-- End Alert Messages -->

        {{-- Isian otomatis akun superadmin HANYA di lingkungan lokal (APP_ENV=local)
             untuk mempercepat pengembangan. Jangan pernah aktif di staging/produksi. --}}
        @php
          $devPrefill = app()->isLocal();
          $prefillUsername = old('username', $devPrefill ? 'superadmin_pusat' : '');
        @endphp

        <form action="{{ route('login-process') }}" class="gde-auth__form" method="POST" id="loginForm" novalidate>
          @csrf

          <div>
            <label class="gde-label" for="yourUsername">Username</label>
            <div class="gde-field">
              <i class="bi bi-person"></i>
              <input type="text"
                     name="username"
                     class="gde-input @error('username') is-invalid @enderror"
                     id="yourUsername"
                     placeholder="Masukkan username"
                     value="{{ $prefillUsername }}"
                     autocomplete="username"
                     autocapitalize="none"
                     spellcheck="false"
                     @error('username') aria-invalid="true" aria-describedby="usernameError" @enderror
                     @if($prefillUsername === '') autofocus @endif
                     required>
            </div>
            @error('username')
              <div class="gde-error" id="usernameError"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="gde-label" for="yourPassword">Password</label>
            <div class="gde-field gde-field--action">
              <i class="bi bi-lock"></i>
              <input type="password"
                     name="password"
                     class="gde-input @error('password') is-invalid @enderror"
                     id="yourPassword"
                     placeholder="Masukkan password"
                     autocomplete="current-password"
                     @error('password') aria-invalid="true" aria-describedby="passwordError" @enderror
                     @if($prefillUsername !== '') autofocus @endif
                     required>
              <button type="button" class="gde-field__action" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            @error('password')
              <div class="gde-error" id="passwordError"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
            <div class="gde-auth__capslock" id="capsLockHint" role="status">
              <i class="bi bi-capslock-fill"></i> Caps Lock aktif
            </div>
          </div>

          <div class="gde-auth__row">
            <label class="gde-check">
              <input type="checkbox" name="remember" value="1"> Ingat saya
            </label>
          </div>

          <button class="gde-btn gde-btn--primary gde-btn--block gde-auth__submit" type="submit" id="loginButton">
            <span class="gde-spinner" aria-hidden="true"></span>
            <span class="gde-auth__submit-text">Masuk</span>
            <i class="bi bi-arrow-right"></i>
          </button>

          @if(config('sso.enabled'))
            <div class="gde-auth__divider">atau</div>
            <a href="{{ route('sso.redirect') }}" class="gde-btn gde-btn--outline gde-btn--block gde-auth__submit">
              <i class="bi bi-shield-lock"></i> Masuk dengan SSO Geodipa
            </a>
          @endif
        </form>

        <div class="gde-auth__help">
          <i class="bi bi-info-circle"></i>
          <div>Tidak memiliki hak akses? Hubungi Tim IT untuk mendapatkan hak akses SIAGA.</div>
        </div>

        <a href="{{ route('welcome') }}" class="gde-auth__back">
          <i class="bi bi-arrow-left"></i> Kembali ke halaman utama
        </a>

      </div>
    </section>

  </main>

  <script>
    (function () {
      var passwordField = document.getElementById('yourPassword');
      var toggle = document.getElementById('togglePassword');
      var capsHint = document.getElementById('capsLockHint');
      var form = document.getElementById('loginForm');
      var button = document.getElementById('loginButton');

      // Tampilkan / sembunyikan password
      toggle.addEventListener('click', function () {
        var show = passwordField.type === 'password';
        passwordField.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        toggle.querySelector('.bi').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        passwordField.focus();
      });

      // Peringatan Caps Lock
      function checkCaps(e) {
        if (typeof e.getModifierState === 'function') {
          capsHint.classList.toggle('is-on', e.getModifierState('CapsLock'));
        }
      }
      passwordField.addEventListener('keydown', checkCaps);
      passwordField.addEventListener('keyup', checkCaps);
      passwordField.addEventListener('blur', function () { capsHint.classList.remove('is-on'); });

      // Status memuat + cegah kirim ganda
      form.addEventListener('submit', function () {
        button.classList.add('is-loading');
        button.querySelector('.gde-auth__submit-text').textContent = 'Memproses…';
      });

      // Bila halaman dipulihkan dari bfcache (tombol Back), kembalikan tombol
      window.addEventListener('pageshow', function () {
        button.classList.remove('is-loading');
        button.querySelector('.gde-auth__submit-text').textContent = 'Masuk';
      });
    })();
  </script>

</body>

</html>
