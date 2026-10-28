// Halaman Kelola Permohonan & Pelaporan (superadmin.permohonan).
import { openDialog } from '../../ui/dialog';
import { rupiah } from '../../lib/format';
import { initDecisions } from '../../lib/decisions';

// Komentar massal dipakai untuk permohonan yang komentarnya kosong.
initDecisions({ fallbackNote: () => document.getElementById('bulkNoteInput')?.value.trim() });

const MAX_NOMINAL = 850000;
const NOMINAL_STEP = 50000;

// Opsi nominal mengikuti budget full tank kendaraan (maksimal 850.000).
function refreshNominalOptions() {
    const vehicle = document.getElementById('editVehicleId');
    const bill = document.getElementById('editBillAmounts');
    const info = document.getElementById('editVehicleBudgetInfo');
    const budget = parseFloat(vehicle.selectedOptions[0]?.dataset.budget) || 0;
    const previous = bill.value;

    info.textContent = budget > 0
        ? `Budget full tank ${rupiah(budget)}`
        : 'Budget full tank kendaraan belum diisi di Master Vehicle.';

    const max = budget > 0 ? Math.min(budget, MAX_NOMINAL) : MAX_NOMINAL;
    bill.innerHTML = '';
    for (let value = NOMINAL_STEP; value <= max; value += NOMINAL_STEP) {
        bill.add(new Option(rupiah(value), value));
    }
    if (previous && parseFloat(previous) <= max) {
        bill.value = previous;
    } else if (previous) {
        // Nilai lama di luar rentang tetap dipertahankan supaya tidak berubah diam-diam saat disimpan.
        bill.add(new Option(`${rupiah(previous)} (nilai saat ini)`, previous, true, true));
    }
}

document.getElementById('editVehicleId')?.addEventListener('change', refreshNominalOptions);

document.addEventListener('click', (event) => {
    const editPermohonan = event.target.closest('[data-edit-permohonan]');
    if (editPermohonan) {
        const d = editPermohonan.dataset;
        document.getElementById('editPermohonanForm').action = d.updateUrl;
        document.getElementById('editRequestNumber').textContent = d.requestNumber;
        document.getElementById('editVehicleId').value = d.vehicleId;
        document.getElementById('editGasolineType').value = d.gasolineType;
        document.getElementById('editRequesterNotes').value = d.requesterNotes;
        document.getElementById('editAuthorizerNotes').value = d.authorizerNotes;
        // Nominal diisi dulu supaya tetap terpilih setelah opsi disusun ulang.
        document.getElementById('editBillAmounts').add(new Option('', d.billAmounts, true, true));
        refreshNominalOptions();
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
        document.getElementById('viewStrukDialog-title').textContent = `Struk BBM ${viewStruk.dataset.transactionId}`;
        document.getElementById('strukImage').src = viewStruk.dataset.imageUrl;
        document.getElementById('downloadStruk').href = viewStruk.dataset.imageUrl;
        openDialog('viewStrukDialog');
    }
});
