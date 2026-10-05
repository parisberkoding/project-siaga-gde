{{--
    Input PEMBERIAN DANA (pencairan) untuk sebuah surat SPD.

    Satu baris = SATU kali pemberian/transfer uang (BUKAN per hari). Tiap baris
    direkam ke tabel `surat_spd_budgets` milik surat tersebut, lengkap dengan
    bentuk pencairan (Tunai/Transfer), nomor referensi perbankan, dan rekening
    tujuan — dipakai juga oleh "Lembar Lampiran Pemberian Dana" pada dokumen LPJ.

    Parameter:
      - $name          : nama field array (default 'pencairans')
      - $rows          : koleksi SuratSpdBudget yang sudah ada (opsional)
      - $tanggalDefault: tanggal awal untuk baris baru (default: hari ini)
      - $totalTargetId : id elemen yang menampilkan total (opsional)
--}}
@php
    $name = $name ?? 'pencairans';
    $rows = $rows ?? collect();
    $tanggalDefault = $tanggalDefault ?? now()->toDateString();
    $totalTargetId = $totalTargetId ?? null;
    $uid = 'pk-' . substr(md5($name . microtime(true) . random_int(1000, 9999)), 0, 8);

    // Disiapkan di sini (bukan langsung di dalam @json) supaya Blade tidak
    // salah mengurai array bertingkat di dalam direktif.
    $existingRows = $rows->map(fn ($b) => [
        'tanggal'        => $b->tanggal ? $b->tanggal->format('Y-m-d') : null,
        'nominal'        => (float) $b->nominal,
        'bentuk'         => $b->bentuk_pencairan_label,
        'bank_reference' => $b->bank_reference,
        'rek_tujuan'     => $b->rek_tujuan,
        'catatan'        => $b->catatan,
    ])->values()->all();
@endphp

<div id="{{ $uid }}" class="pencairan-input" data-pencairan-root
     data-name="{{ $name }}"
     data-tanggal-default="{{ $tanggalDefault }}"
     data-total-target="{{ $totalTargetId }}">

    <div data-pencairan-body></div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1">
        <button type="button" class="btn btn-sm btn-outline-primary" data-pencairan-add>
            <i class="bi bi-plus-circle"></i> Tambah Pemberian Dana
        </button>
        <div class="small">
            Total dana diberikan:
            <strong class="text-success" data-pencairan-total>Rp 0</strong>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById(@json($uid));
    if (!root || root.dataset.pencairanReady === '1') return;
    root.dataset.pencairanReady = '1';

    const name = root.dataset.name;
    const defaultDate = root.dataset.tanggalDefault;
    const totalTarget = root.dataset.totalTarget;
    const body = root.querySelector('[data-pencairan-body]');
    const totalEl = root.querySelector('[data-pencairan-total]');

    const rowHtml = (i) => `
        <div class="border rounded p-2 mb-2" data-pencairan-row>
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" class="form-control form-control-sm"
                           name="${name}[${i}][tanggal]" value="${defaultDate}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Nominal (Rp)</label>
                    <input type="number" class="form-control form-control-sm pencairan-nominal"
                           name="${name}[${i}][nominal]" min="0" step="1000" placeholder="0">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Bentuk</label>
                    <select class="form-select form-select-sm pencairan-bentuk" name="${name}[${i}][bentuk_pencairan]">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 pencairan-transfer-only">
                    <label class="form-label small mb-1">No. Referensi Perbankan</label>
                    <input type="text" class="form-control form-control-sm"
                           name="${name}[${i}][bank_reference]" maxlength="255" placeholder="cth: TRF/2026/09/00123">
                </div>
                <div class="col-12 col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" data-pencairan-remove title="Hapus baris">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row g-2 mt-0">
                <div class="col-12 col-md-5 pencairan-transfer-only">
                    <label class="form-label small mb-1">Rekening Tujuan</label>
                    <input type="text" class="form-control form-control-sm"
                           name="${name}[${i}][rek_tujuan]" maxlength="255" placeholder="cth: BCA 1234567890 a.n. Nama">
                </div>
                <div class="col-12 col-md-7">
                    <label class="form-label small mb-1">Catatan <span class="text-muted">(opsional)</span></label>
                    <input type="text" class="form-control form-control-sm"
                           name="${name}[${i}][catatan]" maxlength="255" placeholder="cth: uang muka perjalanan">
                </div>
            </div>
        </div>`;

    function reindex() {
        body.querySelectorAll('[data-pencairan-row]').forEach((row, i) => {
            row.querySelectorAll('[name]').forEach(el => {
                const field = el.getAttribute('name').replace(/^[^\[]+\[\d+\]/, '');
                el.setAttribute('name', `${name}[${i}]${field}`);
            });
        });
    }

    function syncRow(row) {
        const bentuk = row.querySelector('.pencairan-bentuk');
        const isTransfer = bentuk && bentuk.value === 'Transfer';
        row.querySelectorAll('.pencairan-transfer-only').forEach(el => {
            el.classList.toggle('d-none', !isTransfer);
        });
    }

    function recalc() {
        let total = 0;
        body.querySelectorAll('.pencairan-nominal').forEach(inp => { total += parseInt(inp.value) || 0; });

        if (totalEl) totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        if (totalTarget) {
            const target = document.getElementById(totalTarget);
            if (target) target.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
    }

    function addRow(prefill) {
        const tmp = document.createElement('div');
        tmp.innerHTML = rowHtml(body.querySelectorAll('[data-pencairan-row]').length).trim();
        const row = tmp.firstElementChild;

        if (prefill) {
            row.querySelector(`[name$="[tanggal]"]`).value = prefill.tanggal || defaultDate;
            row.querySelector('.pencairan-nominal').value = prefill.nominal ?? '';
            const bentuk = row.querySelector('.pencairan-bentuk');
            bentuk.value = prefill.bentuk || 'Tunai';
            row.querySelector(`[name$="[bank_reference]"]`).value = prefill.bank_reference || '';
            row.querySelector(`[name$="[rek_tujuan]"]`).value = prefill.rek_tujuan || '';
            row.querySelector(`[name$="[catatan]"]`).value = prefill.catatan || '';
        }

        body.appendChild(row);

        row.querySelector('[data-pencairan-remove]').addEventListener('click', () => {
            row.remove();
            reindex();
            recalc();
        });
        row.querySelector('.pencairan-bentuk').addEventListener('change', () => syncRow(row));
        row.querySelector('.pencairan-nominal').addEventListener('input', recalc);

        syncRow(row);
        reindex();
        recalc();

        return row;
    }

    root.querySelector('[data-pencairan-add]').addEventListener('click', () => addRow(null));

    // Baris awal dari data yang sudah tersimpan (halaman validasi/detail).
    const existing = @json($existingRows);

    if (existing.length) {
        existing.forEach(row => addRow(row));
    } else {
        addRow(null);
    }
})();
</script>
