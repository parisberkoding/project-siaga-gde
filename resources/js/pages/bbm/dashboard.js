// Dashboard BBM: grafik (Chart.js), pencarian & export tabel, layar tunggu cetak PDF.
import Chart from 'chart.js/auto';
import { rupiah, pageData } from '../../lib/format';

const { stats = {} } = pageData();

// Palet mengikuti warna GDE (biru, hijau, oranye BBM, ungu, merah).
const palette = ['#0b50a1', '#71a62f', '#ff771d', '#9747ff', '#ed1b2f', '#4154f1'];
const millions = (value) => `Rp ${(value / 1_000_000).toFixed(1)} jt`;

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.color = '#5b6b80';
Chart.defaults.borderColor = '#e4e9f0';

function emptyState(canvas) {
    canvas.parentElement.innerHTML =
        '<p class="flex h-full items-center justify-center text-sm text-muted-foreground"><i class="bi bi-inbox mr-2"></i>Tidak ada data</p>';
}

const expense = stats.expenseOverTime ?? {};
const lineCanvas = document.getElementById('expenseLineChart');
if (Object.keys(expense).length) {
    new Chart(lineCanvas, {
        type: 'line',
        data: {
            labels: Object.keys(expense),
            datasets: [{
                label: 'Pengeluaran',
                data: Object.values(expense),
                fill: true,
                backgroundColor: 'rgb(11 80 161 / 0.1)',
                borderColor: palette[0],
                tension: 0.35,
                pointRadius: 3,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => rupiah(ctx.parsed.y) } } },
            scales: { y: { beginAtZero: true, ticks: { callback: millions } } },
        },
    });
} else {
    emptyState(lineCanvas);
}

const gasoline = stats.gasolineExpenseDistribution ?? {};
const pieCanvas = document.getElementById('gasolinePieChart');
if (Object.keys(gasoline).length) {
    new Chart(pieCanvas, {
        type: 'doughnut',
        data: {
            labels: Object.keys(gasoline),
            datasets: [{ data: Object.values(gasoline), backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }],
        },
        options: {
            maintainAspectRatio: false,
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
    document.querySelectorAll('[data-legend]').forEach((dot) => {
        dot.style.backgroundColor = palette[dot.dataset.legend % palette.length];
    });
} else {
    emptyState(pieCanvas);
}

// Top 10 per kendaraan atau per driver.
const rankingCanvas = document.getElementById('rankingChart');
let rankingChart = null;

function renderRanking(mode) {
    document.querySelectorAll('[data-ranking]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.ranking === mode));
    const source = (mode === 'vehicle' ? stats.vehicleExpense : stats.driverExpense) ?? {};
    const top = Object.entries(source).sort((a, b) => b[1] - a[1]).slice(0, 10);

    rankingChart?.destroy();
    rankingChart = new Chart(rankingCanvas, {
        type: 'bar',
        data: {
            labels: top.map(([label]) => label),
            datasets: [{ data: top.map(([, value]) => value), backgroundColor: mode === 'vehicle' ? palette[2] : palette[3], borderRadius: 4 }],
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => rupiah(ctx.parsed.x) } } },
            scales: { x: { beginAtZero: true, ticks: { callback: millions } } },
        },
    });
}

if (Object.keys(stats.vehicleExpense ?? {}).length || Object.keys(stats.driverExpense ?? {}).length) {
    renderRanking('vehicle');
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ranking]');
        if (button) renderRanking(button.dataset.ranking);
    });
} else {
    emptyState(rankingCanvas);
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
