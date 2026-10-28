  @if ($activeModule)
    <div class="sidebar-module">
      <div class="sidebar-module__head">
        <span class="sidebar-module__icon">
          <i class="{{ $activeModule->icon ?: 'bi bi-grid' }}"></i>
        </span>
        <div class="sidebar-module__meta">
          <span class="sidebar-module__label">Modul Aktif</span>
          <strong class="sidebar-module__name">{{ $activeModule->name }}</strong>
        </div>
      </div>
      <a href="{{ route('modules.index') }}" class="sidebar-module__switch">
        <i class="bi bi-grid-1x2"></i>
        <span>All Modules</span>
        <i class="bi bi-arrow-left-right ms-auto"></i>
      </a>
    </div>
  @endif

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
