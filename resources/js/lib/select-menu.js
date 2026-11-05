// Helper <x-ui.select-menu> untuk JS halaman: ubah nilai / ganti daftar opsi secara terprogram.
// root = elemen [data-select]; opsi baru dibuat dengan menyalin opsi pertama (placeholder) supaya gayanya sama.

// Membuat <x-ui.select-menu> dari JS (untuk kontrol yang dibuat library, mis. jumlah per halaman tabel).
// Markup HARUS sama dengan resources/views/components/ui/select-menu.blade.php.
let uid = 0;
export function buildSelectMenu({ options, value = '', icon = null, label = 'Pilih', className = '' }) {
    const id = `select-js-${++uid}`;
    const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const current = options.find((o) => String(o.value) === String(value));
    const root = document.createElement('div');
    root.dataset.select = '';
    root.className = `relative ${className || 'w-full'}`; // className menggantikan lebar bawaan
    root.innerHTML = `
        <button type="button" id="${id}" popovertarget="${id}-list" aria-haspopup="listbox" aria-label="${esc(label)}" data-select-trigger
                class="flex h-9 w-full cursor-pointer items-center justify-between gap-2 rounded-md border border-input bg-card px-3 text-left text-sm shadow-xs outline-none transition-[color,box-shadow] hover:bg-accent/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50">
            <span class="flex min-w-0 items-center gap-2">
                ${icon ? `<i class="${icon} text-muted-foreground"></i>` : ''}
                <span data-select-label data-placeholder="${esc(label)}" class="truncate">${esc(current?.label ?? label)}</span>
            </span>
            <i class="bi bi-chevron-down shrink-0 text-xs text-muted-foreground"></i>
        </button>
        <div id="${id}-list" popover data-dropdown data-align="start" role="listbox" aria-labelledby="${id}"
             class="m-0 max-h-72 min-w-32 overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
            ${options.map((o) => `<button type="button" role="option" data-value="${esc(o.value)}" aria-selected="${String(o.value) === String(value)}"
                class="group/opt relative flex w-full cursor-pointer items-center gap-2 rounded-sm py-1.5 pl-2 pr-8 text-left text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-selected:font-medium">
                <span class="truncate">${esc(o.label)}</span>
                <i class="bi bi-check2 invisible absolute right-2 text-primary group-aria-selected/opt:visible"></i></button>`).join('')}
        </div>`;
    return root;
}

export const selectRoot =(id) => document.getElementById(id)?.closest('[data-select]') ?? null;

export const selectValue = (root) => root?.querySelector('[data-select-input]')?.value ?? '';

export function setSelectValue(root, value, { notify = false } = {}) {
    if (!root) return;
    const options = [...root.querySelectorAll('[role="option"]')];
    const match = options.find((o) => o.dataset.value === String(value ?? '')) ?? null;
    options.forEach((o) => o.setAttribute('aria-selected', String(o === match)));

    const label = root.querySelector('[data-select-label]');
    const val = match ? match.dataset.value : '';
    label.textContent = match?.textContent.trim() || label.dataset.placeholder;
    label.classList.toggle('text-muted-foreground', val === '');

    const input = root.querySelector('[data-select-input]');
    if (input) {
        input.value = val;
        if (notify) input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (val) root.querySelector('[data-select-trigger]')?.removeAttribute('aria-invalid');
}

// options: [{ value, label }]; nilai sebelumnya dipertahankan bila masih ada di daftar baru.
export function setSelectOptions(root, options) {
    if (!root) return;
    // Hanya wadah opsi yang diganti (kotak cari searchable tetap ada).
    const list = root.querySelector('[data-select-options]') ?? root.querySelector('[role="listbox"]');
    // Disimpan sekali, supaya daftar boleh dikosongkan lalu diisi lagi.
    root.optionTemplate ??= list.querySelector('[role="option"]')?.cloneNode(true);
    const template = root.optionTemplate;
    if (!template) return;
    const previous = selectValue(root);
    list.replaceChildren(...options.map(({ value, label }) => {
        const option = template.cloneNode(true);
        option.dataset.value = String(value);
        option.querySelector('span').textContent = label;
        return option;
    }));
    setSelectValue(root, options.some((o) => String(o.value) === previous) ? previous : '');
}
