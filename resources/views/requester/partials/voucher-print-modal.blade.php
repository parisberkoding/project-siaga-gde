<!-- Modal Print Voucher -->
<!-- Modal Print Voucher -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="modal fade" id="voucherModal{{ $slotNum }}" tabindex="-1" aria-labelledby="voucherModalLabel{{ $slotNum }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="voucherModalLabel{{ $slotNum }}">
                    <i class="bi bi-ticket-perforated"></i> Voucher BBM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="voucherContent{{ $slotNum }}">
                <!-- Logo & Header -->
                <div class="text-center mb-4">
                    <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png"
                         style="width:80px;" alt="Logo Geodipa">
                    <h5 class="mt-2 mb-0">PT. Geo Dipa Energi (Persero)</h5>
                    <p class="mb-0">Unit Patuha</p>
                </div>

                <!-- Voucher Box -->
                <div class="voucher-box mb-3">
                    <div class="mb-2"><i class="bi bi-ticket-perforated"></i> KODE VOUCHER BBM</div>
                    <div class="voucher-code">{{ $request->approvedRequest->voucher_code ?? 'N/A' }}</div>
                </div>

                <!-- Details Table -->
                <table class="table table-bordered">
                    <tr>
                        <th width="180">No. Permohonan</th>
                        <td><code>{{ $request->request_number }}</code></td>
                    </tr>
                    <tr>
                        <th>Pemohon</th>
                        <td>{{ $request->requester->nama_lengkap ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Kendaraan/Alat</th>
                        <td>
                            {{ $request->vehicle->consumerial_code ?? 'N/A' }}<br>
                            <small class="text-muted">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '' }})</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Jenis BBM</th>
                        <td><span class="badge bg-secondary">{{ $request->gasoline_type }}</span></td>
                    </tr>
                    <tr>
                        <th>Nominal</th>
                        <td><strong class="text-success">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <th>Tanggal Disetujui</th>
                        <td>{{ $request->authorization_date ? $request->authorization_date->translatedFormat('d M Y H:i') : '-' }}</td>
                    </tr>
                </table>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Tekan tombol Print untuk mencetak voucher
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>

                {{-- ✅ FIXED: Pakai <a> tag dengan URL scheme langsung --}}
                <a href="my.bluetoothprint.scheme://{{ str_replace('https://', 'http://', route('voucher.thermer.response', $request->id)) }}"
                   class="btn btn-success"
                   onclick="return handleThermerPrint{{ $slotNum }}(event)">
                    <i class="bi bi-phone"></i> Print
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.voucher-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.voucher-code {
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 3px;
    font-family: 'Courier New', monospace;
    padding: 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    margin: 10px 0;
}

@media print {
    .modal-header, .modal-footer { display: none; }
    .voucher-box { box-shadow: none; }
}
</style>

<script>
// Store voucher data for slot {{ $slotNum }}
window.voucherData{{ $slotNum }} = {
    requestNumber: "{{ $request->request_number }}",
    voucherCode: "{{ $request->approvedRequest->voucher_code ?? 'N/A' }}",
    requesterName: "{{ $request->requester->nama_lengkap ?? 'N/A' }}",
    vehicleCode: "{{ $request->vehicle->consumerial_code ?? 'N/A' }}",
    vehicleName: "{{ $request->vehicle->consumerial_name ?? '' }}",
    vehicleType: "{{ $request->vehicle->consumerial_type ?? '' }}",
    gasolineType: "{{ $request->gasoline_type }}",
    billAmount: {{ $request->bill_amounts }},
    approvalDate: "{{ $request->authorization_date ? $request->authorization_date->translatedFormat('d M Y H:i') : '-' }}"
};

// ✅ Handler untuk Thermer print link
function handleThermerPrint{{ $slotNum }}(event) {
    const data = window.voucherData{{ $slotNum }};

    // Validasi data voucher
    if (!data || !data.voucherCode || data.voucherCode === 'N/A') {
        event.preventDefault();
        alert('❌ Data voucher tidak lengkap. Tidak dapat mencetak.');
        return false;
    }

    // Cek platform
    const isAndroid = /Android/i.test(navigator.userAgent);
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

    console.log('🖨️ Print triggered for slot {{ $slotNum }}');
    console.log('📱 Is Android:', isAndroid);
    console.log('📱 Is Mobile:', isMobile);

    // Untuk desktop, prevent default dan buka preview
    if (!isAndroid && !isMobile) {
        event.preventDefault();

        const responseUrl = '{{ route("voucher.thermer.response", $request->id) }}';

        if (confirm('⚠️ Anda menggunakan desktop.\n\nPreview JSON akan dibuka di tab baru.\n\nUntuk print, gunakan perangkat Android dengan Bluetooth Print app terinstall.\n\nLanjutkan?')) {
            window.open(responseUrl, '_blank');
        }

        return false;
    }

    // ✅ Untuk Android/Mobile, biarkan native <a> tag handle URL scheme
    console.log('✅ Opening Bluetooth Print app...');

    // Show loading indicator (optional)
    const button = event.target.closest('a');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Membuka app...';
    button.classList.add('disabled');

    // Restore button after 3 seconds
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('disabled');
    }, 3000);

    // Tutup modal setelah klik
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('voucherModal{{ $slotNum }}'));
        if (modal) {
            modal.hide();
        }
    }, 500);

    // Return true = allow default <a> tag behavior
    return true;
}

// Helper function untuk convert string ke bytes
function stringToBytes(str) {
    const encoder = new TextEncoder();
    return Array.from(encoder.encode(str));
}
</script>


<!-- Epson Printer Selection Modal -->
<!--<div class="modal fade" id="epsonPrinterModal{{ $slotNum }}" tabindex="-1">-->
<!--    <div class="modal-dialog">-->
<!--        <div class="modal-content">-->
<!--            <div class="modal-header">-->
<!--                <h5 class="modal-title"><i class="bi bi-printer"></i> Pilih Printer Epson</h5>-->
<!--                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>-->
<!--            </div>-->
<!--            <div class="modal-body">-->
<!--                <div id="epsonPrinterList{{ $slotNum }}" class="mb-3">-->
<!--                    <div class="text-center">-->
<!--                        <div class="spinner-border text-primary" role="status">-->
<!--                            <span class="visually-hidden">Mencari printer...</span>-->
<!--                        </div>-->
<!--                        <p class="mt-2 text-muted">Mencari printer Epson di jaringan...</p>-->
<!--                    </div>-->
<!--                </div>-->

<!--                <div class="alert alert-info">-->
<!--                    <strong>Pastikan:</strong>-->
<!--                    <ul class="mb-0">-->
<!--                        <li>Printer Epson sudah terhubung ke jaringan yang sama</li>-->
<!--                        <li>Printer dalam keadaan menyala</li>-->
<!--                        <li>Browser mendukung Web API (Chrome/Edge)</li>-->
<!--                    </ul>-->
<!--                </div>-->
<!--            </div>-->
<!--            <div class="modal-footer">-->
<!--                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>-->
<!--                <button type="button" class="btn btn-primary" onclick="searchEpsonPrinters({{ $slotNum }})">-->
<!--                    <i class="bi bi-arrow-clockwise"></i> Cari Ulang-->
<!--                </button>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</div>-->

<style>
.voucher-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.voucher-code {
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 3px;
    font-family: 'Courier New', monospace;
    padding: 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    margin: 10px 0;
}

@media print {
    .modal-header, .modal-footer { display: none; }
    .voucher-box { box-shadow: none; }
}
</style>

<script>
// // Store voucher data for slot {{ $slotNum }}
// window.voucherData{{ $slotNum }} = {
//     requestNumber: "{{ $request->request_number }}",
//     voucherCode: "{{ $request->approvedRequest->voucher_code ?? 'N/A' }}",
//     requesterName: "{{ $request->requester->nama_lengkap ?? 'N/A' }}",
//     vehicleCode: "{{ $request->vehicle->consumerial_code ?? 'N/A' }}",
//     vehicleName: "{{ $request->vehicle->consumerial_name ?? '' }}",
//     vehicleType: "{{ $request->vehicle->consumerial_type ?? '' }}",
//     gasolineType: "{{ $request->gasoline_type }}",
//     billAmount: {{ $request->bill_amounts }},
//     approvalDate: "{{ $request->authorization_date ? $request->authorization_date->translatedFormat('d M Y H:i') : '-' }}"
// };

// // Fungsi untuk print ke Thermal Printer via USB
// async function printToThermalUSB{{ $slotNum }}() {
//     try {
//         // Cek apakah browser support Web USB API
//         if (!('usb' in navigator)) {
//             alert('Browser Anda tidak mendukung Web USB API. Gunakan Chrome/Edge versi terbaru.');
//             return;
//         }

//         // Request USB device (thermal printer)
//         const device = await navigator.usb.requestDevice({
//             filters: [
//                 // Filter untuk thermal printer umum (ESC/POS)
//                 { vendorId: 0x04b8 }, // Epson
//                 { vendorId: 0x0416 }, // Star
//                 { vendorId: 0x0519 }, // Bixolon
//                 { vendorId: 0x0dd4 }, // Custom
//                 { vendorId: 0x0483 }  // Generic USB Printer
//             ]
//         });

//         // Open device
//         await device.open();

//         // Pilih konfigurasi
//         if (device.configuration === null) {
//             await device.selectConfiguration(1);
//         }

//         // Claim interface
//         await device.claimInterface(0);

//         // Data voucher
//         const voucherCode = '{{ $request->approvedRequest->voucher_code ?? "N/A" }}';
//         const requestNumber = '{{ $request->request_number }}';
//         const vehicleCode = '{{ $request->vehicle->consumerial_code ?? "N/A" }}';
//         const vehicleName = '{{ $request->vehicle->consumerial_name ?? "" }}';
//         const amount = 'Rp {{ number_format($request->bill_amounts, 0, ",", ".") }}';
//         const approvalDate = '{{ $request->authorization_date ? $request->authorization_date->format("d M Y H:i") : "-" }}';

//         // ESC/POS Commands
//         const ESC = 0x1B;
//         const GS = 0x1D;

//         // Format print data
//         let printData = [
//             ESC, 0x40, // Initialize printer
//             ESC, 0x61, 0x01, // Center align
//             ESC, 0x45, 0x01, // Bold ON
//             ...stringToBytes('PT Geo Dipa Energi (Persero)\n'),
//             ...stringToBytes('Unit Patuha\n'),
//             ...stringToBytes('VOUCHER BBM\n'),
//             ESC, 0x45, 0x00, // Bold OFF
//             ESC, 0x61, 0x00, // Left align
//             ...stringToBytes('================================\n'),
//             ...stringToBytes('Request ID: ' + requestNumber + '\n'),
//             ...stringToBytes('Kendaraan: ' + vehicleCode + '\n'),
//             ...stringToBytes(vehicleName + '\n'),
//             ...stringToBytes('Nominal: ' + amount + '\n'),
//             ...stringToBytes('Tanggal: ' + approvalDate + '\n'),
//             ...stringToBytes('================================\n'),
//             ESC, 0x61, 0x01, // Center align
//             ESC, 0x45, 0x01, // Bold ON
//             GS, 0x21, 0x11, // Double size
//             ...stringToBytes('\nKODE VOUCHER:\n'),
//             ...stringToBytes(voucherCode + '\n\n'),
//             GS, 0x21, 0x00, // Normal size
//             ESC, 0x45, 0x00, // Bold OFF
//             ...stringToBytes('================================\n\n\n'),
//             ESC, 0x64, 0x05, // Feed 5 lines
//             GS, 0x56, 0x00  // Cut paper
//         ];

//         // Transfer data ke printer
//         await device.transferOut(1, new Uint8Array(printData));

//         // Tunggu sebentar
//         await new Promise(resolve => setTimeout(resolve, 500));

//         // Release interface dan close device
//         await device.releaseInterface(0);
//         await device.close();

//         alert('Voucher berhasil dicetak ke thermal printer!');

//     } catch (error) {
//         console.error('Error printing to thermal printer:', error);
//         if (error.name === 'NotFoundError') {
//             alert('Tidak ada printer yang dipilih.');
//         } else if (error.name === 'SecurityError') {
//             alert('Akses USB ditolak. Pastikan menggunakan HTTPS.');
//         } else {
//             alert('Gagal mencetak: ' + error.message);
//         }
//     }
// }

// // Fungsi untuk print via Thermer App - JSON Response Method
// function printViaThermer{{ $slotNum }}() {
//     try {
//         const data = window.voucherData{{ $slotNum }};

//         if (!data || !data.voucherCode || data.voucherCode === 'N/A') {
//             alert('Data voucher tidak lengkap');
//             return;
//         }

//         // ✅ Pakai signed URL yang expire dalam 1 jam
//         const responseUrl = '{{ \Illuminate\Support\Facades\URL::temporarySignedRoute("voucher.thermer.response", now()->addHour(), ["id" => $request->id]) }}';

//         const thermerUrl = `my.bluetoothprint.scheme://${responseUrl}`;

//         console.log('Thermer URL:', thermerUrl);
//         console.log('Response URL:', responseUrl);

//         const isAndroid = /Android/i.test(navigator.userAgent);

//         if (isAndroid) {
//             window.location.href = thermerUrl;

//             setTimeout(() => {
//                 const modal = bootstrap.Modal.getInstance(document.getElementById('voucherModal{{ $slotNum }}'));
//                 if (modal) modal.hide();
//             }, 300);
//         } else {
//             window.open(responseUrl, '_blank');
//         }

//     } catch (error) {
//         console.error('Error:', error);
//         alert('Gagal: ' + error.message);
//     }
// }

// Helper: Download file .txt untuk desktop users
function downloadThermerFile{{ $slotNum }}(thermerData, requestNumber) {
    const blob = new Blob([thermerData], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `voucher_${requestNumber}_thermer.txt`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    // Tutup modal
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('voucherModal{{ $slotNum }}'));
        if (modal) modal.hide();
    }, 500);
}

// Helper function untuk truncate text (max 32 karakter)
function truncateText(text, maxLength) {
    if (!text) return 'N/A';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
}

// Helper function untuk format rupiah
function formatRupiah(amount) {
    if (!amount) return '0';
    return amount.toLocaleString('id-ID');
}

// Helper function untuk convert string ke bytes
function stringToBytes(str) {
    const encoder = new TextEncoder();
    return Array.from(encoder.encode(str));
}
</script>
