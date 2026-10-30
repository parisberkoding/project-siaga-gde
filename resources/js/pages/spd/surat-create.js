// Form Buat Surat Tugas (driver; dipakai juga oleh form admin/shortcut): jenis SPD "lainnya",
// lama perjalanan, baris nama pemakai, dan panel ringkasan di kanan.
import { initPemakaiAutocomplete } from '../../lib/pemakai-autocomplete';

const $ = (id) => document.getElementById(id);
const berangkat = $('tanggalBerangkat');
const kembali = $('tanggalKembali');
const jumlahPemakai = $('jumlahPemakai');
const jenisSpd = $('jenis_spd');
const vehicle = $('vehicle_id');

const formatDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const optionText = (select) => (select?.value ? select.selectedOptions[0].textContent.trim() : '-');

const updateJenisSpd = () => {
    $('jenis_spd_lainnya_wrapper').hidden = jenisSpd.value !== 'lainnya';
    $('summaryJenis').textContent = optionText(jenisSpd);
};

const calculate = () => {
    const ok = berangkat.value && kembali.value && kembali.value >= berangkat.value;
    const days = ok ? Math.round((new Date(kembali.value) - new Date(berangkat.value)) / 86_400_000) + 1 : 0;
    $('infoLama').hidden = !ok;
    $('lamaHari').textContent = days;
    $('summaryLama').textContent = ok ? days : '-';
    $('summaryTanggal').textContent = ok
        ? `${formatDate(berangkat.value)} sampai ${formatDate(kembali.value)}`
        : 'Pilih tanggal berangkat dan kembali';
};

const updateNamaRows = () => {
    const count = parseInt(jumlahPemakai.value, 10) || 1;
    document.querySelectorAll('[data-row-nama]').forEach((row) => {
        row.hidden = Number(row.dataset.rowNama) > count;
    });
    $('summaryPemakai').textContent = `${count} orang`;
};

const updateVehicle = () => {
    // Opsi berbentuk "KODE - Nama": tampilkan kodenya saja supaya muat.
    $('summaryKendaraan').textContent = optionText(vehicle).split(' - ')[0];
};

berangkat.addEventListener('change', () => {
    kembali.min = berangkat.value;
    if (kembali.value && kembali.value < berangkat.value) kembali.value = berangkat.value;
    calculate();
});
kembali.addEventListener('change', calculate);
jumlahPemakai.addEventListener('input', updateNamaRows);
jenisSpd.addEventListener('change', updateJenisSpd);
vehicle?.addEventListener('change', updateVehicle);

updateJenisSpd();
updateNamaRows();
calculate();
updateVehicle();
initPemakaiAutocomplete();
