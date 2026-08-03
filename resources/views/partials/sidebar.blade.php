<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    {{-- ╔══════════════════════════════════════════════════════════╗ --}}
    {{-- ║  REQUESTER / DRIVER                                      ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @if (Auth::user()->role === 'requester')

      <li class="nav-heading">Permohonan BBM</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('requester-index') ? '' : 'collapsed' }}"
           href="{{ route('requester-index') }}">
          <i class="bi bi-fuel-pump"></i>
          <span>Permohonan Baru</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('requester-history') ? '' : 'collapsed' }}"
           href="{{ route('requester-history') }}">
          <i class="bi bi-clock-history"></i>
          <span>Riwayat Permohonan</span>
        </a>
      </li>

      <li class="nav-heading">Surat Perjalanan Dinas</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('requester.surat.*') || request()->routeIs('requester.lpj.*') ? '' : 'collapsed' }}"
           data-bs-target="#requester-spd-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-envelope-paper"></i>
          <span>SPD Management</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="requester-spd-nav"
            class="nav-content collapse {{ request()->routeIs('requester.surat.*') || request()->routeIs('requester.lpj.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('requester.surat.index') }}"
               class="{{ request()->routeIs('requester.surat.index') || request()->routeIs('requester.surat.show') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Daftar Surat Tugas</span>
            </a>
          </li>
          <li>
            <a href="{{ route('requester.surat.create') }}"
               class="{{ request()->routeIs('requester.surat.create') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Buat Surat Tugas</span>
            </a>
          </li>
          <li>
            <a href="{{ route('requester.lpj.create') }}"
               class="{{ request()->routeIs('requester.lpj.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Laporan Penggantian (LPJ)</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">Pengecekan Kendaraan</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('requester.checking-acknowledgement.*') || request()->routeIs('requester.replacement-schedule.*') || request()->routeIs('requester.service-transaction.*') ? '' : 'collapsed' }}"
           data-bs-target="#requester-checking-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-clipboard-check"></i>
          <span>Pengecekan & Service</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="requester-checking-nav"
            class="nav-content collapse {{ request()->routeIs('requester.checking-acknowledgement.*') || request()->routeIs('requester.replacement-schedule.*') || request()->routeIs('requester.service-transaction.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('requester.checking-acknowledgement.index') }}"
               class="{{ request()->routeIs('requester.checking-acknowledgement.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Hasil Pengecekan</span>
              @php
                $pendingAck = \App\Models\VehicleCheckingDetail::forDriver(Auth::user()->id)->unacknowledged()->count();
              @endphp
              @if($pendingAck > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingAck }}</span>
              @endif
            </a>
          </li>
          <li>
            <a href="{{ route('requester.replacement-schedule.index') }}"
               class="{{ request()->routeIs('requester.replacement-schedule.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Jadwal Service</span>
              @php
                $activeVpr = \App\Models\VehiclePartsReplacement::where(function($q) {
                    $q->where('desired_driver_id', Auth::id())->orWhere('last_driver_id', Auth::id());
                })->whereIn('status', ['approved','scheduled','in_service'])->doesntHave('transaction')->count();
              @endphp
              @if($activeVpr > 0)
                <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $activeVpr }}</span>
              @endif
            </a>
          </li>
          <li>
            <a href="{{ route('requester.service-transaction.index') }}"
               class="{{ request()->routeIs('requester.service-transaction.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Riwayat Transaksi Service</span>
            </a>
          </li>
        </ul>
      </li>

    {{-- ╔══════════════════════════════════════════════════════════╗ --}}
    {{-- ║  DISPATCHER                                              ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @elseif (Auth::user()->role === 'dispatcher')

      <li class="nav-heading">Beranda</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.dispatcher-dashboard') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.dispatcher-dashboard') }}">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-heading">Barcode & Scanner</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.scan-vehicle*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.scan-vehicle') }}">
          <i class="bi bi-clock-history"></i>
          <span>Scan Riwayat Kendaraan</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.dispatcher-scanner*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.dispatcher-scanner') }}">
          <i class="bi bi-tools"></i>
          <span>Scan untuk Periksa</span>
        </a>
      </li>

      <li class="nav-heading">Pemeriksaan Kendaraan</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.daily-check.*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.daily-check.index') }}">
          <i class="bi bi-calendar-day"></i>
          <span>Periksa Harian</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.weekly-check.*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.weekly-check.index') }}">
          <i class="bi bi-calendar-week"></i>
          <span>Periksa Mingguan</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.monthly-check.*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.monthly-check.index') }}">
          <i class="bi bi-calendar-month"></i>
          <span>Periksa Bulanan</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.body-check.*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.body-check.index') }}">
          <i class="bi bi-car-front"></i>
          <span>Periksa Body</span>
        </a>
      </li>

      <li class="nav-heading">Penggantian & Perbaikan</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.replacement.*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.replacement.index') }}">
          <i class="bi bi-tools"></i>
          <span>Permohonan Penggantian</span>
          @php
            $pendingVpr = \App\Models\VehiclePartsReplacement::forDispatcher(Auth::id())->pending()->count();
          @endphp
          @if($pendingVpr > 0)
            <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $pendingVpr }}</span>
          @endif
        </a>
      </li>

      <li class="nav-heading">Laporan</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.history') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.history') }}">
          <i class="bi bi-clock-history"></i>
          <span>Riwayat & Laporan</span>
        </a>
      </li>

    {{-- ╔══════════════════════════════════════════════════════════╗ --}}
    {{-- ║                HAK AKSES ADMIN                           ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @elseif (Auth::user()->role === 'admin')

      <li class="nav-heading">Beranda</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin-dashboard') ? '' : 'collapsed' }}"
           href="{{ route('admin-dashboard') }}">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-heading">Approval BBM</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin-approval') ? '' : 'collapsed' }}"
           href="{{ route('admin-approval') }}">
          <i class="bi bi-check2-square"></i>
          <span>Approval BBM</span>
        </a>
      </li>

      <li class="nav-heading">Pengecekan & Sparepart</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.scan-vehicle*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.scan-vehicle') }}">
          <i class="bi bi-clock-history"></i>
          <span>Scan Riwayat Kendaraan</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.dispatcher-scanner*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.dispatcher-scanner') }}">
          <i class="bi bi-tools"></i>
          <span>Scan untuk Periksa</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sparepart-request.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.sparepart-request.index') }}">
          <i class="bi bi-wrench-adjustable"></i>
          <span>Permohonan Sparepart</span>
          @php
            $pendingSparepartAdmin = \App\Models\VehiclePartsReplacement::pending()->count();
          @endphp
          @if($pendingSparepartAdmin > 0)
            <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingSparepartAdmin }}</span>
          @endif
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.service-scheduling.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.service-scheduling.index') }}">
          <i class="bi bi-calendar-week"></i>
          <span>Jadwal Service</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.transaction-monitoring.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.transaction-monitoring.index') }}">
          <i class="bi bi-eye"></i>
          <span>Monitoring Transaksi</span>
        </a>
      </li>

      <li class="nav-heading">Lunch Management</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('lunch.*') ? '' : 'collapsed' }}"
           data-bs-target="#admin-lunch-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-egg-fried"></i>
          <span>Lunch Management</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="admin-lunch-nav"
            class="nav-content collapse {{ request()->routeIs('lunch.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('lunch.dashboard') }}" class="{{ request()->routeIs('lunch.dashboard') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Dashboard Lunch</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.employee-defaults.index') }}" class="{{ request()->routeIs('lunch.employee-defaults.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Employee Default</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.guests.calendar') }}" class="{{ request()->routeIs('lunch.guests.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Guest Reservation</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.forecasts.index') }}" class="{{ request()->routeIs('lunch.forecasts.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Forecast</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.distributions.index') }}" class="{{ request()->routeIs('lunch.distributions.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Distribution</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.transactions.index') }}" class="{{ request()->routeIs('lunch.transactions.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Transactions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.analytics.index') }}" class="{{ request()->routeIs('lunch.analytics.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Analytics</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.qr-codes') }}" class="{{ request()->routeIs('lunch.qr-codes') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>QR Lokasi</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.export-form') }}" class="{{ request()->routeIs('lunch.export-form') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Export Vendor</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.self-booking.index') }}" class="{{ request()->routeIs('lunch.self-booking.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Booking Mandiri</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">SPD Management</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('surat.superadmin.*') || request()->routeIs('perpanjangan.superadmin.*') || request()->routeIs('lpj.superadmin.*') ? '' : 'collapsed' }}"
           data-bs-target="#admin-spd-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-envelope-paper"></i>
          <span>SPD Management</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="admin-spd-nav"
            class="nav-content collapse {{ request()->routeIs('surat.superadmin.*') || request()->routeIs('perpanjangan.superadmin.*') || request()->routeIs('lpj.superadmin.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('surat.superadmin.index') }}"
               class="{{ request()->routeIs('surat.superadmin.index') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Daftar Surat SPD</span>
            </a>
          </li>
          <li>
            <a href="{{ route('surat.superadmin.create-shortcut') }}"
               class="{{ request()->routeIs('surat.superadmin.create-shortcut') || request()->routeIs('surat.superadmin.store-shortcut') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Buat Surat</span>
            </a>
          </li>
          <li>
            <a href="{{ route('perpanjangan.superadmin.index') }}"
               class="{{ request()->routeIs('perpanjangan.superadmin.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Perpanjangan (ETD)</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lpj.superadmin.index') }}"
               class="{{ request()->routeIs('lpj.superadmin.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Validasi Laporan</span>
            </a>
          </li>
          <li>
            <a href="{{ route('surat.superadmin.report') }}"
               class="{{ request()->routeIs('surat.superadmin.report') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Laporan SPD</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">Laporan</li>

      {{-- <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.vehicle-health.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.vehicle-health.index') }}">
          <i class="bi bi-heart-pulse"></i>
          <span>Vehicle Health</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.financial-report.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.financial-report.index') }}">
          <i class="bi bi-currency-dollar"></i>
          <span>Lap. Keuangan Service</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.transaction-report.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.transaction-report.index') }}">
          <i class="bi bi-journal-text"></i>
          <span>Lap. Transaksi Sparepart</span>
        </a>
      </li> --}}

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.print-report*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.print-report') }}">
          <i class="bi bi-printer"></i>
          <span>Cetak Laporan BBM</span>
        </a>
      </li>

    {{-- ╔══════════════════════════════════════════════════════════╗ --}}
    {{-- ║  SUPERADMIN                                              ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @elseif (Auth::user()->role === 'superadmin')

      <li class="nav-heading">Dashboard</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin-dashboard') || request()->routeIs('superadmin.dashboard.bisnis') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.dashboard.bisnis') }}">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-heading">Approval & Permohonan BBM</li>

      {{-- <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin-approval') ? '' : 'collapsed' }}"
           href="{{ route('admin-approval') }}">
          <i class="bi bi-check2-square"></i>
          <span>Approval BBM</span>
        </a>
      </li> --}}

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.permohonan*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.permohonan') }}">
          <i class="bi bi-file-earmark-text"></i>
          <span>Manajemen Permohonan</span>
        </a>
      </li>

      <li class="nav-heading">Master Data</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('vehicles') || request()->routeIs('vehicles.create') ? '' : 'collapsed' }}"
           data-bs-target="#vehicles-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-truck"></i>
          <span>Kendaraan & Alat</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="vehicles-nav"
            class="nav-content collapse {{ request()->routeIs('vehicles') || request()->routeIs('vehicles.create') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('vehicles', ['tab' => 'list']) }}"
               class="{{ request()->routeIs('vehicles') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Daftar Kendaraan</span>
            </a>
          </li>
          <li>
            <a href="{{ route('vehicles.create') }}"
               class="{{ request()->routeIs('vehicles.create') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Tambah Kendaraan</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.vehicle-activation*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.vehicle-activation') }}">
          <i class="bi bi-toggles"></i>
          <span>Aktivasi Kendaraan</span>
        </a>
      </li>

       <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.approval*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.approval') }}">
          <i class="bi bi-patch-check"></i>
          <span>Kendaraan Baru via Request</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('users') || request()->routeIs('users.create') ? '' : 'collapsed' }}"
           data-bs-target="#users-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-people"></i>
          <span>Pengguna</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="users-nav"
            class="nav-content collapse {{ request()->routeIs('users') || request()->routeIs('users.create') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('users', ['tab' => 'list']) }}"
               class="{{ request()->routeIs('users') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Daftar User</span>
            </a>
          </li>
          <li>
            <a href="{{ route('users.create') }}"
               class="{{ request()->routeIs('users.create') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Tambah User</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">RBAC & Hak Akses</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.rbac.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.rbac.index') }}">
          <i class="bi bi-shield-lock"></i>
          <span>Role & Permission</span>
        </a>
      </li>

      <li class="nav-heading">Pengecekan & Sparepart</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.scan-vehicle*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.scan-vehicle') }}">
          <i class="bi bi-clock-history"></i>
          <span>Scan Riwayat Kendaraan</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dispatcher.dispatcher-scanner*') ? '' : 'collapsed' }}"
           href="{{ route('dispatcher.dispatcher-scanner') }}">
          <i class="bi bi-tools"></i>
          <span>Scan untuk Periksa</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sparepart-request.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.sparepart-request.index') }}">
          <i class="bi bi-wrench-adjustable"></i>
          <span>Permohonan Sparepart</span>
          @php
            $pendingSparepart = \App\Models\VehiclePartsReplacement::pending()->count();
          @endphp
          @if($pendingSparepart > 0)
            <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingSparepart }}</span>
          @endif
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.service-scheduling.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.service-scheduling.index') }}">
          <i class="bi bi-calendar-week"></i>
          <span>Jadwal Service</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.transaction-monitoring.*') ? '' : 'collapsed' }}"
           href="{{ route('admin.transaction-monitoring.index') }}">
          <i class="bi bi-eye"></i>
          <span>Monitoring Transaksi</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.vehicle-health.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.vehicle-health.index') }}">
          <i class="bi bi-heart-pulse"></i>
          <span>Vehicle Health</span>
        </a>
      </li>

      <li class="nav-heading">SPD Management</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('surat.superadmin.*') || request()->routeIs('perpanjangan.superadmin.*') || request()->routeIs('lpj.superadmin.*') ? '' : 'collapsed' }}"
           data-bs-target="#spd-superadmin-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-envelope-paper"></i>
          <span>SPD Management</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="spd-superadmin-nav"
            class="nav-content collapse {{ request()->routeIs('surat.superadmin.*') || request()->routeIs('perpanjangan.superadmin.*') || request()->routeIs('lpj.superadmin.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('surat.superadmin.index') }}"
               class="{{ request()->routeIs('surat.superadmin.index') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Daftar Surat SPD</span>
            </a>
          </li>
          <li>
            <a href="{{ route('surat.superadmin.create-shortcut') }}"
               class="{{ request()->routeIs('surat.superadmin.create-shortcut') || request()->routeIs('surat.superadmin.store-shortcut') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Buat Surat (Shortcut)</span>
            </a>
          </li>
          <li>
            <a href="{{ route('perpanjangan.superadmin.index') }}"
               class="{{ request()->routeIs('perpanjangan.superadmin.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Perpanjangan (ETD)</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lpj.superadmin.index') }}"
               class="{{ request()->routeIs('lpj.superadmin.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Validasi Laporan</span>
            </a>
          </li>
          <li>
            <a href="{{ route('surat.superadmin.report') }}"
               class="{{ request()->routeIs('surat.superadmin.report') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Laporan SPD</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">Lunch Management</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('lunch.*') ? '' : 'collapsed' }}"
           data-bs-target="#lunch-nav"
           data-bs-toggle="collapse"
           href="#">
          <i class="bi bi-egg-fried"></i>
          <span>Lunch Management</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="lunch-nav"
            class="nav-content collapse {{ request()->routeIs('lunch.*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('lunch.dashboard') }}"
               class="{{ request()->routeIs('lunch.dashboard') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Dashboard Lunch</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.employee-defaults.index') }}"
               class="{{ request()->routeIs('lunch.employee-defaults.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Employee Default</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.guests.calendar') }}"
               class="{{ request()->routeIs('lunch.guests.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Guest Reservation</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.forecasts.index') }}"
               class="{{ request()->routeIs('lunch.forecasts.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Forecast</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.distributions.index') }}"
               class="{{ request()->routeIs('lunch.distributions.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Distribution</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.transactions.index') }}"
               class="{{ request()->routeIs('lunch.transactions.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Lunch Transactions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.analytics.index') }}"
               class="{{ request()->routeIs('lunch.analytics.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Analytics</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.qr-codes') }}"
               class="{{ request()->routeIs('lunch.qr-codes') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>QR Lokasi</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.export-form') }}"
               class="{{ request()->routeIs('lunch.export-form') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Export Vendor</span>
            </a>
          </li>
          <li>
            <a href="{{ route('lunch.self-booking.index') }}"
               class="{{ request()->routeIs('lunch.self-booking.*') ? 'active' : '' }}">
              <i class="bi bi-circle"></i><span>Booking Mandiri</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">Laporan & Analisis</li>

      <li class="nav-item">
        <a class="nav-link {{ Request::is('trip-analysis*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.trip-analysis.index') }}">
          <i class="bi bi-graph-up-arrow"></i>
          <span>Analisis Trip & Efisiensi BBM</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.financial-report.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.financial-report.index') }}">
          <i class="bi bi-currency-dollar"></i>
          <span>Lap. Keuangan Service</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.transaction-report.*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.transaction-report.index') }}">
          <i class="bi bi-journal-text"></i>
          <span>Lap. Transaksi Sparepart</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.print-report*') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.print-report') }}">
          <i class="bi bi-printer"></i>
          <span>Cetak Laporan BBM</span>
        </a>
      </li>

    {{-- ╔══════════════════════════════════════════════════════════╗ --}}
    {{-- ║  MANAGER                                                 ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @elseif (Auth::user()->role === 'manager')

      <li class="nav-heading">Beranda</li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.dashboard.bisnis') ? '' : 'collapsed' }}"
           href="{{ route('superadmin.dashboard.bisnis') }}">
          <i class="bi bi-graph-up"></i>
          <span>Dashboard Bisnis</span>
        </a>
      </li>

    @endif

    {{-- ── Profil (semua role) ── --}}
    <li class="nav-heading">Akun</li>

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
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar .nav-content').forEach(function(submenu) {
      if (submenu.querySelector('.active')) {
        submenu.classList.add('show');
        var toggle = submenu.previousElementSibling;
        if (toggle) toggle.classList.remove('collapsed');
      }
    });
  });
</script>
