// Sidebar mobile: [data-sidebar-toggle] membuka/menutup, klik overlay atau Esc menutup.
const root = document.documentElement;

const setOpen = (open) => root.toggleAttribute('data-sidebar-open', open);

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) setOpen(!root.hasAttribute('data-sidebar-open'));
    else if (event.target.closest('[data-sidebar-overlay]')) setOpen(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
});

// Menu pengguna di header (<details>): tutup saat klik di luar.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
