<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  @php
    $user   = Auth::user();
    $pcodes = $user->getPermissionCodes();

    // Role efektif: gabungkan legacy role column + RBAC roles.
    // Dipakai hanya untuk memilih dashboard default & membedakan varian
    // menu yang memang berbagi permission sama (mis. driver vs dispatcher).
    $normalizeRole = function ($code) {
        return match ($code) {
            'admin', 'admin_bbm', 'general_affairs' => 'general_affairs',
            'requester'                             => 'requester',
            'driver'                                => 'driver',
            'non_ga_staff', 'view_only'             => 'view_only',
            default                                 => $code,
        };
    };
    $roleCodes = collect(array_merge([$user->role], $user->roles()->pluck('code')->all()))
        ->map($normalizeRole)->unique()->values()->all();

    // ── Sidebar sepenuhnya berbasis PERMISSION (dynamic RBAC) ──
    // Item tampil HANYA jika user punya salah satu permission-nya. Tidak ada
    // lagi whitelist role yang membuat role bawaan melihat menu di luar hak
    // aksesnya. User custom yang dibuat superadmin akan otomatis melihat
    // seluruh menu sesuai permission yang diberikan, dan tidak lebih.
    $showItem = function ($item) use ($pcodes) {
        if (empty($item['perms'])) {
            return true;
        }
        return !empty(array_intersect($item['perms'], $pcodes));
    };

    $routeIsAny = function ($patterns) {
        foreach ((array) $patterns as $pat) {
            if (request()->routeIs($pat)) return true;
        }
        return false;
    };

    // Active resolver untuk child: cocokkan route + (opsional) query filter.
    // query value null berarti parameter HARUS kosong (dipakai item "semua").
    $childIsActive = function ($item) use ($routeIsAny) {
        if (! $routeIsAny($item['active'] ?? [])) {
            return false;
        }
        if (array_key_exists('query', $item)) {
            foreach ($item['query'] as $qk => $qv) {
                if ($qv === null) {
                    if (request()->filled($qk)) return false;
                } elseif ((string) request($qk) !== (string) $qv) {
                    return false;
                }
            }
        }
        return true;
    };

    // Dashboard: berbeda per role (role-based agar user bawaan tidak bingung).
    $dashboardItem = match (true) {
        in_array('dispatcher', $roleCodes, true)      => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'dispatcher.dispatcher-dashboard', 'active' => ['dispatcher.dispatcher-dashboard']],
        in_array('superadmin', $roleCodes, true)      => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'superadmin.dashboard.bisnis', 'active' => ['admin-dashboard', 'superadmin.dashboard.bisnis']],
        in_array('manager', $roleCodes, true)         => ['label' => 'Dashboard Bisnis', 'icon' => 'bi bi-graph-up', 'route' => 'superadmin.dashboard.bisnis', 'active' => ['superadmin.dashboard.bisnis']],
        in_array('general_affairs', $roleCodes, true) => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'admin-dashboard', 'active' => ['admin-dashboard']],
        in_array('requester', $roleCodes, true),
        in_array('driver', $roleCodes, true),
        in_array('view_only', $roleCodes, true),
        in_array('vendor', $roleCodes, true)          => null,
        // Role custom: tampilkan dashboard bila punya permission dashboard.view.
        in_array('dashboard.view', $pcodes, true)     => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'route' => 'superadmin.dashboard.bisnis', 'active' => ['superadmin.dashboard.bisnis']],
        default                                       => null,
    };

    $menuDef = array_values(array_filter([
        $dashboardItem ? ['label' => $dashboardItem['label'], 'icon' => $dashboardItem['icon'], 'route' => $dashboardItem['route'], 'active' => $dashboardItem['active']] : null,

        // ── BBM / Permohonan ──
        ['label' => 'Permohonan BBM', 'icon' => 'bi bi-fuel-pump', 'id' => 'bbm-nav', 'active' => ['requester-index', 'requester-history', 'admin-dashboard', 'superadmin.permohonan*'], 'children' => [
            ['label' => 'Permohonan Baru', 'icon' => 'bi bi-circle', 'route' => 'requester-index', 'active' => ['requester-index'], 'perms' => ['bbm.request']],
            ['label' => 'Riwayat Permohonan', 'icon' => 'bi bi-circle', 'route' => 'requester-history', 'active' => ['requester-history'], 'perms' => ['bbm.view', 'bbm.report']],
            ['label' => 'Approval BBM', 'icon' => 'bi bi-circle', 'route' => 'admin-dashboard', 'active' => ['admin-dashboard'], 'perms' => ['bbm.approve']],
            ['label' => 'Kelola Permohonan', 'icon' => 'bi bi-circle', 'route' => 'superadmin.permohonan', 'active' => ['superadmin.permohonan*'], 'perms' => ['bbm.approve']],
        ]],

        // ── Pelaporan BBM & SPD (driver → lapor pembelian / OCR struk) ──
        ['label' => 'Pelaporan BBM & SPD', 'icon' => 'bi bi-journal-text', 'route' => 'pelaporan.index', 'active' => ['pelaporan.*'], 'perms' => ['pelaporan.view', 'pelaporan.create']],

        // ── SPD Management (anak di-filter per permission) ──
        ['label' => 'SPD Management', 'icon' => 'bi bi-envelope-paper', 'id' => 'spd-nav', 'active' => ['requester.surat.*', 'requester.lpj.*', 'surat.superadmin.*', 'perpanjangan.superadmin.*', 'lpj.superadmin.*'], 'children' => [
            // Driver / Requester
            ['label' => 'Buat Surat Tugas', 'icon' => 'bi bi-circle', 'route' => 'requester.surat.create', 'active' => ['requester.surat.create'], 'perms' => ['spd.create']],
            ['label' => 'Daftar Surat Tugas', 'icon' => 'bi bi-circle', 'route' => 'requester.surat.index', 'active' => ['requester.surat.index', 'requester.surat.show'], 'perms' => ['spd.view']],
            ['label' => 'Laporan SPD (LPJ)', 'icon' => 'bi bi-circle', 'route' => 'requester.lpj.index', 'active' => ['requester.lpj.*'], 'perms' => ['lpj.create', 'lpj.view']],
            // Admin / Superadmin
            ['label' => 'Buat Surat (Shortcut)', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.create-shortcut', 'active' => ['surat.superadmin.create-shortcut', 'surat.superadmin.store-shortcut'], 'perms' => ['spd.manage']],
            ['label' => 'Validasi Surat Tugas', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.index', 'active' => ['surat.superadmin.index', 'surat.superadmin.show'], 'perms' => ['spd.manage']],
            ['label' => 'Perpanjangan (ETD)', 'icon' => 'bi bi-circle', 'route' => 'perpanjangan.superadmin.index', 'active' => ['perpanjangan.superadmin.*'], 'perms' => ['spd.update']],
            ['label' => 'Validasi Laporan', 'icon' => 'bi bi-circle', 'route' => 'lpj.superadmin.index', 'active' => ['lpj.superadmin.*'], 'perms' => ['lpj.validate']],
            ['label' => 'Laporan SPD', 'icon' => 'bi bi-circle', 'route' => 'surat.superadmin.report', 'active' => ['surat.superadmin.report'], 'perms' => ['spd.manage']],
        ]],

        // ── Master Data - Kendaraan & Alat ──
        ['label' => 'Kendaraan & Alat', 'icon' => 'bi bi-truck', 'id' => 'vehicles-nav', 'active' => ['vehicles', 'vehicles.create', 'superadmin.vehicle-activation*'], 'children' => [
            ['label' => 'Daftar Kendaraan & Alat', 'icon' => 'bi bi-circle', 'route' => 'vehicles', 'params' => ['tab' => 'list'], 'active' => ['vehicles'], 'perms' => ['vehicle.view']],
            ['label' => 'Tambah Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'vehicles.create', 'active' => ['vehicles.create'], 'perms' => ['vehicle.create']],
            ['label' => 'Aktivasi Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'superadmin.vehicle-activation', 'active' => ['superadmin.vehicle-activation*'], 'perms' => ['vehicle.approve']],
        ]],

        // ── Manajemen User ──
        ['label' => 'Pengguna', 'icon' => 'bi bi-people', 'id' => 'users-nav', 'active' => ['users', 'users.create'], 'children' => [
            ['label' => 'Daftar User', 'icon' => 'bi bi-circle', 'route' => 'users', 'params' => ['tab' => 'list'], 'active' => ['users'], 'perms' => ['users.view']],
            ['label' => 'Tambah User', 'icon' => 'bi bi-circle', 'route' => 'users.create', 'active' => ['users.create'], 'perms' => ['users.create']],
        ]],

        // ── RBAC & Hak Akses ──
        ['label' => 'Role & Permission', 'icon' => 'bi bi-shield-lock', 'route' => 'superadmin.rbac.index', 'active' => ['superadmin.rbac.*'], 'perms' => ['rbac.roles']],

        // ── Pengaturan & Data Master ──
        ['label' => 'Pengaturan & Data Master', 'icon' => 'bi bi-gear', 'id' => 'master-nav', 'active' => ['master-gasoline.*', 'superadmin.units.*', 'superadmin.sites.*'], 'children' => [
            ['label' => 'Harga BBM (Master Gasoline)', 'icon' => 'bi bi-fuel-pump', 'route' => 'master-gasoline.index', 'active' => ['master-gasoline.*'], 'perms' => ['settings.view']],
            ['label' => 'Unit Bisnis', 'icon' => 'bi bi-diagram-3', 'route' => 'superadmin.units.index', 'active' => ['superadmin.units.*'], 'perms' => ['settings.manage']],
            ['label' => 'Site / Lokasi', 'icon' => 'bi bi-geo-alt', 'route' => 'superadmin.sites.index', 'active' => ['superadmin.sites.*'], 'perms' => ['settings.manage']],
        ]],

        // ── Scanner Kendaraan (dispatcher + admin read-only) ──
        ['label' => 'Scanner Kendaraan', 'icon' => 'bi bi-upc-scan', 'id' => 'dispatcher-scanner-nav', 'active' => ['dispatcher.scan-vehicle*', 'dispatcher.dispatcher-scanner*'], 'children' => [
            ['label' => 'Scan Riwayat Kendaraan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.scan-vehicle', 'active' => ['dispatcher.scan-vehicle*'], 'perms' => ['vehicle.scan', 'vehicle.checking']],
            ['label' => 'Scan untuk Periksa', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.dispatcher-scanner', 'active' => ['dispatcher.dispatcher-scanner*'], 'perms' => ['vehicle.checking']],
        ]],

        // ── Pemeriksaan Kendaraan (dispatcher) ──
        ['label' => 'Pemeriksaan Kendaraan', 'icon' => 'bi bi-calendar-check', 'id' => 'dispatcher-check-nav', 'active' => ['dispatcher.daily-check.*', 'dispatcher.weekly-check.*', 'dispatcher.monthly-check.*', 'dispatcher.body-check.*'], 'children' => [
            ['label' => 'Periksa Harian', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.daily-check.index', 'active' => ['dispatcher.daily-check.*'], 'perms' => ['vehicle.checking']],
            ['label' => 'Periksa Mingguan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.weekly-check.index', 'active' => ['dispatcher.weekly-check.*'], 'perms' => ['vehicle.checking']],
            ['label' => 'Periksa Bulanan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.monthly-check.index', 'active' => ['dispatcher.monthly-check.*'], 'perms' => ['vehicle.checking']],
            ['label' => 'Periksa Body', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.body-check.index', 'active' => ['dispatcher.body-check.*'], 'perms' => ['vehicle.checking']],
        ]],

        // ── Penggantian & Perbaikan (dispatcher) ──
        ['label' => 'Permohonan Penggantian', 'icon' => 'bi bi-tools', 'route' => 'dispatcher.replacement.index', 'active' => ['dispatcher.replacement.*'], 'perms' => ['vehicle.checking'], 'badge' => fn () => \App\Models\VehiclePartsReplacement::forDispatcher($user->id)->pending()->count(), 'badgeClass' => 'bg-warning text-dark'],

        // ── Riwayat (dispatcher) ──
        ['label' => 'Riwayat & Laporan', 'icon' => 'bi bi-clock-history', 'route' => 'dispatcher.history', 'active' => ['dispatcher.history'], 'perms' => ['vehicle.checking']],

        // ── Pengecekan Kendaraan (driver): hasil + filter acknowledge ──
        ['label' => 'Pemeriksaan Kendaraan', 'icon' => 'bi bi-clipboard-check', 'id' => 'requester-checking-nav', 'active' => ['requester.checking-acknowledgement.*'], 'children' => [
            ['label' => 'Hasil Pemeriksaan', 'icon' => 'bi bi-circle', 'route' => 'requester.checking-acknowledgement.index', 'active' => ['requester.checking-acknowledgement.*'], 'query' => ['type' => null], 'perms' => ['vehicle.acknowledge'], 'badge' => fn () => \App\Models\VehicleCheckingDetail::forDriver($user->id)->unacknowledged()->count(), 'badgeClass' => 'bg-danger'],
            ['label' => 'Pemeriksaan Rutin', 'icon' => 'bi bi-circle', 'route' => 'requester.checking-acknowledgement.index', 'params' => ['type' => 'routine'], 'active' => ['requester.checking-acknowledgement.*'], 'query' => ['type' => 'routine'], 'perms' => ['vehicle.acknowledge']],
            ['label' => 'Body Checking', 'icon' => 'bi bi-circle', 'route' => 'requester.checking-acknowledgement.index', 'params' => ['type' => 'body'], 'active' => ['requester.checking-acknowledgement.*'], 'query' => ['type' => 'body'], 'perms' => ['vehicle.acknowledge']],
        ]],

        // ── Export Form Pengecekan (driver, dispatcher, superadmin) ──
        ['label' => 'Export Form Pengecekan', 'icon' => 'bi bi-file-earmark-excel', 'id' => 'export-check-nav', 'active' => ['dispatcher.export-checking.*', 'dispatcher.export.body-checking-*'], 'children' => [
            ['label' => 'Ekspor Hasil Pemeriksaan', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.export-checking.index', 'active' => ['dispatcher.export-checking.*'], 'perms' => ['vehicle.export']],
            ['label' => 'Export Body Checking', 'icon' => 'bi bi-circle', 'route' => 'dispatcher.export.body-checking-index', 'active' => ['dispatcher.export.body-checking-*'], 'perms' => ['vehicle.export']],
        ]],

        // ── Pengecekan & Service (admin/superadmin: VPR, service, monitoring) ──
        ['label' => 'Pengecekan & Service', 'icon' => 'bi bi-wrench-adjustable', 'id' => 'staff-check-nav', 'active' => ['admin.replacement-approval.*', 'admin.service-scheduling.*', 'admin.transaction-monitoring.*'], 'children' => [
            ['label' => 'Permohonan VPR', 'icon' => 'bi bi-circle', 'route' => 'admin.replacement-approval.index', 'active' => ['admin.replacement-approval.*'], 'perms' => ['vehicle.sparepart'], 'badge' => fn () => \App\Models\VehiclePartsReplacement::pending()->count(), 'badgeClass' => 'bg-danger'],
            ['label' => 'Jadwal Service', 'icon' => 'bi bi-circle', 'route' => 'admin.service-scheduling.index', 'active' => ['admin.service-scheduling.*'], 'perms' => ['vehicle.sparepart']],
            ['label' => 'Monitoring Transaksi', 'icon' => 'bi bi-circle', 'route' => 'admin.transaction-monitoring.index', 'active' => ['admin.transaction-monitoring.*'], 'perms' => ['vehicle.sparepart']],
        ]],

        // ── Lunch Management ──
        ['label' => 'Lunch Management', 'icon' => 'bi bi-egg-fried', 'id' => 'lunch-nav', 'active' => ['lunch.*'], 'children' => [
            ['label' => 'Dashboard Lunch', 'icon' => 'bi bi-circle', 'route' => 'lunch.dashboard', 'active' => ['lunch.dashboard'], 'perms' => ['lunch.view']],
            ['label' => 'Employee Default', 'icon' => 'bi bi-circle', 'route' => 'lunch.employee-defaults.index', 'active' => ['lunch.employee-defaults.*'], 'perms' => ['lunch.manage']],
            ['label' => 'Analytics', 'icon' => 'bi bi-circle', 'route' => 'lunch.analytics.index', 'active' => ['lunch.analytics.*'], 'perms' => ['lunch.view']],
            ['label' => 'Guest Reservation', 'icon' => 'bi bi-circle', 'route' => 'lunch.guests.calendar', 'active' => ['lunch.guests.*'], 'perms' => ['lunch.view']],
            ['label' => 'Lunch Forecast', 'icon' => 'bi bi-circle', 'route' => 'lunch.forecasts.index', 'active' => ['lunch.forecasts.*'], 'perms' => ['lunch.view']],
            ['label' => 'Lunch Distribution', 'icon' => 'bi bi-circle', 'route' => 'lunch.distributions.index', 'active' => ['lunch.distributions.*'], 'perms' => ['lunch.view']],
            ['label' => 'Lunch Transaction', 'icon' => 'bi bi-circle', 'route' => 'lunch.transactions.index', 'active' => ['lunch.transactions.*'], 'perms' => ['lunch.view']],
            ['label' => 'Export Vendor', 'icon' => 'bi bi-circle', 'route' => 'lunch.export-form', 'active' => ['lunch.export-form'], 'perms' => ['lunch.export']],
            ['label' => 'Booking Mandiri', 'icon' => 'bi bi-circle', 'route' => 'lunch.self-booking.index', 'active' => ['lunch.self-booking.*'], 'perms' => ['lunch.view']],
        ]],

        // ── Cetak Laporan BBM (admin/superadmin) ──
        ['label' => 'Cetak Laporan BBM', 'icon' => 'bi bi-printer', 'route' => 'superadmin.print-report', 'active' => ['superadmin.print-report*'], 'perms' => ['bbm.export']],

        // ── Laporan & Analisis (superadmin) ──
        ['label' => 'Laporan & Analisis', 'icon' => 'bi bi-graph-up-arrow', 'id' => 'laporan-nav', 'active' => ['trip-analysis*', 'superadmin.financial-report.*', 'superadmin.transaction-report.*', 'superadmin.vehicle-health.*', 'ai-assistant.*'], 'children' => [
            ['label' => 'Analisis Trip & Efisiensi BBM', 'icon' => 'bi bi-circle', 'route' => 'superadmin.trip-analysis.index', 'active' => ['trip-analysis*'], 'perms' => ['vehicle.report']],
            ['label' => 'Lap. Keuangan Service', 'icon' => 'bi bi-circle', 'route' => 'superadmin.financial-report.index', 'active' => ['superadmin.financial-report.*'], 'perms' => ['vehicle.report']],
            ['label' => 'Lap. Transaksi Sparepart', 'icon' => 'bi bi-circle', 'route' => 'superadmin.transaction-report.index', 'active' => ['superadmin.transaction-report.*'], 'perms' => ['vehicle.report']],
            ['label' => 'Vehicle Health', 'icon' => 'bi bi-circle', 'route' => 'superadmin.vehicle-health.index', 'active' => ['superadmin.vehicle-health.*'], 'perms' => ['vehicle.report']],
            ['label' => 'AI Assistant (Beta)', 'icon' => 'bi bi-circle', 'route' => 'ai-assistant.index', 'active' => ['ai-assistant.*'], 'perms' => ['ai.view']],
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
                     class="{{ $childIsActive($k) ? 'active' : '' }}">
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
