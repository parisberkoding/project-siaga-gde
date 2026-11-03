// Matriks Hak Akses: saring baris permission; grup yang barisnya habis ikut disembunyikan.
const input = document.querySelector('[data-matrix-search]');

input?.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    let group = null;
    let visible = 0;
    const flush = () => { if (group) group.hidden = visible === 0; };

    document.querySelectorAll('[data-matrix-group], [data-matrix-row]').forEach((tr) => {
        if ('matrixGroup' in tr.dataset) {
            flush();
            group = tr;
            visible = 0;
            return;
        }
        tr.hidden = q !== '' && !tr.dataset.search.includes(q);
        if (!tr.hidden) visible++;
    });
    flush();
});
