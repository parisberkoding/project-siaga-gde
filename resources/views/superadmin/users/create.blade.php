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
                <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
                    @csrf

                    <!-- Username & Password -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Username <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-at text-muted"></i>
                                </span>
                                <input type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       name="username"
                                       value="{{ old('username') }}"
                                       placeholder="Contoh: john.doe"
                                       required>
                            </div>
                            <small class="text-muted">Username untuk login, unik dalam sistem</small>
                            @error('username')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock text-muted"></i>
                                </span>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       name="password"
                                       id="passwordInput"
                                       placeholder="Minimal 6 karakter"
                                       required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('passwordInput', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                            @error('password')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control @error('nama_lengkap') is-invalid @enderror"
                                   name="nama_lengkap"
                                   value="{{ old('nama_lengkap') }}"
                                   placeholder="Nama lengkap sesuai ID"
                                   required>
                        </div>
                        @error('nama_lengkap')
                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email & Jabatan -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope text-muted"></i>
                                </span>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       name="email"
                                       value="{{ old('email') }}"
                                       placeholder="email@perusahaan.com"
                                       required>
                            </div>
                            @error('email')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Jabatan <small class="text-muted fw-normal">(opsional)</small>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-briefcase text-muted"></i>
                                </span>
                                <input type="text"
                                       class="form-control @error('jabatan') is-invalid @enderror"
                                       name="jabatan"
                                       value="{{ old('jabatan') }}"
                                       placeholder="Contoh: Staff IT, Driver">
                            </div>
                            @error('jabatan')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Role (RBAC Dynamic) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Role / Hak Akses <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            @foreach($roles as $role)
                            <div class="col-6">
                                <label class="role-card {{ old('role_code') == $role->code ? 'selected' : '' }}" for="role_{{ $role->code }}">
                                    <input type="radio" name="role_code" value="{{ $role->code }}" id="role_{{ $role->code }}"
                                           {{ old('role_code') == $role->code ? 'checked' : '' }}>
                                    <div class="p-3">
                                        <div class="d-flex align-items-center mb-1">
                                            @php
                                                $roleColors = [
                                                    'superadmin' => 'bg-danger',
                                                    'general_affairs' => 'bg-success',
                                                    'dispatcher' => 'bg-secondary',
                                                    'requester' => 'bg-info',
                                                    'manager' => 'bg-warning text-dark',
                                                    'view_only' => 'bg-dark',
                                                ];
                                                $roleIcons = [
                                                    'superadmin' => 'bi-shield-lock',
                                                    'general_affairs' => 'bi-person-check',
                                                    'dispatcher' => 'bi-person-lines-fill',
                                                    'requester' => 'bi-person',
                                                    'manager' => 'bi-person-badge',
                                                    'view_only' => 'bi-eye',
                                                ];
                                                $color = $roleColors[$role->code] ?? 'bg-secondary';
                                                $icon = $roleIcons[$role->code] ?? 'bi-person';
                                                $permCount = $role->permissions->count();
                                            @endphp
                                            <i class="bi {{ $icon }} me-2"></i>
                                            <span class="badge {{ $color }}">{{ $role->name }}</span>
                                        </div>
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-shield me-1"></i>{{ $permCount }} permission
                                        </small>
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
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-shield-check me-2 text-primary"></i>Role & Hak Akses
                </h6>
                <div class="small">
                    @foreach($roles as $role)
                    <div class="d-flex align-items-start mb-2">
                        @php
                            $roleColors = [
                                'superadmin' => 'bg-danger',
                                'general_affairs' => 'bg-success',
                                'dispatcher' => 'bg-secondary',
                                'requester' => 'bg-info',
                                'manager' => 'bg-warning text-dark',
                                'view_only' => 'bg-dark',
                            ];
                            $color = $roleColors[$role->code] ?? 'bg-secondary';
                            $label = match($role->code) {
                                'superadmin' => 'SA',
                                'general_affairs' => 'GA',
                                'dispatcher' => 'D',
                                'requester' => 'R',
                                'manager' => 'M',
                                'view_only' => 'VO',
                                default => substr($role->code, 0, 2)
                            };
                        @endphp
                        <span class="badge {{ $color }} me-2 mt-1">{{ $label }}</span>
                        <div>
                            <strong>{{ $role->name }}</strong>
                            <div class="text-muted">{{ $role->description }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 small">
            <i class="bi bi-info-circle me-2"></i>
            Role & permission bersifat dinamis. Superadmin dapat mengelola hak akses melalui menu RBAC.
        </div>

        <div class="alert alert-warning border-0 small">
            <i class="bi bi-key me-2"></i>
            Password dapat direset kapan saja melalui tombol <strong>Reset Password</strong> di daftar user.
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
.role-card:hover {
    border-color: #4154f1;
    background: #f8f9ff;
}
.role-card.selected,
.role-card input:checked ~ div {
    border-color: #4154f1;
    background: #f0f2ff;
}
.role-card input[type="radio"] {
    display: none;
}
</style>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

document.querySelectorAll('.role-card').forEach(function(card) {
    const radio = card.querySelector('input[type="radio"]');
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        if (this.checked) card.classList.add('selected');
    });
    if (radio.checked) card.classList.add('selected');
});
</script>

@endsection