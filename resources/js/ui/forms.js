import { confirmDialog } from './dialog';

// <form data-confirm="Pesan" data-confirm-title=".." data-confirm-variant="destructive" data-confirm-text="..">
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.dataset.confirm || form.dataset.confirmed) return;

    event.preventDefault();
    const ok = await confirmDialog({
        title: form.dataset.confirmTitle || 'Konfirmasi',
        message: form.dataset.confirm,
        confirmText: form.dataset.confirmText || 'Ya, lanjutkan',
        variant: form.dataset.confirmVariant || 'default',
    });
    if (!ok) return;

    // requestSubmit memicu event submit secara sinkron, jadi penanda cukup hidup selama panggilan ini.
    form.dataset.confirmed = '1';
    form.requestSubmit(event.submitter ?? undefined);
    delete form.dataset.confirmed;
});

// <form data-loading>: kunci tombol submit setelah dikirim supaya tidak terkirim dua kali.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !('loading' in form.dataset)) return;

    // form.elements juga mencakup tombol di luar form yang memakai atribut form="id".
    [...form.elements].filter((el) => el.type === 'submit').forEach((button) => {
        button.disabled = true;
        if (button.dataset.loadingText) button.textContent = button.dataset.loadingText;
    });
});

// <form data-autosubmit>: filter langsung terkirim saat isian berubah (tanpa tombol).
document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
    let timer;
    form.addEventListener('input', (event) => {
        // Tanpa name = bukan isian filter (mis. kotak cari di daftar x-ui.select-menu searchable): jangan kirim form.
        if (!event.target.name || !event.target.matches('input[type="search"], input[type="text"]')) return;
        clearTimeout(timer);
        timer = setTimeout(() => form.requestSubmit(), 400);
    });
    // <x-ui.select-menu> (shadcn Select) di dalam form autosubmit: kirim saat pilihan berubah.
    form.addEventListener('select:change', () => form.requestSubmit());
    form.addEventListener('change', (event) => {
        if (event.target.matches('select, input[type="date"], input[type="checkbox"], input[type="radio"]')) {
            form.requestSubmit();
        }
    });
});

// <button data-dismiss> di dalam elemen [data-dismissible]
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-dismiss]');
    if (button) button.closest('[data-dismissible]')?.remove();
});

// <form method="GET" data-clean-query>: isian kosong tidak ikut ke URL (?status=&vehicle_id= ...).
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !('cleanQuery' in form.dataset)) return;
    [...form.elements].filter((el) => el.name && el.value === '').forEach((el) => { el.disabled = true; });
});
// Kembali lewat tombol Back (bfcache) memulihkan isian yang tadi dinonaktifkan.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-clean-query] :disabled').forEach((el) => { el.disabled = false; });
});
