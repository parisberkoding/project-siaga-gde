// Tambah Pengguna: ganti mode, isi info pekerja terpilih, pratinjau akun, dan buat password acak.
const form = document.querySelector('[data-user-create]');
const $ = (selector) => form.querySelector(selector);
const out = (key, value) => { $(`[data-preview-out="${key}"]`).textContent = value; };
const initials = (name) => name.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';

function applyMode() {
    const mode = $('[data-mode]:checked').value;
    form.querySelectorAll('[data-mode-section]').forEach((section) => { section.hidden = section.dataset.modeSection !== mode; });
    $('#namaLengkap').required = mode === 'manual';
    $('#employeeSelect').required = mode === 'activate';
    preview();
}

function preview() {
    const activate = $('[data-mode]:checked').value === 'activate';
    const employee = $('#employeeSelect').selectedOptions[0];
    const name = activate ? (employee?.dataset.name ?? '') : $('#namaLengkap').value;
    out('name', name || 'Nama pengguna');
    out('initials', initials(name));
    out('username', `@${$('#username').value || 'username'}`);
    out('role', $('[data-preview="role"]:checked')?.dataset.label ?? 'Belum dipilih');
    const unit = $('#unit');
    out('unit', activate ? 'Sesuai data pekerja' : unit.selectedOptions[0].textContent.trim());

    $('[data-employee="name"]').textContent = employee?.dataset.name || '-';
    $('[data-employee="position"]').textContent = employee?.dataset.position || '-';
    $('[data-employee="site"]').textContent = employee?.dataset.siteName || '-';
}

if (form) {
    form.addEventListener('change', (event) => (event.target.matches('[data-mode]') ? applyMode() : preview()));
    form.addEventListener('input', preview);

    form.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-toggle-password]');
        if (toggle) {
            const input = toggle.parentElement.querySelector('input');
            input.type = input.type === 'password' ? 'text' : 'password';
            toggle.querySelector('i').className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
        // Password acak 10 karakter tanpa huruf yang mudah tertukar (l, 1, O, 0), langsung ditampilkan.
        if (event.target.closest('[data-generate-password]')) {
            const chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            const bytes = crypto.getRandomValues(new Uint32Array(10));
            const password = $('#password');
            password.value = [...bytes].map((n) => chars[n % chars.length]).join('');
            password.type = 'text';
            password.parentElement.querySelector('[data-toggle-password] i').className = 'bi bi-eye-slash';
        }
    });

    applyMode();
}
