// Dashboard Kendaraan & Alat: tren pengecekan 6 bulan.
import Chart, { chartEmptyState } from '../../lib/chart';
import { pageData } from '../../lib/format';

const { trend = { labels: [], values: [] } } = pageData();
const GREEN = '#4a7a1c';

const canvas = document.getElementById('vehicleTrendChart');
if (trend.values.some((v) => v > 0)) {
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: trend.labels,
            datasets: [{ data: trend.values, backgroundColor: 'rgb(74 122 28 / 0.8)', hoverBackgroundColor: GREEN, borderRadius: 6, maxBarThickness: 40 }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y} pengecekan` } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, maxTicksLimit: 6 } },
            },
        },
    });
} else {
    chartEmptyState(canvas, 'Belum ada pengecekan dalam 6 bulan terakhir');
}
