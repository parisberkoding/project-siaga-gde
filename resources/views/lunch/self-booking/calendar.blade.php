<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Booking Mandiri Makan Siang - GeoDipa</title>
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">

  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — design system Geodipa (dimuat setelah NiceAdmin agar menimpanya) -->
  <link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-tokens.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-base.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-components.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-app.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-compat.css') }}" rel="stylesheet">
  
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #F8FAFC 0%, #eef2f7 100%);
      min-height: 100vh;
      color: #2d3748;
    }
    
    .card-custom {
      border: none;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
      background: #ffffff;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .form-control-lg {
      border-radius: 12px;
      padding: 0.75rem 1.25rem;
      font-size: 1rem;
      border: 1.5px solid #e2e8f0;
    }
    .form-control-lg:focus {
      border-color: #0B50A1;
      box-shadow: 0 0 0 4px rgba(11, 80, 161, 0.15);
    }

    .btn-primary-custom {
      background: #0B50A1;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      padding: 0.75rem 1.5rem;
      transition: all 0.2s ease-in-out;
    }
    .btn-primary-custom:hover {
      background: #093D7D;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(11, 80, 161, 0.2);
    }

    /* Suggestion List for Autocomplete */
    .list-group-suggestion {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      background: #fff;
    }
    .list-group-suggestion .list-group-item {
      border: none;
      border-bottom: 1px solid #f1f5f9;
      padding: 12px 16px;
      transition: background 0.15s ease;
    }
    .list-group-suggestion .list-group-item:last-child {
      border-bottom: none;
    }
    .list-group-suggestion .list-group-item:hover {
      background-color: #f8fafc;
      cursor: pointer;
    }

    /* Calendar Styling */
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 12px;
      margin-top: 15px;
    }
    
    @media (max-width: 992px) {
      .calendar-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }
    @media (max-width: 576px) {
      .calendar-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }
    }

    .calendar-day-header {
      background: #f1f5f9;
      text-align: center;
      padding: 10px 4px;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      border-radius: 8px;
      letter-spacing: 0.5px;
    }

    @media (max-width: 576px) {
      .calendar-day-header {
        display: none;
      }
    }

    .calendar-cell {
      background: #fff;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      min-height: 165px;
      padding: 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      transition: all 0.2s ease;
      box-shadow: 0 4px 6px rgba(0,0,0,0.01);
    }
    .calendar-cell:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
      border-color: #cbd5e1;
    }
    .calendar-cell.weekend {
      background: #f8fafc;
    }
    .calendar-cell.override {
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    .calendar-date {
      font-size: 14px;
      font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
    }
    .calendar-date small {
      font-size: 11px;
      font-weight: 500;
      color: #64748b;
    }
    .calendar-badge {
      display: inline-block;
      font-size: 10px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 6px;
      margin-top: 4px;
    }
    .badge-weekend {
      background: #e2e8f0;
      color: #475569;
    }
    .badge-override {
      background: #dcfce7;
      color: #15803d;
    }

    .calendar-card {
      margin-top: 8px;
      background: #f8fafc;
      border-radius: 10px;
      padding: 8px;
      border: 1px dashed #cbd5e1;
    }
    .calendar-cell.override .calendar-card {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
    }
    .calendar-card .form-select {
      font-size: 12px;
      font-weight: 600;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      background-color: #fff;
    }
    .calendar-card .badge {
      font-size: 9px;
      padding: 3px 6px;
    }

    .toast-custom {
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
<main class="container py-4">

  {{-- 1. TAMPILAN SEARCH NAMA (JIKA EMPLOYEE BELUM TERPILIH) --}}
  @if (!$employee)
  <div style="max-width: 480px; margin: 6vh auto 0 auto;">
    <div class="text-center mb-4">
      <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="Logo" height="52" class="mb-3">
      <h3 class="fw-bold text-dark mb-1">Booking Makan Siang</h3>
      <p class="text-muted mb-0">Atur & ubah lokasi makan siang katering Anda dengan mudah.</p>
    </div>
    
    <div class="card card-custom p-2">
      <div class="card-body p-4">
        <form method="GET" id="searchForm" autocomplete="off">
          <div class="mb-4 position-relative">
            <label for="nameInput" class="form-label fw-bold text-dark mb-2">
              <i class="bi bi-person-circle me-1 text-primary"></i>Masukkan Nama Anda
            </label>
            <input type="text" class="form-control form-control-lg" id="nameInput"
                   placeholder="Ketik nama untuk mencari…" required autofocus maxlength="150" value="{{ request('name') }}">
            <input type="hidden" name="slug" id="employeeSlug" value="{{ request('slug') }}">
            
            <div id="suggestions" class="list-group list-group-suggestion position-absolute w-100 d-none"
                 style="z-index: 1050; max-height: 280px; overflow-y: auto;"></div>
            
            <div class="form-text mt-2 small text-muted">
              <i class="bi bi-info-circle me-1"></i>Ketik minimal 2 karakter nama, lalu pilih dari daftar yang muncul.
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary-custom btn-lg w-100" id="btnSubmitSearch">
            <i class="bi bi-calendar-check-fill me-2"></i>Buka Kalender Booking
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- 2. TAMPILAN KALENDER BOOKING (JIKA EMPLOYEE SUDAH TERPILIH) --}}
  @else
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" alt="Logo" height="32">
        <span class="badge bg-primary px-2.5 py-1.5 rounded-3">Self Booking</span>
      </div>
      <h3 class="fw-bold mb-1 text-dark">
        {{ $employee->employee_name }}
      </h3>
      <p class="text-muted mb-0 small">
        @if ($employee->division)
          <span class="me-2"><i class="bi bi-briefcase me-1"></i>{{ $employee->division }}</span>
        @endif
        @if ($employee->resolvedUnit())
          <span class="me-2"><i class="bi bi-diagram-3 me-1"></i>{{ $employee->resolvedUnit()->name }}</span>
        @endif
        @if ($employee->defaultSite)
          <span><i class="bi bi-geo-alt me-1"></i>Default Site: <strong class="text-primary">{{ $employee->defaultSite->name }}</strong></span>
        @endif
      </p>
    </div>

    {{-- Filter Kalender & Ganti Karyawan (jika login/admin) --}}
    <form method="GET" class="row g-2 align-items-center">
      @auth
      <div class="col-auto">
        <select name="slug" class="form-select form-select-sm rounded-3 py-2 px-3 border-0 shadow-sm" onchange="this.form.submit()">
          <option value="">Ganti karyawan...</option>
          @foreach ($employees as $emp)
            <option value="{{ $emp->slug }}" {{ $employee->slug === $emp->slug ? 'selected' : '' }}>
              {{ $emp->employee_name }}
            </option>
          @endforeach
        </select>
      </div>
      @else
      {{-- Simpan slug dalam input hidden untuk guest agar state tetap terjaga saat ganti filter tanggal --}}
      <input type="hidden" name="slug" value="{{ $employee->slug }}">
      @endauth

      <div class="col-auto">
        <input type="date" name="start_date" class="form-control form-control-sm rounded-3 py-2 px-3 border-0 shadow-sm"
               value="{{ $startDate->toDateString() }}" onchange="this.form.submit()">
      </div>
      <div class="col-auto">
        <select name="days" class="form-select form-select-sm rounded-3 py-2 px-3 border-0 shadow-sm" onchange="this.form.submit()">
          <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 hari</option>
          <option value="14" {{ $days === 14 ? 'selected' : '' }}>14 hari</option>
          <option value="21" {{ $days === 21 ? 'selected' : '' }}>21 hari</option>
          <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 hari</option>
        </select>
      </div>
    </form>
  </div>

  {{-- Kalender Grid Form --}}
  <form method="POST" action="{{ route('my.lunch-plan.store') }}">
    @csrf
    <input type="hidden" name="slug" value="{{ $employee->slug }}">

    <div class="card card-custom mb-4">
      <div class="card-body p-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-2">
          <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-calendar-range me-2 text-primary"></i>Pilih Lokasi Makan Siang</h5>
            <p class="text-muted mb-0 small">Ubah pilihan lokasi pada tanggal tertentu apabila Anda ditugaskan ke luar site/kantor.</p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center small">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>Override Admin</span>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>Akhir Pekan</span>
          </div>
        </div>

        <div class="calendar-grid">
          {{-- Week headers (Hanya muncul di desktop) --}}
          @foreach ($weekHeaders as $dayName)
          <div class="calendar-day-header">{{ $dayName }}</div>
          @endforeach

          {{-- Calendar Cells --}}
          @foreach ($dates as $i => $d)
            @php($dateValue = $d['date'] ?? '')
            @php($dayName = $d['day_name'] ?? '')
            @php($formattedDate = $dateValue ? \Carbon\Carbon::parse($dateValue)->translatedFormat('d F Y') : '')
            @php($isWeekend = $dateValue ? \Carbon\Carbon::parse($dateValue)->isWeekend() : (bool) ($d['is_weekend'] ?? false))
            @php($isAdminOverride = $d['is_admin_override'] ?? false)
            @php($selectedSiteId = $d['selected_site_id'] ?? null)
            @php($forecast = $d['forecast'] ?? null)
            
            <div class="calendar-cell {{ $isWeekend ? 'weekend' : '' }} {{ $isAdminOverride ? 'override' : '' }}">
              <div>
                <div class="calendar-date">
                  {{ $dateValue ? \Carbon\Carbon::parse($dateValue)->translatedFormat('d') : '' }}
                  <small class="d-block text-muted">{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->translatedFormat('M Y') : '' }}</small>
                  <small class="d-sm-none text-primary d-block fw-bold mt-1">{{ $dayName }}</small>
                </div>

                @if ($isWeekend)
                  <span class="calendar-badge badge-weekend"><i class="bi bi-calendar-x me-1"></i>Weekend</span>
                @endif

                @if ($isAdminOverride)
                  <span class="calendar-badge badge-override"><i class="bi bi-lock-fill me-1"></i>Override</span>
                @endif
              </div>

              <div class="calendar-card">
                <select name="bookings[{{ $i }}][date]" class="d-none">
                  <option value="{{ $dateValue }}" selected></option>
                </select>

                <select name="bookings[{{ $i }}][site_id]"
                        class="form-select form-select-sm {{ $isAdminOverride ? 'border-success bg-white text-success fw-bold' : '' }}"
                        {{ $isAdminOverride ? 'disabled' : '' }}>
                  <option value="">— Libur / tidak makan —</option>
                  @if ($employee?->defaultSite)
                    <option value="{{ $employee->default_site_id }}"
                            {{ ($selectedSiteId == $employee->default_site_id) ? 'selected' : '' }}>
                      Default — {{ $employee->defaultSite->name }}
                    </option>
                  @endif
                  @foreach ($sites as $site)
                    @continue($employee?->default_site_id == $site->id)
                    <option value="{{ $site->id }}" {{ ($selectedSiteId == $site->id) ? 'selected' : '' }}>
                      {{ $site->name }} ({{ $site->code }})
                    </option>
                  @endforeach
                </select>

                @if ($forecast)
                  <div class="mt-2 text-center">
                    <span class="badge bg-{{ $forecast->sourceBadgeClass() }} rounded-2 w-100 py-1">
                      {{ $forecast->sourceLabel() }}
                    </span>
                  </div>
                @else
                  <div class="mt-2 text-center">
                    <span class="badge bg-light text-muted border rounded-2 w-100 py-1">
                      Default
                    </span>
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 pt-3 border-top gap-3">
          <div class="text-muted small text-center text-sm-start">
            <i class="bi bi-info-circle-fill text-primary me-1"></i> Pilih opsi <strong>— Skip / Libur —</strong> jika Anda sedang libur, cuti, atau tidak makan di site kantor.
          </div>
          <div class="d-flex gap-2 w-100 w-sm-auto justify-content-center">
            <a href="{{ route('welcome') }}" class="btn btn-light px-4 rounded-3 border">
              Kembali
            </a>
            <button type="submit" class="btn btn-primary-custom px-4 shadow-sm text-white">
              <i class="bi bi-save-fill me-2"></i>Simpan Booking
            </button>
          </div>
        </div>
        
      </div>
    </div>
  </form>
  @endif

</main>

<script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script nonce="{{ csp_nonce() }}">
const lookupUrl  = @json(route('lunch.attendance.lookup'));
const nameInput  = document.getElementById('nameInput');
const employeeSlug = document.getElementById('employeeSlug');
const suggestionsEl = document.getElementById('suggestions');
let debounceTimer;

if (nameInput) {
  nameInput.addEventListener('input', () => {
    employeeSlug.value = '';
    clearTimeout(debounceTimer);
    const q = nameInput.value.trim();
    if (q.length < 2) { suggestionsEl.classList.add('d-none'); return; }
    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(lookupUrl + '?q=' + encodeURIComponent(q));
        const data = await res.json();
        renderSuggestions(data.results || []);
      } catch (e) { /* ignore */ }
    }, 250);
  });

  document.addEventListener('click', (e) => {
    if (!suggestionsEl.contains(e.target) && e.target !== nameInput) {
      suggestionsEl.classList.add('d-none');
    }
  });
}

function renderSuggestions(items) {
  suggestionsEl.innerHTML = '';
  if (!items.length) { suggestionsEl.classList.add('d-none'); return; }
  items.forEach((item) => {
    const a = document.createElement('a');
    a.href = '#';
    a.className = 'list-group-item list-group-item-action py-2.5';
    
    // Tampilkan name, position, dan site dengan aman dari XSS
    const escapedName = escapeHtml(item.name);
    const escapedPosition = item.position ? `<br><small class="text-muted"><i class="bi bi-briefcase me-1"></i>${escapeHtml(item.position)}</small>` : '';
    const escapedSite = item.site ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle float-end mt-1">${escapeHtml(item.site)}</span>` : '';
    
    a.innerHTML = `<div>${escapedSite}<strong>${escapedName}</strong>${escapedPosition}</div>`;
    a.addEventListener('click', (e) => {
      e.preventDefault();
      nameInput.value = item.name;
      employeeSlug.value = item.slug;
      suggestionsEl.classList.add('d-none');
      document.getElementById('searchForm').submit();
    });
    suggestionsEl.appendChild(a);
  });
  suggestionsEl.classList.remove('d-none');
}

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}
</script>
</body>
</html>
