// Halaman Approval BBM (admin-dashboard & admin-approval):
// keputusan massal, foto laporan, prognosa voucher, dan voucher terbit per tanggal.
import { openDialog, alertDialog } from '../../ui/dialog';
import { rupiah, escapeHtml } from '../../lib/format';
import { initDecisions } from '../../lib/decisions';

initDecisions();

/* ── Foto laporan transaksi (dimuat saat dibuka) ─────────────────── */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-photo-url]');
    if (!button) return;

    const body = document.getElementById('photoDialogBody');
    document.getElementById('photoDialog-title').textContent = button.dataset.photoTitle;
    body.innerHTML = '<p class="text-sm text-muted-foreground"><i class="bi bi-hourglass-split"></i> Memuat foto...</p>';
    openDialog('photoDialog');

    try {
        const response = await fetch(button.dataset.photoUrl, { headers: { Accept: 'application/json' } });
        const data = response.ok ? await response.json() : null;
        if (!data?.success || !data.base64) throw new Error('Foto tidak ditemukan');
        body.innerHTML = `<img src="${data.base64}" alt="${escapeHtml(button.dataset.photoTitle)}" class="max-h-[70vh] rounded-md">`;
    } catch (error) {
        body.innerHTML = `<p class="text-sm text-destructive"><i class="bi bi-exclamation-triangle"></i> Gagal memuat foto: ${escapeHtml(error.message)}</p>`;
    }
});

/* ── Prognosa voucher: filter status + export CSV ───────────────── */
const prognosisRows = () => [...document.querySelectorAll('#prognosisTable tbody tr[data-status]')];

document.addEventListener('click', (event) => {
    const filter = event.target.closest('[data-prognosis-filter]');
    if (!filter) return;
    const status = filter.dataset.prognosisFilter;
    document.querySelectorAll('[data-prognosis-filter]').forEach((b) => b.setAttribute('aria-pressed', b === filter));
    prognosisRows().forEach((row) => {
        row.hidden = status !== 'all' && row.dataset.status !== status;
    });
});

document.getElementById('exportPrognosis')?.addEventListener('click', () => {
    const rows = prognosisRows().filter((row) => !row.hidden);
    if (rows.length === 0) {
        alertDialog({ title: 'Tidak ada data', message: 'Tidak ada voucher untuk diexport.' });
        return;
    }

    const cell = (text) => `"${String(text).trim().replace(/"/g, '""')}"`;
    const header = 'Pemohon,Kendaraan/Alat,Kode Kendaraan,Tipe,Nominal,Kode Voucher,Status,Valid Sampai,Checklist';
    const lines = rows.map((row) => [...JSON.parse(row.dataset.csv), ''].map(cell).join(','));
    // BOM supaya Excel membaca UTF-8 dengan benar.
    const blob = new Blob(['﻿' + [header, ...lines].join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const link = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: `prognosa-voucher-${new Date().toISOString().slice(0, 10)}.csv`,
    });
    link.click();
    URL.revokeObjectURL(link.href);
});

/* ── Voucher terbit per tanggal (AJAX) + export Excel ───────────── */
const voucherPanel = document.getElementById('voucherPanel');

if (voucherPanel) {
    const dateInput = document.getElementById('voucherDate');
    const statusSelect = document.getElementById('voucherStatus');
    const tbody = document.getElementById('voucherBody');
    const selectAll = document.getElementById('voucherSelectAll');

    const emptyRow = '<tr><td colspan="7" class="py-10 text-center text-muted-foreground">Tidak ada voucher terbit pada tanggal ini.</td></tr>';
    const badge = (expired) => expired
        ? '<span class="inline-flex items-center gap-1 rounded-md bg-destructive-soft px-2 py-0.5 text-xs font-medium text-destructive"><i class="bi bi-clock-history"></i> Kedaluwarsa</span>'
        : '<span class="inline-flex items-center gap-1 rounded-md bg-success-soft px-2 py-0.5 text-xs font-medium text-success"><i class="bi bi-check-circle"></i> Disetujui</span>';

    const rowHtml = (r) => `
        <tr>
            <td><input type="checkbox" class="voucher-check size-4 accent-primary" value="${r.id}" aria-label="Pilih voucher"></td>
            <td><span class="font-medium">${escapeHtml(r.pemohon)}</span><span class="block text-xs text-muted-foreground">${escapeHtml(r.request_number)}</span></td>
            <td><span class="font-medium text-primary">${escapeHtml(r.vehicle_code)}</span><span class="block text-xs text-muted-foreground">${escapeHtml(r.vehicle_name)} (${escapeHtml(r.vehicle_type)})</span></td>
            <td class="whitespace-nowrap text-right tabular-nums">${rupiah(r.approved_amount)}</td>
            <td><code class="font-semibold tracking-wide">${escapeHtml(r.voucher_code)}</code><span class="block text-xs text-muted-foreground">Berlaku sampai ${escapeHtml(r.valid_until)}</span></td>
            <td>${badge(r.status === 'expired')}</td>
            <td class="text-xs">
                <span class="block">Approver: <strong>${escapeHtml(r.approver)}</strong></span>
                <span class="block text-muted-foreground">Approval: ${escapeHtml(r.approval_date)}</span>
                <span class="block text-muted-foreground">Permohonan: ${escapeHtml(r.permohonan_status)}</span>
                ${r.approval_notes ? `<span class="block text-muted-foreground">${escapeHtml(r.approval_notes)}</span>` : ''}
            </td>
        </tr>`;

    const updateSelected = () => {
        document.getElementById('voucherSelectedCount').textContent = tbody.querySelectorAll('.voucher-check:checked').length;
    };

    async function loadVouchers() {
        const url = new URL(voucherPanel.dataset.listUrl);
        url.search = new URLSearchParams({ date: dateInput.value, status: statusSelect.value });
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then((r) => r.json());
            tbody.innerHTML = res.data?.length ? res.data.map(rowHtml).join('') : emptyRow;
            document.getElementById('voucherCount').textContent = res.count ?? 0;
        } catch {
            tbody.innerHTML = emptyRow;
            document.getElementById('voucherCount').textContent = 0;
        }
        selectAll.checked = false;
        updateSelected();
    }

    dateInput.addEventListener('change', loadVouchers);
    statusSelect.addEventListener('change', loadVouchers);
    selectAll.addEventListener('change', () => {
        tbody.querySelectorAll('.voucher-check').forEach((cb) => { cb.checked = selectAll.checked; });
        updateSelected();
    });
    tbody.addEventListener('change', (event) => {
        if (event.target.matches('.voucher-check')) updateSelected();
    });

    voucherPanel.addEventListener('click', (event) => {
        const button = event.target.closest('[data-voucher-export]');
        if (!button) return;

        const ids = [...tbody.querySelectorAll('.voucher-check:checked')].map((cb) => cb.value);
        if (button.dataset.voucherExport === 'selected' && ids.length === 0) {
            alertDialog({ title: 'Belum ada pilihan', message: 'Pilih minimal satu voucher untuk diexport.' });
            return;
        }

        document.getElementById('exportDate').value = dateInput.value;
        document.getElementById('exportStatus').value = statusSelect.value;
        document.getElementById('exportIdsContainer').innerHTML = button.dataset.voucherExport === 'selected'
            ? ids.map((id) => `<input type="hidden" name="ids[]" value="${escapeHtml(id)}">`).join('')
            : '';
        document.getElementById('voucherExportForm').submit();
    });
}
