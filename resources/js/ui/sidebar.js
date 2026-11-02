// [data-sidebar-toggle]: di layar lebar menyembunyikan/menampilkan sidebar (diingat di localStorage),
// di layar kecil membuka sidebar sebagai drawer. Klik overlay atau Esc menutup drawer.
// Status dipasang di <body> (bukan <html>) supaya selektor [data-sidebar-*] juga cocok di dalam @scope (html) pada legacy-shell.css.
const root = document.body;
const desktop = window.matchMedia('(min-width: 1024px)');

const setOpen = (open) => root.toggleAttribute('data-sidebar-open', open);

const toggleCollapsed = () => {
    const collapsed = root.toggleAttribute('data-sidebar-collapsed');
    try {
        localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
    } catch {
        // penyimpanan diblokir: status hanya berlaku di halaman ini
    }
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        if (desktop.matches) toggleCollapsed();
        else setOpen(!root.hasAttribute('data-sidebar-open'));
    } else if (event.target.closest('[data-sidebar-overlay]')) setOpen(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
});

// Menu akun (<details data-menu>): tutup saat klik di luar.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
