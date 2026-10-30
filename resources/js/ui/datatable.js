import { DataTable } from 'simple-datatables';

// <table data-datatable='{"perPage":10}'> menjadi tabel dengan pencarian, urutan & halaman.
const labels = {
    placeholder: 'Cari...',
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

    const options = JSON.parse(table.dataset.datatable || '{}');
    // Kolom aksi / tanpa judul tidak perlu diurutkan.
    const unsortable = [...table.tHead.rows[0].cells]
        .map((th, index) => (/^(aksi)?$/i.test(th.textContent.trim()) ? index : null))
        .filter((index) => index !== null);
    table.datatable = new DataTable(table, {
        searchable: true,
        fixedColumns: false,
        perPage: 10,
        perPageSelect: [10, 25, 50, 100],
        ...options,
        columns: [...(unsortable.length ? [{ select: unsortable, sortable: false }] : []), ...(options.columns ?? [])],
        labels: { ...labels, ...(options.labels ?? {}) },
    });
});
