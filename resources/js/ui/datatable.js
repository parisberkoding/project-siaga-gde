import { DataTable } from 'simple-datatables';
import { buildSelectMenu } from '../lib/select-menu';

// <table data-datatable='{"perPage":10}'> menjadi tabel dengan pencarian, urutan & halaman.
const labels = {
    placeholder: 'Cari di tabel...',
    searchTitle: 'Cari di tabel',
    searchLabel: '',
    pageTitle: 'Halaman {page}',
    perPage: 'per halaman',
    noRows: 'Tidak ada data',
    noResults: 'Tidak ada hasil yang cocok',
    info: 'Menampilkan {start} sampai {end} dari {rows} data',
};

document.querySelectorAll('table[data-datatable]').forEach((table) => {
    // Tabel kosong hanya berisi satu baris pesan (colspan), tidak perlu diubah.
    if (!table.querySelector('tbody td:not([colspan])')) return;

    // controlsTarget (selector): toolbar cari + per halaman dipindah ke elemen itu (mis. sebaris dengan sub-tab).
    const { controlsTarget, ...options } = JSON.parse(table.dataset.datatable || '{}');
    // Kolom aksi / tanpa judul tidak perlu diurutkan.
    const unsortable = [...table.tHead.rows[0].cells]
        .map((th, index) => (/^(aksi)?$/i.test(th.textContent.trim()) ? index : null))
        .filter((index) => index !== null);
    const perPageSelect = options.perPageSelect ?? [10, 25, 50, 100];
    const dt = new DataTable(table, {
        searchable: true,
        fixedColumns: false,
        perPage: 10,
        perPageSelect,
        // Pagination ringkas: ‹ 1 … 4 5 6 … 13 › (muat di layar HP). prevText/nextText dirender sebagai teks,
        // jadi pakai karakter bawaan ‹ › (bukan ikon HTML).
        pagerDelta: 1,
        ...options,
        columns: [...(unsortable.length ? [{ select: unsortable, sortable: false }] : []), ...(options.columns ?? [])],
        labels: { ...labels, ...(options.labels ?? {}) },
    });
    table.datatable = dt;

    // Toolbar (cari, per halaman) & bagian bawah (info, halaman) dikeluarkan dari wadah scroll x-ui.table:
    // di HP hanya tabelnya yang bergeser ke samping, kontrolnya tetap di tempat.
    const outer = dt.wrapperDOM.parentElement?.closest('[data-slot="table-container"]');
    if (outer) outer.replaceWith(dt.wrapperDOM);

    // Jumlah per halaman: <select> bawaan diganti Select shadcn (x-ui.select-menu).
    const native = dt.wrapperDOM.querySelector('.datatable-dropdown');
    if (native && perPageSelect) {
        const menu = buildSelectMenu({
            options: perPageSelect.map((n) => ({ value: n, label: `${n} / halaman` })),
            value: dt.options.perPage,
            icon: 'bi bi-list-ol',
            label: 'Jumlah baris per halaman',
            className: 'w-full sm:w-44',
        });
        menu.addEventListener('select:change', (event) => {
            dt.options.perPage = Number(event.detail.value);
            dt.update();
        });
        native.replaceWith(menu);
    }

    // Di luar wrapper, listener cari bawaan library tidak menangkap ketikan, jadi diteruskan ke dt.search().
    // controlsFor = nama tab pemilik tabel, dipakai JS halaman untuk menampilkan kontrol tab yang aktif.
    const target = controlsTarget && document.querySelector(controlsTarget);
    const top = dt.wrapperDOM.querySelector('.datatable-top');
    if (target && top) {
        top.dataset.controlsFor = table.closest('[data-tabs-content]')?.dataset.tabsContent ?? '';
        top.addEventListener('input', (event) => {
            if (event.target.matches('.datatable-input')) dt.search(event.target.value);
        });
        target.append(top);
    }
});
