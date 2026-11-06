// Riwayat Permohonan BBM: 10 baris per halaman dari batch 100 baris; batch berikutnya diminta saat halaman habis.
import { escapeHtml, isoDate, pageData } from '../../lib/format';
import { toast } from '../../ui/toast';

const { url, rows: initialRows = [], hasMore = false } = pageData();
const PER_PAGE = 10;
const state = { rows: initialRows, page: 0, hasMore, loading: false };
const $ = (id) => document.getElementById(id);
const body = $('historyBody');
const cards = $('historyCards');
const prev = $('historyPrev');
const next = $('historyNext');

// Selaras dengan components/bbm/status-badge.blade.php.
const BADGE = {
    pending: ['bg-warning-soft text-warning', 'bi-hourglass-split'],
    pending_vehicle_approval: ['bg-warning-soft text-warning', 'bi-truck'],
    approved: ['bg-success-soft text-success', 'bi-check-circle'],
    completed: ['bg-info-soft text-info', 'bi-check2-all'],
    rejected: ['bg-destructive-soft text-destructive', 'bi-x-circle'],
    expired: ['bg-destructive-soft text-destructive', 'bi-clock-history'],
};
const badge = (r) => {
    const [cls, icon] = BADGE[r.status] ?? ['bg-muted text-muted-foreground', 'bi-slash-circle'];
    return `<span class="inline-flex w-fit items-center gap-1 whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium ${cls}"><i class="bi ${icon}"></i> ${escapeHtml(r.status_label)}</span>`;
};
const vehicleType = (r) => (r.vehicle_type ? ` (${escapeHtml(r.vehicle_type)})` : '');

function render() {
    const start = state.page * PER_PAGE;
    const rows = state.rows.slice(start, start + PER_PAGE);
    const empty = state.hasMore ? 'Tidak ada data pada halaman ini.' : 'Belum ada riwayat permohonan.';

    body.innerHTML = rows.length
        ? rows.map((r) => `<tr>
            <td><code class="text-xs">${escapeHtml(r.request_number)}</code></td>
            <td class="whitespace-nowrap">${escapeHtml(r.date)}</td>
            <td>${escapeHtml(r.vehicle_code)}<span class="block text-xs text-muted-foreground">${escapeHtml(r.vehicle_name)}${vehicleType(r)}</span></td>
            <td><span class="rounded-md bg-secondary px-2 py-0.5 text-xs font-medium">${escapeHtml(r.gasoline_type)}</span></td>
            <td class="whitespace-nowrap text-right font-medium tabular-nums">${escapeHtml(r.amount_formatted)}</td>
            <td>${badge(r)}</td>
            <td class="text-right"><a href="${escapeHtml(r.detail_url)}" class="inline-flex h-8 items-center gap-1.5 rounded-md border bg-card px-3 text-sm font-medium shadow-xs hover:bg-accent"><i class="bi bi-eye"></i> Detail</a></td>
        </tr>`).join('')
        : `<tr><td colspan="7" class="py-10 text-center text-muted-foreground">${empty}</td></tr>`;

    cards.innerHTML = rows.length
        ? rows.map((r) => `<li><a href="${escapeHtml(r.detail_url)}" class="grid gap-2 rounded-xl border bg-card p-4 shadow-xs active:bg-accent">
            <div class="flex items-start justify-between gap-2"><code class="text-xs">${escapeHtml(r.request_number)}</code>${badge(r)}</div>
            <div class="flex items-end justify-between gap-2">
                <div class="min-w-0"><p class="truncate font-medium">${escapeHtml(r.vehicle_code)}${vehicleType(r)}</p><p class="text-xs text-muted-foreground">${escapeHtml(r.date)} · ${escapeHtml(r.gasoline_type)}</p></div>
                <p class="shrink-0 font-semibold tabular-nums">${escapeHtml(r.amount_formatted)}</p>
            </div>
        </a></li>`).join('')
        : `<li class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">${empty}</li>`;

    $('historyCount').textContent = rows.length
        ? `Menampilkan ${start + 1} sampai ${start + rows.length} dari ${state.rows.length} data dimuat${state.hasMore ? ' (masih ada data berikutnya)' : ''}`
        : 'Tidak ada data';

    const pages = Math.max(1, Math.ceil(state.rows.length / PER_PAGE));
    $('historyPager').hidden = state.rows.length <= PER_PAGE && !state.hasMore;
    $('historyPageInfo').textContent = `Halaman ${state.page + 1} / ${pages}${state.hasMore ? '+' : ''}`;
    prev.disabled = state.page === 0 || state.loading;
    next.disabled = ((state.page + 1) * PER_PAGE >= state.rows.length && !state.hasMore) || state.loading;
}

async function fetchNextBatch() {
    state.loading = true;
    render();
    try {
        const u = new URL(url, location.origin);
        new URLSearchParams(location.search).forEach((v, k) => u.searchParams.set(k, v));
        u.searchParams.set('json', '1');
        u.searchParams.set('offset', state.rows.length);
        const res = await fetch(u, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const payload = await res.json();
        state.rows = state.rows.concat(payload.rows || []);
        state.hasMore = !!payload.has_more;
    } catch (error) {
        console.error('Gagal memuat batch riwayat:', error);
        toast({ title: 'Gagal memuat data riwayat berikutnya', description: 'Silakan coba lagi.', variant: 'error' });
    } finally {
        state.loading = false;
    }
}

prev.addEventListener('click', () => {
    if (state.page > 0) state.page--;
    render();
});

next.addEventListener('click', async () => {
    if ((state.page + 1) * PER_PAGE >= state.rows.length && state.hasMore) await fetchNextBatch();
    if ((state.page + 1) * PER_PAGE < state.rows.length) state.page++;
    render();
});

// Tukar tanggal bila terbalik dan potong rentang ke 30 hari sebelum dikirim.
$('historyFilter').addEventListener('submit', () => {
    const from = $('historyFrom');
    const to = $('historyTo');
    if (from.value && to.value && from.value > to.value) [from.value, to.value] = [to.value, from.value];
    if (from.value && to.value) {
        const max = new Date(`${from.value}T00:00:00`);
        max.setDate(max.getDate() + 29);
        const maxStr = isoDate(max);
        if (to.value > maxStr) to.value = maxStr;
    }
});

render();
