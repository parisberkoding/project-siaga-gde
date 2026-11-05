// <x-ui.date-range-picker>: kalender rentang (Senin di kiri), dua bulan di layar >= 640px, satu bulan di HP.
// Pilihan sementara disimpan di elemen root; input tersembunyi baru diisi saat Terapkan (submit form).
const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const monthFormat = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
const dayFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const longFormat = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const parse = (v) => (v ? new Date(`${v}T00:00`) : null);
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
const nav = 'inline-flex size-7 items-center justify-center rounded-md border bg-card text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground';

export const formatRange = (from, to) => (from && to ? `${dayFormat.format(parse(from))} – ${dayFormat.format(parse(to))}` : '');

function monthGrid(root, month, { prev, next }) {
    const { start, end } = root.state;
    const max = root.dataset.max;
    const today = iso(new Date());
    const first = addDays(month, -((month.getDay() + 6) % 7));
    const weeks = Math.ceil((((month.getDay() + 6) % 7) + new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate()) / 7);

    const days = Array.from({ length: weeks * 7 }, (_, i) => {
        const date = addDays(first, i);
        if (date.getMonth() !== month.getMonth()) return '<span></span>';
        const v = iso(date);
        const ok = !max || v <= max;
        const edge = v === start || v === end;
        const inside = start && end && v > start && v < end;
        const cls = [
            'flex h-9 w-full items-center justify-center text-sm tabular-nums outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50',
            edge ? 'rounded-md bg-primary font-semibold text-primary-foreground'
                : inside ? 'bg-accent text-accent-foreground'
                : v === today ? 'rounded-md font-semibold text-primary ring-1 ring-primary/30 hover:bg-accent'
                : 'rounded-md hover:bg-accent',
            v === start && end && end !== start ? 'rounded-r-none' : '',
            v === end && start && end !== start ? 'rounded-l-none' : '',
            ok ? 'cursor-pointer' : 'cursor-not-allowed opacity-30',
        ].join(' ');
        return `<button type="button" data-day="${v}" class="${cls}" tabindex="-1" ${ok ? '' : 'disabled'}
            aria-label="${longFormat.format(date)}" ${edge || inside ? 'aria-pressed="true"' : ''}>${date.getDate()}</button>`;
    }).join('');

    return `<div class="w-64">
        <div class="mb-2 flex h-7 items-center justify-between">
            ${prev ? `<button type="button" class="${nav}" data-range-step="-1" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button>` : '<span class="size-7"></span>'}
            <span class="text-sm font-semibold capitalize">${monthFormat.format(month)}</span>
            ${next ? `<button type="button" class="${nav}" data-range-step="1" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button>` : '<span class="size-7"></span>'}
        </div>
        <div class="grid grid-cols-7 gap-y-1 text-center">
            ${WEEKDAYS.map((d, i) => `<span class="pb-1 text-xs font-medium ${i > 4 ? 'text-destructive/70' : 'text-muted-foreground'}">${d}</span>`).join('')}
            ${days}
        </div>
    </div>`;
}

function render(root) {
    const two = window.matchMedia('(min-width: 640px)').matches;
    const m = root.state.month;
    const second = new Date(m.getFullYear(), m.getMonth() + 1, 1);
    root.querySelector('[data-range-months]').innerHTML = two
        ? monthGrid(root, m, { prev: true }) + monthGrid(root, second, { next: true })
        : monthGrid(root, m, { prev: true, next: true });

    const { start, end } = root.state;
    root.querySelector('[data-range-summary]').textContent = start && end
        ? formatRange(start, end)
        : start ? 'Pilih tanggal akhir' : 'Pilih tanggal awal';
    root.querySelector('[data-range-apply]').disabled = !(start && end);
}

function focusDay(root, value) {
    const grid = root.querySelector('[data-range-months]');
    const target = grid.querySelector(`[data-day="${value}"]:not(:disabled)`) ?? grid.querySelector('[data-day]:not(:disabled)');
    target?.setAttribute('tabindex', '0');
    target?.focus();
}

const rootOf = (el) => el.closest('[data-range-picker]');

// Gambar kalender sebelum tampil, supaya ui/dropdown.js (event toggle) mengukur lebar aslinya saat
// menggeser popover agar tidak keluar layar (penting di HP).
document.addEventListener('beforetoggle', (event) => {
    const cal = event.target;
    if (!cal.matches?.('[data-range-calendar]') || event.newState !== 'open') return;
    const root = rootOf(cal);
    const start = root.querySelector('[data-range-from]').value || null;
    const end = root.querySelector('[data-range-to]').value || null;
    const anchor = parse(end) ?? new Date();
    // Dua bulan: bulan terakhir rentang tampil di kanan, jadi mulai dari bulan sebelumnya.
    const offset = window.matchMedia('(min-width: 640px)').matches ? 1 : 0;
    root.state = { start, end, month: new Date(anchor.getFullYear(), anchor.getMonth() - offset, 1) };
    render(root);
}, true);

document.addEventListener('toggle', (event) => {
    const cal = event.target;
    if (!cal.matches?.('[data-range-calendar]') || event.newState !== 'open') return;
    const root = rootOf(cal);
    focusDay(root, root.state.end ?? iso(new Date()));
}, true);

document.addEventListener('click', (event) => {
    const root = rootOf(event.target);
    if (!root?.state) return;
    const day = event.target.closest('[data-day]');
    const step = event.target.closest('[data-range-step]');

    if (day && !day.disabled) {
        const v = day.dataset.day;
        const { start, end } = root.state;
        if (!start || end) Object.assign(root.state, { start: v, end: null });
        else if (v < start) Object.assign(root.state, { start: v, end: start });
        else root.state.end = v;
        render(root);
        focusDay(root, v);
    } else if (step) {
        const m = root.state.month;
        root.state.month = new Date(m.getFullYear(), m.getMonth() + Number(step.dataset.rangeStep), 1);
        render(root);
    } else if (event.target.closest('[data-range-cancel]')) {
        root.querySelector('[data-range-calendar]').hidePopover();
    }
});

// Terapkan = submit form: isi input tersembunyi dari pilihan sementara (capture, sebelum handler lain).
document.addEventListener('submit', (event) => {
    const root = event.submitter?.closest('[data-range-picker]');
    if (!root?.state) return;
    root.querySelector('[data-range-from]').value = root.state.start;
    root.querySelector('[data-range-to]').value = root.state.end;
}, true);

// Panah geser hari/minggu (pindah bulan bila perlu).
const KEYS = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
document.addEventListener('keydown', (event) => {
    const day = event.target.closest?.('[data-range-months] [data-day]');
    if (!day || !(event.key in KEYS)) return;
    event.preventDefault();
    const root = rootOf(day);
    const next = addDays(parse(day.dataset.day), KEYS[event.key]);
    if (!root.querySelector(`[data-day="${iso(next)}"]`)) {
        root.state.month = new Date(next.getFullYear(), next.getMonth(), 1);
        render(root);
    }
    focusDay(root, iso(next));
});
