<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Booking Mandiri Makan Siang</title>
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/css/style.css') }}" rel="stylesheet">
  <style>
  
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 1px;
    background: #e9ecef;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
  }
  
  @media (max-width: 768px) {
    .calendar-grid {
      grid-template-columns: 1fr;
      gap: 8px;
      background: transparent;
      border: 0;
      border-radius: 0;
    }
    .calendar-day-header {
      display: none;
    }
    .calendar-cell {
      min-height: auto;
      border: 1px solid #e9ecef;
      border-radius: 8px;
      background: #fff;
    }
    .calendar-card {
      margin-top: 8px;
    }
  }
  .calendar-day-header {
    background: #f8f9fa;
    text-align: center;
    padding: 8px 4px;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
  }
  .calendar-cell {
    background: #fff;
    min-height: 140px;
    padding: 8px;
    position: relative;
  }
  .calendar-cell.weekend {
    background: rgba(233, 236, 239, 0.35);
  }
  .calendar-cell.override {
    background: #f3f7ff;
  }
  .calendar-cell.weekend.override {
    background: linear-gradient(135deg, rgba(243, 247, 255, 0.95), rgba(233, 236, 239, 0.4));
  }
  .calendar-date {
    font-size: 13px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 6px;
  }
  .calendar-badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 999px;
    margin-bottom: 6px;
  }
  .calendar-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #fcfdff;
    padding: 8px;
    margin-top: 6px;
  }
  .calendar-card .form-select {
    font-size: 12px;
  }
  .calendar-card .badge { font-size: 10px; }
  </style>
</head>
<body class="bg-light">
<main class="container py-4">

  @if (!$employee)
  <div style="max-width: 480px; margin: 0 auto;">
    <div class="text-center mb-3 pt-3">
      <h4 class="fw-bold mb-1">Booking Mandiri Makan Siang</h4>
      <p class="text-muted mb-0">Atur jadwal makan siang untuk beberapa hari ke depan.</p>
    </div>
    @auth
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form method="GET" id="searchForm" autocomplete="off">
          <div class="mb-3 position-relative">
            <label for="nameInput" class="form-label fw-semibold">Nama kamu</label>
            <input type="text" class="form-control form-control-lg" id="nameInput"
                   placeholder="Ketik nama / panggilan…" required maxlength="150" value="{{ request('name') }}">
            <input type="hidden" name="slug" id="employeeSlug" value="{{ request('slug') }}">
            <div id="suggestions" class="list-group position-absolute w-100 shadow-sm d-none"
                 style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
            <div class="form-text">Pilih nama dari daftar agar langsung dikenali.</div>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-calendar-check me-1"></i>Lihat Jadwal
          </button>
        </form>
      </div>
    </div>
    @endauth
    @guest
    <div class="card shadow-sm">
      <div class="card-body p-4 text-center">
        <i class="bi bi-qr-code text-primary" style="font-size: 3rem;"></i>
        <h5 class="fw-bold mt-3">Gunakan link pribadi Anda</h5>
        <p class="text-muted mb-0">
          Untuk membuka kalender booking, gunakan link/QR pribadi yang dibagikan.
          Link tersebut berisi identitas Anda sehingga hanya Anda yang bisa
          melihat dan mengatur jadwal makan siang sendiri.
        </p>
      </div>
    </div>
    @endguest
  </div>
  @else
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
    <div>
      <h4 class="fw-bold mb-1">
        <i class="bi bi-calendar-check me-1"></i>Booking Mandiri
      </h4>
      <p class="text-muted mb-0">
        {{ $employee->employee_name }}
        @if ($employee->division)
          &middot; {{ $employee->division }}
        @endif
        @if ($employee->defaultSite)
          &middot; <span class="badge bg-info">{{ $employee->defaultSite->code }}</span>
        @endif
      </p>
    </div>
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-auto">
        <select name="slug" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Ganti karyawan...</option>
          @foreach ($employees as $emp)
            <option value="{{ $emp->slug }}" {{ $employee->slug === $emp->slug ? 'selected' : '' }}>
              {{ $emp->employee_name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-auto">
        <input type="date" name="start_date" class="form-control form-control-sm"
               value="{{ $startDate->toDateString() }}" onchange="this.form.submit()">
      </div>
      <div class="col-auto">
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 hari</option>
          <option value="14" {{ $days === 14 ? 'selected' : '' }}>14 hari</option>
          <option value="21" {{ $days === 21 ? 'selected' : '' }}>21 hari</option>
          <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 hari</option>
        </select>
      </div>
    </form>
  </div>

  <form method="POST" action="{{ route('my.lunch-plan.store') }}">
    @csrf
    <input type="hidden" name="slug" value="{{ $employee->slug }}">

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="fw-bold mb-1">Kalender Booking</h5>
            <p class="text-muted mb-0 small">Default site karyawan akan muncul otomatis. Kamu bisa ubah sesuai kebutuhan.</p>
          </div>
          <div class="text-muted small">
            <span class="badge bg-primary me-1"></span> Override admin
            <span class="badge bg-secondary ms-2 me-1"></span> Akhir Pekan
          </div>
        </div>

        <div class="calendar-grid">
          @foreach ($weekHeaders as $dayName)
          <div class="calendar-day-header">{{ $dayName }}</div>
          @endforeach

          @foreach ($dates as $i => $d)
            @php($dateValue = $d['date'] ?? '')
            @php($dayName = $d['day_name'] ?? '')
            @php($formattedDate = $dateValue ? \Carbon\Carbon::parse($dateValue)->translatedFormat('d F Y') : '')
            @php($isWeekend = $dateValue ? \Carbon\Carbon::parse($dateValue)->isWeekend() : (bool) ($d['is_weekend'] ?? false))
            @php($isAdminOverride = $d['is_admin_override'] ?? false)
            @php($selectedSiteId = $d['selected_site_id'] ?? null)
            @php($forecast = $d['forecast'] ?? null)
            <div class="calendar-cell {{ $isWeekend ? 'weekend' : '' }} {{ $isAdminOverride ? 'override' : '' }}">
              <div class="calendar-date">{{ $formattedDate }}<br><small class="text-muted">{{ $dayName }}</small></div>

              @if ($isWeekend)
                <span class="calendar-badge bg-secondary text-white">Akhir Pekan</span>
              @endif

              @if ($isAdminOverride)
                <span class="calendar-badge bg-primary text-white">Override</span>
              @endif

              <div class="calendar-card">
                <select name="bookings[{{ $i }}][date]" class="d-none">
                  <option value="{{ $dateValue }}" selected></option>
                </select>

                <select name="bookings[{{ $i }}][site_id]"
                        class="form-select form-select-sm {{ $isAdminOverride ? 'border-primary' : '' }}"
                        {{ $isAdminOverride ? 'disabled' : '' }}>
                  <option value="">— Skip —</option>
                  @foreach ($sites as $site)
                    <option value="{{ $site->id }}" {{ ($selectedSiteId == $site->id) ? 'selected' : '' }}>
                      {{ $site->code }}
                    </option>
                  @endforeach
                </select>

                @if ($forecast)
                  <div class="mt-2">
                    <span class="badge bg-{{ $forecast->sourceBadgeClass() }}">
                      {{ $forecast->sourceLabel() }}
                    </span>
                  </div>
                @else
                  <div class="mt-2">
                    <span class="badge bg-light text-dark">
                      @if ($employee->defaultSite)
                        {{ $employee->defaultSite->code }}
                      @else
                        Default
                      @endif
                    </span>
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted small">
            Pilih <strong>— Skip —</strong> jika tidak makan di site mana pun.
          </div>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-floppy me-1"></i>Simpan Booking
          </button>
        </div>
      </div>
    </div>
  </form>
  @endif

</main>
<script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
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
    a.className = 'list-group-item list-group-item-action py-2';
    a.innerHTML = `<strong>${escapeHtml(item.name)}</strong>` +
      (item.position ? `<br><small class="text-muted">${escapeHtml(item.position)}</small>` : '') +
      (item.site ? ` <span class="badge bg-info float-end">${escapeHtml(item.site)}</span>` : '');
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
