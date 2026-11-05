// Halaman Permohonan BBM (requester-index): pencarian kendaraan, opsi nominal, cetak voucher.
import './voucher-print';
import { rupiah, escapeHtml, pageData } from '../../lib/format';
import { selectRoot, selectValue, setSelectOptions, setSelectValue } from '../../lib/select-menu';

const { vehicles = [], fallbackOptions = [] } = pageData();

const search = document.getElementById('vehicle_search');
const vehicleId = document.getElementById('vehicle_id');
const suggestions = document.getElementById('vehicleSuggestions');
// Jenis BBM & Nominal memakai <x-ui.select-menu> (shadcn Select): diakses lewat lib/select-menu.
const billRoot = selectRoot('bill_payment');
const fuelRoot = selectRoot('gasoline_type');
const vehicleInfo = document.getElementById('vehicleBudgetInfo');
const billInfo = document.getElementById('billPaymentInfo');

const NOMINAL_STEP = 50000;
const fallbackMin = fallbackOptions[0];
const fallbackMax = fallbackOptions[fallbackOptions.length - 1];

function populateBillPayment(min, max, benchmark = null) {
    if (!min || !max || min > max) {
        setSelectOptions(billRoot, []);
        billInfo.textContent = 'Nominal belum tersedia. Hubungi tim IT untuk melengkapi data kendaraan.';
        return;
    }
    const options = [];
    for (let value = min; value <= max; value += NOMINAL_STEP) {
        options.push({ value, label: rupiah(value) + (value === benchmark ? ' (benchmark)' : '') });
    }
    setSelectOptions(billRoot, options); // nilai sebelumnya dipertahankan bila masih dalam rentang
    billInfo.textContent = '';
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
        setSelectValue(fuelRoot, '');
        vehicleInfo.textContent = 'Ketik untuk memilih kendaraan atau alat.';
        renderVehicleInfo(null);
        populateBillPayment(fallbackMin, fallbackMax);
        return;
    }

    vehicleId.value = vehicle.id;
    const benchmark = vehicle.benchmark ? parseInt(vehicle.benchmark, 10) : null;
    const range = vehicle.range?.min && vehicle.range?.max ? vehicle.range : null;

    if (range) populateBillPayment(range.min, range.max, benchmark);
    else populateBillPayment(fallbackMin, fallbackMax);

    // Jenis BBM mengikuti master kendaraan (tidak peka huruf besar/kecil).
    const fuel = (vehicle.fuel || '').toLowerCase();
    const match = [...fuelRoot.querySelectorAll('[role="option"]')].find((o) => o.dataset.value.toLowerCase() === fuel);
    if (match) setSelectValue(fuelRoot, match.dataset.value);

    // Nominal mengikuti benchmark (selalu kelipatan 50.000 di dalam rentang).
    if (benchmark && range && benchmark >= range.min && benchmark <= range.max) setSelectValue(billRoot, benchmark);

    renderVehicleInfo(vehicle, benchmark);
    vehicleInfo.textContent = benchmark
        ? 'Nominal awal mengikuti benchmark kendaraan, pilihan lain tersedia dalam rentang yang diizinkan.'
        : 'Data tangki atau harga belum lengkap, memakai rentang umum 50.000 sampai 3.000.000.';
}

// Daftar saran = popover manual (top layer) yang ditempel di bawah input, jadi tidak tertutup kartu
// lain (setiap kartu ornament-corner punya stacking context sendiri). Ditata ulang saat gulir/ubah ukuran.
const isOpen = () => suggestions.matches(':popover-open');
function placeSuggestions() {
    const rect = search.getBoundingClientRect();
    Object.assign(suggestions.style, { inset: 'auto', top: `${rect.bottom + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` });
}
function toggleSuggestions(open) {
    if (open === isOpen()) return;
    if (open) {
        placeSuggestions();
        suggestions.showPopover();
    } else {
        suggestions.hidePopover();
    }
    search.setAttribute('aria-expanded', String(open));
}

function chooseVehicle(item) {
    search.value = item.label;
    toggleSuggestions(false);
    selectVehicle(item);
    renderSummary();
    search.focus();
}

function renderSuggestions(items) {
    suggestions.replaceChildren(
        ...items.map((item) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.setAttribute('role', 'option');
            option.className = 'flex w-full flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent focus-visible:bg-accent';
            option.innerHTML = `<span class="font-medium">${escapeHtml(item.code)}</span>` +
                `<span class="text-xs text-muted-foreground">${escapeHtml(item.name)} (${escapeHtml(item.type)})</span>`;
            option.addEventListener('click', () => chooseVehicle(item));
            return option;
        }),
    );
    toggleSuggestions(items.length > 0);
}

if (search) {
    window.addEventListener('resize', () => isOpen() && placeSuggestions());
    window.addEventListener('scroll', (event) => {
        if (isOpen() && !suggestions.contains(event.target)) placeSuggestions();
    }, true);

    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        if (!query) {
            toggleSuggestions(false);
            selectVehicle(null);
            return;
        }
        renderSuggestions(
            vehicles.filter((v) => `${v.label} ${v.code} ${v.name}`.toLowerCase().includes(query)).slice(0, 8),
        );
    });

    document.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== search) toggleSuggestions(false);
    });

    // Keyboard: panah bawah dari input masuk ke daftar, panah atas/bawah berpindah, Esc menutup.
    const options = () => [...suggestions.querySelectorAll('[role="option"]')];
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && options().length) {
            event.preventDefault();
            toggleSuggestions(true);
            options()[0].focus();
        } else if (event.key === 'Escape') {
            toggleSuggestions(false);
        }
    });
    suggestions.addEventListener('keydown', (event) => {
        const list = options();
        const index = list.indexOf(document.activeElement);
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const next = index + (event.key === 'ArrowDown' ? 1 : -1);
            (next < 0 ? search : list[Math.min(next, list.length - 1)]).focus();
        } else if (event.key === 'Escape') {
            toggleSuggestions(false);
            search.focus();
        }
    });

    // Isi ulang setelah gagal validasi (old('vehicle_id')).
    const preselected = vehicles.find((v) => String(v.id) === vehicleId.value);
    if (preselected) search.value = preselected.label;
    const oldNominal = selectValue(billRoot);
    selectVehicle(preselected ?? null);
    if (oldNominal) setSelectValue(billRoot, oldNominal);
}

// Ringkasan di panel kanan mengikuti isian form.
function renderSummary() {
    const nominal = document.getElementById('summaryNominal');
    if (!nominal) return;
    nominal.textContent = rupiah(selectValue(billRoot) || 0);
    document.getElementById('summaryVehicle').textContent = vehicleId.value ? search.value : '-';
    document.getElementById('summaryFuel').textContent = vehicleId.value
        ? (selectValue(fuelRoot) || 'Jenis BBM belum dipilih')
        : 'Pilih kendaraan terlebih dahulu';
}
billRoot?.addEventListener('change', renderSummary);
fuelRoot?.addEventListener('change', renderSummary);
search?.addEventListener('input', renderSummary);
renderSummary();
