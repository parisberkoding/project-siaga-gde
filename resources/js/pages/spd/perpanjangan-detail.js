// Detail perpanjangan: konfirmasi sebelum setujui (nominal dana diinput Admin GA, bukan di sini).
import { confirmDialog } from '../../ui/dialog';

const form = document.getElementById('approveForm');

form.addEventListener('submit', async (event) => {
    if (form.dataset.confirmed) return;
    event.preventDefault();
    const ok = await confirmDialog({
        title: 'Setujui perpanjangan?',
        html: 'Tanggal kembali dan tenggat LPJ akan diperbarui.',
        confirmText: 'Ya, setujui',
        variant: 'success',
    });
    if (!ok) return;
    form.dataset.confirmed = '1';
    form.requestSubmit();
});
