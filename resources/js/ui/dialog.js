// Dialog & AlertDialog (struktur shadcn) di atas elemen <dialog> native.
//   <button data-dialog-open="id">  membuka <dialog id="id">
//   <button data-dialog-close>      menutup dialog terdekat
//   <dialog data-static>            tidak tertutup saat klik di luar

export function openDialog(id) {
    const dialog = document.getElementById(id);
    if (dialog && !dialog.open) dialog.showModal();
    return dialog;
}

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        event.preventDefault();
        openDialog(opener.dataset.dialogOpen);
        return;
    }

    if (event.target.closest('[data-dialog-close]')) {
        event.target.closest('dialog')?.close();
        return;
    }

    // Konten dialog berada di elemen anak, jadi klik tepat pada <dialog> berarti klik backdrop.
    if (event.target instanceof HTMLDialogElement && event.target.open && !('static' in event.target.dataset)) {
        event.target.close();
    }
});

// Pengganti confirm()/alert() bawaan browser, memakai #confirm-dialog di layout.
function runDialog({ title, message, html, confirmText, cancelText, variant, showCancel }) {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog) return Promise.resolve(showCancel ? window.confirm(message) : (window.alert(message), true));

    const ok = dialog.querySelector('[data-confirm-ok]');
    const cancel = dialog.querySelector('[data-confirm-cancel]');
    const body = dialog.querySelector('[data-confirm-message]');

    dialog.querySelector('[data-confirm-title]').textContent = title;
    if (html) body.innerHTML = html;
    else body.textContent = message;
    ok.textContent = confirmText;
    ok.dataset.variant = variant;
    cancel.textContent = cancelText;
    cancel.hidden = !showCancel;

    return new Promise((resolve) => {
        const finish = (result) => {
            ok.removeEventListener('click', onOk);
            dialog.removeEventListener('close', onClose);
            resolve(result);
        };
        const onOk = () => {
            dialog.returnValue = 'ok';
            dialog.close();
        };
        const onClose = () => finish(dialog.returnValue === 'ok');

        dialog.returnValue = '';
        ok.addEventListener('click', onOk);
        dialog.addEventListener('close', onClose);
        dialog.showModal();
        ok.focus();
    });
}

export function confirmDialog({
    title = 'Konfirmasi',
    message = '',
    html = '',
    confirmText = 'Ya, lanjutkan',
    cancelText = 'Batal',
    variant = 'default',
} = {}) {
    return runDialog({ title, message, html, confirmText, cancelText, variant, showCancel: true });
}

export function alertDialog({ title = 'Informasi', message = '', html = '', confirmText = 'OK', variant = 'default' } = {}) {
    return runDialog({ title, message, html, confirmText, cancelText: '', variant, showCancel: false });
}

window.siaga = Object.assign(window.siaga ?? {}, { openDialog, confirmDialog, alertDialog });
