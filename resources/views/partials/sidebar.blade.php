<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  @php
    $user       = Auth::user();
    $pcodes     = $user->getPermissionCodes();

    // Role efektif: gabungkan legacy role column + RBAC roles, dinormalisasi ke kode RBAC
    // (selaras dengan CheckRole::LEGACY_TO_RBAC)
    $normalizeRole = function ($code) {
        return match ($code) {
            'admin', 'admin_bbm', 'general_affairs' => 'general_affairs',
            'driver', 'requester'                   => 'requester',
            'non_ga_staff', 'view_only'             => 'view_only',
            default                                 => $code,
        };
    };
    $roleCodes = collect(array_merge([$user->role], $user->roles()->pluck('code')->all()))
        ->map($normalizeRole)->unique()->values()->all();

    // Item tampil jika: role ada di whitelist (jika diisi) DAN user punya salah satu permission (jika diisi)
    $showItem = function ($item) use ($roleCodes, $pcodes) {
        if (!empty($item['roles']) && empty(array_intersect($item['roles'], $roleCodes))) {
            return false;
        }
        if (!empty($item['perms']) && empty(array_intersect($item['perms'], $pcodes))) {
            return false;
        }
        return true;
    };

    $routeIsAny = function ($patterns) {
        foreach ($patterns as $pat) {
            if (request()->routeIs($pat)) return true;
        }
        return false;
    };

    // Dashboard: berbeda per role
    $dashboardItem = match (true) {
        in_array('dispatcher', $roleCodes, true)      => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'dispatcher.dispatcher-dashboard', 'active' => ['dispatcher.dispatcher-dashboard']],
        in_array('superadmin', $roleCodes, true)      => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'superadmin.dashboard.bisnis', 'active' => ['admin-dashboard', 'superadmin.dashboard.bisnis']],
        in_array('manager', $roleCodes, true)         => ['label' => 'Dashboard Bisnis', 'icon' => 'bi bi-graph-up', 'route' => 'superadmin.dashboard.bisnis', 'active' => ['superadmin.dashboard.bisnis']],
        in_array('general_affairs', $roleCodes, true) => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'admin-dashboard', 'active' => ['admin-dashboard']],
        default                                       => null,
    };

    // Struktur menu: satu modul = satu item.
    // - 'children' => collapsible dengan chevron (submenu). Dibuka otomatis bila route aktif.
    // - tanpa 'children' => link langsung (tidak ada heading dobel / tumpang tindih).
    $menuDef = array_values(array_filter([
        $dashboardItem ? ['label' => $dashboardItem['label'], 'icon' => $dashboardItem['icon'], 'route' => $dashboardItem['route'], 'active' => $dashboardItem['active']] : null,

        // ── BBM / Permohonan ──
        ['label' => 'BBM / Permohonan', 'icon' => 'bi bi-fuel-pump', 'id' => 'bbm-nav', 'active' => ['requester-index', 'requester-history', 'admin-approval', 'superadmin.permohonan*'], 'children' => [
            ['label' => 'Permohonan Baru', 'icon' => 'bi bi-circle', 'route' => 'requester-index', 'active' => ['requester-index'], 'roles' => ['requester'], 'perms' => ['bbm.request']],
            ['label' => 'Riwayat Permohonan', 'icon' => 'bi bi-circle', 'route' => 'requester-history', 'active' => ['requester-history'], 'roles' => ['requester'], 'perms' => ['bbm.report']],
            ['label' => 'Approval BBM', 'icon' => 'bi bi-circle', 'route' => 'admin-approval', 'active' => ['admin-approval'], 'roles' => ['general_affairs'], 'perms' => ['bbm.approve']],
            ['label' => 'Manajemen Permohonan', 'icon' => 'bi bi-circle', 'route' => 'superadmin.permohonan', 'active' => ['superadmin.permohonan*'], 'roles' => ['superadmin'], 'perms' => ['bbm.approve']],
        ]],

        // ── SPD Management (satu modul untuk semua role; anak di-filter per role) ──
        ['label' => 'SPD Management', 'icon' => 'bi bi-envelope-paper', 'id' => 'spd-nav', 'active' => ['requester.surat.*', 'requester.lpj.*', 'surat.superadmin.*', 'perpanjangan.superadmin.*', 'lpj.superadmin.*'], 'children' => [
            ['label' => 'Daftar Surat Tugas', 'icon' => 'bi bi-circle', 'route' => 'requester.surat.index', 'active' => ['requester.surat.index', 'requester.surat.show'], 'roles' => ['requester']],
            ['label' => 'Buat Surat Tugas', 'icon' => 'bi bi-circle', 'route' => 'requester.surat.create', 'active' => ['requester.surat.create'], 'roles' => ['requester']],
            ['label' => 'Laporan Penggantian (LPJ)', 'icon' => 'bi bi-circle', 'route' => 'requester.lpj.index', 'active' => ['requester.lpj.*'], 'roles' => ['requester'], 'perms' => ['lpj.create']],
            ['label' => 'Daftar Surat SPD', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.index', 'active' => ['surat.superadmin.index'], 'perms' => ['spd.view']],
            ['label' => 'Buat Surat (Shortcut)', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.create-shortcut', 'active' => ['surat.superadmin.create-shortcut', 'surat.superadmin.store-shortcut'], 'perms' => ['spd.create']],
            ['label' => 'Perpanjangan (ETD)', 'icon' => 'bi bi-circle', 'route' => 'perpanjangan.superadmin.index', 'active' => ['perpanjangan.superadmin.*'], 'perms' => ['spd.update']],
            ['label' => 'Validasi Laporan', 'icon' => 'bi bi-circle', 'route' => 'lpj.superadmin.index', 'active' => ['lpj.superadmin.*'], 'perms' => ['lpj.validate']],
            ['label' => 'Laporan SPD', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.report', 'active' => ['surat.superadmin.report'], 'perms' => ['spd.view']],
        ]],

        // ── Pelaporan ──
        ['label' => 'Pelaporan BBM & SPD', 'icon' => 'bi bi-journal-text', 'route' => 'pelaporan.index', 'active' => ['pelaporan.*'], 'perms' => ['pelaporan.view']],

        // ── Master Data - Kendaraan & Alat ──
        ['label' => 'Kendaraan & Alat', 'icon' => 'bi bi-truck', 'id' => 'vehicles-nav', 'active' => ['vehicles', 'vehicles.create', 'superadmin.vehicle-activation*', 'superadmin.approval*', 'master-gasoline.*', 'superadmin.units.*'], 'children' => [
            ['label' => 'Daftar Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'vehicles', 'params' => ['tab' => 'list'], 'active' => ['vehicles'], 'perms' => ['vehicle.view']],
            ['label' => 'Tambah Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'vehicles.create', 'active' => ['vehicles.create'], 'perms' => ['vehicle.create']],
            ['label' => 'Aktivasi Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'superadmin.vehicle-activation', 'active' => ['superadmin.vehicle-activation*'], 'roles' => ['superadmin'], 'perms' => ['vehicle.checking']],
            ['label' => 'Kendaraan Baru via Request', 'icon' => 'bi bi-circle', 'route' => 'superadmin.approval', 'active' => ['superadmin.approval*'], 'roles' => ['superadmin'], 'perms' => ['vehicle.approve']],
            ['label' => 'Harga BBM (Master Gasoline)', 'icon' => 'bi bi-fuel-pump', 'route' => 'master-gasoline.index', 'active' => ['master-gasoline.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Unit Bisnis', 'icon' => 'bi bi-diagram-3', 'route' => 'superadmin.units.index', 'active' => ['superadmin.units.*'], 'roles' => ['superadmin']],
            ['label' => 'Site / Lokasi', 'icon' => 'bi bi-geo-alt', 'route' => 'superadmin.sites.index', 'active' => ['superadmin.sites.*'], 'roles' => ['superadmin']],
        ]],

        // ── Manajemen User ──
        ['label' => 'Pengguna', 'icon' => 'bi bi-people', 'id' => 'users-nav', 'active' => ['users', 'users.create'], 'children' => [
            ['label' => 'Daftar User', 'icon' => 'bi bi-circle', 'route' => 'users', 'params' => ['tab' => 'list'], 'active' => ['users'], 'perms' => ['users.view']],
            ['label' => 'Tambah User', 'icon' => 'bi bi-circle', 'route' => 'users.create', 'active' => ['users.create'], 'perms' => ['users.create']],
        ]],

        // ── RBAC & Hak Akses ──
        ['label' => 'Role & Permission', 'icon' => 'bi bi-shield-lock', 'route' => 'superadmin.rbac.index', 'active' => ['superadmin.rbac.*'], 'roles' => ['superadmin'], 'perms' => ['rbac.view']],

        // ── Pengecekan Kendaraan (requester) ──
        ['label' => 'Pengecekan & Service', 'icon' => 'bi bi-clipboard-check', 'id' => 'requester-checking-nav', 'active' => ['requester.checking-acknowledgement.*', 'requester.replacement-schedule.*', 'requester.service-transaction.*'], 'children' => [
            ['label' => 'Hasil Pengecekan', 'icon' => 'bi bi-circle', 'route' => 'requester.checking-acknowledgement.index', 'active' => ['requester.checking-acknowledgement.*'], 'roles' => ['requester'], 'badge' => fn () => \App\Models\VehicleCheckingDetail::forDriver($user->id)->unacknowledged()->count(), 'badgeClass' => 'bg-danger'],
            ['label' => 'Jadwal Service', 'icon' => 'bi bi-circle', 'route' => 'requester.replacement-schedule.index', 'active' => ['requester.replacement-schedule.*'], 'roles' => ['requester'], 'badge' => fn () => \App\Models\VehiclePartsReplacement::where(function ($q) use ($user) {
                $q->where('desired_driver_id', $user->id)->orWhere('last_driver_id', $user->id);
            })->whereIn('status', ['approved', 'scheduled', 'in_service'])->doesntHave('transaction')->count(), 'badgeClass' => 'bg-warning text-dark'],
            ['label' => 'Riwayat Transaksi Service', 'icon' => 'bi bi-circle', 'route' => 'requester.service-transaction.index', 'active' => ['requester.service-transaction.*'], 'roles' => ['requester']],
        ]],

        // ── Scanner Kendaraan (dispatcher) ──
        ['label' => 'Scanner Kendaraan', 'icon' => 'bi bi-upc-scan', 'id' => 'dispatcher-scanner-nav', 'active' => ['dispatcher.scan-vehicle*', 'dispatcher.dispatcher-scanner*'], 'children' => [
            ['label' => 'Scan Riwayat Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.scan-vehicle', 'active' => ['dispatcher.scan-vehicle*'], 'roles' => ['dispatcher']],
            ['label' => 'Scan untuk Periksa', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.dispatcher-scanner', 'active' => ['dispatcher.dispatcher-scanner*'], 'roles' => ['dispatcher']],
        ]],

        // ── Pemeriksaan Kendaraan (dispatcher) ──
        ['label' => 'Pemeriksaan Kendaraan', 'icon' => 'bi bi-calendar-check', 'id' => 'dispatcher-check-nav', 'active' => ['dispatcher.daily-check.*', 'dispatcher.weekly-check.*', 'dispatcher.monthly-check.*', 'dispatcher.body-check.*'], 'children' => [
            ['label' => 'Periksa Harian', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.daily-check.index', 'active' => ['dispatcher.daily-check.*'], 'roles' => ['dispatcher']],
            ['label' => 'Periksa Mingguan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.weekly-check.index', 'active' => ['dispatcher.weekly-check.*'], 'roles' => ['dispatcher']],
            ['label' => 'Periksa Bulanan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.monthly-check.index', 'active' => ['dispatcher.monthly-check.*'], 'roles' => ['dispatcher']],
            ['label' => 'Periksa Body', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.body-check.index', 'active' => ['dispatcher.body-check.*'], 'roles' => ['dispatcher']],
        ]],

        // ── Penggantian & Perbaikan (dispatcher) ──
        ['label' => 'Permohonan Penggantian', 'icon' => 'bi bi-tools', 'route' => 'dispatcher.replacement.index', 'active' => ['dispatcher.replacement.*'], 'roles' => ['dispatcher'], 'badge' => fn () => \App\Models\VehiclePartsReplacement::forDispatcher($user->id)->pending()->count(), 'badgeClass' => 'bg-warning text-dark'],

        // ── Laporan (dispatcher) ──
        ['label' => 'Riwayat & Laporan', 'icon' => 'bi bi-clock-history', 'route' => 'dispatcher.history', 'active' => ['dispatcher.history'], 'roles' => ['dispatcher']],

        // ── Pengecekan & Sparepart (admin/superadmin) ──
        ['label' => 'Pengecekan & Service', 'icon' => 'bi bi-wrench-adjustable', 'id' => 'staff-check-nav', 'active' => ['dispatcher.scan-vehicle*', 'dispatcher.dispatcher-scanner*', 'admin.sparepart-request.*', 'admin.service-scheduling.*', 'admin.transaction-monitoring.*', 'superadmin.vehicle-health.*'], 'children' => [
            ['label' => 'Scan Riwayat Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.scan-vehicle', 'active' => ['dispatcher.scan-vehicle*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Scan untuk Periksa', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.dispatcher-scanner', 'active' => ['dispatcher.dispatcher-scanner*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Permohonan Sparepart', 'icon' => 'bi bi-circle', 'route' => 'admin.sparepart-request.index', 'active' => ['admin.sparepart-request.*'], 'roles' => ['general_affairs', 'superadmin'], 'badge' => fn () => \App\Models\VehiclePartsReplacement::pending()->count(), 'badgeClass' => 'bg-danger'],
            ['label' => 'Jadwal Service', 'icon' => 'bi bi-circle', 'route' => 'admin.service-scheduling.index', 'active' => ['admin.service-scheduling.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Monitoring Transaksi', 'icon' => 'bi bi-circle', 'route' => 'admin.transaction-monitoring.index', 'active' => ['admin.transaction-monitoring.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Vehicle Health', 'icon' => 'bi bi-circle', 'route' => 'superadmin.vehicle-health.index', 'active' => ['superadmin.vehicle-health.*'], 'roles' => ['superadmin']],
        ]],

        // ── Lunch Management (satu modul, dropdown) ──
        ['label' => 'Lunch Management', 'icon' => 'bi bi-egg-fried', 'id' => 'lunch-nav', 'active' => ['lunch.*'], 'children' => [
            ['label' => 'Dashboard Lunch', 'icon' => 'bi bi-circle', 'route' => 'lunch.dashboard', 'active' => ['lunch.dashboard'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Employee Default', 'icon' => 'bi bi-circle', 'route' => 'lunch.employee-defaults.index', 'active' => ['lunch.employee-defaults.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Guest Reservation', 'icon' => 'bi bi-circle', 'route' => 'lunch.guests.calendar', 'active' => ['lunch.guests.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Lunch Forecast', 'icon' => 'bi bi-circle', 'route' => 'lunch.forecasts.index', 'active' => ['lunch.forecasts.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Lunch Distribution', 'icon' => 'bi bi-circle', 'route' => 'lunch.distributions.index', 'active' => ['lunch.distributions.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Lunch Transactions', 'icon' => 'bi bi-circle', 'route' => 'lunch.transactions.index', 'active' => ['lunch.transactions.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Analytics', 'icon' => 'bi bi-circle', 'route' => 'lunch.analytics.index', 'active' => ['lunch.analytics.*'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'QR Lokasi', 'icon' => 'bi bi-circle', 'route' => 'lunch.qr-codes', 'active' => ['lunch.qr-codes'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Export Vendor', 'icon' => 'bi bi-circle', 'route' => 'lunch.export-form', 'active' => ['lunch.export-form'], 'roles' => ['general_affairs', 'superadmin']],
            ['label' => 'Booking Mandiri', 'icon' => 'bi bi-circle', 'route' => 'lunch.self-booking.index', 'active' => ['lunch.self-booking.*'], 'roles' => ['general_affairs', 'superadmin']],
        ]],

        // ── Cetak Laporan BBM (admin) ──
        ['label' => 'Cetak Laporan BBM', 'icon' => 'bi bi-printer', 'route' => 'superadmin.print-report', 'active' => ['superadmin.print-report*'], 'roles' => ['general_affairs']],

        // ── Laporan & Analisis (superadmin) ──
        ['label' => 'Laporan & Analisis', 'icon' => 'bi bi-graph-up-arrow', 'id' => 'laporan-nav', 'active' => ['trip-analysis*', 'superadmin.financial-report.*', 'superadmin.transaction-report.*', 'superadmin.print-report*', 'ai-assistant.*'], 'children' => [
            ['label' => 'Analisis Trip & Efisiensi BBM', 'icon' => 'bi bi-circle', 'route' => 'superadmin.trip-analysis.index', 'active' => ['trip-analysis*'], 'roles' => ['superadmin']],
            ['label' => 'Lap. Keuangan Service', 'icon' => 'bi bi-circle', 'route' => 'superadmin.financial-report.index', 'active' => ['superadmin.financial-report.*'], 'roles' => ['superadmin']],
            ['label' => 'Lap. Transaksi Sparepart', 'icon' => 'bi bi-circle', 'route' => 'superadmin.transaction-report.index', 'active' => ['superadmin.transaction-report.*'], 'roles' => ['superadmin']],
            ['label' => 'Cetak Laporan BBM', 'icon' => 'bi bi-circle', 'route' => 'superadmin.print-report', 'active' => ['superadmin.print-report*'], 'roles' => ['superadmin']],
            ['label' => 'AI Assistant (Beta)', 'icon' => 'bi bi-circle', 'route' => 'ai-assistant.index', 'active' => ['ai-assistant.*'], 'roles' => ['superadmin']],
        ]],
    ]));
  @endphp

  <ul class="sidebar-nav" id="sidebar-nav">

    @foreach ($menuDef as $item)
      @if (!empty($item['children']))
        @php
          $kids = array_values(array_filter($item['children'], $showItem));
          $open = $routeIsAny($item['active']);
        @endphp
        @if (!empty($kids))
          <li class="nav-item">
            <a class="nav-link {{ $open ? '' : 'collapsed' }}"
               data-bs-toggle="collapse"
               data-bs-target="#{{ $item['id'] }}"
               aria-expanded="{{ $open ? 'true' : 'false' }}"
               href="#">
              <i class="{{ $item['icon'] }}"></i>
              <span>{{ $item['label'] }}</span>
              <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="{{ $item['id'] }}" class="nav-content collapse {{ $open ? 'show' : '' }}">
              @foreach ($kids as $k)
                <li>
                  <a href="{{ !empty($k['params']) ? route($k['route'], $k['params']) : route($k['route']) }}"
                     class="{{ $routeIsAny($k['active']) ? 'active' : '' }}">
                    <i class="{{ $k['icon'] }}"></i><span>{{ $k['label'] }}</span>
                    @if (!empty($k['badge']))
                      @php $badgeValue = $k['badge'](); @endphp
                      @if ($badgeValue > 0)
                        <span class="badge {{ $k['badgeClass'] ?? 'bg-danger' }} rounded-pill ms-auto">{{ $badgeValue }}</span>
                      @endif
                    @endif
                  </a>
                </li>
              @endforeach
            </ul>
          </li>
        @endif
      @else
        @if ($showItem($item))
          <li class="nav-item">
            <a class="nav-link {{ $routeIsAny($item['active']) ? '' : 'collapsed' }}"
               href="{{ !empty($item['params']) ? route($item['route'], $item['params']) : route($item['route']) }}">
              <i class="{{ $item['icon'] }}"></i>
              <span>{{ $item['label'] }}</span>
              @if (!empty($item['badge']))
                @php $badgeValue = $item['badge'](); @endphp
                @if ($badgeValue > 0)
                  <span class="badge {{ $item['badgeClass'] ?? 'bg-danger' }} rounded-pill ms-auto">{{ $badgeValue }}</span>
                @endif
              @endif
            </a>
          </li>
        @endif
      @endif
    @endforeach

    {{-- ── Profil (semua role) ── --}}
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('profile') ? '' : 'collapsed' }}"
         href="{{ route('profile') }}">
        <i class="bi bi-person-circle"></i>
        <span>Profil Saya</span>
      </a>
    </li>

  </ul>

</aside><!-- End Sidebar -->

<script>
  // Submenu aktif dibuka langsung saat render (class "show" sudah ditulis
  // server-side). Fallback ini hanya untuk keselamatan bila route pattern
  // aktif tidak terdeteksi — tanpa animasi ulang agar tidak terjadi
  // layout jump saat user sedang berada di dalam modul tersebut.
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar .nav-content').forEach(function(submenu) {
      if (submenu.querySelector('.active') && !submenu.classList.contains('show')) {
        submenu.classList.add('show');
        var toggle = submenu.previousElementSibling;
        if (toggle) {
          toggle.classList.remove('collapsed');
          toggle.setAttribute('aria-expanded', 'true');
        }
      }
    });
  });
</script>
