@extends('templates.main')

@section('title', 'Kelola Permohonan & Pelaporan')

@section('content')
<!-- Alert Messages -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Terjadi kesalahan:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Kelola Permohonan & Pelaporan</h5>
    </div>
    <div class="card-body">
        <!-- Sub Tabs -->
        <ul class="nav nav-tabs sub-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'permohonan' ? 'active' : '' }}"
                   href="{{ route('superadmin.permohonan', ['tab' => 'permohonan']) }}">
                    <i class="bi bi-file-earmark-text me-1"></i> Kelola Permohonan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'laporan' ? 'active' : '' }}"
                   href="{{ route('superadmin.permohonan', ['tab' => 'laporan']) }}">
                    <i class="bi bi-receipt me-1"></i> Kelola Laporan Transaksi
                </a>
            </li>
        </ul>

        <!-- TAB: Kelola Permohonan -->
        @if($tab == 'permohonan')
        <div id="permohonanTab">
            <!-- Filter -->
            <form method="GET" action="{{ route('superadmin.permohonan') }}" class="mb-3">
                <input type="hidden" name="tab" value="permohonan">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search"
                               value="{{ $search }}"
                               placeholder="🔍 Cari nomor permohonan, pemohon, kendaraan...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">-- Semua Status --</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="pending_vehicle_approval" {{ $status == 'pending_vehicle_approval' ? 'selected' : '' }}>Pending Kendaraan</option>
                            <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="cancelled_by_driver" {{ $status == 'cancelled_by_driver' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>

            <!-- Table Permohonan -->
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th width="100">No. Permohonan</th>
                            <th>Pemohon</th>
                            <th>Kendaraan/Alat</th>
                            <th>Jenis BBM</th>
                            <th>Nominal</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permohonans as $p)
                        <tr>
                            <td><code>{{ $p->request_number }}</code></td>
                            <td>
                                <small>
                                    <strong>{{ $p->requester->nama_lengkap ?? 'Unknown' }}</strong><br>
                                    <span class="text-muted">{{ $p->requester->username ?? '-' }}</span>
                                </small>
                            </td>
                            <td>
                                <small>
                                    <strong>{{ $p->vehicle->consumerial_code ?? 'N/A' }}</strong><br>
                                    {{ $p->vehicle->consumerial_name ?? 'N/A' }}
                                </small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $p->gasoline_type }}</span></td>
                            <td>Rp {{ number_format($p->bill_amounts, 0, ',', '.') }}</td>
                            <td><small>{{ $p->request_date->format('d/m/Y') }}</small></td>
                            <td>
                                @if($p->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($p->status == 'pending_vehicle_approval')
                                    <span class="badge bg-info">Pending Kendaraan</span>
                                @elseif($p->status == 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($p->status == 'completed')
                                    <span class="badge bg-primary">Completed</span>
                                @elseif($p->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($p->status == 'cancelled_by_driver')
                                    <span class="badge bg-dark">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    {{-- ✅ GUNAKAN DATA ATTRIBUTES BUKAN ONCLICK INLINE --}}
                                    <button class="btn btn-warning btn-edit-permohonan"
                                            data-id="{{ $p->id }}"
                                            data-request-number="{{ $p->request_number }}"
                                            data-vehicle-id="{{ $p->consumerial_tools_id }}"
                                            data-gasoline-type="{{ $p->gasoline_type }}"
                                            data-bill-amounts="{{ $p->bill_amounts }}"
                                            data-requester-notes="{{ $p->requester_notes ?? '' }}"
                                            data-authorizer-notes="{{ $p->authorizer_notes ?? '' }}"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                @if(!in_array($p->status, ['completed', 'cancelled_by_driver']))
                                    <form action="{{ route('superadmin.permohonan.cancel', $p->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin membatalkan permohonan {{ $p->request_number }}?')">
                                        @csrf
                                        <input type="hidden" name="cancel_reason" value="cancel_by_superadmin">
                                        <button type="submit" class="btn btn-dark btn-sm" title="Cancel">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($p->status != 'completed')
                                    <form action="{{ route('superadmin.permohonan.delete', $p->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus permohonan {{ $p->request_number }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p>Tidak ada data permohonan</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $permohonans->appends(['tab' => 'permohonan', 'status' => $status, 'search' => $search])->links() }}
            </div>
        </div>
        @endif

        <!-- TAB: Kelola Laporan -->
        @if($tab == 'laporan')
        <div id="laporanTab">
            <!-- Filter -->
            <form method="GET" action="{{ route('superadmin.permohonan') }}" class="mb-3">
                <input type="hidden" name="tab" value="laporan">
                <div class="row g-2">
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="search"
                               value="{{ $search }}"
                               placeholder="🔍 Cari ID transaksi, nomor permohonan...">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>

            <!-- Table Laporan -->
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>ID Transaksi</th>
                            <th>No. Permohonan</th>
                            <th>Pemohon</th>
                            <th>Kendaraan</th>
                            <th>Tanggal Pengisian</th>
                            <th>Volume (L)</th>
                            <th>KM Terakhir</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporans as $lap)
                        <tr>
                            <td><code>{{ $lap->transaction_id }}</code></td>
                            <td><code>{{ $lap->permohonan->request_number ?? 'N/A' }}</code></td>
                            <td>
                                <small>{{ $lap->permohonan->requester->nama_lengkap ?? 'Unknown' }}</small>
                            </td>
                            <td>
                                <small>
                                    <strong>{{ $lap->permohonan->vehicle->consumerial_code ?? 'N/A' }}</strong><br>
                                    {{ $lap->permohonan->vehicle->consumerial_name ?? 'N/A' }}
                                </small>
                            </td>
                            <td><small>{{ $lap->purchase_datetime->format('d/m/Y H:i') }}</small></td>
                            <td>{{ number_format($lap->fuel_volume, 2) }} L</td>
                            <td>{{ number_format($lap->km_terakhir, 0, ',', '.') }} km</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    {{-- ✅ GUNAKAN DATA ATTRIBUTES --}}
                                    <button class="btn btn-warning btn-edit-laporan"
                                            data-id="{{ $lap->id }}"
                                            data-transaction-id="{{ $lap->transaction_id }}"
                                            data-purchase-date="{{ $lap->purchase_datetime->format('Y-m-d') }}"
                                            data-fuel-volume="{{ $lap->fuel_volume }}"
                                            data-km-terakhir="{{ $lap->km_terakhir }}"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    {{-- ✅ GUNAKAN DATA ATTRIBUTES --}}
                                    <button class="btn btn-info btn-view-struk"
                                            data-image-url="{{ asset('storage/' . $lap->struk_bbm_path) }}"
                                            data-transaction-id="{{ $lap->transaction_id }}"
                                            title="Lihat Struk">
                                        <i class="bi bi-receipt"></i>
                                    </button>

                                    <form action="{{ route('superadmin.laporan.delete', $lap->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus laporan {{ $lap->transaction_id }}?\n\nPermohonan akan dikembalikan ke status approved.')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p>Tidak ada data laporan transaksi</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $laporans->appends(['tab' => 'laporan', 'search' => $search])->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal: Edit Permohonan -->
<div class="modal fade" id="editPermohonanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editPermohonanForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Permohonan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Nomor Permohonan:</strong> <span id="editRequestNumber"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kendaraan/Alat *</label>
                        <select class="form-select" name="consumerial_tools_id" id="editVehicleId" required>
                            <option value="">-- Pilih Kendaraan/Alat --</option>
                            @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" data-budget="{{ $vehicle->detail->nominal_full_tank ?? '' }}">
                                {{ $vehicle->dropdown_label }} ({{ $vehicle->consumerial_type }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="editVehicleBudgetInfo"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis BBM *</label>
                            <select class="form-select" name="gasoline_type" id="editGasolineType" required>
                                @foreach($gasolineTypes as $gas)
                                {{-- <option value="{{ $gas->gasoline_name }}">{{ $gas->gasoline_name }}</option> --}}
                                <option value="Pertamax">Pertamax</option>
                                <option value="Pertamax">Pertamax</option>
                                <option value="Pertamina Dex">Pertamina Dex</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nominal (Rp) *</label>
                            <select class="form-select" name="bill_amounts" id="editBillAmounts" required>
                                @for($i = 150000; $i <= 850000; $i += 50000)
                                <option value="{{ $i }}">Rp {{ number_format($i, 0, ',', '.') }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Pemohon</label>
                        <textarea class="form-control" name="requester_notes" id="editRequesterNotes" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Authorizer</label>
                        <textarea class="form-control" name="authorizer_notes" id="editAuthorizerNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Laporan -->
<div class="modal fade" id="editLaporanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editLaporanForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Laporan Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>ID Transaksi:</strong> <span id="editTransactionId"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Pengisian *</label>
                            <input type="date" class="form-control" name="purchase_date" id="editPurchaseDate" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Volume BBM (Liter) *</label>
                            <input type="number" step="0.01" class="form-control" name="fuel_volume" id="editFuelVolume" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">KM Terakhir (Opsional untuk Alat)</label>
                        <input type="number" class="form-control" name="km_terakhir" id="editKmTerakhir">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Ulang Foto Struk (Opsional)</label>
                        <input type="file" class="form-control" name="foto_struk" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Struk BBM -->
<div class="modal fade" id="viewStrukModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-receipt me-2"></i>Struk BBM - <span id="strukTransactionId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="strukImage" src="" alt="Struk BBM" class="img-fluid rounded shadow-sm" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <a id="downloadStruk" href="" download class="btn btn-success">
                    <i class="bi bi-download me-2"></i>Download Foto
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Foto Odometer -->
<div class="modal fade" id="viewOdometerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-speedometer2 me-2"></i>Foto Odometer - <span id="odoTransactionId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="odoImage" src="" alt="Foto Odometer" class="img-fluid rounded shadow-sm" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <a id="downloadOdometer" href="" download class="btn btn-success">
                    <i class="bi bi-download me-2"></i>Download Foto
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script>
// ✅ CRITICAL FIX: Pisahkan event listeners dari lifecycle alert
(function() {
    'use strict';

    // Global state
    window.isModalOpening = false;

    // ✅ Setup event delegation dengan capture phase (lebih reliable)
    function initEventHandlers() {
        // ✅ Edit Permohonan Handler
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-permohonan');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if (window.isModalOpening) {
                console.log('Modal already opening, blocked');
                return;
            }

            window.isModalOpening = true;
            console.log('Opening modal...');

            setTimeout(() => {
                window.isModalOpening = false;
            }, 1000);

            try {
                const data = {
                    id: btn.getAttribute('data-id'),
                    requestNumber: btn.getAttribute('data-request-number'),
                    vehicleId: btn.getAttribute('data-vehicle-id'),
                    gasolineType: btn.getAttribute('data-gasoline-type'),
                    billAmounts: btn.getAttribute('data-bill-amounts'),
                    requesterNotes: btn.getAttribute('data-requester-notes') || '',
                    authorizerNotes: btn.getAttribute('data-authorizer-notes') || ''
                };

                openEditPermohonanModal(
                    data.id,
                    data.requestNumber,
                    data.vehicleId,
                    data.gasolineType,
                    data.billAmounts,
                    data.requesterNotes,
                    data.authorizerNotes
                );
            } catch (error) {
                console.error('Error opening modal:', error);
                window.isModalOpening = false;
            }
        }, true); // ✅ Use capture phase

        // ✅ Edit Laporan Handler
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-laporan');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if (window.isModalOpening) return;

            window.isModalOpening = true;
            setTimeout(() => { window.isModalOpening = false; }, 1000);

            try {
                const data = {
                    id: btn.getAttribute('data-id'),
                    transactionId: btn.getAttribute('data-transaction-id'),
                    purchaseDate: btn.getAttribute('data-purchase-date'),
                    fuelVolume: btn.getAttribute('data-fuel-volume'),
                    kmTerakhir: btn.getAttribute('data-km-terakhir')
                };

                openEditLaporanModal(
                    data.id,
                    data.transactionId,
                    data.purchaseDate,
                    data.fuelVolume,
                    data.kmTerakhir
                );
            } catch (error) {
                console.error('Error opening modal:', error);
                window.isModalOpening = false;
            }
        }, true);

        // ✅ View Struk Handler
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-struk');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if (window.isModalOpening) return;

            window.isModalOpening = true;
            setTimeout(() => { window.isModalOpening = false; }, 1000);

            try {
                const imageUrl = btn.getAttribute('data-image-url');
                const transactionId = btn.getAttribute('data-transaction-id');

                viewStrukModal(imageUrl, transactionId);
            } catch (error) {
                console.error('Error opening modal:', error);
                window.isModalOpening = false;
            }
        }, true);
    }

    // ✅ Cleanup function
    function cleanupModals() {
        document.querySelectorAll('.modal-backdrop').forEach(b => {
            b.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    // ✅ Modal Functions
    // Budget dinamis dari Master Vehicle: tampilkan info + susun opsi nominal
    function updateVehicleBudgetUI() {
        const vehicleSelect = document.getElementById('editVehicleId');
        const infoEl = document.getElementById('editVehicleBudgetInfo');
        const billSelect = document.getElementById('editBillAmounts');
        if (!vehicleSelect || !infoEl || !billSelect) return;

        const opt = vehicleSelect.options[vehicleSelect.selectedIndex];
        const budget = opt && opt.dataset.budget ? parseFloat(opt.dataset.budget) : 0;
        const prevValue = billSelect.value;

        if (budget > 0) {
            infoEl.innerHTML = '<i class="bi bi-fuel-pump"></i> Budget full tank: <strong>Rp ' +
                new Intl.NumberFormat('id-ID').format(budget) + '</strong>';
        } else {
            infoEl.textContent = 'Budget full tank kendaraan belum diisi di Master Vehicle.';
        }

        // Susun ulang opsi nominal maksimal budget full tank (kelipatan 50rb)
        billSelect.innerHTML = '';
        const max = budget > 0 ? Math.min(budget, 850000) : 850000;
        for (let i = 50000; i <= max; i += 50000) {
            const op = document.createElement('option');
            op.value = i;
            op.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(i);
            billSelect.appendChild(op);
        }
        if (prevValue && parseFloat(prevValue) <= max) {
            billSelect.value = prevValue;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const vhSel = document.getElementById('editVehicleId');
        if (vhSel) {
            vhSel.addEventListener('change', updateVehicleBudgetUI);
        }
    });

    window.openEditPermohonanModal = function(id, requestNumber, vehicleId, gasolineType, billAmounts, requesterNotes, authorizerNotes) {
        console.log('openEditPermohonanModal called', id);

        const modalElement = document.getElementById('editPermohonanModal');
        if (!modalElement) {
            console.error('Modal element not found');
            return;
        }

        // Dispose existing
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }

        cleanupModals();

        // Set form
        const form = document.getElementById('editPermohonanForm');
        if (form) {
            form.action = `/permohonan/${id}/update`;
        }

        // Set values
        const elements = {
            editRequestNumber: document.getElementById('editRequestNumber'),
            editVehicleId: document.getElementById('editVehicleId'),
            editGasolineType: document.getElementById('editGasolineType'),
            editBillAmounts: document.getElementById('editBillAmounts'),
            editRequesterNotes: document.getElementById('editRequesterNotes'),
            editAuthorizerNotes: document.getElementById('editAuthorizerNotes')
        };

        if (elements.editRequestNumber) elements.editRequestNumber.textContent = requestNumber;
        if (elements.editVehicleId) elements.editVehicleId.value = vehicleId;
        if (elements.editGasolineType) elements.editGasolineType.value = gasolineType;
        if (elements.editBillAmounts) elements.editBillAmounts.value = billAmounts;
        if (elements.editRequesterNotes) elements.editRequesterNotes.value = requesterNotes;
        if (elements.editAuthorizerNotes) elements.editAuthorizerNotes.value = authorizerNotes;

        // Susun ulang opsi nominal sesuai budget full tank kendaraan terpilih
        updateVehicleBudgetUI();

        // Show modal
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });

        modal.show();

        // Cleanup on hide
        modalElement.addEventListener('hidden.bs.modal', function hideHandler() {
            const inst = bootstrap.Modal.getInstance(modalElement);
            if (inst) inst.dispose();
            cleanupModals();
            modalElement.removeEventListener('hidden.bs.modal', hideHandler);
        });
    };

    window.openEditLaporanModal = function(id, transactionId, purchaseDate, fuelVolume, kmTerakhir) {
        const modalElement = document.getElementById('editLaporanModal');
        if (!modalElement) return;

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();

        cleanupModals();

        const form = document.getElementById('editLaporanForm');
        if (form) form.action = `/laporan/${id}/update`;

        const elements = {
            editTransactionId: document.getElementById('editTransactionId'),
            editPurchaseDate: document.getElementById('editPurchaseDate'),
            editFuelVolume: document.getElementById('editFuelVolume'),
            editKmTerakhir: document.getElementById('editKmTerakhir')
        };

        if (elements.editTransactionId) elements.editTransactionId.textContent = transactionId;
        if (elements.editPurchaseDate) elements.editPurchaseDate.value = purchaseDate;
        if (elements.editFuelVolume) elements.editFuelVolume.value = fuelVolume;
        if (elements.editKmTerakhir) elements.editKmTerakhir.value = kmTerakhir;

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();

        modalElement.addEventListener('hidden.bs.modal', function hideHandler() {
            const inst = bootstrap.Modal.getInstance(modalElement);
            if (inst) inst.dispose();
            cleanupModals();
            modalElement.removeEventListener('hidden.bs.modal', hideHandler);
        });
    };

    window.viewStrukModal = function(imageUrl, transactionId) {
        const modalElement = document.getElementById('viewStrukModal');
        if (!modalElement) return;

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();

        cleanupModals();

        const strukImage = document.getElementById('strukImage');
        const strukTransactionId = document.getElementById('strukTransactionId');
        const downloadStruk = document.getElementById('downloadStruk');

        if (strukImage) strukImage.src = imageUrl;
        if (strukTransactionId) strukTransactionId.textContent = transactionId;
        if (downloadStruk) downloadStruk.href = imageUrl;

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('hidden.bs.modal', function hideHandler() {
            const inst = bootstrap.Modal.getInstance(modalElement);
            if (inst) inst.dispose();
            cleanupModals();
            modalElement.removeEventListener('hidden.bs.modal', hideHandler);
        });
    };

    window.viewOdometerModal = function(imageUrl, transactionId) {
        const modalElement = document.getElementById('viewOdometerModal');
        if (!modalElement) return;

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();

        cleanupModals();

        const odoImage = document.getElementById('odoImage');
        const odoTransactionId = document.getElementById('odoTransactionId');
        const downloadOdometer = document.getElementById('downloadOdometer');

        if (odoImage) odoImage.src = imageUrl;
        if (odoTransactionId) odoTransactionId.textContent = transactionId;
        if (downloadOdometer) downloadOdometer.href = imageUrl;

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('hidden.bs.modal', function hideHandler() {
            const inst = bootstrap.Modal.getInstance(modalElement);
            if (inst) inst.dispose();
            cleanupModals();
            modalElement.removeEventListener('hidden.bs.modal', hideHandler);
        });
    };

    // ✅ Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            cleanupModals();
            initEventHandlers();
            console.log('Event handlers initialized');
        });
    } else {
        cleanupModals();
        initEventHandlers();
        console.log('Event handlers initialized (immediate)');
    }

    // ✅ Cleanup on unload
    window.addEventListener('beforeunload', cleanupModals);

    // ✅ IMPORTANT: Alert dismissal dipisahkan dan delayed
    // Biarkan alerts auto-dismiss tanpa mengganggu event listeners
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                } catch (e) {
                    // Silently ignore
                }
            }, 5000);
        });
    }, 100);

})();
</script>

<style>
.sub-tabs .nav-link {
    border: 2px solid #e9ecef;
    color: #6c757d;
    font-weight: 500;
    padding: 8px 16px;
    margin-right: 5px;
}

.sub-tabs .nav-link.active {
    background: #ffc107;
    border-color: #ffc107;
    color: #000;
}

.table-sm th, .table-sm td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Modal image styling */
#viewStrukModal img,
#viewOdometerModal img {
    cursor: zoom-in;
    transition: transform 0.3s ease;
}

#viewStrukModal img:hover,
#viewOdometerModal img:hover {
    transform: scale(1.05);
}

/* Prevent modal backdrop z-index issues */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

/* Prevent double-click issues */
.btn:disabled {
    pointer-events: none;
}
</style>
@endsection
