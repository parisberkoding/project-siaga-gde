@extends('templates.main')

@section('title', 'Tambah User')

@section('content')

<div class="pagetitle">
    <h1>Tambah User Baru</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users', ['tab' => 'list']) }}">Pengguna</a></li>
            <li class="breadcrumb-item active">Tambah User</li>
        </ol>
    </nav>
</div>

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

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-person-plus me-2"></i>Form Tambah User
                </h5>
            </div>
            <div class="card-body p-4">

                {{-- Mode Toggle --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">Mode Pembuatan User</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="mode" id="modeManual" value="manual" {{ old('mode', 'manual') == 'manual' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="modeManual">
                            <i class="bi bi-pencil-square me-1"></i> Input Sendiri
                        </label>
                        <input type="radio" class="btn-check" name="mode" id="modeActivate" value="activate" {{ old('mode') == 'activate' ? 'checked' : '' }}>
                        <label class="btn btn-outline-success" for="modeActivate">
                            <i class="bi bi-person-check me-1"></i> Aktivasi Pekerja (dari Master Pekerja)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <span id="modeHint"><i class="bi bi-info-circle"></i> Input manual biodata pekerja baru sekaligus akun login.</span>
                    </small>
                </div>

                <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
                    @csrf

                    {{-- Mode Aktivasi: pilih pekerja dari Master Pekerja --}}
                    <div id="activateSection" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">
                            Pilih Pekerja dari Master Pekerja <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('employee_default_id') is-invalid @enderror"
                                name="employee_default_id" id="employeeSelect">
                            <option value="">-- Cari & pilih pekerja --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}"
                                        data-name="{{ $emp->employee_name }}"
                                        data-position="{{ $emp->position ?? '' }}"
                                        data-site="{{ $emp->default_site_id ?? '' }}"
                                        data-site-name="{{ $emp->defaultSite?->name ?? '-' }}"
                                        {{ old('employee_default_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employee_name }} · {{ $emp->position ?? 'Tanpa jabatan' }} ({{ $emp->defaultSite?->name ?? 'Tanpa site' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hanya pekerja aktif yang belum punya akun yang ditampilkan.</small>
                        @error('employee_default_id')
                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror

                        <div class="alert alert-light border mt-3 mb-0 small">
                            <div class="row g-2">
                                <div class="col-md-6"><i class="bi bi-person text-muted"></i> Nama: <strong id="actName">-</strong></div>
                                <div class="col-md-6"><i class="bi bi-briefcase text-muted"></i> Jabatan: <strong id="actPosition">-</strong></div>
                                <div class="col-md-6"><i class="bi bi-geo-alt text-muted"></i> Site: <strong id="actSite">-</strong></div>
                            </div>
                        </div>
                    </div>

                    {{-- Mode Manual: input biodata --}}
                    <div id="manualSection">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror"
                                       name="nama_lengkap" id="namaLengkapInput"
                                       value="{{ old('nama_lengkap') }}" placeholder="Nama lengkap sesuai ID" required>
                            </div>
                            @error('nama_lengkap')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jabatan <small class="text-muted fw-normal">(opsional)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-briefcase text-muted"></i></span>
                                    <input type="text" class="form-control @error('jabatan') is-invalid @enderror"
                                           name="jabatan" id="jabatanInput" value="{{ old('jabatan') }}"
                                           placeholder="Contoh: Staff IT, Driver">
                                </div>
                                @error('jabatan')
                                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Site / Unit <small class="text-muted fw-normal">(opsional)</small></label>
                                <select class="form-select @error('default_site_id') is-invalid @enderror" name="default_site_id" id="siteInput">
                                    <option value="">-- Otomatis (by role) --</option>
                                    @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('default_site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->code }} — {{ $site->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('default_site_id')
                                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Unit Bisnis <small class="text-muted fw-normal">(opsional)</small>
                            </label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" name="unit_id" id="unitInput">
                                <option value="">-- Global / Pusat (tanpa scope) --</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->code }} — {{ $unit->name }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Data user ini dibatasi hanya ke unit yang dipilih. Kosongkan untuk akses global (pusat).
                            </small>
                            @error('unit_id')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="create_employee" value="1"
                                   id="createEmployee" {{ old('create_employee') ? 'checked' : '' }}>
                            <label class="form-check-label" for="createEmployee">
                                Daftarkan juga ke <strong>Master Pekerja</strong> (agar muncul di daftar pekerja SPD/Lunch)
                            </label>
                        </div>
                    </div>

                    <hr>

                    <!-- Akun Login (umum untuk kedua mode) -->
                    <h6 class="text-muted mb-3"><i class="bi bi-key me-1"></i>Akun Login</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-at text-muted"></i></span>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       name="username" value="{{ old('username') }}" placeholder="Contoh: john.doe" required>
                            </div>
                            @error('username')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" id="passwordInput" placeholder="Minimal 6 karakter" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passwordInput', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" placeholder="email@perusahaan.com" required>
                        </div>
                        @error('email')
                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Role (RBAC) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Role / Hak Akses <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            @foreach($roles as $role)
                            @php
                                $roleColors = [
                                    'superadmin' => 'bg-danger', 'general_affairs' => 'bg-success',
                                    'dispatcher' => 'bg-secondary', 'requester' => 'bg-info',
                                    'manager' => 'bg-warning text-dark', 'view_only' => 'bg-dark',
                                ];
                                $roleIcons = [
                                    'superadmin' => 'bi-shield-lock', 'general_affairs' => 'bi-person-check',
                                    'dispatcher' => 'bi-person-lines-fill', 'requester' => 'bi-person',
                                    'manager' => 'bi-person-badge', 'view_only' => 'bi-eye',
                                ];
                                $color = $roleColors[$role->code] ?? 'bg-secondary';
                                $icon = $roleIcons[$role->code] ?? 'bi-person';
                            @endphp
                            <div class="col-6">
                                <label class="role-card {{ old('role_code') == $role->code ? 'selected' : '' }}" for="role_{{ $role->code }}">
                                    <input type="radio" name="role_code" value="{{ $role->code }}" id="role_{{ $role->code }}"
                                           {{ old('role_code') == $role->code ? 'checked' : '' }}>
                                    <div class="p-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi {{ $icon }} me-2"></i>
                                            <span class="badge {{ $color }}">{{ $role->name }}</span>
                                        </div>
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('role_code')
                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Simpan User
                        </button>
                        <a href="{{ route('users', ['tab' => 'list']) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>2 Mode Pembuatan User</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1"><strong>Input Sendiri:</strong> isi biodata pekerja baru + akun login.</li>
                    <li class="mb-1"><strong>Aktivasi Pekerja:</strong> pilih dari Master Pekerja (data sudah ada), beri akun & role. Nama/jabatan/site menyesuaikan otomatis.</li>
                </ul>
            </div>
        </div>
        <div class="alert alert-warning border-0 small">
            <i class="bi bi-key me-2"></i>Password dapat direset kapan saja lewat tombol <strong>Reset Password</strong> di daftar user.
        </div>
    </div>
</div>

<style>
.role-card {
    display: block;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
    margin: 0;
}
.role-card:hover { border-color: #4154f1; background: #f8f9ff; }
.role-card.selected, .role-card input:checked ~ div { border-color: #4154f1; background: #f0f2ff; }
.role-card input[type="radio"] { display: none; }
</style>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('bi-eye', 'bi-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('bi-eye-slash', 'bi-eye'); }
}

(function () {
    const modeManual = document.getElementById('modeManual');
    const modeActivate = document.getElementById('modeActivate');
    const manualSection = document.getElementById('manualSection');
    const activateSection = document.getElementById('activateSection');
    const modeHint = document.getElementById('modeHint');
    const employeeSelect = document.getElementById('employeeSelect');

    function applyMode() {
        const isActivate = modeActivate.checked;
        manualSection.style.display = isActivate ? 'none' : 'block';
        activateSection.style.display = isActivate ? 'block' : 'none';

        // Toggle required pada field dinamis
        document.getElementById('namaLengkapInput').required = !isActivate;
        employeeSelect.required = isActivate;

        modeHint.innerHTML = isActivate
            ? '<i class="bi bi-info-circle"></i> Pilih pekerja dari Master Pekerja, beri akun login & role.'
            : '<i class="bi bi-info-circle"></i> Input manual biodata pekerja baru sekaligus akun login.';
    }

    modeManual.addEventListener('change', applyMode);
    modeActivate.addEventListener('change', applyMode);
    applyMode();

    // Saat pekerja dipilih, tampilkan info (nama/jabatan/site)
    employeeSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        document.getElementById('actName').textContent = opt.dataset.name || '-';
        document.getElementById('actPosition').textContent = opt.dataset.position || '-';
        document.getElementById('actSite').textContent = opt.dataset.siteName || '-';
    });

    document.querySelectorAll('.role-card').forEach(function (card) {
        const radio = card.querySelector('input[type="radio"]');
        radio.addEventListener('change', function () {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
            if (this.checked) card.classList.add('selected');
        });
        if (radio.checked) card.classList.add('selected');
    });
})();
</script>

@endsection
