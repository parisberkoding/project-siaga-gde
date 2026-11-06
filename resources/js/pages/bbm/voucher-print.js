// Cetak voucher BBM (dialog requester/partials/voucher-print-modal): dipakai halaman Permohonan Aktif dan Detail.
import { confirmDialog, alertDialog } from '../../ui/dialog';

document.addEventListener('click', async (event) => {
    const link = event.target.closest('[data-thermer-print]');
    if (!link) return;

    if (!link.dataset.voucherCode) {
        event.preventDefault();
        alertDialog({ title: 'Voucher tidak lengkap', message: 'Data voucher tidak lengkap, voucher tidak dapat dicetak.' });
        return;
    }

    if (!/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        event.preventDefault();
        const ok = await confirmDialog({
            title: 'Cetak dari desktop',
            message: 'Pencetakan memakai aplikasi Bluetooth Print di perangkat Android. Buka pratinjau data voucher di tab baru?',
            confirmText: 'Buka pratinjau',
        });
        if (ok) window.open(link.dataset.responseUrl, '_blank');
        return;
    }

    const original = link.innerHTML;
    link.innerHTML = '<i class="bi bi-hourglass-split"></i> Membuka aplikasi...';
    link.setAttribute('aria-disabled', 'true');
    setTimeout(() => link.closest('dialog')?.close(), 500);
    setTimeout(() => {
        link.innerHTML = original;
        link.removeAttribute('aria-disabled');
    }, 3000);
});
