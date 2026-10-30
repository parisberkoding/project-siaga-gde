// <x-spd.pencairan-input>: baris pemberian dana dinamis (tambah/hapus, field transfer, total).
import { rupiah } from '../lib/format';

function init(root) {
    const { name, tanggalDefault, totalTarget } = root.dataset;
    const body = root.querySelector('[data-pencairan-body]');
    const template = root.querySelector('[data-pencairan-template]');
    const rows = () => [...body.querySelectorAll('[data-pencairan-row]')];
    const input = (row, key) => row.querySelector(`[data-name="${key}"]`);

    const reindex = () => rows().forEach((row, i) => {
        row.querySelectorAll('[data-name]').forEach((el) => { el.name = `${name}[${i}][${el.dataset.name}]`; });
    });

    const recalc = () => {
        const total = rows().reduce((sum, row) => sum + (parseInt(input(row, 'nominal').value, 10) || 0), 0);
        root.querySelector('[data-pencairan-total]').textContent = rupiah(total);
        if (totalTarget) document.getElementById(totalTarget)?.replaceChildren(rupiah(total));
    };

    const syncTransfer = (row) => {
        const transfer = input(row, 'bentuk_pencairan').value === 'Transfer';
        row.querySelectorAll('[data-transfer-only]').forEach((el) => { el.hidden = !transfer; });
    };

    const addRow = (prefill = {}) => {
        const row = template.content.firstElementChild.cloneNode(true);
        input(row, 'tanggal').value = prefill.tanggal || tanggalDefault;
        input(row, 'nominal').value = prefill.nominal ?? '';
        input(row, 'bentuk_pencairan').value = prefill.bentuk || 'Tunai';
        input(row, 'bank_reference').value = prefill.bank_reference || '';
        input(row, 'rek_tujuan').value = prefill.rek_tujuan || '';
        input(row, 'catatan').value = prefill.catatan || '';

        row.querySelector('[data-pencairan-remove]').addEventListener('click', () => {
            row.remove();
            reindex();
            recalc();
        });
        input(row, 'bentuk_pencairan').addEventListener('change', () => syncTransfer(row));
        input(row, 'nominal').addEventListener('input', recalc);

        body.appendChild(row);
        syncTransfer(row);
        reindex();
        recalc();
    };

    root.querySelector('[data-pencairan-add]').addEventListener('click', () => addRow());
    const existing = JSON.parse(root.dataset.rows || '[]');
    if (existing.length) existing.forEach(addRow);
    else addRow();
}

document.querySelectorAll('[data-pencairan-root]').forEach(init);
