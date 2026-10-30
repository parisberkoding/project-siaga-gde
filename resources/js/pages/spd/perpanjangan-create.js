// Form Ajukan Perpanjangan: info surat terpilih, durasi & budget tambahan, batas LPJ baru.
import { rupiah, isoDate } from '../../lib/format';

const $ = (id) => document.getElementById(id);
const select = $('selectSurat');
const tanggalBaru = $('tanggalKembaliBaru');
const DAY = 86_400_000;

let currentKembali = null;
let budgetPerHari = 0;

const formatDate = (date) => date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

function updateCalculation() {
    const newKembali = tanggalBaru.value ? new Date(tanggalBaru.value) : null;
    const ok = currentKembali && newKembali && newKembali > currentKembali;
    $('infoPerpanjangan').hidden = !ok;
    if (!ok) return;

    const days = Math.round((newKembali - currentKembali) / DAY);
    $('durasiTambahan').textContent = `+${days}`;
    $('budgetTambahan').textContent = rupiah(days * budgetPerHari);
    $('deadlineBaru').textContent = formatDate(new Date(newKembali.getTime() + 3 * DAY));
}

function updateSuratInfo() {
    const option = select.selectedOptions[0];
    $('infoSurat').hidden = !option?.value;

    if (!option?.value) {
        tanggalBaru.min = '';
        currentKembali = null;
        return updateCalculation();
    }

    const d = option.dataset;
    $('infoTujuan').textContent = d.tujuan;
    $('infoTanggal').textContent = `${d.berangkat} - ${d.kembaliFormatted} (${d.lama} hari)`;
    $('infoKembali').textContent = d.kembaliFormatted;
    $('infoBudget').textContent = rupiah(d.budget);

    currentKembali = new Date(d.kembali);
    tanggalBaru.min = isoDate(new Date(currentKembali.getTime() + DAY));
    budgetPerHari = parseFloat(d.budget) || 0;
    updateCalculation();
}

select.addEventListener('change', updateSuratInfo);
tanggalBaru.addEventListener('change', updateCalculation);
updateSuratInfo();
