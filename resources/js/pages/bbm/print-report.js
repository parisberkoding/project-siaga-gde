// Halaman Cetak Laporan BBM: rentang tanggal cepat + layar tunggu selama PDF dibuat.
import { isoDate } from '../../lib/format';

const form = document.getElementById('printReportForm');
const startDate = document.getElementById('startDate');
const endDate = document.getElementById('endDate');

function rangeFor(range, today = new Date()) {
    const y = today.getFullYear();
    const m = today.getMonth();
    switch (range) {
        case 'today':
            return [today, today];
        case 'week': {
            const monday = new Date(today);
            monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
            return [monday, today];
        }
        case 'month':
            return [new Date(y, m, 1), today];
        case 'lastmonth':
            return [new Date(y, m - 1, 1), new Date(y, m, 0)];
        case 'year':
            return [new Date(y, 0, 1), today];
    }
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-range]');
    if (!button) return;
    const [start, end] = rangeFor(button.dataset.range);
    startDate.value = isoDate(start);
    endDate.value = isoDate(end);
});

// Server memasang cookie download_ready_<token> setelah PDF siap; layar tunggu ditutup saat cookie muncul.
form.addEventListener('submit', () => {
    const overlay = document.getElementById('loadingOverlay');
    const button = document.getElementById('btnGenerate');
    const token = `dl_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    const cookieName = `download_ready_${token}`;
    document.getElementById('downloadToken').value = token;

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
