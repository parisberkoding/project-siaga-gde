// Keputusan massal Setujui/Tolak per permohonan, dipakai halaman Approval BBM dan Kelola Permohonan.
//   [data-decision="approve|reject"][data-id]  tombol pilihan (aria-pressed)
//   [data-note="id"]                           komentar per permohonan (opsional)
//   #bulkActionBar, #selectedCount, #approveCount, #rejectCount, #saveDecisions
//   form#bulkApprovalForm berisi input#decisionsInput
import { confirmDialog } from '../ui/dialog';

export function initDecisions({ fallbackNote = () => '' } = {}) {
    const decisions = new Map(); // id -> 'approve' | 'reject'
    const bar = document.getElementById('bulkActionBar');
    if (!bar) return;

    const render = () => {
        document.querySelectorAll('[data-decision]').forEach((button) => {
            button.setAttribute('aria-pressed', decisions.get(button.dataset.id) === button.dataset.decision);
        });
        const values = [...decisions.values()];
        document.getElementById('selectedCount').textContent = values.length;
        document.getElementById('approveCount').textContent = values.filter((v) => v === 'approve').length;
        document.getElementById('rejectCount').textContent = values.filter((v) => v === 'reject').length;
        bar.hidden = values.length === 0;
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-decision]');
        if (!button) return;
        const { id, decision } = button.dataset;
        // Setujui dan Tolak saling meniadakan; klik ulang membatalkan pilihan.
        if (decisions.get(id) === decision) decisions.delete(id);
        else decisions.set(id, decision);
        render();
    });

    document.getElementById('saveDecisions').addEventListener('click', async () => {
        if (decisions.size === 0) return;

        const payload = [...decisions].map(([id, status]) => ({
            request_id: Number(id),
            status,
            note: document.querySelector(`[data-note="${id}"]`)?.value.trim() || fallbackNote() || null,
        }));
        const approve = payload.filter((d) => d.status === 'approve').length;

        const ok = await confirmDialog({
            title: 'Simpan keputusan?',
            message: `${approve} permohonan disetujui dan ${payload.length - approve} permohonan ditolak.`,
            confirmText: 'Simpan keputusan',
        });
        if (!ok) return;

        document.getElementById('decisionsInput').value = JSON.stringify(payload);
        document.getElementById('bulkApprovalForm').submit();
    });
}
