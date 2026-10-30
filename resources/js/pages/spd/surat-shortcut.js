// Buat Surat (Shortcut) admin: logika form surat sama dengan form driver, ditambah tipe mendadak,
// autofill dari Master Pekerja, dan input pemberian dana.
import './surat-create';
import '../../ui/pencairan-input';

const $ = (id) => document.getElementById(id);

// SPD mendadak: pemberian dana diisi saat pencetakan surat.
const tipe = $('tipeSpd');
const toggleBudget = () => {
    $('budgetCard').hidden = tipe.value === 'mendadak';
    $('mendadakNotice').hidden = tipe.value !== 'mendadak';
};
tipe.addEventListener('change', toggleBudget);
toggleBudget();

const autofill = (select, fields) => select.addEventListener('change', () => {
    const option = select.selectedOptions[0];
    if (!select.value || !option.dataset.nama) return;
    Object.entries(fields).forEach(([key, id]) => { $(id).value = option.dataset[key] || ''; });
});
autofill($('pemberiEmployee'), { nama: 'pemberiNama', jabatan: 'pemberiJabatan' });
autofill($('pengemudiEmployee'), { nama: 'pengemudiNama' });
