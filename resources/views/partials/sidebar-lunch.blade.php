{{-- ── Lunch Management (Phase 1) — admin & superadmin ── --}}
<li class="nav-heading">Lunch Management</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.dashboard') ? '' : 'collapsed' }}"
     href="{{ route('lunch.dashboard') }}">
    <i class="bi bi-egg-fried"></i>
    <span>Dashboard Lunch</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.employee-defaults.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.employee-defaults.index') }}">
    <i class="bi bi-person-badge"></i>
    <span>Employee Default</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.guests.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.guests.calendar') }}">
    <i class="bi bi-people"></i>
    <span>Guest Reservation</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.forecasts.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.forecasts.index') }}">
    <i class="bi bi-magic"></i>
    <span>Lunch Forecast</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.distributions.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.distributions.index') }}">
    <i class="bi bi-box-seam"></i>
    <span>Lunch Distribution</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.transactions.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.transactions.index') }}">
    <i class="bi bi-qr-code-scan"></i>
    <span>Lunch Transactions</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.analytics.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.analytics.index') }}">
    <i class="bi bi-bar-chart-line"></i>
    <span>Analytics</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.qr-codes') ? '' : 'collapsed' }}"
     href="{{ route('lunch.qr-codes') }}">
    <i class="bi bi-qr-code"></i>
    <span>QR Lokasi</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.export-form') ? '' : 'collapsed' }}"
     href="{{ route('lunch.export-form') }}">
    <i class="bi bi-file-earmark-excel"></i>
    <span>Export Vendor</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link {{ request()->routeIs('lunch.self-booking.*') ? '' : 'collapsed' }}"
     href="{{ route('lunch.self-booking.index') }}">
    <i class="bi bi-calendar-check"></i>
    <span>Booking Mandiri</span>
  </a>
</li>
