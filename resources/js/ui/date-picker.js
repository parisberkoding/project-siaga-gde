// <x-ui.date-picker>: kalender satu bulan (Senin di kiri) di popover. Nilai tetap disimpan di
// <input type="date"> asli supaya form, validasi, dan JS halaman tidak perlu tahu soal picker ini.
const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const labelFormat = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const monthFormat = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
const valueDesc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const parse = (value) => (value ? new Date(`${value}T00:00`) : null);
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
const parts = (root) => ({
    input: root.querySelector('input[type="date"]'),
    trigger: root.querySelector('[data-date-trigger]'),
    label: root.querySelector('[data-date-label]'),
    calendar: root.querySelector('[data-date-calendar]'),
});
const allowed = (input, value) => (!input.min || value >= input.min) && (!input.max || value <= input.max);

function syncLabel(root) {
    const { input, label } = parts(root);
    const date = parse(input.value);
    label.textContent = date ? labelFormat.format(date) : label.dataset.placeholder;
    label.classList.toggle('text-muted-foreground', !date);
}

const nav = 'inline-flex size-8 items-center justify-center rounded-md border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-accent hover:text-foreground';

function render(root, month) {
    const { input, calendar } = parts(root);
    root.month = new Date(month.getFullYear(), month.getMonth(), 1);
    const today = iso(new Date());
    const first = addDays(root.month, -((root.month.getDay() + 6) % 7));

    const days = Array.from({ length: 42 }, (_, i) => {
        const date = addDays(first, i);
        const value = iso(date);
        const outside = date.getMonth() !== root.month.getMonth();
        const ok = allowed(input, value);
        const selected = value === input.value;
        const classes = [
            'flex size-9 items-center justify-center rounded-md text-sm tabular-nums outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50',
            selected ? 'bg-primary font-semibold text-primary-foreground shadow-xs'
                : value === today ? 'bg-accent font-semibold text-accent-foreground ring-1 ring-primary/30'
                : outside ? 'text-muted-foreground/50 hover:bg-accent' : 'hover:bg-accent',
            ok ? 'cursor-pointer' : 'cursor-not-allowed line-through opacity-30',
        ].join(' ');
        return `<button type="button" data-day="${value}" class="${classes}" tabindex="-1" ${ok ? '' : 'disabled'}
            aria-label="${labelFormat.format(date)}" ${selected ? 'aria-pressed="true"' : ''}>${date.getDate()}</button>`;
    }).join('');

    const todayOk = allowed(input, today);
    calendar.innerHTML = `
        <div class="mb-3 flex items-center justify-between">
            <button type="button" class="${nav}" data-month-step="-1" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button>
            <span class="text-sm font-semibold capitalize" aria-live="polite">${monthFormat.format(root.month)}</span>
            <button type="button" class="${nav}" data-month-step="1" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center">
            ${WEEKDAYS.map((d, i) => `<span class="pb-1 text-xs font-medium ${i > 4 ? 'text-destructive/70' : 'text-muted-foreground'}">${d}</span>`).join('')}
            ${days}
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 border-t pt-3">
            <button type="button" data-day="${today}" class="h-8 rounded-md px-3 text-sm font-medium text-primary hover:bg-accent disabled:opacity-40" ${todayOk ? '' : 'disabled'}>Hari ini</button>
            ${input.required ? '' : '<button type="button" data-date-clear class="h-8 rounded-md px-3 text-sm text-muted-foreground hover:bg-accent">Kosongkan</button>'}
        </div>`;
}

// Fokus satu tanggal (roving tabindex): tanggal terpilih, hari ini, atau tanggal pertama yang boleh.
function focusDay(root, value) {
    const { calendar } = parts(root);
    const target = calendar.querySelector(`.grid [data-day="${value}"]:not(:disabled)`)
        ?? calendar.querySelector('.grid [data-day]:not(:disabled)');
    target?.setAttribute('tabindex', '0');
    target?.focus();
}

function pick(root, value) {
    const { input, trigger, calendar } = parts(root);
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    calendar.hidePopover();
    trigger.focus();
}

document.querySelectorAll('[data-date-picker]').forEach((root) => {
    const { input, trigger } = parts(root);
    // JS halaman kadang mengubah .value langsung (tanpa event); label ikut diperbarui.
    Object.defineProperty(input, 'value', {
        get() { return valueDesc.get.call(this); },
        set(value) { valueDesc.set.call(this, value); syncLabel(root); },
    });
    // Label/validasi browser memfokus input asli yang transparan: alihkan ke tombolnya.
    input.addEventListener('focus', () => trigger.focus());
    syncLabel(root);
});

document.addEventListener('toggle', (event) => {
    const calendar = event.target;
    if (!calendar.matches?.('[data-date-calendar]') || event.newState !== 'open') return;
    const root = calendar.closest('[data-date-picker]');
    const { input } = parts(root);
    const start = parse(input.value) ?? (input.min && iso(new Date()) < input.min ? parse(input.min) : new Date());
    render(root, start);
    focusDay(root, iso(start));
}, true);

document.addEventListener('click', (event) => {
    const root = event.target.closest('[data-date-picker]');
    if (!root) return;
    const day = event.target.closest('[data-day]');
    const step = event.target.closest('[data-month-step]');
    if (day && !day.disabled) pick(root, day.dataset.day);
    else if (step) render(root, new Date(root.month.getFullYear(), root.month.getMonth() + Number(step.dataset.monthStep), 1));
    else if (event.target.closest('[data-date-clear]')) pick(root, '');
});

// Panah: geser hari/minggu, PageUp/PageDown: geser bulan. Enter/Spasi = klik tombol biasa.
const KEYS = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
document.addEventListener('keydown', (event) => {
    const day = event.target.closest?.('[data-date-calendar] .grid [data-day]');
    if (!day || !(event.key in KEYS || event.key === 'PageUp' || event.key === 'PageDown')) return;
    event.preventDefault();
    const root = day.closest('[data-date-picker]');
    const current = parse(day.dataset.day);
    const next = event.key in KEYS
        ? addDays(current, KEYS[event.key])
        : new Date(current.getFullYear(), current.getMonth() + (event.key === 'PageUp' ? -1 : 1), current.getDate());
    if (next.getMonth() !== root.month.getMonth() || next.getFullYear() !== root.month.getFullYear()) render(root, next);
    focusDay(root, iso(next));
});
