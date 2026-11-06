// Dashboard SPD: tren surat tugas 6 bulan & donut status surat.
import Chart, { chartEmptyState } from '../../lib/chart';
import { pageData } from '../../lib/format';

const { trend = { labels: [], values: [] }, status = { labels: [], values: [], colors: [] } } = pageData();
const SPD = '#4154f1';

const trendCanvas = document.getElementById('spdTrendChart');
if (trend.values.some((v) => v > 0)) {
    new Chart(trendCanvas, {
        type: 'bar',
        data: {
            labels: trend.labels,
            datasets: [{ data: trend.values, backgroundColor: 'rgb(65 84 241 / 0.85)', hoverBackgroundColor: SPD, borderRadius: 6, maxBarThickness: 40 }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y} surat tugas` } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, maxTicksLimit: 6 } },
            },
        },
    });
} else {
    chartEmptyState(trendCanvas, 'Belum ada surat tugas dalam 6 bulan terakhir');
}

const statusCanvas = document.getElementById('spdStatusChart');
if (status.values.some((v) => v > 0)) {
    new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: status.labels,
            datasets: [{ data: status.values, backgroundColor: status.colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }],
        },
        options: {
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} surat` } } },
        },
    });
} else {
    chartEmptyState(statusCanvas, 'Belum ada surat');
}
