// Pelaporan & Validasi SPD (driver): upload struk bulk + AI, kelompok per tanggal, autosave item,
// isi manual untuk foto yang gagal dianalisis, dan mode "Ubah Semua Isian".
import { rupiah, pageData } from '../../lib/format';
import { alertDialog, confirmDialog } from '../../ui/dialog';
import { toast } from '../../ui/toast';

const { bulkUploadUrl, bulkUpdateUrl, updateItemUrlBase, csrfToken, maxBulkUpload } = pageData();
const $ = (id) => document.getElementById(id);
const draftContainer = $('draftItemsContainer');
const uploadStatus = $('uploadStatus');

const kategoriClass = {
    BBM: 'bg-destructive-soft text-destructive', Tol: 'bg-accent text-accent-foreground', Parkir: 'bg-info-soft text-info',
    Makan: 'bg-success-soft text-success', Hotel: 'bg-secondary text-secondary-foreground', Tambahan: 'bg-warning-soft text-warning',
};
const tones = { info: 'text-primary', success: 'text-success', warning: 'text-warning', danger: 'text-destructive' };

const jsonHeaders = { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' };
const field = (card, name) => card.querySelector(`[data-field="${name}"]`);
const allCards = (root = document) => [...root.querySelectorAll('[data-struk]')];
const shortDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
const longDate = (key) => (key === 'tanpa-tanggal' ? 'Tanpa tanggal'
    : new Date(`${key}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
const cardAmount = (card) => (field(card, 'kategori').value === 'Tambahan' ? 0 : parseFloat(field(card, 'nominal').value) || 0);
const sum = (cards) => cards.reduce((total, card) => total + cardAmount(card), 0);

function setStatus(text, tone = 'info') {
    uploadStatus.textContent = text;
    uploadStatus.className = `text-sm sm:ml-auto ${tones[tone] ?? ''}`;
}

// ── Total & ringkasan ──
function recalcTotals() {
    draftContainer.querySelectorAll('[data-date-group]').forEach((group) => {
        const cards = allCards(group);
        group.querySelector('[data-group-meta]').textContent = `${cards.length} item · Total ${rupiah(sum(cards))}`;
    });
    document.querySelectorAll('[data-report]').forEach((report) => {
        report.querySelector('[data-report-total]').textContent = rupiah(sum(allCards(report)));
    });

    const drafts = allCards(draftContainer);
    $('draftSummary').textContent = `${drafts.length} item · ${rupiah(sum(drafts))}`;
    $('draftEmpty').hidden = drafts.length > 0;
    if ($('totalFinal')) $('totalFinal').textContent = rupiah(sum(allCards()));
}

// ── Kelompok tanggal ──
function ensureDateGroup(key) {
    let group = draftContainer.querySelector(`[data-date-group="${key}"]`);
    if (!group) {
        group = $('dateGroupTemplate').content.firstElementChild.cloneNode(true);
        group.dataset.dateGroup = key;
        group.querySelector('[data-group-label]').textContent = longDate(key);
        const next = [...draftContainer.querySelectorAll(':scope > [data-date-group]')].find((g) => g.dataset.dateGroup > key);
        draftContainer.insertBefore(group, next ?? null);
    }
    return group.querySelector('[data-group-items]');
}

function pruneEmptyGroups() {
    draftContainer.querySelectorAll(':scope > [data-date-group]').forEach((group) => {
        if (!group.querySelector('[data-struk]')) group.remove();
    });
}

function moveCardToGroup(card, key) {
    ensureDateGroup(key).appendChild(card);
    card.dataset.groupKey = key;
    pruneEmptyGroups();
}

// ── Kartu item ──
function syncSummary(card) {
    const kategori = field(card, 'kategori').value;
    const tanggal = field(card, 'tanggal').value;
    const keterangan = field(card, 'keterangan').value;

    const badge = card.querySelector('[data-kategori-badge]');
    badge.textContent = kategori;
    badge.className = `rounded-md px-2 py-0.5 text-xs font-medium ${kategoriClass[kategori] ?? 'bg-muted text-muted-foreground'}`;
    card.querySelector('[data-nominal]').textContent = rupiah(parseFloat(field(card, 'nominal').value) || 0);

    card.querySelector('[data-date]').hidden = !tanggal;
    if (tanggal) card.querySelector('[data-date-label]').textContent = shortDate(tanggal);

    const ket = card.querySelector('[data-ket]');
    ket.textContent = keterangan;
    ket.title = keterangan;
    ket.hidden = !keterangan;
}

function flashAutosave(card) {
    const el = card.querySelector('[data-autosave]');
    el.hidden = false;
    clearTimeout(el.timer);
    el.timer = setTimeout(() => { el.hidden = true; }, 1600);
}

async function saveItem(card) {
    try {
        const res = await fetch(`${updateItemUrlBase}/${card.dataset.slug}`, {
            method: 'PATCH',
            headers: jsonHeaders,
            body: JSON.stringify({
                nominal: field(card, 'nominal').value,
                struk_kategori: field(card, 'kategori').value,
                keterangan: field(card, 'keterangan').value,
                tanggal: field(card, 'tanggal').value || null,
            }),
        });
        const data = await res.json();
        if (!data.success) {
            toast({ title: 'Gagal menyimpan', description: data.message || 'Gagal menyimpan perubahan.', variant: 'error' });
            return;
        }
        // Tanggal berubah: pindahkan kartu ke kelompok tanggal baru tanpa reload.
        if (data.groupKey && card.dataset.groupKey !== data.groupKey && draftContainer.contains(card)) moveCardToGroup(card, data.groupKey);
        syncSummary(card);
        flashAutosave(card);
        recalcTotals();
    } catch {
        toast({ title: 'Koneksi gagal', description: 'Perubahan belum tersimpan, coba lagi.', variant: 'error' });
    }
}

async function deleteItem(card) {
    const ok = await confirmDialog({ title: 'Hapus struk?', message: 'File ini akan dihapus dari laporan.', confirmText: 'Hapus', variant: 'destructive' });
    if (!ok) return;
    try {
        const res = await fetch(`${updateItemUrlBase}/${card.dataset.slug}`, { method: 'DELETE', headers: jsonHeaders });
        const data = await res.json();
        if (!data.success) return toast({ title: 'Gagal menghapus', description: data.message || 'Gagal menghapus item.', variant: 'error' });
        card.remove();
        toast({ title: 'Struk dihapus', variant: 'success', duration: 3000 });
        pruneEmptyGroups();
        recalcTotals();
    } catch {
        toast({ title: 'Koneksi gagal', description: 'Item belum terhapus, coba lagi.', variant: 'error' });
    }
}

// Perubahan nominal/keterangan disimpan otomatis (debounce) supaya tidak hilang saat refresh.
function bindCard(card) {
    let timer;
    const debounced = () => {
        clearTimeout(timer);
        timer = setTimeout(() => saveItem(card), 900);
    };
    card.querySelector('[data-save]').addEventListener('click', () => saveItem(card));
    card.querySelector('[data-del]').addEventListener('click', () => deleteItem(card));
    field(card, 'nominal').addEventListener('input', debounced);
    field(card, 'keterangan').addEventListener('input', debounced);
    field(card, 'kategori').addEventListener('change', () => saveItem(card));
    field(card, 'tanggal').addEventListener('change', () => saveItem(card));
}

function fillCard(card, item) {
    const tanggal = item.tanggal ? item.tanggal.split('T')[0] : '';
    card.dataset.slug = item.slug;
    card.dataset.groupKey = tanggal || 'tanpa-tanggal';
    card.querySelector('[data-thumb]').href = item.url;
    const img = card.querySelector('[data-thumb-img]');
    img.src = item.url;
    img.alt = `Struk ${item.item_code}`;
    img.hidden = false;
    const code = card.querySelector('[data-code]');
    code.textContent = item.item_code;
    code.title = item.item_code;

    field(card, 'tanggal').value = tanggal;
    field(card, 'nominal').value = item.nominal ?? 0;
    field(card, 'kategori').value = item.kategori;
    field(card, 'keterangan').value = item.keterangan || '';

    // Plat struk vs plat kendaraan SPD; null/undefined berarti tidak dibandingkan.
    const plat = item.plat_cocok === true ? 'ok' : item.plat_cocok === false ? 'beda' : null;
    card.querySelectorAll('[data-plat]').forEach((el) => { el.hidden = el.dataset.plat !== plat; });
    card.querySelector('[data-plat-nomor]').textContent = (item.plat_nomor || '').toUpperCase();
    card.querySelector('[data-plat="beda"]').title = `Plat pada struk berbeda dari plat kendaraan SPD (${item.plat_kendaraan || '-'}).`;

    const warn = card.querySelector('[data-tanggal-warning]');
    warn.hidden = !(item.tanggal_di_luar_periode || item.tanggal_tahun_beda);
    warn.title = 'Tanggal hasil OCR di luar periode perjalanan dinas.'
        + (item.tanggal_periode_spd ? ` Periode SPD: ${item.tanggal_periode_spd}.` : '')
        + (item.tanggal_mentah ? ` Terbaca di struk: "${item.tanggal_mentah}".` : '')
        + ' Periksa & perbaiki pada kolom Tanggal Struk.';

    syncSummary(card);
}

function appendItemCard(item) {
    const card = $('strukCardTemplate').content.firstElementChild.cloneNode(true);
    fillCard(card, item);
    ensureDateGroup(card.dataset.groupKey).appendChild(card);
    bindCard(card);
    if (draftContainer.hasAttribute('data-bulk-edit-mode')) card.toggleAttribute('data-bulk-edit', true);
    flashAutosave(card);
    recalcTotals();
}

// ── Upload bulk (maks maxBulkUpload per kirim) ──
async function postStrukBatch(body) {
    const res = await fetch(bulkUploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }, body });
    if (res.status === 429) return { error: 'Terlalu banyak permintaan. Tunggu sekitar 1 menit lalu coba lagi.' };
    try {
        return await res.json();
    } catch {
        return { error: 'Respons server tidak valid.' };
    }
}

function refreshManualWrap() {
    $('manualPendingWrap').hidden = $('manualPendingList').children.length === 0;
}

function queueManualEntry(file, detail) {
    const card = $('manualEntryTemplate').content.firstElementChild.cloneNode(true);
    const mp = (name) => card.querySelector(`[data-mp="${name}"]`);
    card.querySelector('[data-mp-reason]').textContent = detail.reason === 'api_limit' ? 'AI limit, isi manual' : 'Gagal analisis, isi manual';
    card.querySelector('[data-mp-message]').textContent = detail.message || '';
    if (file) card.querySelector('[data-mp-preview]').src = URL.createObjectURL(file);

    const done = (message) => {
        card.remove();
        refreshManualWrap();
        setStatus(message, 'success');
    };

    const saveButton = card.querySelector('[data-mp-save]');
    saveButton.addEventListener('click', async () => {
        if (!file) return alertDialog({ message: 'File tidak tersedia untuk dikirim ulang.' });
        const kategori = mp('kategori').value;
        const nominal = mp('nominal').value;
        if (!kategori) return alertDialog({ message: 'Pilih kategori terlebih dahulu.' });
        if (kategori !== 'Tambahan' && nominal === '') return alertDialog({ message: 'Isi nominal terlebih dahulu.' });

        const body = new FormData();
        body.append('images[]', file);
        body.append('manual_data[0][kategori]', kategori);
        if (nominal !== '') body.append('manual_data[0][nominal]', nominal);
        if (mp('tanggal').value) body.append('manual_data[0][tanggal]', mp('tanggal').value);
        if (mp('keterangan').value.trim()) body.append('manual_data[0][keterangan]', mp('keterangan').value.trim());

        saveButton.disabled = true;
        const data = await postStrukBatch(body);
        saveButton.disabled = false;
        if (data.error) return alertDialog({ message: data.error, variant: 'destructive' });
        if (!data.items?.length) return alertDialog({ message: data.message || 'Gagal menyimpan. Coba lagi.', variant: 'destructive' });
        data.items.forEach(appendItemCard);
        done('Foto disimpan manual sebagai draft.');
    });

    const retryButton = card.querySelector('[data-mp-retry]');
    retryButton.addEventListener('click', async () => {
        if (!file) return alertDialog({ message: 'File tidak tersedia.' });
        const body = new FormData();
        body.append('images[]', file);
        retryButton.disabled = true;
        setStatus('Menganalisis ulang 1 foto...');
        const data = await postStrukBatch(body);
        retryButton.disabled = false;
        if (data.error) return setStatus(data.error, 'danger');
        if (!data.items?.length) return setStatus('Analisis masih gagal, silakan isi manual.', 'warning');
        data.items.forEach(appendItemCard);
        done('Analisis ulang berhasil.');
    });

    card.querySelector('[data-mp-discard]').addEventListener('click', () => {
        card.remove();
        refreshManualWrap();
    });

    $('manualPendingList').appendChild(card);
    refreshManualWrap();
}

const bulkFile = $('bulkFile');
const btnBulkUpload = $('btnBulkUpload');

bulkFile.addEventListener('change', () => {
    const count = bulkFile.files.length;
    $('bulkFileCount').textContent = count ? `${count} file dipilih (${Math.ceil(count / maxBulkUpload)}x kirim @${maxBulkUpload})` : '';
    if (count > maxBulkUpload) setStatus(`Lebih dari ${maxBulkUpload} file, akan dikirim per ${maxBulkUpload} gambar.`, 'warning');
    else setStatus('');
});

btnBulkUpload.addEventListener('click', async () => {
    const files = [...bulkFile.files];
    if (!files.length) return alertDialog({ title: 'Belum ada file', message: 'Pilih minimal 1 gambar struk terlebih dahulu.' });

    const batches = [];
    for (let i = 0; i < files.length; i += maxBulkUpload) batches.push(files.slice(i, i + maxBulkUpload));

    btnBulkUpload.disabled = true;
    for (const [b, batch] of batches.entries()) {
        const label = `Batch ${b + 1}/${batches.length}`;
        const body = new FormData();
        batch.forEach((file) => body.append('images[]', file));
        setStatus(`${label}: mengupload & menganalisis ${batch.length} foto...`);

        let data;
        try {
            data = await postStrukBatch(body);
        } catch {
            setStatus(`${label}: koneksi gagal.`, 'danger');
            break;
        }
        if (data.error) {
            setStatus(`${label}: ${data.error}`, 'danger');
            break;
        }

        (data.items || []).forEach(appendItemCard);
        (data.failed_details || []).forEach((detail) => queueManualEntry(batch[(detail.index || 1) - 1] || null, detail));

        if (data.has_api_limit) {
            setStatus('Kuota AI (Gemini) habis sementara, lengkapi foto yang gagal lewat form manual.', 'warning');
        } else if (data.success) {
            setStatus(data.message || `${label} berhasil.`, (data.failed || []).length ? 'warning' : 'success');
        } else {
            setStatus(`${label} gagal: ${data.message || 'terjadi kesalahan.'}`, 'danger');
            break;
        }
    }
    btnBulkUpload.disabled = false;
    bulkFile.value = '';
    $('bulkFileCount').textContent = '';
});

// ── Mode "Ubah Semua Isian": semua kartu draft disimpan sekali jalan ──
const btnBulkEdit = $('btnBulkEdit');

function setBulkEdit(active) {
    draftContainer.toggleAttribute('data-bulk-edit-mode', active);
    allCards(draftContainer).forEach((card) => card.toggleAttribute('data-bulk-edit', active));
    $('bulkEditBar').hidden = !active;
    $('bulkEditHint').hidden = !active;
    if (btnBulkEdit) btnBulkEdit.innerHTML = active ? '<i class="bi bi-x-circle"></i> Selesai' : '<i class="bi bi-pencil-square"></i> Ubah Semua Isian';
    if (active) {
        $('bulkEditCount').textContent = allCards(draftContainer).length;
        draftContainer.querySelector('[data-field="tanggal"]')?.focus();
    }
}

btnBulkEdit?.addEventListener('click', () => setBulkEdit(!draftContainer.hasAttribute('data-bulk-edit-mode')));
$('btnBulkCancel').addEventListener('click', () => setBulkEdit(false));

$('btnBulkSave').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const cards = allCards(draftContainer);
    if (!cards.length) return;

    const items = Object.fromEntries(cards.map((card) => [card.dataset.slug, {
        tanggal: field(card, 'tanggal').value || null,
        nominal: field(card, 'nominal').value || 0,
        struk_kategori: field(card, 'kategori').value || 'Lainnya',
        keterangan: field(card, 'keterangan').value || '',
    }]));

    const label = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Menyimpan...';
    try {
        const res = await fetch(bulkUpdateUrl, { method: 'PATCH', headers: jsonHeaders, body: JSON.stringify({ items }) });
        const data = await res.json();
        if (!data.success) return alertDialog({ title: 'Gagal menyimpan', message: data.message || 'Gagal menyimpan perubahan.', variant: 'destructive' });
        // Muat ulang supaya pengelompokan per tanggal dihitung ulang server.
        window.location.reload();
    } catch {
        alertDialog({ title: 'Koneksi gagal', message: 'Perubahan belum tersimpan, coba lagi.', variant: 'destructive' });
    } finally {
        button.disabled = false;
        button.innerHTML = label;
    }
});

allCards().forEach(bindCard);
recalcTotals();
