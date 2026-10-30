// Revisi LPJ (requester): kartu item pengeluaran, analisis struk AI, ringkasan & cek budget per tanggal.
import { rupiah, escapeHtml, pageData } from '../../lib/format';
import { alertDialog, confirmDialog } from '../../ui/dialog';

const { analyzeUrl, csrfToken, totalBudget = 0, budgets = [], items = [] } = pageData();
const $ = (id) => document.getElementById(id);
const container = $('itemsContainer');
const template = $('itemTemplate');
const jumlahDiberikan = $('jumlahDiberikan');
const form = $('formLpj');

const budgetMap = Object.fromEntries(budgets.map((b) => [b.tanggal, parseFloat(b.nominal)]));
const summaryIds = { Makan: 'summaryMakan', Hotel: 'summaryHotel', BBM: 'summaryBBM', Parkir: 'summaryParkir', Tol: 'summaryTol', 'Lain-lain': 'summaryLain' };
const tipeMap = { BBM: 'BBM', Tol: 'Tol', Parkir: 'Parkir', Makan: 'Makan', Hotel: 'Hotel', Lainnya: 'Lain-lain' };
const statusTone = { muted: 'text-muted-foreground', info: 'text-primary', success: 'text-success', warning: 'text-warning', danger: 'text-destructive' };

let counter = 0;

const formatTanggal = (iso) => new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const cards = () => [...container.querySelectorAll('[data-item]')];
const field = (card, name) => card.querySelector(`[data-field="${name}"]`);

function setStatus(card, text, tone = 'muted') {
    const el = card.querySelector('[data-ai-status]');
    el.textContent = text;
    el.className = `text-xs empty:hidden ${statusTone[tone]}`;
}

function addItem({ tanggal = '', tipe_penggunaan = 'BBM', nominal = '', keterangan = '' } = {}) {
    counter++;
    const fragment = template.content.cloneNode(true);
    fragment.querySelectorAll('[name]').forEach((el) => { el.name = el.name.replace('__I__', counter); });
    const card = fragment.querySelector('[data-item]');

    field(card, 'tanggal').value = tanggal;
    field(card, 'tipe').value = tipe_penggunaan || 'BBM';
    field(card, 'nominal').value = nominal ?? '';
    field(card, 'keterangan').value = keterangan ?? '';

    card.addEventListener('input', calculate);
    card.addEventListener('change', calculate);
    field(card, 'struk').addEventListener('change', () => onStrukChange(card));
    card.querySelector('[data-analyze]').addEventListener('click', () => analyze(card));
    card.querySelector('[data-remove-item]').addEventListener('click', () => {
        card.remove();
        renumber();
        calculate();
    });

    container.appendChild(card);
    renumber();
}

function renumber() {
    cards().forEach((card, i) => { card.querySelector('[data-item-number]').textContent = `#${i + 1}`; });
    $('itemsEmpty').hidden = cards().length > 0;
}

function onStrukChange(card) {
    const file = field(card, 'struk').files[0];
    const preview = card.querySelector('[data-preview]');
    const button = card.querySelector('[data-analyze]');
    button.disabled = !file;
    preview.hidden = !file;
    if (!file) {
        preview.removeAttribute('src');
        return setStatus(card, '');
    }
    preview.src = URL.createObjectURL(file);
    setStatus(card, 'Klik Analisis AI untuk mengisi otomatis', 'info');
}

async function analyze(card) {
    const file = field(card, 'struk').files[0];
    if (!file) return;
    const button = card.querySelector('[data-analyze]');
    button.disabled = true;
    setStatus(card, 'Menganalisis...', 'info');

    const body = new FormData();
    body.append('image', file);
    try {
        const res = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }, body });
        if (res.status === 429) return setStatus(card, 'Terlalu banyak permintaan analisis (rate limit). Silakan isi data secara manual.', 'warning');

        const data = await res.json();
        if (data.success && data.status === 'ok') {
            const { kategori } = data;
            const metadata = data.metadata || {};
            const text = data.text || '';
            field(card, 'struk_kategori').value = kategori;
            field(card, 'kategori_source').value = 'ai';
            field(card, 'metadata_json').value = JSON.stringify({ kategori, metadata, text });

            const tipe = field(card, 'tipe');
            if (['BBM', 'Hotel', 'Makan', ''].includes(tipe.value)) tipe.value = tipeMap[kategori] || 'Lain-lain';
            const nominal = field(card, 'nominal');
            if (metadata.nominal && !(parseFloat(nominal.value) > 0)) nominal.value = metadata.nominal;
            const keterangan = field(card, 'keterangan');
            if (text && !keterangan.value.trim()) keterangan.value = text.substring(0, 200);

            setStatus(card, `AI mendeteksi ${kategori}${metadata.nominal ? `, ${rupiah(metadata.nominal)}` : ''}`, 'success');
            calculate();
        } else if (data.success) {
            field(card, 'kategori_source').value = 'manual';
            setStatus(card, data.status === 'api_limit'
                ? 'Kuota AI (Gemini) habis sementara, silakan isi data secara manual.'
                : 'Analisis AI gagal membaca struk, silakan isi data secara manual.', 'warning');
        } else {
            setStatus(card, `Gagal: ${data.message || 'analisis tidak tersedia'}`, 'danger');
        }
    } catch {
        setStatus(card, 'Koneksi gagal. Coba analisis ulang, atau isi data secara manual.', 'danger');
    } finally {
        button.disabled = false;
    }
}

function calculate() {
    let total = 0;
    const perTipe = Object.fromEntries(Object.keys(summaryIds).map((k) => [k, 0]));
    const perDate = {};

    cards().forEach((card) => {
        const nominal = parseFloat(field(card, 'nominal').value) || 0;
        const tanggal = field(card, 'tanggal').value;
        card.querySelector('[data-subtotal]').textContent = rupiah(nominal);
        total += nominal;
        if (field(card, 'tipe').value in perTipe) perTipe[field(card, 'tipe').value] += nominal;
        if (tanggal) perDate[tanggal] = (perDate[tanggal] || 0) + nominal;
    });

    $('totalPengeluaran').textContent = rupiah(total);
    $('summaryPengeluaran').textContent = rupiah(total);
    Object.entries(summaryIds).forEach(([tipe, id]) => { $(id).textContent = rupiah(perTipe[tipe]); });

    if (totalBudget > 0) {
        const selisih = totalBudget - total;
        const el = $('summarySelisih');
        $('rowSelisih').hidden = false;
        el.textContent = (selisih < 0 ? '-' : '') + rupiah(Math.abs(selisih));
        el.className = `font-semibold tabular-nums ${selisih < 0 ? 'text-destructive' : 'text-success'}`;
        $('alertOverBudget').hidden = selisih >= 0;
        $('alertOverBudget').querySelector('[data-message]').innerHTML =
            `Total pengeluaran melebihi total budget sebesar <strong>${rupiah(Math.abs(selisih))}</strong>.`;
    }

    // Status per tanggal: semua tanggal dari budget dan dari pengeluaran.
    const overs = [];
    const hasBudget = Object.keys(budgetMap).length > 0;
    $('summaryBudgetPerTanggal').hidden = !hasBudget;
    if (hasBudget) {
        const dates = [...new Set([...Object.keys(budgetMap), ...Object.keys(perDate)])].sort();
        $('summaryDateStatus').innerHTML = dates.map((tanggal) => {
            const budget = budgetMap[tanggal] || 0;
            const spent = perDate[tanggal] || 0;
            const persen = budget > 0 ? Math.round((spent / budget) * 100) : (spent > 0 ? 999 : 0);
            let badge = '<span class="rounded-md bg-muted px-1.5 text-xs text-muted-foreground">Belum ada</span>';
            if (spent > 0 && spent <= budget) badge = '<span class="rounded-md bg-success-soft px-1.5 text-xs text-success">OK</span>';
            if (spent > budget) {
                overs.push({ tanggal, budget, spent, over: spent - budget });
                badge = `<span class="rounded-md bg-destructive-soft px-1.5 text-xs text-destructive">Over ${rupiah(spent - budget)}</span>`;
            }
            const bar = persen > 100 ? 'bg-destructive' : persen > 80 ? 'bg-warning' : 'bg-success';
            return `<div class="grid gap-1 text-xs">
                <div class="flex items-center justify-between"><span class="font-medium">${formatTanggal(tanggal)}</span>${badge}</div>
                <div class="flex justify-between text-muted-foreground"><span>${rupiah(spent)} / ${rupiah(budget)}</span><span>${persen}%</span></div>
                <div class="h-1 overflow-hidden rounded-full bg-muted"><div class="h-full ${bar}" style="width:${Math.min(persen, 100)}%"></div></div>
            </div>`;
        }).join('');
    }

    $('alertOverBudgetDetail').hidden = overs.length === 0;
    $('overBudgetList').innerHTML = overs.map((w) =>
        `<li><strong>${escapeHtml(formatTanggal(w.tanggal))}</strong>: ${rupiah(w.spent)} / ${rupiah(w.budget)}, lebih <strong>${rupiah(w.over)}</strong></li>`).join('');

    cards().forEach((card) => {
        const tanggal = field(card, 'tanggal').value;
        card.toggleAttribute('data-over', Boolean(tanggal && budgetMap[tanggal] > 0 && perDate[tanggal] > budgetMap[tanggal]));
    });

    const sisa = (parseFloat(jumlahDiberikan.value) || 0) - total;
    $('pengembalian').textContent = rupiah(Math.max(sisa, 0));
    $('kekurangan').textContent = rupiah(Math.max(-sisa, 0));
}

function hitungJarak() {
    const sebelum = parseFloat($('odoBefore').value) || 0;
    const sesudah = parseFloat($('odoAfter').value) || 0;
    $('jarakTempuh').textContent = sebelum > 0 && sesudah > 0 ? `${new Intl.NumberFormat('id-ID').format(sesudah - sebelum)} km` : '-';
}

form.addEventListener('submit', async (event) => {
    if (form.dataset.confirmed) return;
    const rows = cards();
    if (rows.length === 0) {
        event.preventDefault();
        return alertDialog({ title: 'Belum ada item', message: 'Minimal harus ada 1 item pengeluaran.' });
    }
    if (rows.some((card) => !(parseFloat(field(card, 'nominal').value) > 0))) {
        event.preventDefault();
        const ok = await confirmDialog({ title: 'Nominal Rp 0', message: 'Ada item dengan nominal Rp 0. Lanjutkan kirim?', confirmText: 'Ya, kirim' });
        if (!ok) return;
        form.dataset.confirmed = '1';
        form.requestSubmit();
    }
});

$('btnAddItem').addEventListener('click', () => addItem());
jumlahDiberikan.addEventListener('input', calculate);
$('odoBefore').addEventListener('input', hitungJarak);
$('odoAfter').addEventListener('input', hitungJarak);

$('summaryBudget').textContent = totalBudget > 0 ? rupiah(totalBudget) : '-';
items.forEach(addItem);
renumber();
calculate();
hitungJarak();
