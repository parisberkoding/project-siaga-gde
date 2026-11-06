// <x-ui.dropdown-menu> & <x-ui.select-menu>: Popover API (top layer) + posisi menempel di bawah-kanan tombol pemicunya.
const GAP = 4;

const triggerOf = (menu) => document.querySelector(`[popovertarget="${menu.id}"]`);

// Sebelum tampil: rata kanan dengan tombol, tepat di bawahnya.
document.addEventListener('beforetoggle', (event) => {
    const menu = event.target;
    if (!menu.matches?.('[data-dropdown]') || event.newState !== 'open') return;
    const rect = triggerOf(menu).getBoundingClientRect();
    // data-align="start": rata kiri dengan tombol (mis. month picker di kiri filter).
    const horizontal = menu.dataset.align === 'start'
        ? { left: `${Math.max(8, rect.left)}px` }
        : { right: `${Math.max(8, window.innerWidth - rect.right)}px` };
    Object.assign(menu.style, { inset: 'auto', top: `${rect.bottom + GAP}px`, ...horizontal });
    // Select (listbox) minimal selebar tombolnya, seperti shadcn Select.
    if (menu.getAttribute('role') === 'listbox') menu.style.minWidth = `${rect.width}px`;
}, true);

const ITEMS = '[role="menuitem"], [role="option"]';
const visibleItems = (menu) => [...menu.querySelectorAll(ITEMS)].filter((item) => item.checkVisibility());

// Setelah tampil (ukuran sudah diketahui): balik ke atas tombol bila tidak muat di bawah.
document.addEventListener('toggle', (event) => {
    const menu = event.target;
    if (!menu.matches?.('[data-dropdown]') || event.newState !== 'open') return;
    const rect = triggerOf(menu).getBoundingClientRect();
    if (rect.bottom + GAP + menu.offsetHeight > window.innerHeight - 8) {
        menu.style.top = `${Math.max(8, rect.top - GAP - menu.offsetHeight)}px`;
    }
    if (menu.dataset.align === 'start' && rect.left + menu.offsetWidth > window.innerWidth - 8) {
        menu.style.left = `${Math.max(8, window.innerWidth - 8 - menu.offsetWidth)}px`;
    }
    const items = visibleItems(menu);
    (items.find((item) => item.hasAttribute('aria-current') || item.getAttribute('aria-selected') === 'true') ?? items[0])?.focus();
}, true);

// Pilih item menutup menu; menggulir halaman juga menutup (posisi tidak lagi pas).
document.addEventListener('click', (event) => {
    const item = event.target.closest(`[data-dropdown] :is(${ITEMS})`);
    if (item) item.closest('[data-dropdown]').hidePopover();
});
window.addEventListener('scroll', (event) => {
    // Menggulir isi daftar itu sendiri (daftar pilihan panjang) tidak menutupnya.
    document.querySelectorAll('[data-dropdown]:popover-open').forEach((menu) => {
        if (!menu.contains(event.target)) menu.hidePopover();
    });
}, true);

// Navigasi panah atas/bawah di dalam menu.
document.addEventListener('keydown', (event) => {
    const menu = event.target.closest?.('[data-dropdown]');
    if (!menu || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;
    event.preventDefault();
    const items = visibleItems(menu);
    const next = items.indexOf(document.activeElement) + (event.key === 'ArrowDown' ? 1 : -1);
    items[(next + items.length) % items.length]?.focus();
});
