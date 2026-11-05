// Detail & Validasi Surat (admin): input pemberian dana, pemberi tugas dari Master Pekerja.
import '../../ui/pencairan-input';

// Nama & jabatan ikut Master Pekerja agar sama persis dengan yang dicetak di template surat.
document.getElementById('pemberiEmployeeApproval')?.addEventListener('change', (e) => {
    const opt = e.target.selectedOptions[0];
    if (!opt?.value) return;
    document.getElementById('pemberi_tugas_nama').value = opt.dataset.nama || '';
    document.getElementById('pemberi_tugas_jabatan').value = opt.dataset.jabatan || '';
});
