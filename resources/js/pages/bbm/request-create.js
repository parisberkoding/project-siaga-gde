// Halaman Permohonan BBM (requester-index): pencarian kendaraan, opsi nominal, cetak voucher.
import './voucher-print';
import { rupiah, escapeHtml, pageData } from '../../lib/format';

const { vehicles = [], fallbackOptions = [] } = pageData();

const search = document.getElementById('vehicle_search');
const vehicleId = document.getElementById('vehicle_id');
const suggestions = document.getElementById('vehicleSuggestions');
const billPayment = document.getElementById('bill_payment');
const fuelSelect = document.getElementById('gasoline_type');
const vehicleInfo = document.getElementById('vehicleBudgetInfo');
const billInfo = document.getElementById('billPaymentInfo');

const NOMINAL_STEP = 50000;
const fallbackMin = fallbackOptions[0];
const fallbackMax = fallbackOptions[fallbackOptions.length - 1];

function populateBillPayment(min, max) {
    if (!min || !max || min > max) {
        billPayment.innerHTML = '<option value="">Pilih nominal</option>';
        billInfo.textContent = 'Nominal belum tersedia. Hubungi tim IT untuk melengkapi data kendaraan.';
        return;
    }

    const previous = Number(billPayment.value);
    let html = '<option value="">Pilih nominal</option>';
    for (let value = min; value <= max; value += NOMINAL_STEP) {
        html += `<option value="${value}">${rupiah(value)}</option>`;
    }
    billPayment.innerHTML = html;
    billInfo.textContent = '';
    if (previous >= min && previous <= max) billPayment.value = previous;
}

// Kisi ringkasan kendaraan terpilih (Jenis, BBM, Tangki, Benchmark).
const infoGrid = document.getElementById('vehicleInfo');
function renderVehicleInfo(vehicle, benchmark) {
    if (!infoGrid) return;
    infoGrid.hidden = !vehicle;
    if (!vehicle) return;
    const set = (id, text) => { document.getElementById(id).textContent = text || '-'; };
    set('viType', vehicle.type);
    set('viFuel', vehicle.fuel);
    set('viTank', vehicle.tank ? `${parseFloat(vehicle.tank)} liter` : '');
    set('viBenchmark', benchmark ? rupiah(benchmark) : '');
}

function selectVehicle(vehicle) {
    if (!vehicle) {
        vehicleId.value = '';
        fuelSelect.value = '';
        vehicleInfo.textContent = 'Ketik untuk memilih kendaraan atau alat.';
        renderVehicleInfo(null);
        populateBillPayment(fallbackMin, fallbackMax);
        return;
    }

    vehicleId.value = vehicle.id;
    const benchmark = vehicle.benchmark ? parseInt(vehicle.benchmark, 10) : null;
    const range = vehicle.range?.min && vehicle.range?.max ? vehicle.range : null;

    if (range) populateBillPayment(range.min, range.max);
    else populateBillPayment(fallbackMin, fallbackMax);

    // Jenis BBM mengikuti master kendaraan (tidak peka huruf besar/kecil).
    const fuel = (vehicle.fuel || '').toLowerCase();
    const match = [...fuelSelect.options].find((option) => option.value && option.value.toLowerCase() === fuel);
    if (match) fuelSelect.value = match.value;

    // Nominal mengikuti benchmark (selalu kelipatan 50.000 di dalam rentang).
    if (benchmark && range && benchmark >= range.min && benchmark <= range.max) {
        billPayment.value = benchmark;
        const option = billPayment.querySelector(`option[value="${benchmark}"]`);
        if (option) option.textContent += ' (benchmark)';
    }

    renderVehicleInfo(vehicle, benchmark);
    vehicleInfo.textContent = benchmark
        ? 'Nominal awal mengikuti benchmark kendaraan, pilihan lain tersedia dalam rentang yang diizinkan.'
        : 'Data tangki atau harga belum lengkap, memakai rentang umum 50.000 sampai 3.000.000.';
}

// Tautan "Ajukan permohonan" di slot kosong: gulir ke form lalu fokus ke pencarian kendaraan.
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-focus-vehicle]') || !search) return;
    event.preventDefault();
    search.scrollIntoView({ behavior: 'smooth', block: 'center' });
    search.focus({ preventScroll: true });
});

function renderSuggestions(items) {
    suggestions.replaceChildren(
        ...items.map((item) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'flex w-full flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent';
            option.innerHTML = `<span class="font-medium">${escapeHtml(item.code)}</span>` +
                `<span class="text-xs text-muted-foreground">${escapeHtml(item.name)} (${escapeHtml(item.type)})</span>`;
            option.addEventListener('click', () => {
                search.value = item.label;
                suggestions.hidden = true;
                selectVehicle(item);
            });
            return option;
        }),
    );
    suggestions.hidden = items.length === 0;
}

if (search) {
    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        if (!query) {
            suggestions.hidden = true;
            selectVehicle(null);
            return;
        }
        renderSuggestions(
            vehicles.filter((v) => `${v.label} ${v.code} ${v.name}`.toLowerCase().includes(query)).slice(0, 8),
        );
    });

    document.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== search) suggestions.hidden = true;
    });

    // Isi ulang setelah gagal validasi (old('vehicle_id')).
    const preselected = vehicles.find((v) => String(v.id) === vehicleId.value);
    if (preselected) search.value = preselected.label;
    selectVehicle(preselected ?? null);
}

// Ringkasan di panel kanan mengikuti isian form.
const summary = {
    nominal: document.getElementById('summaryNominal'),
    fuel: document.getElementById('summaryFuel'),
    vehicle: document.getElementById('summaryVehicle'),
};
function renderSummary() {
    if (!summary.nominal) return;
    summary.nominal.textContent = rupiah(billPayment.value || 0);
    summary.vehicle.textContent = vehicleId.value ? search.value : '-';
    summary.fuel.textContent = vehicleId.value
        ? (fuelSelect.value || 'Jenis BBM belum dipilih')
        : 'Pilih kendaraan terlebih dahulu';
}
billPayment?.addEventListener('change', renderSummary);
fuelSelect?.addEventListener('change', renderSummary);
search?.addEventListener('input', renderSummary);
suggestions?.addEventListener('click', () => setTimeout(renderSummary));
renderSummary();
