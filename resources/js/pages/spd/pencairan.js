// Kelola Pencairan: field referensi bank & rekening tujuan hanya tampil (dan wajib) untuk Transfer.
const sync = (select) => {
    const transfer = select.value === 'Transfer';
    select.form.querySelectorAll('[data-transfer-only]').forEach((field) => {
        field.hidden = !transfer;
        field.querySelector('input').required = transfer;
    });
};

document.querySelectorAll('select[data-bentuk]').forEach((select) => {
    select.addEventListener('change', () => sync(select));
    sync(select);
});
