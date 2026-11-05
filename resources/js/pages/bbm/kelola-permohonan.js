// Halaman Kelola Permohonan & Pelaporan (superadmin.permohonan).
import { openDialog } from '../../ui/dialog';
import { rupiah } from '../../lib/format';
import { initDecisions } from '../../lib/decisions';
import { selectRoot, selectValue, setSelectOptions, setSelectValue } from '../../lib/select-menu';

// Setujui/Tolak lewat dialog (satu per satu atau massal).
initDecisions();

const MAX_NOMINAL = 850000;
const NOMINAL_STEP = 50000;

// Dialog Ubah permohonan memakai <x-ui.select-menu> (shadcn Select); budget full tank per kendaraan dari JSON.
const budgets = JSON.parse(document.getElementById('vehicle-budgets')?.textContent || '{}');
const vehicleRoot = selectRoot('editVehicleId');
const fuelRoot = selectRoot('editGasolineType');
const billRoot = selectRoot('editBillAmounts');

// Opsi nominal mengikuti budget full tank kendaraan (maksimal 850.000).
function refreshNominalOptions(raw = selectValue(billRoot)) {
    // Nilai dari DB bisa "900000.00"; samakan dengan nilai opsi (bilangan bulat).
    const current = raw ? String(Math.round(Number(raw))) : '';
    const info = document.getElementById('editVehicleBudgetInfo');
    const budget = Number(budgets[selectValue(vehicleRoot)]) || 0;

    info.textContent = budget > 0
        ? `Budget full tank ${rupiah(budget)}`
        : 'Budget full tank kendaraan belum diisi di Master Vehicle.';

    const max = budget > 0 ? Math.min(budget, MAX_NOMINAL) : MAX_NOMINAL;
    const options = [];
    for (let value = NOMINAL_STEP; value <= max; value += NOMINAL_STEP) options.push({ value, label: rupiah(value) });
    // Nilai lama di luar rentang tetap dipertahankan supaya tidak berubah diam-diam saat disimpan.
    if (current && Number(current) > max) options.push({ value: current, label: `${rupiah(current)} (nilai saat ini)` });
    setSelectOptions(billRoot, options);
    if (current) setSelectValue(billRoot, current);
}

vehicleRoot?.addEventListener('select:change', () => refreshNominalOptions());

document.addEventListener('click', (event) => {
    const editPermohonan = event.target.closest('[data-edit-permohonan]');
    if (editPermohonan) {
        const d = editPermohonan.dataset;
        document.getElementById('editPermohonanForm').action = d.updateUrl;
        document.getElementById('editRequestNumber').textContent = d.requestNumber;
        setSelectValue(vehicleRoot, d.vehicleId);
        setSelectValue(fuelRoot, d.gasolineType);
        document.getElementById('editRequesterNotes').value = d.requesterNotes;
        document.getElementById('editAuthorizerNotes').value = d.authorizerNotes;
        refreshNominalOptions(d.billAmounts);
        openDialog('editPermohonanDialog');
        return;
    }

    const editLaporan = event.target.closest('[data-edit-laporan]');
    if (editLaporan) {
        const d = editLaporan.dataset;
        document.getElementById('editLaporanForm').action = d.updateUrl;
        document.getElementById('editTransactionId').textContent = d.transactionId;
        document.getElementById('editPurchaseDate').value = d.purchaseDate;
        document.getElementById('editFuelVolume').value = d.fuelVolume;
        document.getElementById('editKmTerakhir').value = d.kmTerakhir;
        openDialog('editLaporanDialog');
        return;
    }

    const viewStruk = event.target.closest('[data-view-struk]');
    if (viewStruk) {
        // data-view-title: judul lain untuk foto yang sama-sama dibuka di dialog ini (mis. odometer).
        document.getElementById('viewStrukDialog-title').textContent = `${viewStruk.dataset.viewTitle ?? 'Struk BBM'} ${viewStruk.dataset.transactionId}`;
        document.getElementById('strukImage').src = viewStruk.dataset.imageUrl;
        document.getElementById('downloadStruk').href = viewStruk.dataset.imageUrl;
        openDialog('viewStrukDialog');
    }
});
