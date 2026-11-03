// Manajemen Pengguna: isi dialog dari data baris, reveal kredensial, filter otomatis, dan pilihan massal.
import { openDialog } from '../../ui/dialog';

const initials = (name) => (name || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0].toUpperCase()).join('');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const dataOf = (el, key) => JSON.parse(el.dataset[key]);

// ── Detail & hak akses efektif ──
const rolePermsEl = document.querySelector('[data-role-perms]');
const rolePerms = rolePermsEl ? JSON.parse(rolePermsEl.dataset.rolePerms) : {};

function showDetail(user) {
    const dialog = document.getElementById('userDetailDialog');
    const set = (key, value) => { dialog.querySelector(`[data-detail="${key}"]`).textContent = value || '-'; };
    set('avatar', initials(user.nama_lengkap || user.username));
    set('nama_lengkap', user.nama_lengkap || user.username);
    set('handle', `@${user.username}${user.email ? ` · ${user.email}` : ''}`);
    ['jabatan', 'unit_label', 'site_label', 'provider', 'source', 'created'].forEach((key) => set(key, user[key]));
    dialog.querySelector('[data-detail="status"]').innerHTML = user.is_active
        ? '<span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-medium text-success">Aktif</span>'
        : `<span class="rounded-full bg-destructive-soft px-2.5 py-1 text-xs font-medium text-destructive" title="${esc(user.deactivation_reason)}">Nonaktif</span>`;

    // Gabungkan permission semua role pengguna, dikelompokkan per grup.
    const groups = {};
    const names = [];
    user.roles.forEach((code) => {
        const role = rolePerms[code];
        if (!role) return;
        names.push(role.name);
        Object.entries(role.groups).forEach(([group, perms]) => {
            groups[group] ??= new Map();
            perms.forEach((p) => groups[group].set(p.code, p.label));
        });
    });
    const total = Object.values(groups).reduce((sum, m) => sum + m.size, 0);
    set('role_names', names.join(', '));
    set('perm_count', `${total} permission`);
    dialog.querySelector('[data-detail="perms"]').innerHTML = total === 0
        ? '<p class="text-sm text-muted-foreground">Role ini belum punya permission.</p>'
        : Object.entries(groups).sort().map(([group, perms]) => `
            <div class="grid gap-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">${esc(group)} <span class="font-normal">(${perms.size})</span></span>
                <div class="flex flex-wrap gap-1.5">${[...perms].map(([code, label]) =>
                    `<span class="rounded-md bg-primary/8 px-2 py-0.5 text-xs text-primary ring-1 ring-primary/15" title="${esc(label)}">${esc(code)}</span>`).join('')}</div>
            </div>`).join('');
    openDialog('userDetailDialog');
}

// ── Ubah data & role ──
function showEdit(user) {
    const form = document.getElementById('userEditForm');
    form.action = `/users/${user.id}/update`;
    form.querySelectorAll('[data-edit]').forEach((field) => {
        const value = user[field.dataset.edit];
        if (field.type === 'checkbox') field.checked = Boolean(value);
        else field.value = value ?? '';
    });
    form.querySelectorAll('input[name="role_code"]').forEach((radio) => { radio.checked = radio.value === user.role; });
    openDialog('userEditDialog');
}

function showReset(user) {
    const form = document.getElementById('userResetForm');
    form.action = `/users/${user.id}/reset-password`;
    form.reset();
    form.querySelector('[data-reset="username"]').textContent = user.username;
    openDialog('userResetDialog');
}

function showCredentials(user) {
    const form = document.getElementById('userCredentialsForm');
    form.action = `/users/${user.id}/credentials`;
    form.reset();
    form.querySelector('[data-credentials="username"]').value = user.username;
    form.querySelector('[data-credentials="revealed"]').hidden = true;
    const reveal = form.querySelector('[data-credentials="reveal"]');
    reveal.dataset.url = `/users/${user.id}/credentials/reveal`;
    reveal.disabled = !user.has_credentials;
    reveal.innerHTML = user.has_credentials
        ? '<i class="bi bi-eye"></i> Lihat password tersimpan'
        : '<i class="bi bi-slash-circle"></i> Belum ada password tersimpan';
    openDialog('userCredentialsDialog');
}

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-user-detail], [data-user-edit], [data-user-reset], [data-user-credentials]');
    if (trigger) {
        if ('userDetail' in trigger.dataset) showDetail(dataOf(trigger, 'userDetail'));
        else if ('userEdit' in trigger.dataset) showEdit(dataOf(trigger, 'userEdit'));
        else if ('userReset' in trigger.dataset) showReset(dataOf(trigger, 'userReset'));
        else showCredentials(dataOf(trigger, 'userCredentials'));
        return;
    }

    const toggle = event.target.closest('[data-toggle-password]');
    if (toggle) {
        const input = toggle.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        toggle.querySelector('i').className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        return;
    }

    const reveal = event.target.closest('[data-credentials="reveal"]');
    if (reveal?.dataset.url) {
        reveal.disabled = true;
        const box = reveal.closest('form');
        try {
            const res = await fetch(reveal.dataset.url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal membuka kredensial.');
            box.querySelector('[data-credentials="password"]').textContent = data.password;
            box.querySelector('[data-credentials="count"]').textContent = `Sudah dibuka ${data.reveals} kali.`;
            box.querySelector('[data-credentials="revealed"]').hidden = false;
        } catch (error) {
            box.querySelector('[data-credentials="password"]').textContent = error.message;
            box.querySelector('[data-credentials="revealed"]').hidden = false;
        } finally {
            reveal.disabled = false;
        }
    }
});

// ── Filter otomatis: cari setelah berhenti mengetik ──
document.querySelectorAll('form[data-autofilter]').forEach((form) => {
    let timer;
    form.querySelector('input[name="search"]')?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 400);
    });
});

// ── Pilihan massal: tampilkan bar aksi saat ada baris dipilih ──
const bar = document.querySelector('[data-bulk-bar]');
const items = [...document.querySelectorAll('[data-bulk-item]')];
const all = document.querySelector('[data-bulk-all]');
const syncBulk = () => {
    const count = items.filter((i) => i.checked).length;
    if (bar) {
        bar.hidden = count === 0;
        bar.querySelector('[data-bulk-count]').textContent = count;
    }
    if (all) {
        all.checked = count > 0 && count === items.length;
        all.indeterminate = count > 0 && count < items.length;
    }
};
items.forEach((i) => i.addEventListener('change', syncBulk));
all?.addEventListener('change', () => { items.forEach((i) => { i.checked = all.checked; }); syncBulk(); });
document.querySelector('[data-bulk-clear]')?.addEventListener('click', () => { items.forEach((i) => { i.checked = false; }); syncBulk(); });
