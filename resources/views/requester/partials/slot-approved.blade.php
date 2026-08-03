<div class="card border-start border-success border-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="card-title mb-0">
                <i class="bi bi-check-circle text-success"></i> Slot {{ $slotNum }}
            </h6>
            <span class="badge bg-success">APPROVED</span>
        </div>

        <!-- Voucher Box -->
        <div class="voucher-box mb-3">
            <div class="text-white text-center">
                <small><i class="bi bi-ticket-perforated"></i> KODE VOUCHER BBM</small>
                <div class="voucher-code">{{ $request->approvedRequest->voucher_code ?? 'N/A' }}</div>
            </div>
        </div>

        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Request ID:</strong></td>
                <td><code>{{ $request->request_number }}</code></td>
            </tr>
            <tr>
                <td><strong>Tanggal Disetujui:</strong></td>
                <td>{{ $request->authorization_date ? $request->authorization_date->format('d M Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <td><strong>Kendaraan/Alat:</strong></td>
                <td>
                    {{ $request->vehicle->consumerial_code ?? 'N/A' }}<br>
                    <small class="text-muted">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '' }})</small>
                </td>
            </tr>
            <tr>
                <td><strong>Nominal:</strong></td>
                <td><strong class="text-primary">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</strong></td>
            </tr>
            @if($request->authorizer_notes)
            <tr>
                <td><strong>Catatan Admin:</strong></td>
                <td><em>{{ $request->authorizer_notes }}</em></td>
            </tr>
            @endif
        </table>

        <hr>

        <button class="btn btn-success btn-sm w-100 mb-3"
                data-bs-toggle="modal"
                data-bs-target="#voucherModal{{ $slotNum }}">
            <i class="bi bi-printer"></i> Cetak Voucher BBM
        </button>

        <hr>

        <h6 class="mb-3"><i class="bi bi-cloud-upload"></i> Laporan Pembelian</h6>

        @php
            $isVehicle = $request->vehicle && $request->vehicle->consumerial_type === 'Kendaraan';
        @endphp

        @if(!$isVehicle)
        <div class="alert alert-info alert-sm p-2 mb-3">
            <small><i class="bi bi-info-circle"></i> <strong>Catatan:</strong> Untuk alat/mesin, KM dan foto odometer tidak wajib diisi.</small>
        </div>
        @endif

        <form action="{{ route('requester.submit-report', $request->id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="reportForm{{ $slotNum }}">
            @csrf

            <div class="mb-2">
                <label class="form-label">Tanggal Pengisian <span class="text-danger">*</span></label>
                <input type="date"
                       class="form-control form-control-sm @error('purchase_date') is-invalid @enderror"
                       name="purchase_date"
                       value="{{ old('purchase_date') }}"
                       required
                       max="{{ date('Y-m-d') }}">
                <small class="text-muted">Tanggal transaksi pengisian BBM ke SPBU</small>
                @error('purchase_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="form-label">Volume Pengisian (Liter) <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control form-control-sm @error('volume_bbm') is-invalid @enderror"
                       name="volume_bbm"
                       value="{{ old('volume_bbm') }}"
                       required
                       min="0"
                       step="0.01"
                       placeholder="Contoh: 25.5">
                <small class="text-muted">Lihat dari "Volume" pada struk BBM</small>
                @error('volume_bbm')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if($isVehicle)
            <!-- Field KM Akhir (WAJIB untuk Kendaraan) -->
            <div class="mb-2">
                <label class="form-label">KM Akhir <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control form-control-sm @error('km_akhir') is-invalid @enderror"
                       name="km_akhir"
                       value="{{ old('km_akhir') }}"
                       required
                       min="0"
                       placeholder="Contoh: 12500">
                <small class="text-muted">Odometer kendaraan setelah pengisian</small>
                @error('km_akhir')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Foto Odometer (WAJIB untuk Kendaraan) -->
            <div class="mb-2">
                <label class="form-label">Foto Odometer <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control form-control-sm @error('foto_km') is-invalid @enderror"
                       name="foto_km"
                       accept="image/*"
                       required
                       onchange="previewImage(this, 'previewKm{{ $slotNum }}')">
                <small class="text-muted">Foto speedometer/odometer kendaraan (max 5MB)</small>
                @error('foto_km')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <img id="previewKm{{ $slotNum }}"
                     class="img-thumbnail mt-2"
                     style="max-height: 150px; display: none;">
            </div>
            @else
            <!-- Field KM Akhir (OPSIONAL untuk Alat) -->
            <div class="mb-2">
                <label class="form-label"> Odoometer Terakhir <small class="text-muted"></small></label>
                <input type="number"
                       class="form-control form-control-sm @error('km_akhir') is-invalid @enderror"
                       name="km_akhir"
                       value="{{ old('km_akhir', 0) }}"
                       min="0"
                       placeholder="Isi 0 jika tidak ada">
                <small class="text-muted">Isi dengan 0 jika tidak memiliki odometer</small>
                @error('km_akhir')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endif

            <!-- Foto Struk BBM (WAJIB untuk semua) -->
            <div class="mb-3">
                <label class="form-label">Foto Struk BBM <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control form-control-sm @error('foto_struk') is-invalid @enderror"
                       name="foto_struk"
                       accept="image/*"
                       required
                       onchange="previewImage(this, 'previewStruk{{ $slotNum }}')">
                <small class="text-muted">Foto struk pembelian dari SPBU (max 5MB)</small>
                @error('foto_struk')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <img id="previewStruk{{ $slotNum }}"
                     class="img-thumbnail mt-2"
                     style="max-height: 150px; display: none;">
            </div>

            <button type="submit"
                    class="btn btn-primary btn-sm w-100"
                    id="submitReport{{ $slotNum }}">
                <i class="bi bi-send"></i> Kirim Laporan
            </button>
        </form>
    </div>
</div>

<!-- Include Print Modal -->
@include('requester.partials.voucher-print-modal', ['request' => $request, 'slotNum' => $slotNum])


<style>
.voucher-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
}

.voucher-code {
    font-size: 1.2rem;
    font-weight: bold;
    letter-spacing: 2px;
    font-family: 'Courier New', monospace;
    padding: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    margin-top: 5px;
}

.alert-sm {
    font-size: 0.875rem;
}
</style>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        // Check file size (max 5MB)
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal 5MB');
            input.value = '';
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

</script>
