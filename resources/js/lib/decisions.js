// Keputusan Setujui/Tolak permohonan BBM lewat dialog, satu per satu atau massal.
// Dipakai halaman Approval BBM dan Kelola Permohonan. Kontrak markup:
//   form#bulkApprovalForm > input#decisionsInput      tujuan kirim (payload JSON sama seperti sebelumnya)
//   [data-decision-item="id"][data-summary='{...}']    satu permohonan (number, name, vehicle, amount)
//     [data-select]                                    checkbox pilih massal
//     [data-decide="approve|reject"]                   tombol keputusan cepat
//   [data-select-all]                                  checkbox pilih semua (opsional)
//   #bulkActionBar > #selectedCount, [data-decide-selected="approve|reject"], [data-clear-selection]
//   <x-bbm.decision-dialog />                          dialog konfirmasi + komentar
import { openDialog } from '../ui/dialog';
import { escapeHtml } from './format';

const config = {
    approve: {
        title: 'Setujui permohonan', confirm: 'Setujui', variant: 'success', icon: 'bi-check-lg',
        banner: 'bg-success-soft text-success', iconBg: 'bg-success text-white',
        text: (n) => `${n} permohonan akan disetujui dan voucher BBM diterbitkan.`,
    },
    reject: {
        title: 'Tolak permohonan', confirm: 'Tolak', variant: 'destructive', icon: 'bi-x-lg',
        banner: 'bg-destructive-soft text-destructive', iconBg: 'bg-destructive text-white',
        text: (n) => `${n} permohonan akan ditolak. Sebaiknya beri alasan di komentar.`,
    },
};

export function initDecisions() {
    const form = document.getElementById('bulkApprovalForm');
    const dialog = document.getElementById('decisionDialog');
    if (!form || !dialog) return;

    const bar = document.getElementById('bulkActionBar');
    const items = () => [...document.querySelectorAll('[data-decision-item]')];
    const selected = () => items().filter((item) => item.querySelector('[data-select]')?.checked);
    const confirmButton = dialog.querySelector('[data-decision-confirm]');
    let pending = { items: [], status: 'approve' };

    function renderSelection() {
        const count = selected().length;
        if (bar) bar.hidden = count === 0;
        const counter = document.getElementById('selectedCount');
        if (counter) counter.textContent = count;
        const all = document.querySelector('[data-select-all]');
        if (all) {
            all.checked = count > 0 && count === items().length;
            all.indeterminate = count > 0 && count < items().length;
        }
    }

    function open(targets, status) {
        if (!targets.length) return;
        const c = config[status];
        pending = { items: targets, status };

        document.getElementById('decisionDialog-title').textContent = `${c.title}${targets.length > 1 ? ` (${targets.length})` : ''}`;
        dialog.querySelector('[data-decision-banner]').className = `flex items-center gap-3 rounded-lg p-3 text-sm ${c.banner}`;
        dialog.querySelector('[data-decision-icon]').className = `flex size-9 shrink-0 items-center justify-center rounded-full text-base ${c.iconBg}`;
        dialog.querySelector('[data-decision-icon]').innerHTML = `<i class="bi ${c.icon}"></i>`;
        dialog.querySelector('[data-decision-text]').textContent = c.text(targets.length);
        dialog.querySelector('[data-decision-list]').innerHTML = targets.map((item) => {
            const s = JSON.parse(item.dataset.summary || '{}');
            return `<li class="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm">
                <span class="min-w-0"><span class="block truncate font-medium">${escapeHtml(s.name)}</span>
                <span class="block truncate text-xs text-muted-foreground">${escapeHtml(s.number)} · ${escapeHtml(s.vehicle)}</span></span>
                <span class="shrink-0 font-semibold tabular-nums">${escapeHtml(s.amount)}</span></li>`;
        }).join('');
        dialog.querySelector('[data-decision-note]').value = '';
        confirmButton.dataset.variant = c.variant;
        confirmButton.innerHTML = `<i class="bi ${c.icon}"></i> ${c.confirm}${targets.length > 1 ? ` ${targets.length} permohonan` : ''}`;
        openDialog('decisionDialog');
        dialog.querySelector('[data-decision-note]').focus();
    }

    confirmButton.addEventListener('click', () => {
        const note = dialog.querySelector('[data-decision-note]').value.trim() || null;
        document.getElementById('decisionsInput').value = JSON.stringify(
            pending.items.map((item) => ({ request_id: Number(item.dataset.decisionItem), status: pending.status, note })),
        );
        confirmButton.disabled = true;
        confirmButton.textContent = 'Menyimpan...';
        form.submit();
    });

    document.addEventListener('click', (event) => {
        const single = event.target.closest('[data-decide]');
        if (single) return open([single.closest('[data-decision-item]')], single.dataset.decide);

        const bulk = event.target.closest('[data-decide-selected]');
        if (bulk) return open(selected(), bulk.dataset.decideSelected);

        if (event.target.closest('[data-clear-selection]')) {
            items().forEach((item) => { item.querySelector('[data-select]').checked = false; });
            renderSelection();
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-select-all]')) {
            items().forEach((item) => { item.querySelector('[data-select]').checked = event.target.checked; });
        }
        if (event.target.matches('[data-select], [data-select-all]')) renderSelection();
    });

    renderSelection();
}
