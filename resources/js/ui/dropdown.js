// <x-ui.dropdown-menu>: Popover API (top layer) + posisi menempel di bawah-kanan tombol pemicunya.
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
}, true);

const visibleItems = (menu) => [...menu.querySelectorAll('[role="menuitem"]')].filter((item) => item.checkVisibility());

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
    (items.find((item) => item.hasAttribute('aria-current')) ?? items[0])?.focus();
}, true);

// Pilih item menutup menu; menggulir halaman juga menutup (posisi tidak lagi pas).
document.addEventListener('click', (event) => {
    const item = event.target.closest('[data-dropdown] [role="menuitem"]');
    if (item) item.closest('[data-dropdown]').hidePopover();
});
window.addEventListener('scroll', () => {
    document.querySelectorAll('[data-dropdown]:popover-open').forEach((menu) => menu.hidePopover());
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
