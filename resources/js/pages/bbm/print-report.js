// Halaman Cetak Laporan BBM: layar tunggu selama PDF dibuat (rentang cepat ada di ui/date-range-picker.js).
const form = document.getElementById('printReportForm');

// Server memasang cookie download_ready_<token> setelah PDF siap; layar tunggu ditutup saat cookie muncul.
form.addEventListener('submit', () => {
    const overlay = document.getElementById('loadingOverlay');
    const button = document.getElementById('btnGenerate');
    const token = `dl_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    const cookieName = `download_ready_${token}`;
    form.elements.download_token.value = token;

    overlay.hidden = false;
    button.disabled = true;

    const done = () => {
        clearInterval(poll);
        clearTimeout(fallback);
        document.cookie = `${cookieName}=; Max-Age=0; path=/`;
        overlay.hidden = true;
        button.disabled = false;
    };
    const poll = setInterval(() => {
        if (document.cookie.includes(cookieName)) done();
    }, 500);
    const fallback = setTimeout(done, 60000);
});
