export const rupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);

export const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

// Tanggal lokal dalam format yyyy-mm-dd (untuk <input type="date">).
export const isoDate = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

// Data halaman dari <script type="application/json" id="..."> yang ditulis Blade.
export const pageData = (id = 'page-data') => JSON.parse(document.getElementById(id)?.textContent || '{}');
