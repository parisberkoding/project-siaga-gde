// <button data-action-dialog="dialogId" data-url="..." data-info='{"nomor":"..."}'>: isi action form di dialog
// dan teks [data-info="kunci"] dari ringkasan, lalu buka dialognya. Dipakai antrian SPD/LPJ & perpanjangan.
import { openDialog } from './dialog';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-action-dialog]');
    if (!button) return;

    const dialog = openDialog(button.dataset.actionDialog);
    const info = JSON.parse(button.dataset.info || '{}');
    dialog.querySelector('form').action = button.dataset.url;
    dialog.querySelectorAll('[data-info]').forEach((el) => { el.textContent = info[el.dataset.info] ?? '-'; });
});
