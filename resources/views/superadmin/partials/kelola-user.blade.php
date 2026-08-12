@extends('templates.main')

@section('title', 'Kelola User')

@section('content')
<div class="pagetitle">
    <h1>Manajemen User</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Pengguna</li>
        </ol>
    </nav>
</div>

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
    <div class="card-body pt-4">
        <!-- Sub Tabs -->
        <ul class="nav nav-tabs sub-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'list' ? 'active' : '' }}"
                   href="{{ route('users', ['tab' => 'list']) }}">
                    <i class="bi bi-list-ul me-1"></i> Daftar User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('users.create') ? 'active' : '' }}"
                   href="{{ route('users.create') }}">
                    <i class="bi bi-plus-circle me-1"></i> Tambah User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'deleted' ? 'active' : '' }}"
                   href="{{ route('users', ['tab' => 'deleted']) }}">
                    <i class="bi bi-trash me-1"></i> Data Terhapus
                </a>
            </li>
        </ul>

        <!-- Tab Content: Daftar User -->
        @if($tab == 'list')
        <div id="userListTab">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('users') }}" class="mb-3" data-autofilter>
                <input type="hidden" name="tab" value="list">
                <div class="row g-2">
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="search"
                               value="{{ $search }}"
                               placeholder="🔍 Cari berdasarkan nama, username, atau email...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">-- Semua Role --</option>
                            @foreach($roles as $r)
                            <option value="{{ $r->code }}" {{ $role == $r->code ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Jabatan</th>
                            <th width="120">Role</th>
                            <th>Site</th>
                            <th>Unit Bisnis</th>
                            <th width="110">Master Pekerja</th>
                            <th>Tanggal Dibuat</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td><small class="text-muted">{{ $user->id }}</small></td>
                            <td><strong>{{ $user->username }}</strong><div class="small text-muted">{{ $user->user_code }}</div></td>
                            <td>{{ $user->nama_lengkap }}</td>
                            <td><small>{{ $user->email }}</small></td>
                            <td><small class="text-muted">{{ $user->jabatan ?? '-' }}</small></td>
                            <td>
                                @php
                                    $roleColors = [
                                        'superadmin' => 'bg-danger',
                                        'general_affairs' => 'bg-success',
                                        'dispatcher' => 'bg-secondary',
                                        'requester' => 'bg-info',
                                        'manager' => 'bg-warning text-dark',
                                        'view_only' => 'bg-dark',
                                    ];
                                    $rbacRole = $user->roles->first();
                                @endphp
                                @if($rbacRole)
                                    <span class="badge {{ $roleColors[$rbacRole->code] ?? 'bg-secondary' }}">
                                        {{ $rbacRole->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $user->role }}
                                    </span>
                                @endif
                            </td>
                            <td><small>{{ $user->defaultSite?->code ?? '-' }}</small></td>
                            <td>
                                @if($user->unit)
                                    <span class="badge bg-primary">{{ $user->unit->code }}</span>
                                @else
                                    <span class="badge bg-light text-muted">Global</span>
                                @endif
                            </td>
                            <td>
                                @if($user->employeeDefault)
                                    <span class="badge bg-success" title="Terhubung ke Master Pekerja"><i class="bi bi-link"></i> Aktif</span>
                                @else
                                    <span class="badge bg-light text-muted" title="Tidak terdaftar di Master Pekerja">-</span>
                                @endif
                            </td>
                            <td><small>{{ $user->created_at->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-action"
                                        onclick="openEditModal({{ $user->id }}, '{{ $user->username }}', '{{ e($user->nama_lengkap) }}', '{{ $user->email }}', '{{ e($user->jabatan ?? '') }}', '{{ $user->roles->first()->code ?? $user->role }}', '{{ $user->default_site_id ?? '' }}', '{{ $user->unit_id ?? '' }}')"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-info btn-sm btn-action"
                                        onclick="openResetModal({{ $user->id }}, '{{ $user->username }}')"
                                        title="Reset Password">
                                    <i class="bi bi-key"></i>
                                </button>
                                <form action="{{ route('users.delete', $user->id) }}"
                                      method="POST"
                                      style="display: inline;"
                                      onsubmit="return confirm('Yakin ingin menghapus user {{ $user->username }}?\n\nData dapat dipulihkan dari tab Data Terhapus.')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm btn-action" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Belum ada data user</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $users->appends(['tab' => 'list', 'role' => $role, 'search' => $search])->links() }}
            </div>
        </div>
        @endif

        <!-- Tab Content: Data Terhapus -->
        @if($tab == 'deleted')
        <div id="userDeletedTab">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Data yang dihapus masih dapat dipulihkan. Data akan terhapus permanen setelah 30 hari.
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Dihapus Pada</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deletedUsers as $user)
                        <tr>
                            <td><strong>{{ $user->username }}</strong></td>
                            <td>{{ $user->nama_lengkap }}</td>
                            <td><small>{{ $user->email }}</small></td>
                            <td>
                                @php
                                    $roleColors = [
                                        'superadmin' => 'bg-danger',
                                        'general_affairs' => 'bg-success',
                                        'dispatcher' => 'bg-secondary',
                                        'requester' => 'bg-info',
                                        'manager' => 'bg-warning text-dark',
                                        'view_only' => 'bg-dark',
                                    ];
                                    $rbacRole = $user->roles->first();
                                @endphp
                                @if($rbacRole)
                                    <span class="badge {{ $roleColors[$rbacRole->code] ?? 'bg-secondary' }}">
                                        {{ $rbacRole->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $user->role }}
                                    </span>
                                @endif
                            </td>
                            <td><small>{{ $user->deleted_at->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <form action="{{ route('users.restore', $user->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Pulihkan user {{ $user->username }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Pulihkan">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-check-circle"></i>
                                    <p>Tidak ada data yang dihapus</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $deletedUsers->appends(['tab' => 'deleted'])->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" id="editUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="editNamaLengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" name="jabatan" id="editJabatan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Hak Akses *</label>
                        <select class="form-select" name="role_code" id="editRoleCode" required>
                            <option value="">— Pilih Role —</option>
                            @foreach($roles as $r)
                            <option value="{{ $r->code }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site / Unit</label>
                        <select class="form-select" name="default_site_id" id="editSiteId">
                            <option value="">— Tidak ditentukan —</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->code }} — {{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Bisnis</label>
                        <select class="form-select" name="unit_id" id="editUnitId">
                            <option value="">— Global / Pusat (tanpa scope) —</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Kosongkan untuk akses global (pusat).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resetPasswordForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Password akan direset untuk user: <strong id="resetUsername"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" id="newPassword" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('newPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password_confirmation"
                                   id="newPasswordConfirmation" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('newPasswordConfirmation', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ✅ CRITICAL FIX: Pisahkan event listeners dari lifecycle alert
(function() {
    'use strict';

    // Global state untuk prevent double-opening
    window.isModalOpening = false;

    // ✅ Cleanup function untuk clear semua modal backdrop
    function cleanupModals() {
        document.querySelectorAll('.modal-backdrop').forEach(b => {
            b.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    // ✅ Toggle Password Visibility
    window.togglePassword = function(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    };

    // ✅ Open Edit User Modal
    window.openEditModal = function(id, username, namaLengkap, email, jabatan, role, siteId, unitId) {
        if (window.isModalOpening) {
            console.log('Modal already opening, blocked');
            return;
        }

        window.isModalOpening = true;
        console.log('Opening Edit Modal for user:', username);

        setTimeout(() => {
            window.isModalOpening = false;
        }, 1000);

        const modalElement = document.getElementById('editUserModal');
        if (!modalElement) {
            console.error('Edit Modal element not found');
            window.isModalOpening = false;
            return;
        }

        // Dispose existing modal instance
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }

        // Cleanup any leftover backdrops
        cleanupModals();

        // Set form action
        const form = document.getElementById('editUserForm');
        if (form) {
            form.action = `/users/${id}/update`;
        }

        // Set form values
        const elements = {
            editUsername: document.getElementById('editUsername'),
            editNamaLengkap: document.getElementById('editNamaLengkap'),
            editEmail: document.getElementById('editEmail'),
            editJabatan: document.getElementById('editJabatan'),
            editRoleCode: document.getElementById('editRoleCode'),
            editSiteId: document.getElementById('editSiteId'),
            editUnitId: document.getElementById('editUnitId'),
        };

        if (elements.editUsername) elements.editUsername.value = username;
        if (elements.editNamaLengkap) elements.editNamaLengkap.value = namaLengkap;
        if (elements.editEmail) elements.editEmail.value = email;
        if (elements.editJabatan) elements.editJabatan.value = jabatan || '';
        if (elements.editRoleCode) elements.editRoleCode.value = role;
        if (elements.editSiteId) elements.editSiteId.value = siteId || '';
        if (elements.editUnitId) elements.editUnitId.value = unitId || '';

        // Create new modal instance
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

    // ✅ Open Reset Password Modal
    window.openResetModal = function(id, username) {
        if (window.isModalOpening) {
            console.log('Modal already opening, blocked');
            return;
        }

        window.isModalOpening = true;
        console.log('Opening Reset Password Modal for user:', username);

        setTimeout(() => {
            window.isModalOpening = false;
        }, 1000);

        const modalElement = document.getElementById('resetPasswordModal');
        if (!modalElement) {
            console.error('Reset Modal element not found');
            window.isModalOpening = false;
            return;
        }

        // Dispose existing modal instance
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }

        // Cleanup any leftover backdrops
        cleanupModals();

        // Set form action
        const form = document.getElementById('resetPasswordForm');
        if (form) {
            form.action = `/users/${id}/reset-password`;
        }

        // Set username display
        const resetUsernameEl = document.getElementById('resetUsername');
        if (resetUsernameEl) {
            resetUsernameEl.textContent = username;
        }

        // Clear password fields
        const newPasswordEl = document.getElementById('newPassword');
        const newPasswordConfEl = document.getElementById('newPasswordConfirmation');

        if (newPasswordEl) newPasswordEl.value = '';
        if (newPasswordConfEl) newPasswordConfEl.value = '';

        // Reset password visibility to hidden
        ['newPassword', 'newPasswordConfirmation'].forEach(id => {
            const input = document.getElementById(id);
            if (input && input.type === 'text') {
                input.type = 'password';
                const btn = input.nextElementSibling;
                if (btn) {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });

        // Create new modal instance
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

    // ✅ Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            cleanupModals();
            initAutoFilter();
            console.log('User page initialized');
        });
    } else {
        cleanupModals();
        initAutoFilter();
        console.log('User page initialized (immediate)');
    }

    // ✅ Realtime filter: submit otomatis tanpa tombol Terapkan Filter
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
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.btn-action {
    padding: 5px 10px;
    font-size: 0.85rem;
    margin: 2px;
}

.sub-tabs .nav-link {
    border: 2px solid #e9ecef;
    color: #6c757d;
    font-weight: 500;
    padding: 8px 16px;
    margin-right: 5px;
}

.sub-tabs .nav-link.active {
    background: #4154f1;
    border-color: #4154f1;
    color: white;
}

.input-group .btn-outline-secondary {
    border-color: #ced4da;
}

.input-group .btn-outline-secondary:hover {
    background-color: #e9ecef;
    border-color: #ced4da;
}
</style>
@endsection
