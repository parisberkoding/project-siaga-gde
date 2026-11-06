// Dashboard BBM: grafik (Chart.js), pemilih periode kustom, layar tunggu cetak PDF.
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
        for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) axis.push(isoDate(d));
        // Lebih dari ~2 bulan per hari terlalu rapat dibaca: rangkum per bulan.
        if (axis.length > 62) return monthlySeries(data);
    }
    // Kunci yang tidak cocok dengan sumbu (mis. format bulan berbeda) tetap ditampilkan di akhir.
    const extra = keys.filter((k) => !axis.includes(k));
    const labels = [...axis, ...extra];
    const label = (key) => (/^\d{4}-\d{2}-\d{2}$/.test(key)
        ? new Date(`${key}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
        : key);
    return { labels: labels.map(label), values: labels.map((k) => data[k] ?? 0) };
}

function monthlySeries(data) {
    const sums = {};
    Object.entries(data).forEach(([key, value]) => { sums[key.slice(0, 7)] = (sums[key.slice(0, 7)] ?? 0) + value; });
    const months = Object.keys(sums).sort();
    const axis = [];
    if (months.length) {
        for (let d = new Date(`${months[0]}-01T00:00:00`); iso7(d) <= months[months.length - 1]; d.setMonth(d.getMonth() + 1)) axis.push(iso7(d));
    }
    return {
        labels: axis.map((k) => `${MONTHS[Number(k.slice(5, 7)) - 1]} ` + k.slice(2, 4)),
        values: axis.map((k) => sums[k] ?? 0),
    };
}
const iso7 = (d) => isoDate(d).slice(0, 7);

const expense = stats.expenseOverTime ?? {};
const expenseCanvas = document.getElementById('expenseChart');
if (Object.values(expense).some((v) => v > 0)) {
    const { labels, values } = expenseSeries(expense);
    // Grafik garis untuk tren (lebih tenang dibaca daripada batang yang rapat); titik hanya tampil saat disorot
    // kecuali datanya sedikit. responsive + maintainAspectRatio false: tinggi dari wadah (h-72), lebar ikut kartu.
    const ctx = expenseCanvas.getContext('2d');
    const fill = ctx.createLinearGradient(0, 0, 0, expenseCanvas.parentElement.clientHeight || 288);
    fill.addColorStop(0, 'rgb(11 80 161 / 0.22)');
    fill.addColorStop(1, 'rgb(11 80 161 / 0)');
    new Chart(expenseCanvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: primary,
                backgroundColor: fill,
                fill: true,
                borderWidth: 2,
                tension: 0.35,
                cubicInterpolationMode: 'monotone',
                pointRadius: values.length <= 14 ? 3 : 0,
                pointHoverRadius: 5,
                pointBackgroundColor: primary,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => rupiah(c.parsed.y) } } },
            scales: {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0, autoSkipPadding: 16 } },
                y: { beginAtZero: true, border: { display: false }, ticks: { callback: compact, maxTicksLimit: 5 } },
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
    customRange.addEventListener('submit', () => {
        const from = customRange.querySelector('[data-range-from]').value;
        const to = customRange.querySelector('[data-range-to]').value;
        const days = (new Date(to) - new Date(from)) / 86_400_000;
        customRange.elements.period.value = days < 1 ? 'day' : days <= 62 ? 'month' : 'year';
    });
}
