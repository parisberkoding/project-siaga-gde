// Dashboard BBM: grafik (Chart.js), pencarian & export tabel, layar tunggu cetak PDF.
import Chart, { compact, chartEmptyState as emptyState } from '../../lib/chart';
import { rupiah, pageData, isoDate } from '../../lib/format';

const { stats = {}, chart: range = {} } = pageData();

const primary = '#0b50a1';

// Sumbu lengkap untuk rentang terpilih: hari tanpa transaksi tetap tampil (nilai 0), bukan dilewati.
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
function expenseSeries(data) {
    const keys = Object.keys(data);
    let axis;
    if (range.period === 'day') {
        axis = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`);
    } else if (range.period === 'year') {
        // Hanya bulan di dalam rentang (bulan yang belum lewat tidak ditampilkan kosong).
        const sameYear = range.start && range.end && range.start.slice(0, 4) === range.end.slice(0, 4);
        axis = sameYear ? MONTHS.slice(Number(range.start.slice(5, 7)) - 1, Number(range.end.slice(5, 7))) : MONTHS;
    } else {
        const sorted = [...keys].sort();
        const from = new Date(range.start || sorted[0]);
        const to = new Date(range.end || sorted[sorted.length - 1]);
        axis = [];
        // ponytail: rentang "Semua waktu" yang sangat panjang tetap per hari; ganti ke per bulan bila datanya bertahun-tahun.
        for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) axis.push(isoDate(d));
    }
    // Kunci yang tidak cocok dengan sumbu (mis. format bulan berbeda) tetap ditampilkan di akhir.
    const extra = keys.filter((k) => !axis.includes(k));
    const labels = [...axis, ...extra];
    const label = (key) => (/^\d{4}-\d{2}-\d{2}$/.test(key)
        ? new Date(`${key}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
        : key);
    return { labels: labels.map(label), values: labels.map((k) => data[k] ?? 0) };
}

const expense = stats.expenseOverTime ?? {};
const expenseCanvas = document.getElementById('expenseChart');
if (Object.values(expense).some((v) => v > 0)) {
    const { labels, values } = expenseSeries(expense);
    new Chart(expenseCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: 'rgb(11 80 161 / 0.85)',
                hoverBackgroundColor: primary,
                borderRadius: 4,
                maxBarThickness: 32,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => rupiah(ctx.parsed.y) } } },
            scales: {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0, autoSkipPadding: 12 } },
                y: { beginAtZero: true, border: { display: false }, ticks: { callback: compact, maxTicksLimit: 6 } },
            },
        },
    });
} else {
    emptyState(expenseCanvas);
}

const gasoline = stats.gasolineExpenseDistribution ?? {};
const gasolineCanvas = document.getElementById('gasolineChart');
if (Object.keys(gasoline).length) {
    new Chart(gasolineCanvas, {
        type: 'doughnut',
        data: {
            labels: Object.keys(gasoline),
            datasets: [{ data: Object.values(gasoline), backgroundColor: range.colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }],
        },
        options: {
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            return `${ctx.label}: ${rupiah(ctx.parsed)} (${((ctx.parsed / total) * 100).toFixed(1)}%)`;
                        },
                    },
                },
            },
        },
    });
} else {
    emptyState(gasolineCanvas, 'Belum ada data');
}

// Pencarian baris pada halaman tabel yang sedang tampil.
document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = input.value.toLowerCase();
        document.querySelectorAll(`#${input.dataset.tableSearch} tbody tr`).forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });
});

// Export baris yang tampil ke CSV.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-export-table]');
    if (!button) return;

    const table = document.getElementById(button.dataset.exportTable);
    const cell = (el) => `"${el.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""')}"`;
    const header = [...table.querySelectorAll('thead th')].map(cell).join(',');
    const rows = [...table.querySelectorAll('tbody tr:not([hidden])')]
        .filter((row) => !row.querySelector('td[colspan]'))
        .map((row) => [...row.cells].map(cell).join(','));
    const blob = new Blob(['﻿' + [header, ...rows].join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: button.dataset.filename });
    link.click();
    URL.revokeObjectURL(link.href);
});

// Cetak PDF: progres perkiraan sampai server memasang cookie download_ready_<token>.
const printForm = document.getElementById('formPrintReport');
printForm?.addEventListener('submit', () => {
    const overlay = document.getElementById('pdfLoadingOverlay');
    const bar = document.getElementById('pdfProgressBar');
    const label = document.getElementById('pdfProgressLabel');
    const token = Math.random().toString(36).slice(2, 11) + Date.now().toString(36);
    const cookieName = `download_ready_${token}`;
    let progress = 0;

    printForm.querySelector('[name="download_token"]').value = token;
    printForm.closest('dialog')?.close();
    overlay.hidden = false;

    const setProgress = (value) => {
        bar.style.width = `${Math.round(value)}%`;
        label.textContent = `${Math.round(value)}%`;
    };
    // Naik cepat di awal lalu melambat, berhenti di 94% sampai PDF siap.
    const crawl = setInterval(() => setProgress((progress += (94 - progress) * 0.08)), 400);

    const finish = () => {
        clearInterval(crawl);
        clearInterval(poll);
        clearTimeout(fallback);
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
        setProgress(100);
        setTimeout(() => {
            overlay.hidden = true;
            progress = 0;
            setProgress(0);
        }, 800);
    };
    const poll = setInterval(() => {
        if (document.cookie.includes(`${cookieName}=`)) finish();
    }, 500);
    const fallback = setTimeout(finish, 60000);
});

// Rentang kustom: "period" hanya mengatur pengelompokan grafik di controller (per jam / hari / bulan),
// jadi pilih sesuai panjang rentang.
const customRange = document.querySelector('[data-custom-range]');
if (customRange) {
    const from = customRange.elements.start_date;
    const to = customRange.elements.end_date;
    from.addEventListener('change', () => { to.min = from.value; });
    customRange.addEventListener('submit', () => {
        const days = (new Date(to.value) - new Date(from.value)) / 86_400_000;
        customRange.elements.period.value = days < 1 ? 'day' : days <= 62 ? 'month' : 'year';
    });
}
