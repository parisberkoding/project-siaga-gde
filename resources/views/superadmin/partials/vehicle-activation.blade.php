@extends('templates.main')

@section('title', 'Aktivasi Kendaraan & Alat')

@section('content')

<div class="pagetitle">
    <h1>Aktivasi Kendaraan & Alat</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Aktivasi Kendaraan</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats Cards --}}
<div class="row mb-4">
    <div class="col-xxl-4 col-md-6">
        <div class="card info-card sales-card">
            <div class="card-body">
                <h5 class="card-title">Total Kendaraan & Alat</h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="ps-3">
                        <h6>{{ $stats['total'] }}</h6>
                        <span class="text-muted small pt-2">Keseluruhan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-md-6">
        <div class="card info-card revenue-card">
            <div class="card-body">
                <h5 class="card-title">Aktif</h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="ps-3">
                        <h6>{{ $stats['active'] }}</h6>
                        <span class="text-muted small pt-2">
                            {{ $stats['kendaraan_active'] }} Kendaraan · {{ $stats['alat_active'] }} Alat
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-md-6">
        <div class="card info-card warning-card">
            <div class="card-body">
                <h5 class="card-title">Tidak Aktif</h5>
                <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="ps-3">
                        <h6>{{ $stats['inactive'] }}</h6>
                        <span class="text-muted small pt-2">Perlu aktivasi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Card --}}
<div class="card">
    <div class="card-body pt-4">
        <h5 class="card-title">
            <i class="bi bi-toggle-on me-2"></i>Kelola Status Kendaraan & Alat
        </h5>

        <form method="GET" action="{{ route('superadmin.vehicle-activation') }}" class="mb-3" data-autofilter>
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text"
                           class="form-control"
                           name="search"
                           value="{{ $search }}"
                           placeholder="🔍 Cari kode, nama, atau catatan...">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="type">
                        <option value="">-- Semua Tipe --</option>
                        <option value="KENDARAAN" {{ $type == 'KENDARAAN' ? 'selected' : '' }}>Kendaraan</option>
                        <option value="ALAT" {{ $type == 'ALAT' ? 'selected' : '' }}>Alat</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">-- Semua Status --</option>
                        <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- Bulk Actions -->
        <div class="mb-3">
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm" onclick="bulkActivate()">
                    <i class="bi bi-check-circle me-1"></i>Aktifkan Terpilih
                </button>
                {{-- <button class="btn btn-warning btn-sm" onclick="openBulkDeactivateModal()">
                    <i class="bi bi-x-circle me-1"></i>Nonaktifkan Terpilih
                </button> --}}
                {{-- <button class="btn btn-secondary btn-sm" onclick="selectAll()">
                    <i class="bi bi-check-square me-1"></i>Pilih Semua
                </button> --}}
                <button class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">
                    <i class="bi bi-square me-1"></i>Batal Pilih
                </button>
            </div>
            <small class="text-muted">Pilih kendaraan/alat dengan checkbox, lalu gunakan tombol aksi di atas</small>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th width="100">Tipe</th>
                        <th width="150">Kode</th>
                        <th>Nama</th>
                        <th>Catatan</th>
                        <th width="100">Status</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $v)
                    <tr class="{{ !$v->is_active ? 'table-warning' : '' }}">
                        <td>
                            <input type="checkbox"
                                   class="vehicle-checkbox"
                                   value="{{ $v->id }}"
                                   data-status="{{ $v->is_active ? 'active' : 'inactive' }}">
                        </td>
                        <td>
                            @if($v->consumerial_type == 'KENDARAAN')
                                <span class="badge bg-primary">
                                    <i class="bi bi-car-front"></i> Kendaraan
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-tools"></i> Alat
                                </span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-primary">{{ $v->consumerial_code }}</strong>
                        </td>
                        <td>
                            <strong>{{ $v->consumerial_name }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $v->consumerial_notes ? Str::limit($v->consumerial_notes, 50) : '-' }}
                            </small>
                        </td>
                        <td>
                            @if($v->is_active)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Aktif
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-x-circle"></i> Tidak Aktif
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                @if($v->is_active)
                                    {{-- Tombol Deactivate --}}
                                    <button class="btn btn-warning btn-deactivate"
                                            data-id="{{ $v->id }}"
                                            data-code="{{ $v->consumerial_code }}"
                                            data-name="{{ $v->consumerial_name }}"
                                            title="Nonaktifkan">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                @else
                                    {{-- Tombol Activate --}}
                                    <form action="{{ route('superadmin.vehicle.activate', $v->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Aktifkan {{ $v->consumerial_code }} - {{ $v->consumerial_name }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Info Button --}}
                                <button class="btn btn-info btn-view-notes"
                                        data-code="{{ $v->consumerial_code }}"
                                        data-name="{{ $v->consumerial_name }}"
                                        data-notes="{{ $v->consumerial_notes }}"
                                        data-created-by="{{ $v->creator->nama_lengkap ?? 'Unknown' }}"
                                        data-approved-by="{{ $v->approver->nama_lengkap ?? '-' }}"
                                        title="Info">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2">Tidak ada data kendaraan/alat</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            {{ $vehicles->appends(['type' => $type, 'status' => $status, 'search' => $search])->links() }}
        </div>
    </div>
</div>

<!-- Modal: Deactivate Confirmation -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deactivateForm" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle me-2"></i>Nonaktifkan Kendaraan/Alat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong id="deactivateVehicleName"></strong>
                    </div>

                    <p class="text-muted">
                        Kendaraan/alat yang dinonaktifkan tidak akan muncul di daftar pilihan
                        saat membuat permohonan baru.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Alasan Nonaktif (Opsional)</label>
                        <textarea class="form-control"
                                  name="deactivation_reason"
                                  rows="3"
                                  placeholder="Contoh: Kendaraan rusak, dalam perbaikan, dll"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-x-circle me-2"></i>Nonaktifkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Bulk Deactivate -->
<div class="modal fade" id="bulkDeactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkDeactivateForm" method="POST" action="{{ route('superadmin.vehicle.bulk-deactivate') }}">
                @csrf
                <input type="hidden" name="vehicle_ids" id="bulkDeactivateIds">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle me-2"></i>Nonaktifkan Banyak Kendaraan/Alat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Anda akan menonaktifkan <strong id="bulkDeactivateCount">0</strong> kendaraan/alat
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan Nonaktif (Opsional)</label>
                        <textarea class="form-control"
                                  name="deactivation_reason"
                                  rows="3"
                                  placeholder="Contoh: Maintenance rutin, renovasi, dll"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-x-circle me-2"></i>Nonaktifkan Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Notes -->
<div class="modal fade" id="viewNotesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>Informasi Kendaraan/Alat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="200">Kode</th>
                        <td id="notesCode"></td>
                    </tr>
                    <tr>
                        <th>Nama</th>
                        <td id="notesName"></td>
                    </tr>
                    <tr>
                        <th>Catatan</th>
                        <td id="notesContent" style="white-space: pre-wrap;"></td>
                    </tr>
                    <tr>
                        <th>Dibuat Oleh</th>
                        <td id="notesCreatedBy"></td>
                    </tr>
                    <tr>
                        <th>Disetujui Oleh</th>
                        <td id="notesApprovedBy"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ ULTIMATE FIX: Vehicle Activation Modals
(function() {
    'use strict';

    // Global state
    window.isModalOpening = false;

    // ✅ Cleanup function
    function cleanupModals() {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    // ✅ Setup event handlers dengan CAPTURE PHASE
    function initEventHandlers() {

        // ✅ DEACTIVATE BUTTON HANDLER
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-deactivate');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if (window.isModalOpening) {
                console.log('Modal already opening, blocked');
                return;
            }

            window.isModalOpening = true;
            console.log('Opening deactivate modal...');

            setTimeout(() => {
                window.isModalOpening = false;
            }, 1000);

            try {
                const id = btn.getAttribute('data-id');
                const code = btn.getAttribute('data-code');
                const name = btn.getAttribute('data-name');

                openDeactivateModal(id, code, name);
            } catch (error) {
                console.error('Error opening deactivate modal:', error);
                window.isModalOpening = false;
            }
        }, true); // ✅ CAPTURE PHASE

        // ✅ VIEW NOTES BUTTON HANDLER
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-notes');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if (window.isModalOpening) return;

            window.isModalOpening = true;
            setTimeout(() => { window.isModalOpening = false; }, 1000);

            try {
                const code = btn.getAttribute('data-code');
                const name = btn.getAttribute('data-name');
                const notes = btn.getAttribute('data-notes') || '-';
                const createdBy = btn.getAttribute('data-created-by');
                const approvedBy = btn.getAttribute('data-approved-by');

                openNotesModal(code, name, notes, createdBy, approvedBy);
            } catch (error) {
                console.error('Error opening notes modal:', error);
                window.isModalOpening = false;
            }
        }, true);
    }

    // ✅ Modal Functions
    window.openDeactivateModal = function(id, code, name) {
        console.log('openDeactivateModal called', id);

        const modalElement = document.getElementById('deactivateModal');
        if (!modalElement) {
            console.error('Deactivate modal element not found');
            return;
        }

        // Dispose existing
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }

        cleanupModals();

        // Set form action
        const form = document.getElementById('deactivateForm');
        if (form) {
            form.action = `/vehicle/${id}/deactivate`;
        }

        // Set vehicle name
        const nameElement = document.getElementById('deactivateVehicleName');
        if (nameElement) {
            nameElement.textContent = `${code} - ${name}`;
        }

        // Clear textarea
        const textarea = form.querySelector('[name="deactivation_reason"]');
        if (textarea) {
            textarea.value = '';
        }

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

    window.openNotesModal = function(code, name, notes, createdBy, approvedBy) {
        console.log('openNotesModal called', code);

        const modalElement = document.getElementById('viewNotesModal');
        if (!modalElement) {
            console.error('Notes modal element not found');
            return;
        }

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();

        cleanupModals();

        // Set values
        const elements = {
            notesCode: document.getElementById('notesCode'),
            notesName: document.getElementById('notesName'),
            notesContent: document.getElementById('notesContent'),
            notesCreatedBy: document.getElementById('notesCreatedBy'),
            notesApprovedBy: document.getElementById('notesApprovedBy')
        };

        if (elements.notesCode) elements.notesCode.textContent = code;
        if (elements.notesName) elements.notesName.textContent = name;
        if (elements.notesContent) elements.notesContent.textContent = notes;
        if (elements.notesCreatedBy) elements.notesCreatedBy.textContent = createdBy;
        if (elements.notesApprovedBy) elements.notesApprovedBy.textContent = approvedBy;

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('hidden.bs.modal', function hideHandler() {
            const inst = bootstrap.Modal.getInstance(modalElement);
            if (inst) inst.dispose();
            cleanupModals();
            modalElement.removeEventListener('hidden.bs.modal', hideHandler);
        });
    };

    window.openBulkDeactivateModal = function() {
        const ids = getSelectedIds();

        if (ids.length === 0) {
            alert('Pilih minimal 1 kendaraan/alat');
            return;
        }

        const modalElement = document.getElementById('bulkDeactivateModal');
        if (!modalElement) return;

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();

        cleanupModals();

        document.getElementById('bulkDeactivateCount').textContent = ids.length;
        document.getElementById('bulkDeactivateIds').value = JSON.stringify(ids);

        // Clear textarea
        const form = document.getElementById('bulkDeactivateForm');
        const textarea = form.querySelector('[name="deactivation_reason"]');
        if (textarea) textarea.value = '';

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

    // ✅ Helper Functions
    window.toggleSelectAll = function(checkbox) {
        const checkboxes = document.querySelectorAll('.vehicle-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    };

    window.selectAll = function() {
        document.querySelectorAll('.vehicle-checkbox').forEach(cb => cb.checked = true);
        document.getElementById('selectAllCheckbox').checked = true;
    };

    window.deselectAll = function() {
        document.querySelectorAll('.vehicle-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
    };

    function getSelectedIds() {
        const checkboxes = document.querySelectorAll('.vehicle-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }
    window.getSelectedIds = getSelectedIds; // Expose

    window.bulkActivate = function() {
        const ids = getSelectedIds();

        if (ids.length === 0) {
            alert('Pilih minimal 1 kendaraan/alat');
            return;
        }

        if (!confirm(`Aktifkan ${ids.length} kendaraan/alat yang dipilih?`)) {
            return;
        }

        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("superadmin.vehicle.bulk-activate") }}';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'vehicle_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    };

    // ✅ Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            cleanupModals();
            initEventHandlers();
            setupBulkDeactivateForm();
            initAutoFilter();
            console.log('Vehicle activation handlers initialized');
        });
    } else {
        cleanupModals();
        initEventHandlers();
        setupBulkDeactivateForm();
        initAutoFilter();
        console.log('Vehicle activation handlers initialized (immediate)');
    }

    // ✅ Realtime filter: submit otomatis tanpa tombol Filter
    function initAutoFilter() {
        document.querySelectorAll('form[data-autofilter]').forEach(function(form) {
            const search = form.querySelector('input[name="search"]');
            if (search) {
                let timer;
                search.addEventListener('input', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() { form.submit(); }, 400);
                });
            }
            form.querySelectorAll('select').forEach(function(sel) {
                sel.addEventListener('change', function() { form.submit(); });
            });
        });
    }

    // ✅ Setup bulk deactivate form
    function setupBulkDeactivateForm() {
        const form = document.getElementById('bulkDeactivateForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const idsJson = document.getElementById('bulkDeactivateIds').value;
            const ids = JSON.parse(idsJson);
            const reason = this.querySelector('[name="deactivation_reason"]').value;

            const submitForm = document.createElement('form');
            submitForm.method = 'POST';
            submitForm.action = this.action;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            submitForm.appendChild(csrfInput);

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'vehicle_ids[]';
                input.value = id;
                submitForm.appendChild(input);
            });

            if (reason) {
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'deactivation_reason';
                reasonInput.value = reason;
                submitForm.appendChild(reasonInput);
            }

            document.body.appendChild(submitForm);
            submitForm.submit();
        });
    }

    // ✅ Cleanup on unload
    window.addEventListener('beforeunload', cleanupModals);

    // ✅ Alert dismissal (delayed & isolated)
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) bsAlert.close();
                } catch (e) {
                    // Silently ignore
                }
            }, 5000);
        });
    }, 100);

})();
</script>

<style>
.table-warning {
    background-color: #fff3cd !important;
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
