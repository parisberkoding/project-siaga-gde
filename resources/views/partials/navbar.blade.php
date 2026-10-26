<style>
/* Hamburger Menu */
.toggle-sidebar-btn {
  font-size: 28px;
  cursor: pointer;
  color: #093D7D;
  padding: 0 15px;
  transition: color 0.3s;
}

.toggle-sidebar-btn:hover {
  color: #0B50A1;
}

/* Logo Section */
.header .logo {
  text-decoration: none;
}

.header .logo img {
  max-height: 38px;
}

.header .logo span {
  font-size: 20px;
  font-weight: 600;
  color: #093D7D;
  white-space: nowrap;
}

/* Profile Section */
.nav-profile {
  transition: all 0.3s;
}

.nav-profile:hover {
  color: #0B50A1;
}

.nav-profile i.bi-person-circle {
  color: #0B50A1;
}

.nav-profile span {
  color: #093D7D;
}

/* Dropdown Menu */
.dropdown-menu.profile {
  min-width: 260px;
  box-shadow: 0px 0 30px rgba(9, 61, 125, 0.1);
}

.dropdown-header h6 {
  font-size: 16px;
  margin-bottom: 3px;
  font-weight: 600;
  color: #093D7D;
}

.dropdown-header span {
  font-size: 13px;
  color: #64748B;
}

.dropdown-item {
  padding: 10px 20px;
  transition: all 0.3s;
}

.dropdown-item:hover {
  background-color: #F4F7FB;
  color: #0B50A1;
}

.dropdown-item i {
  margin-right: 10px;
  color: #899bbd;
}

.dropdown-item:hover i {
  color: #0B50A1;
}

/* Responsive */
@media (max-width: 991px) {
  .header .logo span {
    display: none !important;
  }
}

@media (max-width: 768px) {
  .toggle-sidebar-btn {
    font-size: 24px;
    padding: 0 10px;
  }

  .header .logo img {
    max-height: 34px;
  }
}
</style>

<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center">
    <i class="bi bi-list toggle-sidebar-btn"></i>
    <a href="{{ url('/') }}" class="logo d-flex align-items-center ms-3">
      <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo.png') }}" alt="Logo">
      <span class="d-none d-lg-block ms-2">SIAGA</span>
    </a>
  </div>

  @auth
  @php $navModule = app(\App\Services\ModuleContext::class)->current(); @endphp
  <!-- Right Section: Module Indicator + Profile Dropdown -->
  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
      @if ($navModule)
        <li class="nav-item d-none d-md-flex align-items-center me-2">
          <a href="{{ route('modules.index') }}" class="nav-module-switch" title="Kembali ke All Modules">
            <i class="bi bi-grid-1x2"></i>
            <span>All Modules</span>
          </a>
        </li>
        <li class="nav-item d-none d-lg-flex align-items-center me-3">
          <span class="nav-module-current">
            <i class="{{ $navModule->icon ?: 'bi bi-grid' }}"></i>
            {{ $navModule->name }}
          </span>
        </li>
      @endif

      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
          <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->nama_lengkap }}</span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6>{{ Auth::user()->nama_lengkap }}</h6>
            <span>{{ ucfirst(strtolower(Auth::user()->jabatan)) }}</span>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="{{ route('modules.index') }}">
              <i class="bi bi-grid-1x2"></i>
              <span>All Modules</span>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
              <i class="bi bi-person"></i>
              <span>Profil Saya</span>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
              @csrf
              <button type="submit" class="dropdown-item d-flex align-items-center" style="border: none; background: none; width: 100%; text-align: left; cursor: pointer;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
              </button>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </nav>
  @endauth

</header><!-- End Header -->

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Profil Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <i class="bi bi-person-circle" style="font-size: 5rem; color: #0B50A1;"></i>
        </div>
        <table class="table table-borderless">
          <tbody>
            <tr>
              <td width="40%" class="fw-semibold">Nama Lengkap</td>
              <td>{{ Auth::user()->nama_lengkap }}</td>
            </tr>
            <tr>
              <td class="fw-semibold">Email</td>
              <td>{{ Auth::user()->email }}</td>
            </tr>
            <tr>
              <td class="fw-semibold">Jabatan</td>
              <td>{{ ucfirst(strtolower(Auth::user()->jabatan)) }}</td>
            </tr>
            <tr>
              <td class="fw-semibold">Role</td>
              <td>
                <span class="badge bg-{{ Auth::user()->role === 'admin' ? 'primary' : 'success' }}">
                  {{ ucfirst(Auth::user()->role) }}
                </span>
              </td>
            </tr>
            <tr>
              <td class="fw-semibold">NIP</td>
              <td>{{ Auth::user()->nip ?? '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <a href="{{ route('profile') }}" class="btn btn-primary" >Detail</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
