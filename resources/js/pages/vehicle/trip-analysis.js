// Analisis Trip & Efisiensi BBM: pilih massal + validasi massal, dialog validasi satu trip, dialog detail transaksi.
import { openDialog } from '../../ui/dialog';

const $ = (sel, root = document) => root.querySelector(sel);
const boxes = () => [...document.querySelectorAll('.trip-checkbox')];

// ── Pilih massal ──
const bulkBar = $('#bulkBar');
const checkAll = $('#checkAll');

function syncBulk() {
    const all = boxes();
    const picked = all.filter((cb) => cb.checked);
    $('#selectedCount').textContent = picked.length;
    bulkBar.hidden = picked.length === 0;
    checkAll.checked = all.length > 0 && picked.length === all.length;
    checkAll.indeterminate = picked.length > 0 && picked.length < all.length;
}

checkAll.disabled = boxes().length === 0;
checkAll.addEventListener('change', () => {
    boxes().forEach((cb) => { cb.checked = checkAll.checked; });
    syncBulk();
});
document.addEventListener('change', (e) => { if (e.target.matches('.trip-checkbox')) syncBulk(); });
$('[data-clear-selection]').addEventListener('click', () => {
    boxes().forEach((cb) => { cb.checked = false; });
    syncBulk();
});

// Id terpilih ditulis ulang ke form sebelum dikirim (forms.js menampilkan konfirmasi lebih dulu).
const bulkForm = $('#bulkForm');
bulkForm.addEventListener('submit', () => {
    bulkForm.querySelectorAll('input[name="validation_ids[]"]').forEach((el) => el.remove());
    boxes().filter((cb) => cb.checked).forEach((cb) => {
        bulkForm.insertAdjacentHTML('beforeend', `<input type="hidden" name="validation_ids[]" value="${Number(cb.value)}">`);
    });
}, true);

// ── Dialog validasi satu trip ──
// Patokan standar mengikuti TripValidation::getDefaultBaselineByFuelType.
const DEFAULT_BASELINE = { Pertamax: 15, 'Pertamina Dex': 7 };
const form = $('#validationForm');
const baseline = $('#baselineOverride');
const reason = $('#baselineReason');
const syncReason = () => { reason.required = baseline.value.trim() !== ''; };
baseline.addEventListener('input', syncReason);

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-validate-trip]');
    if (!btn) return;
    form.reset();
    form.action = btn.dataset.url;
    $('[data-trip-info]', form).textContent = btn.dataset.info;
    const fuel = btn.dataset.fuel || '-';
    $('[data-baseline-default]', form).textContent = `Patokan standar untuk ${fuel}: ${DEFAULT_BASELINE[fuel] ?? 12} km/L.`;
    syncReason();
    openDialog('validationDialog');
});

// ── Dialog detail transaksi ──
const proofDialog = $('#proofDialog');
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-proof]');
    if (!btn) return;
    const data = JSON.parse(btn.dataset.proof);
    proofDialog.querySelectorAll('[data-proof-field]').forEach((el) => {
        el.textContent = data[el.dataset.proofField] || '-';
    });
    const latest = data.position === 'latest';
    const badge = $('[data-proof-position]', proofDialog);
    badge.innerHTML = latest ? '<i class="bi bi-flag"></i> Pengisian akhir' : '<i class="bi bi-play-circle"></i> Pengisian awal';

    const img = $('[data-proof-img]', proofDialog);
    const link = $('[data-proof-link]', proofDialog);
    const empty = $('[data-proof-empty]', proofDialog);
    const showEmpty = (text) => { link.hidden = true; empty.hidden = false; empty.lastChild.textContent = ' ' + text; };
    link.hidden = !data.struk;
    empty.hidden = !!data.struk;
    if (!data.struk) showEmpty('Tidak ada foto struk');
    img.onerror = () => showEmpty('Foto struk tidak dapat dimuat');
    if (data.struk) {
        img.src = data.struk;
        link.href = data.struk;
    } else {
        img.removeAttribute('src');
    }
    openDialog('proofDialog');
});
