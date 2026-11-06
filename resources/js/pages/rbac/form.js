// Form role: hitung permission terpilih, centang per grup (tiga keadaan), pilih semua, dan cari permission.
const form = document.querySelector('[data-role-form]');
const groups = [...document.querySelectorAll('[data-perm-group]')];
const boxes = (scope) => [...scope.querySelectorAll('input[name="permissions[]"]')];

function refresh() {
    const summary = document.querySelector('[data-perm-summary]');
    const lines = [];
    groups.forEach((group) => {
        const items = boxes(group);
        const checked = items.filter((b) => b.checked).length;
        const toggle = group.querySelector('[data-group-toggle]');
        toggle.checked = checked > 0 && checked === items.length;
        toggle.indeterminate = checked > 0 && checked < items.length;
        group.querySelector('[data-group-count]').textContent = checked;
        if (checked) {
            const name = group.querySelector('summary .flex-1').textContent.trim();
            lines.push(`<li class="flex justify-between gap-2"><span class="truncate text-muted-foreground">${name}</span><span class="font-medium tabular-nums">${checked}/${items.length}</span></li>`);
        }
    });
    const total = boxes(form).filter((b) => b.checked).length;
    document.querySelectorAll('[data-perm-count]').forEach((el) => { el.textContent = total; });
    summary.innerHTML = lines.join('') || '<li class="text-muted-foreground">Belum ada permission dipilih.</li>';
}

form?.addEventListener('change', (event) => {
    const toggle = event.target.closest('[data-group-toggle]');
    if (toggle) boxes(toggle.closest('[data-perm-group]')).forEach((b) => { if (!b.closest('[hidden]')) b.checked = toggle.checked; });
    refresh();
});

document.querySelectorAll('[data-perm-all]').forEach((button) => button.addEventListener('click', () => {
    boxes(form).forEach((b) => { if (!b.closest('[hidden]')) b.checked = button.dataset.permAll === '1'; });
    refresh();
}));

// Cari: sembunyikan permission yang tidak cocok dan grup yang kosong; grup yang cocok dibuka.
document.querySelector('[data-perm-search]')?.addEventListener('input', (event) => {
    const q = event.target.value.trim().toLowerCase();
    groups.forEach((group) => {
        let visible = 0;
        group.querySelectorAll('[data-perm-item]').forEach((item) => {
            item.hidden = q !== '' && !item.dataset.search.includes(q);
            if (!item.hidden) visible++;
        });
        group.hidden = visible === 0;
        if (q) group.open = true;
    });
});

if (form) refresh();
