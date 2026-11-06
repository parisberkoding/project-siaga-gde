// <x-ui.select-menu>: memilih opsi (tombol) mengisi input tersembunyi, label pemicu, dan tanda centang.
// Opsi tautan (href) dibiarkan berpindah halaman seperti biasa. Event "select:change" dikirim dari root
// ([data-select]) dengan detail { value } untuk JS halaman.
import { setSelectValue } from '../lib/select-menu';

document.addEventListener('click', (event) => {
    const option = event.target.closest('[data-select] [role="option"]');
    if (!option || option.tagName === 'A') return;

    const root = option.closest('[data-select]');
    setSelectValue(root, option.dataset.value, { notify: true });
    // Fokus kembali ke pemicu lebih dulu, supaya JS halaman yang membuka popover lain (mis. kalender) tetap memegang fokus.
    root.querySelector('[data-select-trigger]').focus();
    root.dispatchEvent(new CustomEvent('select:change', { bubbles: true, detail: { value: option.dataset.value } }));
});

// searchable: saring opsi sesuai ketikan; dibuka = kotak cari dikosongkan & difokuskan; Enter = pilih opsi pertama yang tampil.
const filterOptions = (list, query) => {
    const q = query.trim().toLowerCase();
    let shown = 0;
    list.querySelectorAll('[role="option"]').forEach((o) => {
        o.hidden = q !== '' && !o.textContent.toLowerCase().includes(q);
        if (!o.hidden) shown += 1;
    });
    const empty = list.querySelector('[data-select-empty]');
    if (empty) empty.hidden = shown > 0;
};
document.addEventListener('input', (event) => {
    if (event.target.matches?.('[data-select-search]')) filterOptions(event.target.closest('[role="listbox"]'), event.target.value);
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || !event.target.matches?.('[data-select-search]')) return;
    event.preventDefault(); // jangan kirim form induk
    [...event.target.closest('[role="listbox"]').querySelectorAll('[role="option"]')].find((o) => !o.hidden)?.click();
});
document.addEventListener('toggle', (event) => {
    const search = event.newState === 'open' && event.target.querySelector?.('[data-select-search]');
    if (!search) return;
    search.value = '';
    filterOptions(event.target, '');
    setTimeout(() => search.focus()); // setelah ui/dropdown.js memfokuskan opsi terpilih
}, true);

// required: input tersembunyi tidak ikut validasi bawaan browser, jadi diperiksa di sini (capture, sebelum
// handler data-confirm / data-loading). Kosong = kirim dibatalkan, pemicu ditandai merah dan difokuskan.
document.addEventListener('submit', (event) => {
    const empty = [...event.target.querySelectorAll('[data-select][data-required]')]
        .filter((root) => !root.querySelector('[data-select-input]')?.value);
    if (!empty.length) return;
    event.preventDefault();
    event.stopPropagation();
    empty.forEach((root) => root.querySelector('[data-select-trigger]').setAttribute('aria-invalid', 'true'));
    empty[0].querySelector('[data-select-trigger]').focus();
}, true);
