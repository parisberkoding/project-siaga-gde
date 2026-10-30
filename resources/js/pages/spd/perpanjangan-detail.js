// Detail perpanjangan: total budget = budget per hari x lama perjalanan, konfirmasi sebelum setujui.
import { rupiah } from '../../lib/format';
import { confirmDialog } from '../../ui/dialog';

const form = document.getElementById('approveForm');
const budget = document.getElementById('budgetPerHari');
const lama = Number(form.dataset.lama) || 0;
const total = () => (parseFloat(budget.value) || 0) * lama;

budget.addEventListener('input', () => {
    document.getElementById('totalBudgetCalc').textContent = rupiah(total());
});

form.addEventListener('submit', async (event) => {
    if (form.dataset.confirmed) return;
    event.preventDefault();
    const ok = await confirmDialog({
        title: 'Setujui surat tugas?',
        html: `Budget per hari <strong>${rupiah(budget.value)}</strong>, total <strong>${rupiah(total())}</strong>.<br>Batas LPJ: 3 hari setelah tanggal kembali.`,
        confirmText: 'Ya, setujui',
        variant: 'success',
    });
    if (!ok) return;
    form.dataset.confirmed = '1';
    form.requestSubmit();
});
