// Halaman Pelaporan (pelaporan.index): pilihan mode SPD/Operasional dan laporan BBM operasional
// dengan analisis struk AI. Foto yang gagal dianalisis (kuota AI habis atau error) diisi manual.
import { alertDialog } from '../../ui/dialog';
import { rupiah, escapeHtml, pageData } from '../../lib/format';
import { selectRoot, selectValue } from '../../lib/select-menu';

const { analyzeUrl, csrfToken, vouchers = {} } = pageData();

const KATEGORI_LABEL = { BBM: 'Struk BBM', Tol: 'Struk Tol', Parkir: 'Struk Parkir', Lainnya: 'Struk Lainnya', Tambahan: 'Tambahan' };
const KATEGORI_BADGE = {
    BBM: 'bg-destructive-soft text-destructive',
    Tol: 'bg-info-soft text-info',
    Parkir: 'bg-accent text-accent-foreground',
    Lainnya: 'bg-muted text-muted-foreground',
    Tambahan: 'bg-warning-soft text-warning',
};
const BADGE_BASE = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium';
const CONTROL = 'h-8 w-full rounded-md border border-input bg-card px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive';

/* ── Pilihan mode ───────────────────────────────────────────────── */
const panelSpd = document.getElementById('panel-spd');
const panelOp = document.getElementById('panel-operational');
const params = new URLSearchParams(location.search);

function activateMode(mode, persist = true) {
    if (panelSpd) panelSpd.hidden = mode !== 'spd';
    panelOp.hidden = mode === 'spd';
    document.querySelectorAll('[data-mode]').forEach((card) => card.setAttribute('aria-pressed', card.dataset.mode === mode));

    if (persist) {
        const url = new URL(location.href);
        url.searchParams.set('mode', mode);
        history.replaceState(null, '', url);
    }
}

document.querySelectorAll('[data-mode]').forEach((card) => card.addEventListener('click', () => activateMode(card.dataset.mode)));

// Bawaan: SPD bila ada surat yang perlu dilaporkan, selain itu operasional.
const wantsOperational = params.get('mode') === 'operational' || params.has('permohonan_id');
activateMode(wantsOperational || !panelSpd ? 'operational' : 'spd', false);

/* ── Langkah 1: pilih voucher (x-ui.select-menu) + kartu voucher ─ */
const permohonanRoot = selectRoot('selectPermohonan');
const infoPermohonan = document.getElementById('infoPermohonan');
const voucherCard = document.getElementById('voucherCard');
const dateFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

function renderVoucher() {
    const v = vouchers[selectValue(permohonanRoot)];
    if (!voucherCard) return;
    voucherCard.hidden = !v;
    if (!v) return;
    const set = (key, text) => { voucherCard.querySelector(`[data-v="${key}"]`).textContent = text || '-'; };
    set('voucher', v.voucher || v.number);
    set('number', v.voucher ? v.number : '');
    set('amount', rupiah(v.amount));
    set('vehicle', v.vehicle);
    set('gasoline', v.gasoline);
    set('validity', v.validUntil ? `s.d. ${dateFormat.format(new Date(v.validUntil))}` : '-');
    infoPermohonan.innerHTML = `${escapeHtml(v.vehicleName || '')}${v.vehicleName ? ', ' : ''}budget full tank <strong class="text-foreground">${v.budget ? rupiah(v.budget) : 'belum diisi'}</strong>`;
}
permohonanRoot?.addEventListener('select:change', renderVoucher);
// ?permohonan_id= dari tombol "Lapor Pembelian" sudah dipilih di server; tampilkan kartunya.
if (selectValue(permohonanRoot)) renderVoucher();

/* ── Ringkasan laporan (panel kanan) ────────────────────────────── */
const summary = (key) => document.querySelector(`[data-sum="${key}"]`);
const longDate = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
function renderSummary() {
    if (!summary('nominal')) return;
    const nominal = num(document.getElementById('nominalOp')?.value);
    const volume = num(document.getElementById('volumeOp')?.value);
    const date = document.getElementById('purchaseDateOp')?.value;
    summary('nominal').textContent = rupiah(nominal);
    summary('volume').textContent = volume > 0 ? `${volume.toLocaleString('id-ID')} L` : '-';
    summary('date').textContent = date ? longDate.format(new Date(`${date}T00:00`)) : '-';
    summary('files').textContent = String(document.getElementById('fotoFilesOp')?.files.length ?? 0);
}

/* ── Unggah foto + analisis AI ──────────────────────────────────── */
const fileInput = document.getElementById('fotoFilesOp');
const resultsBox = document.getElementById('aiResultsOp');
const statusEl = document.getElementById('aiStatusOp');
const fieldsWrap = document.getElementById('fileFieldsWrap');
const validationAlert = document.getElementById('strukValidationAlertOp');
const limitAlert = document.getElementById('aiLimitAlertOp');
const dropzone = document.querySelector('[data-dropzone]');
if (dropzone) {
    ['dragenter', 'dragover'].forEach((type) => dropzone.addEventListener(type, () => { dropzone.dataset.drag = 'true'; }));
    ['dragleave', 'drop'].forEach((type) => dropzone.addEventListener(type, () => { delete dropzone.dataset.drag; }));
}

// State per indeks file: { status:'ai_ok'|'manual', kategori, nominal, volume, tanggal, platNomor, text, el }
let fileState = {};
let strukValidationFailed = false;

const num = (value) => (Number.isNaN(parseFloat(value)) ? 0 : parseFloat(value));
const effKategori = (s) => (s.status === 'ai_ok' ? s.kategori || 'Lainnya' : s.kategori || '');

const STATUS_TONE = {
    muted: 'text-muted-foreground',
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    destructive: 'text-destructive',
};

function setStatus(text, tone = 'muted') {
    statusEl.textContent = text;
    statusEl.className = `text-sm ${STATUS_TONE[tone]}`;
}

function setMainField(id, value) {
    const el = document.getElementById(id);
    if (el && el.dataset.userEdited !== '1') el.value = value;
}

function recomputeTotals() {
    let totalNominal = 0;
    let totalVolume = 0;
    let mainTanggal = null;

    Object.values(fileState).forEach((s) => {
        const kat = effKategori(s);
        if (kat === 'BBM') {
            totalNominal += num(s.nominal);
            totalVolume += num(s.volume);
            mainTanggal ??= s.tanggal || null;
        } else if (kat && kat !== 'Tambahan') {
            totalNominal += num(s.nominal);
        }
    });

    setMainField('nominalOp', totalNominal > 0 ? totalNominal : '');
    setMainField('volumeOp', totalVolume > 0 ? totalVolume : '');
    const date = mainTanggal ? new Date(mainTanggal) : null;
    if (date && !Number.isNaN(date.getTime())) setMainField('purchaseDateOp', date.toISOString().slice(0, 10));

    // Kode lama hanya menyinkronkan sekali setelah analisis AI, sehingga isian manual
    // (kategori, nominal) tidak ikut terkirim dan nominal tersimpan Rp 0 (BUG-BBM-002).
    syncHiddenFields();
    renderSummary();
}

// Field utama (tanggal, volume, nominal) dipindah dari input tersembunyi ke panel rekap yang terlihat.
function buildManualRekap() {
    const panel = document.getElementById('manualRekapOp');
    const wrap = document.getElementById('manualRekapFieldsOp');
    if (wrap.childElementCount > 0) return;
    panel.hidden = false;

    [
        { id: 'purchaseDateOp', label: 'Tanggal pengisian', type: 'date', required: true },
        { id: 'volumeOp', label: 'Volume BBM (liter)', type: 'number', step: '0.01', min: '0', required: true },
        { id: 'nominalOp', label: 'Nominal total (Rp)', type: 'number', step: '1', min: '0' },
    ].forEach((spec) => {
        const input = document.getElementById(spec.id);
        Object.assign(input, { type: spec.type, className: CONTROL });
        if (spec.step) input.step = spec.step;
        if (spec.min) input.min = spec.min;

        const field = document.createElement('label');
        field.className = 'grid gap-1.5 text-sm font-medium';
        field.innerHTML = `<span>${spec.label}${spec.required ? ' <span class="text-destructive">*</span>' : ''}</span>`;
        field.appendChild(input);
        wrap.appendChild(field);
        input.addEventListener('input', () => { input.dataset.userEdited = '1'; });
    });
}

function syncHiddenFields() {
    fieldsWrap.innerHTML = '';
    Object.keys(fileState).sort((a, b) => a - b).forEach((i) => {
        const s = fileState[i];
        const kat = effKategori(s);
        const nominal = s.status === 'ai_ok' ? s.nominal || 0 : s.nominal;
        const meta = {
            kategori: kat,
            metadata: {
                tanggal: s.tanggal || null,
                nominal: nominal === '' ? null : num(nominal),
                volume: num(s.volume) || null,
                plat_nomor: s.platNomor || null,
            },
            text: s.status === 'ai_ok' ? s.text || '' : '',
            source: s.status,
        };
        // Keterangan hanya diisi user, hasil OCR tidak ditulis ke keterangan.
        const fields = {
            [`struk_kategoris[${i}]`]: kat,
            [`nominals[${i}]`]: nominal ?? '',
            [`keterangan_s[${i}]`]: '',
            [`kategori_sources[${i}]`]: s.status === 'ai_ok' ? 'ai' : 'manual',
            [`metadata_s[${i}]`]: JSON.stringify(meta),
        };
        Object.entries(fields).forEach(([name, value]) => {
            fieldsWrap.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name, value }));
        });
    });
}

function fileCard(index, file) {
    const card = document.createElement('div');
    card.id = `fileCard${index}`;
    card.className = 'flex items-start gap-3 rounded-lg border bg-card p-3';
    card.innerHTML = `
        <img class="size-20 shrink-0 rounded-md object-cover" alt="Pratinjau">
        <div class="grid min-w-0 flex-1 gap-1 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="file-ai-badge ${BADGE_BASE} bg-muted text-muted-foreground">Menganalisis</span>
                <strong class="file-ai-nominal"></strong>
                <span class="file-ai-status"></span>
            </div>
            <div class="file-ai-detail text-xs text-muted-foreground"></div>
            <details class="text-xs text-muted-foreground">
                <summary class="cursor-pointer">Teks OCR</summary>
                <pre class="file-ai-text mt-1 max-h-24 overflow-y-auto whitespace-pre-wrap break-words rounded bg-muted p-2"></pre>
            </details>
        </div>`;

    const reader = new FileReader();
    reader.onload = (event) => { card.querySelector('img').src = event.target.result; };
    reader.readAsDataURL(file);
    return card;
}

function renderManualInputs(card, index) {
    const detail = card.querySelector('.file-ai-detail');
    detail.innerHTML = `
        <div class="mt-1 grid gap-2 sm:grid-cols-4">
            <select class="m-kategori ${CONTROL} sm:col-span-2" aria-label="Kategori">
                <option value="">Pilih kategori</option>
                <option value="BBM">Struk BBM</option>
                <option value="Tol">Struk Tol</option>
                <option value="Parkir">Struk Parkir</option>
                <option value="Makan">Struk Makan / Konsumsi</option>
                <option value="Hotel">Struk Hotel / Penginapan</option>
                <option value="Lainnya">Struk Lainnya</option>
                <option value="Tambahan">Tambahan (bukan struk)</option>
            </select>
            <input type="number" min="0" step="1" class="m-nominal ${CONTROL}" placeholder="Nominal (Rp)" aria-label="Nominal">
            <input type="date" class="m-tanggal ${CONTROL}" aria-label="Tanggal transaksi">
            <input type="number" min="0" step="0.01" class="m-volume ${CONTROL}" placeholder="Liter" aria-label="Volume" hidden>
        </div>`;

    const state = fileState[index];
    state.el = {
        kategori: detail.querySelector('.m-kategori'),
        nominal: detail.querySelector('.m-nominal'),
        volume: detail.querySelector('.m-volume'),
        tanggal: detail.querySelector('.m-tanggal'),
    };

    state.el.kategori.addEventListener('change', () => {
        state.kategori = state.el.kategori.value;
        state.el.volume.hidden = state.kategori !== 'BBM';
        if (state.kategori !== 'BBM') state.volume = '';
        recomputeTotals();
    });
    state.el.nominal.addEventListener('input', () => { state.nominal = state.el.nominal.value; recomputeTotals(); });
    state.el.volume.addEventListener('input', () => { state.volume = state.el.volume.value; recomputeTotals(); });
    state.el.tanggal.addEventListener('change', () => { state.tanggal = state.el.tanggal.value; recomputeTotals(); });
}

function showAiResult(card, index, result) {
    const kategori = result.kategori || 'Lainnya';
    const metadata = result.metadata || {};
    fileState[index] = {
        status: 'ai_ok',
        kategori,
        nominal: metadata.nominal || 0,
        volume: metadata.volume || 0,
        tanggal: metadata.tanggal || '',
        platNomor: metadata.plat_nomor || '',
        text: result.text || '',
        el: null,
    };

    const badge = card.querySelector('.file-ai-badge');
    badge.textContent = KATEGORI_LABEL[kategori] || kategori;
    badge.className = `file-ai-badge ${BADGE_BASE} ${KATEGORI_BADGE[kategori] || KATEGORI_BADGE.Lainnya}`;
    card.querySelector('.file-ai-nominal').textContent = rupiah(metadata.nominal || 0);
    card.querySelector('.file-ai-detail').textContent = [
        metadata.volume ? `${metadata.volume} L` : '',
        metadata.harga_per_liter ? `${rupiah(metadata.harga_per_liter)} per liter` : '',
    ].filter(Boolean).join(', ') || '-';
    card.querySelector('.file-ai-text').textContent = result.text || '(tanpa teks)';
    card.querySelector('.file-ai-status').innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
}

function showManual(card, index, reason) {
    fileState[index] = { status: 'manual', kategori: '', nominal: '', volume: '', tanggal: '', platNomor: '', text: '', el: null };
    card.classList.add('border-warning', 'bg-warning-soft/40');

    const badge = card.querySelector('.file-ai-badge');
    badge.textContent = reason === 'api_limit' ? 'Batas AI, isi manual' : 'Gagal dianalisis, isi manual';
    badge.className = `file-ai-badge ${BADGE_BASE} bg-warning-soft text-warning`;
    card.querySelector('.file-ai-nominal').textContent = '';
    card.querySelector('.file-ai-text').textContent = '';
    card.querySelector('.file-ai-status').innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
    renderManualInputs(card, index);
}

// Semua kegagalan AI (rate limit, respons rusak, jaringan, hasil parsial) berakhir di isian manual:
// laporan harus tetap bisa dikirim, tidak boleh berhenti di pesan error.
function showAiFailure(message, hasApiLimit = false) {
    document.getElementById('aiLimitMsgOp').textContent = hasApiLimit
        ? 'Layanan AI (Gemini) sedang mencapai batas kuota. Isi data secara manual pada foto yang ditandai kuning, lalu kirim laporan seperti biasa.'
        : `${message} Lengkapi kategori dan nominal tiap foto yang ditandai, lalu kirim laporan seperti biasa.`;
    limitAlert.hidden = false;
    buildManualRekap();
}

function enableManualEntry(message) {
    [...fileInput.files].forEach((_, index) => {
        const card = document.getElementById(`fileCard${index}`);
        if (card && fileState[index]?.status !== 'ai_ok') showManual(card, index, 'error');
    });
    recomputeTotals();
    showAiFailure(message);
    setStatus('Mode manual aktif. Isi data struk lalu kirim laporan.', 'warning');
}

fileInput?.addEventListener('change', async () => {
    const files = [...fileInput.files];

    // Voucher wajib dipilih dulu supaya struk bisa dicocokkan dengan permohonan.
    if (!selectValue(permohonanRoot)) {
        fileInput.value = '';
        setStatus('Pilih voucher terlebih dahulu sebelum mengunggah struk.', 'destructive');
        permohonanRoot?.querySelector('[data-select-trigger]').setAttribute('aria-invalid', 'true');
        renderSummary();
        return;
    }
    const label = dropzone?.querySelector('[data-dropzone-label]');
    if (label) label.textContent = files.length ? `${files.length} foto dipilih. Ketuk untuk mengganti.` : 'JPG, PNG, atau WEBP. Bisa beberapa foto sekaligus.';
    renderSummary();

    resultsBox.innerHTML = '';
    fieldsWrap.innerHTML = '';
    fileState = {};
    strukValidationFailed = false;
    limitAlert.hidden = true;
    validationAlert.hidden = true;
    setStatus('');
    if (files.length === 0) return;

    files.forEach((file, index) => resultsBox.appendChild(fileCard(index, file)));
    setStatus(`Menganalisis ${files.length} foto...`, 'primary');

    const body = new FormData();
    files.forEach((file) => body.append('images[]', file));
    body.append('permohonan_id', selectValue(permohonanRoot));

    let response;
    try {
        response = await fetch(analyzeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }, body });
    } catch {
        enableManualEntry('Tidak dapat menghubungi layanan AI.');
        return;
    }
    if (response.status === 429) {
        enableManualEntry('Permintaan analisis sedang dibatasi (rate limit).');
        return;
    }

    let data;
    try {
        data = await response.json();
    } catch {
        enableManualEntry('Respons server tidak valid.');
        return;
    }

    if (!(data?.success && data.results)) {
        enableManualEntry(data?.message ? `${data.message}.` : 'Analisis AI tidak tersedia.');
        return;
    }

    let hasApiLimit = Boolean(data.has_api_limit);
    let hasApiError = false;

    data.results.forEach((result, index) => {
        const card = document.getElementById(`fileCard${index}`);
        if (!card) return;
        if (result.status === 'ok') {
            showAiResult(card, index, result);
        } else {
            if (result.status === 'api_limit') hasApiLimit = true;
            else hasApiError = true;
            showManual(card, index, result.status);
        }
    });

    // Foto tanpa hasil (mis. server berhenti di tengah batch) wajib diisi manual.
    files.forEach((_, index) => {
        const card = document.getElementById(`fileCard${index}`);
        if (card && !fileState[index]) {
            showManual(card, index, 'error');
            hasApiError = true;
        }
    });

    recomputeTotals();

    // Kecocokan struk dengan permohonan (nominal dan plat nomor).
    if (data.validation) {
        strukValidationFailed = !data.validation.valid;
        validationAlert.hidden = !strukValidationFailed;
        if (strukValidationFailed) {
            document.getElementById('strukValidationMsgOp').textContent = data.validation.message || 'Harap unggah struk yang sebenarnya.';
            Object.entries(fileState).forEach(([index, s]) => {
                if (effKategori(s) === 'BBM') document.getElementById(`fileCard${index}`)?.classList.add('border-destructive');
            });
        }
    }

    if (hasApiLimit || hasApiError) {
        showAiFailure('Sebagian foto gagal dianalisis AI.', hasApiLimit);
    }

    const manualCount = Object.values(fileState).filter((s) => s.status === 'manual').length;
    if (manualCount > 0) {
        setStatus(`${data.results.length - manualCount} dari ${data.results.length} foto dianalisis AI, ${manualCount} foto perlu diisi manual.`, 'warning');
    } else {
        setStatus(`${data.results.length} foto sudah dianalisis. Periksa hasilnya lalu kirim laporan.`, 'success');
    }
});

/* ── Validasi sebelum kirim ─────────────────────────────────────── */
document.getElementById('formOperational')?.addEventListener('submit', (event) => {
    if (strukValidationFailed) {
        event.preventDefault();
        alertDialog({
            title: 'Struk berbeda',
            message: document.getElementById('strukValidationMsgOp').textContent.trim() || 'Harap unggah struk yang sebenarnya.',
            variant: 'destructive',
        });
        return;
    }

    const problems = [];
    const flag = (el, invalid) => el?.toggleAttribute('aria-invalid', invalid);

    Object.entries(fileState).forEach(([index, s]) => {
        if (s.status !== 'manual') return;
        const n = Number(index) + 1;
        flag(s.el?.kategori, !s.kategori);
        if (!s.kategori) problems.push(`Foto ${n}: pilih kategori struk.`);
        const missingNominal = s.kategori && s.kategori !== 'Tambahan' && (s.nominal === '' || s.nominal === null);
        flag(s.el?.nominal, Boolean(missingNominal));
        if (missingNominal) problems.push(`Foto ${n}: isi nominal.`);
    });

    const volume = document.getElementById('volumeOp');
    const purchaseDate = document.getElementById('purchaseDateOp');
    const volumeMissing = !(num(volume.value) > 0);
    flag(volume, volumeMissing);
    if (volumeMissing) problems.push('Volume BBM wajib diisi, minimal 0.01 liter.');
    flag(purchaseDate, !purchaseDate.value);
    if (!purchaseDate.value) problems.push('Tanggal pengisian wajib diisi.');

    if (problems.length > 0) {
        event.preventDefault();
        alertDialog({
            title: 'Lengkapi data manual',
            html: `<ul class="list-inside list-disc text-left">${problems.map((p) => `<li>${escapeHtml(p)}</li>`).join('')}</ul>`,
            confirmText: 'OK, saya isi',
        });
    }
});

renderSummary();
