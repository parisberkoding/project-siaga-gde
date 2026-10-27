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
             style="z-index: 1050; max-height: 280px; overflow-y: auto;"></div>
        <div class="form-text">Pilih nama dari daftar agar langsung dikenali.</div>
      </div>

      {{-- Kartu identitas setelah nama dipilih --}}
      <div id="selected-card" class="alert alert-light border d-none mb-3">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="fw-bold" id="selected-name"></div>
            <div class="small text-muted" id="selected-meta"></div>
          </div>
          <div id="selected-badges" class="text-end"></div>
        </div>
      </div>

      {{-- Field tambahan untuk pegawai non-SSO / belum terdaftar --}}
      <div id="profile-fields" class="d-none">
        <div class="alert alert-warning py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          Nama belum terdaftar. Lengkapi data berikut — akun SIAGA akan dibuatkan
          dan diverifikasi admin unit.
        </div>
        <div class="row g-2">
          <div class="col-12 col-sm-6">
            <label for="division" class="form-label fw-semibold">Divisi</label>
            <input type="text" class="form-control" id="division" placeholder="Contoh: Operasi" maxlength="100">
          </div>
          <div class="col-12 col-sm-6">
            <label for="unit_id" class="form-label fw-semibold">Unit</label>
            <select class="form-select" id="unit_id">
              <option value="">— pilih unit —</option>
              @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ (int) $site->unit_id === (int) $unit->id ? 'selected' : '' }}>
                  {{ $unit->name }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" id="submit-btn">
        <i class="bi bi-check2-circle me-1"></i>Catat Kehadiran
      </button>
    </form>

    <hr class="my-3">
    <div class="text-center">
      <a href="#" id="guest-toggle" class="text-muted text-decoration-none">
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
    <p class="text-muted mb-2" id="done-message"></p>
    <p class="small text-muted d-none" id="done-account-note"></p>
    <div class="d-grid gap-2 mt-3">
      <a href="{{ route('my.lunch-plan') }}" class="btn btn-outline-primary" id="booking-link">
        <i class="bi bi-calendar-check me-1"></i>Atur jadwal makan beberapa hari ke depan
      </a>
      <button type="button" class="btn btn-light" onclick="location.reload()">
        Absen lagi
      </button>
    </div>
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

{{-- ─── MODAL: BESOK MAKAN DI MANA ─────────────────────────────── --}}
<div class="modal fade" id="tomorrow-modal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold mb-1"><i class="bi bi-sun me-1"></i>Besok makan di mana?</h5>
          <p class="text-muted small mb-0" id="tomorrow-date"></p>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          Pilihan di bawah hanya site di unit kamu. Kalau dilewati, besok otomatis
          memakai site default.
        </p>

        <div class="row g-2" id="tomorrow-sites"></div>

        <div class="mt-4">
          <label class="form-label fw-semibold small mb-2">Cara makan</label>
          <div class="btn-group w-100" role="group" id="dine-group">
            <input type="radio" class="btn-check" name="dine_type" id="dine-in" value="dine_in">
            <label class="btn btn-outline-success" for="dine-in"><i class="bi bi-cup-hot me-1"></i>Dine In</label>
            <input type="radio" class="btn-check" name="dine_type" id="dine-away" value="take_away">
            <label class="btn btn-outline-warning" for="dine-away"><i class="bi bi-bag me-1"></i>Take Away</label>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 flex-column flex-sm-row">
        <button type="button" class="btn btn-light w-100 w-sm-auto" id="tomorrow-skip">
          Lewati, pakai default
        </button>
        <button type="button" class="btn btn-primary w-100 w-sm-auto" id="tomorrow-save" disabled>
          <i class="bi bi-check2 me-1"></i>Simpan jawaban
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
  const lookupUrl       = @json(route('lunch.attendance.lookup'));
  const submitUrl       = @json(route('lunch.attendance.submit'));
  const confirmUrlBase  = @json(url('lunch-attendance'));
  const guestSubmitUrl  = @json(route('lunch.attendance.guest-submit'));
  const siteCode        = @json($site->code);
  const csrfToken       = @json(csrf_token());

  const nameInput       = document.getElementById('name');
  const employeeId      = document.getElementById('employee_id');
  const divisionInput   = document.getElementById('division');
  const unitSelect      = document.getElementById('unit_id');
  const suggestions     = document.getElementById('suggestions');
  const selectedCard    = document.getElementById('selected-card');
  const selectedName    = document.getElementById('selected-name');
  const selectedMeta    = document.getElementById('selected-meta');
  const selectedBadges  = document.getElementById('selected-badges');
  const profileFields   = document.getElementById('profile-fields');
  const submitBtn       = document.getElementById('submit-btn');
  const stepForm        = document.getElementById('step-form');
  const stepGuest       = document.getElementById('step-guest');
  const stepDone        = document.getElementById('step-done');
  const guestToggle     = document.getElementById('guest-toggle');
  const guestBack       = document.getElementById('guest-back');
  const guestForm       = document.getElementById('guest-form');
  const guestName       = document.getElementById('guest-name');
  const guestInst       = document.getElementById('guest-institution');
  const guestSubmitBtn  = document.getElementById('guest-submit-btn');
  const tomorrowModalEl = document.getElementById('tomorrow-modal');
  const tomorrowModal   = new bootstrap.Modal(tomorrowModalEl);
  const tomorrowSites   = document.getElementById('tomorrow-sites');
  const tomorrowDate    = document.getElementById('tomorrow-date');
  const tomorrowSave    = document.getElementById('tomorrow-save');
  const tomorrowSkip    = document.getElementById('tomorrow-skip');

  let transactionId   = null;
  let confirmToken    = null;
  let defaultDineType = 'dine_in';
  let selectedSiteId  = null;
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
    selectedCard.classList.add('d-none');
    profileFields.classList.toggle('d-none', nameInput.value.trim().length < 2);
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

      const badges = [];
      if (item.has_sso) badges.push('<span class="badge bg-success">SSO</span>');
      else if (item.has_account) badges.push('<span class="badge bg-secondary">Akun SIAGA</span>');
      else badges.push('<span class="badge bg-warning text-dark">Belum ada akun</span>');
      if (item.pending_verification) badges.push('<span class="badge bg-info text-dark">Pending</span>');

      a.innerHTML = `<div class="d-flex justify-content-between gap-2">
          <div>
            <strong>${escapeHtml(item.name)}</strong>
            ${item.division ? `<br><small class="text-muted">${escapeHtml(item.division)}</small>` : ''}
          </div>
          <div class="text-end small">
            ${item.unit ? `<div class="text-muted">${escapeHtml(item.unit)}</div>` : ''}
            ${badges.join(' ')}
          </div>
        </div>`;

      a.addEventListener('click', (e) => {
        e.preventDefault();
        selectEmployee(item);
      });
      suggestions.appendChild(a);
    });
    suggestions.classList.remove('d-none');
  }

  function selectEmployee(item) {
    nameInput.value  = item.name;
    employeeId.value = item.id;
    suggestions.classList.add('d-none');

    // Master lama mungkin belum punya divisi/unit. Tampilkan field tambahan
    // hanya bila datanya memang belum lengkap.
    const needsProfile = !item.division || !item.unit_id;
    profileFields.classList.toggle('d-none', !needsProfile);

    if (needsProfile) {
      if (item.division) divisionInput.value = item.division;
      if (item.unit_id) unitSelect.value = item.unit_id;
    }

    selectedName.textContent = item.name;
    selectedMeta.textContent = [item.division, item.unit].filter(Boolean).join(' · ') || 'Data master karyawan';
    selectedBadges.innerHTML = item.has_sso
      ? '<span class="badge bg-success">SSO</span>'
      : (item.has_account ? '<span class="badge bg-secondary">Akun SIAGA</span>' : '<span class="badge bg-warning text-dark">Belum ada akun</span>');
    selectedCard.classList.remove('d-none');
  }

  document.addEventListener('click', (e) => {
    if (!suggestions.contains(e.target) && e.target !== nameInput) {
      suggestions.classList.add('d-none');
    }
  });

  // ─── Employee submit ─────────────────────────────────────────
  document.getElementById('attendance-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!employeeId.value && !unitSelect.value) {
      profileFields.classList.remove('d-none');
      showError('Pilih unit kamu terlebih dahulu.');
      return;
    }

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
          division:    divisionInput.value.trim() || null,
          unit_id:     unitSelect.value || null,
        }),
      });

      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan kehadiran.');

      transactionId   = data.transaction_id;
      confirmToken    = data.token;
      defaultDineType = data.default_dine_type || 'dine_in';

      const bookingLink = document.getElementById('booking-link');
      if (bookingLink && data.slug) {
        bookingLink.href = @json(route('my.lunch-plan')) + '?slug=' + encodeURIComponent(data.slug);
      }
      selectedSiteId  = data.tomorrow_site_id || data.default_site_id;

      openTomorrowModal(data);
    } catch (err) {
      showError(err.message);
      if (/unit|lengkapi|terdaftar/i.test(err.message || '')) {
        profileFields.classList.remove('d-none');
      }
    } finally {
      submitBtn.disabled  = false;
      submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Catat Kehadiran';
    }
  });

  // ─── Modal besok ─────────────────────────────────────────────
  function openTomorrowModal(data) {
    const date = data.tomorrow_date
      ? new Date(data.tomorrow_date + 'T00:00:00')
      : new Date(Date.now() + 86400000);
    tomorrowDate.textContent = date.toLocaleDateString('id-ID', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

    tomorrowSites.innerHTML = '';

    (data.tomorrow_sites || []).forEach((s) => {
      const col = document.createElement('div');
      col.className = 'col-6';
      const active = Number(s.id) === Number(selectedSiteId);
      col.innerHTML = `
        <button type="button" class="btn w-100 h-100 border site-choice ${active ? 'btn-primary text-white' : 'btn-outline-primary'}"
                data-site-id="${s.id}">
          <div class="fw-semibold">${escapeHtml(s.name)}</div>
          <small class="${active ? 'text-white-50' : 'text-muted'}">${escapeHtml(s.code)}</small>
          ${s.is_default ? '<div class="badge bg-light text-dark mt-1">Default</div>' : ''}
        </button>`;
      col.querySelector('button').addEventListener('click', () => {
        selectedSiteId = Number(s.id);
        tomorrowSites.querySelectorAll('.site-choice').forEach((btn) => {
          btn.classList.remove('btn-primary', 'text-white');
          btn.classList.add('btn-outline-primary');
        });
        const btn = col.querySelector('button');
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary', 'text-white');
        tomorrowSave.disabled = false;
      });
      tomorrowSites.appendChild(col);
    });

    // Dine type default.
    const dineInput = document.getElementById(defaultDineType === 'take_away' ? 'dine-away' : 'dine-in');
    if (dineInput) dineInput.checked = true;

    tomorrowSave.disabled = !selectedSiteId;

    stepDone.dataset.accountPending = data.account_pending ? '1' : '';
    stepDone.dataset.registered = data.registered ? '1' : '';

    tomorrowModal.show();
  }

  tomorrowSave.addEventListener('click', async () => {
    if (!selectedSiteId) return;
    tomorrowSave.disabled = true;
    tomorrowSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

    try {
      await saveTomorrow(selectedSiteId);
      await saveDineType();
      finish('Terima kasih!', 'Kehadiran hari ini dan rencana makan besok sudah tercatat.');
    } catch (err) {
      showError(err.message);
    } finally {
      tomorrowSave.disabled = false;
      tomorrowSave.innerHTML = '<i class="bi bi-check2 me-1"></i>Simpan jawaban';
    }
  });

  tomorrowSkip.addEventListener('click', async () => {
    tomorrowSkip.disabled = true;
    try {
      await saveTomorrow(null);
      await saveDineType();
      finish('Baik, dilewati.', 'Besok otomatis memakai site default kamu.');
    } catch (err) {
      showError(err.message);
    } finally {
      tomorrowSkip.disabled = false;
    }
  });

  async function saveTomorrow(siteId) {
    const res = await fetch(confirmUrlBase + '/' + transactionId + '/tomorrow', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({ token: confirmToken, site_id: siteId }),
    });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan jawaban besok.');
    return data;
  }

  async function saveDineType() {
    const checked = document.querySelector('input[name="dine_type"]:checked');
    const dineType = checked ? checked.value : defaultDineType;

    try {
      await fetch(confirmUrlBase + '/' + transactionId + '/choose-dine-type', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ token: confirmToken, dine_type: dineType }),
      });
    } catch (e) { /* dine type opsional, jangan blokir flow */ }
  }

  function finish(title, message) {
    tomorrowModal.hide();
    stepForm.classList.add('d-none');
    stepGuest.classList.add('d-none');
    stepDone.classList.remove('d-none');
    document.getElementById('done-title').textContent = title;
    document.getElementById('done-message').textContent = message;

    const note = document.getElementById('done-account-note');
    if (stepDone.dataset.accountPending === '1') {
      note.textContent = 'Akun SIAGA kamu sedang menunggu verifikasi admin unit. Kredensial akan diberikan admin secara langsung.';
      note.classList.remove('d-none');
    }
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

      stepGuest.classList.add('d-none');
      stepDone.classList.remove('d-none');
      document.getElementById('done-title').textContent = 'Tercatat!';
      document.getElementById('done-message').textContent = data.message;
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
