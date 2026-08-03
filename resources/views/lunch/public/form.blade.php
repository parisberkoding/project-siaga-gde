@extends('templates.public', ['title' => 'Absen Makan Siang — ' . $site->name])

@section('content')
<div class="text-center mb-3 pt-3">
  <span class="badge bg-primary fs-6 px-3 py-2 mb-2">
    <i class="bi bi-geo-alt-fill me-1"></i>{{ $site->name }}
  </span>
  <h4 class="fw-bold mb-1">Absen Makan Siang</h4>
  <p class="text-muted mb-0">
    {{ now()->translatedFormat('l, d F Y') }}
    &middot; jam layanan {{ config('siaga.lunch_window.start') }}–{{ config('siaga.lunch_window.end') }}
  </p>
</div>

{{-- ─── EMPLOYEE FORM ──────────────────────────────────────────── --}}
<div class="card shadow-sm" id="step-form">
  <div class="card-body p-4">
    <form id="attendance-form" autocomplete="off">
      <div class="mb-3 position-relative">
        <label for="name" class="form-label fw-semibold">Nama kamu</label>
        <input type="text" class="form-control form-control-lg" id="name"
               placeholder="Ketik nama / panggilan…" required maxlength="150">
        <input type="hidden" id="employee_id">
        <div id="suggestions" class="list-group position-absolute w-100 shadow-sm d-none"
             style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
        <div class="form-text">Pilih nama dari daftar agar langsung dikenali.</div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg w-100" id="submit-btn">
        <i class="bi bi-check2-circle me-1"></i>Catat Kehadiran
      </button>
    </form>

    <hr class="my-3">
    <div class="text-center">
      <a href="#" id="guest-toggle" class="text-muted text-decoration-none" style="transition: all 0.2s;" onmouseover="this.style.textDecoration='underline'; this.style.color='#0d6efd';" onmouseout="this.style.textDecoration='none'; this.style.color='#6c757d';">
        <i class="bi bi-people me-1"></i>Klik di sini untuk absen tamu
      </a>
    </div>
  </div>
</div>

{{-- ─── GUEST FORM ─────────────────────────────────────────────── --}}
<div class="card shadow-sm d-none" id="step-guest">
  <div class="card-body p-4">
    <form id="guest-form" autocomplete="off">
      <div class="mb-3">
        <label for="guest-name" class="form-label fw-semibold">Nama tamu</label>
        <input type="text" class="form-control form-control-lg" id="guest-name"
               placeholder="Nama lengkap…" required maxlength="150">
      </div>
      <div class="mb-3">
        <label for="guest-institution" class="form-label fw-semibold">Instansi / Perusahaan</label>
        <input type="text" class="form-control form-control-lg" id="guest-institution"
               placeholder="Nama instansi…" required maxlength="200">
      </div>
      <button type="submit" class="btn btn-primary btn-lg w-100" id="guest-submit-btn">
        <i class="bi bi-check2-circle me-1"></i>Catat Kehadiran Tamu
      </button>
    </form>

    <hr class="my-3">
    <div class="text-center">
      <a href="#" id="guest-back" class="text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke absen pegawai
      </a>
    </div>
  </div>
</div>

{{-- ─── SUCCESS ────────────────────────────────────────────────── --}}
<div class="card shadow-sm d-none" id="step-done">
  <div class="card-body p-4 text-center">
    <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
    <h5 class="fw-bold mt-3" id="done-title">Tercatat!</h5>
    <p class="text-muted" id="done-message"></p>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
      Absen lagi
    </button>
  </div>
</div>

{{-- ─── TOAST ERROR ────────────────────────────────────────────── --}}
<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
  <div id="error-toast" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="error-toast-body">Terjadi kesalahan.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

{{-- ─── MODAL 1: Pilih Lokasi Makan ────────────────────────────── --}}
<div class="modal fade" id="site-modal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-geo-alt me-1"></i>Makan di mana?</h5>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Pilih lokasi makan siang kamu hari ini.</p>
        <div class="d-grid gap-2" id="site-options">
          @foreach ($sites as $s)
            <button type="button" class="btn btn-outline-primary btn-lg site-btn"
                    data-site-id="{{ $s->id }}">
              <i class="bi bi-geo-alt me-1"></i>{{ $s->code }}
            </button>
          @endforeach
          <a href="{{ route('my.lunch-plan') }}" class="btn btn-outline-dark btn-lg" target="_blank">
            <i class="bi bi-calendar-check me-1"></i>Booking Lama
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ─── MODAL 2: Dine In / Take Away ───────────────────────────── --}}
<div class="modal fade" id="dine-modal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-shop me-1"></i>Mau makan bagaimana?</h5>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Pilih cara penyajian atau gunakan default.</p>
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-outline-success btn-lg dine-btn" data-dine-type="dine_in">
            <i class="bi bi-cup-hot me-1"></i>Dine In
          </button>
          <button type="button" class="btn btn-outline-warning btn-lg dine-btn" data-dine-type="take_away">
            <i class="bi bi-bag me-1"></i>Take Away
          </button>
          <button type="button" class="btn btn-light btn-lg" id="dine-skip">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Skip — pakai default
          </button>
        </div>
        <p class="text-muted small text-center mt-3 mb-0" id="dine-default-hint"></p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const lookupUrl     = @json(route('lunch.attendance.lookup'));
  const submitUrl     = @json(route('lunch.attendance.submit'));
  const chooseSiteUrl = @json(url('lunch-attendance'));
  const chooseDineUrl = @json(url('lunch-attendance'));
  const guestSubmitUrl= @json(route('lunch.attendance.guest-submit'));
  const siteCode      = @json($site->code);
  const csrfToken     = @json(csrf_token());

  const nameInput      = document.getElementById('name');
  const employeeId     = document.getElementById('employee_id');
  const suggestions    = document.getElementById('suggestions');
  const submitBtn      = document.getElementById('submit-btn');
  const stepForm       = document.getElementById('step-form');
  const stepGuest      = document.getElementById('step-guest');
  const stepDone       = document.getElementById('step-done');
  const guestToggle    = document.getElementById('guest-toggle');
  const guestBack      = document.getElementById('guest-back');
  const guestForm      = document.getElementById('guest-form');
  const guestName      = document.getElementById('guest-name');
  const guestInst      = document.getElementById('guest-institution');
  const guestSubmitBtn = document.getElementById('guest-submit-btn');

  const siteModal      = new bootstrap.Modal(document.getElementById('site-modal'));
  const dineModal      = new bootstrap.Modal(document.getElementById('dine-modal'));
  const siteOptions    = document.getElementById('site-options');
  const dineDefaultHint= document.getElementById('dine-default-hint');

  let transactionId   = null;
  let confirmToken    = null;
  let defaultDineType = 'dine_in';
  let debounceTimer   = null;

  // ─── Guest toggle ────────────────────────────────────────────
  guestToggle.addEventListener('click', (e) => {
    e.preventDefault();
    stepForm.classList.add('d-none');
    stepGuest.classList.remove('d-none');
  });
  guestBack.addEventListener('click', (e) => {
    e.preventDefault();
    stepGuest.classList.add('d-none');
    stepForm.classList.remove('d-none');
  });

  // ─── Autocomplete ────────────────────────────────────────────
  nameInput.addEventListener('input', () => {
    employeeId.value = '';
    clearTimeout(debounceTimer);
    const q = nameInput.value.trim();
    if (q.length < 2) { suggestions.classList.add('d-none'); return; }
    debounceTimer = setTimeout(async () => {
      try {
        const res  = await fetch(lookupUrl + '?q=' + encodeURIComponent(q));
        const data = await res.json();
        renderSuggestions(data.results || []);
      } catch (e) { /* ignore */ }
    }, 250);
  });

  function renderSuggestions(items) {
    suggestions.innerHTML = '';
    if (!items.length) { suggestions.classList.add('d-none'); return; }
    items.forEach((item) => {
      const a = document.createElement('a');
      a.href = '#';
      a.className = 'list-group-item list-group-item-action py-2';
      a.innerHTML = `<strong>${escapeHtml(item.name)}</strong>` +
        (item.position ? `<br><small class="text-muted">${escapeHtml(item.position)}</small>` : '');
      a.addEventListener('click', (e) => {
        e.preventDefault();
        nameInput.value  = item.name;
        employeeId.value = item.id;
        suggestions.classList.add('d-none');
      });
      suggestions.appendChild(a);
    });
    suggestions.classList.remove('d-none');
  }

  document.addEventListener('click', (e) => {
    if (!suggestions.contains(e.target) && e.target !== nameInput) {
      suggestions.classList.add('d-none');
    }
  });

  // ─── Employee submit ─────────────────────────────────────────
  document.getElementById('attendance-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    submitBtn.disabled  = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

    try {
      const res = await fetch(submitUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
          site_code:   siteCode,
          name:        nameInput.value.trim(),
          employee_id: employeeId.value || null,
        }),
      });

      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan kehadiran.');

      transactionId   = data.transaction_id;
      confirmToken    = data.token;
      defaultDineType = data.default_dine_type || 'dine_in';

      dineDefaultHint.textContent = 'Default: ' + (defaultDineType === 'take_away' ? 'Take Away' : 'Dine In');

      siteModal.show();
    } catch (err) {
      showError(err.message);
    } finally {
      submitBtn.disabled  = false;
      submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Catat Kehadiran';
    }
  });

  // ─── Modal 1: site selection ─────────────────────────────────
  siteOptions.querySelectorAll('.site-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        await fetch(chooseSiteUrl + '/' + transactionId + '/choose-site', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          body: JSON.stringify({ token: confirmToken, site_id: btn.dataset.siteId }),
        });
      } catch (e) { /* ignore */ }

      siteModal.hide();
      dineModal.show();
    });
  });

  siteOptions.querySelector('[target="_blank"]').addEventListener('click', () => {
    siteModal.hide();
  });

  // ─── Modal 2: dine type ──────────────────────────────────────
  document.querySelectorAll('.dine-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      await submitDineType(btn.dataset.dineType);
    });
  });

  document.getElementById('dine-skip').addEventListener('click', async () => {
    await submitDineType(defaultDineType);
  });

  async function submitDineType(dineType) {
    try {
      const res = await fetch(chooseDineUrl + '/' + transactionId + '/choose-dine-type', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ token: confirmToken, dine_type: dineType }),
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan pilihan.');
    } catch (err) {
      showError(err.message);
    }

    dineModal.hide();
    window.location.href = '/';
  }

  // ─── Guest submit ────────────────────────────────────────────
  guestForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    guestSubmitBtn.disabled  = true;
    guestSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

    try {
      const res = await fetch(guestSubmitUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
          site_code:   siteCode,
          guest_name:  guestName.value.trim(),
          institution: guestInst.value.trim(),
        }),
      });

      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan tamu.');

      window.location.href = '/';
    } catch (err) {
      showError(err.message);
    } finally {
      guestSubmitBtn.disabled  = false;
      guestSubmitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Catat Kehadiran Tamu';
    }
  });

  // ─── Helpers ─────────────────────────────────────────────────
  function showError(msg) {
    document.getElementById('error-toast-body').textContent = msg;
    new bootstrap.Toast(document.getElementById('error-toast')).show();
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }
</script>
@endpush
