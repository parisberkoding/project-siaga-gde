// Chart.js dengan gaya bersama dashboard (font, warna sumbu, tooltip gelap) + helper format & empty state.
import Chart from 'chart.js/auto';

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 11;
Chart.defaults.color = '#5b6b80';
Chart.defaults.borderColor = '#eef2f7';
Object.assign(Chart.defaults.plugins.tooltip, {
    backgroundColor: '#16202e', padding: 10, cornerRadius: 8, displayColors: false, titleFont: { weight: '600' },
});

// 10.800.000 -> "10,8 jt", 25.000 -> "25 rb"
export const compact = (value) => (value >= 1_000_000 ? `${(value / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`
    : value >= 1_000 ? `${Math.round(value / 1_000)} rb` : `${value}`);

export function chartEmptyState(canvas, text = 'Belum ada data pada periode ini') {
    canvas.parentElement.innerHTML = `<div class="flex h-full flex-col items-center justify-center gap-2 text-sm text-muted-foreground">
        <span class="flex size-10 items-center justify-center rounded-lg bg-muted text-lg"><i class="bi bi-bar-chart"></i></span>${text}</div>`;
}

export default Chart;
