@extends('templates.main')

@section('title', 'Profil Pengguna')

@section('content')

<div class="pagetitle">
    <h1>Profil Pengguna</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item active">Profil Pengguna</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section profile">
    <div class="row">

        <!-- Left Side - Profile Card -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mb-3"
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-person-circle text-white" style="font-size: 80px;"></i>
                    </div>
                    <h2>{{ Auth::user()->nama_lengkap }}</h2>
                    <h3 class="text-muted">{{ ucfirst(strtolower(Auth::user()->role)) }}</h3>
                    <div class="mt-3">
                        <span class="badge bg-{{ Auth::user()->role === 'superadmin' ? 'danger' : (Auth::user()->role === 'Admin' ? 'primary' : 'success') }} px-3 py-2">
                            <i class="bi bi-shield-check"></i> {{ ucfirst(Auth::user()->role) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Tabs -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body pt-3">

                    <!-- Bordered Tabs -->
                    <ul class="nav nav-tabs nav-tabs-bordered">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">
                                <i class="bi bi-person"></i> Data Profil
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">
                                <i class="bi bi-key"></i> Ubah Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">

                        <!-- Tab Overview -->
                        <div class="tab-pane fade show active profile-overview" id="profile-overview">
                            <h5 class="card-title">Informasi Profil</h5>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">Nama Lengkap</div>
                                <div class="col-lg-9 col-md-8">{{ Auth::user()->nama_lengkap }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">Username</div>
                                <div class="col-lg-9 col-md-8">{{ Auth::user()->username }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">Email</div>
                                <div class="col-lg-9 col-md-8">{{ Auth::user()->email }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">Jabatan</div>
                                <div class="col-lg-9 col-md-8">{{ ucfirst(strtolower(Auth::user()->jabatan)) }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">NIP</div>
                                <div class="col-lg-9 col-md-8">{{ Auth::user()->nip ?? '-' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-4 label fw-semibold">Role</div>
                                <div class="col-lg-9 col-md-8">
                                    <span class="badge bg-{{ Auth::user()->role === 'superadmin' ? 'danger' : (Auth::user()->role === 'Admin' ? 'primary' : 'success') }}">
                                        {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Change Password -->
                        <div class="tab-pane fade pt-3" id="profile-change-password">

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <strong>Berhasil!</strong> {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Gagal!</strong> {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="mb-4">
                                <h5 class="card-title">Ubah Password</h5>
                                <p class="text-muted">Masukkan password baru untuk akun Anda</p>
                            </div>

                            <!-- Change Password Form -->
                            <form action="{{ route('profile.change-password') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row mb-3">
                                    <label for="new_password" class="col-md-4 col-lg-3 col-form-label">
                                        Password Baru <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-8 col-lg-9">
                                        <div class="input-group">
                                            <input name="new_password"
                                                   type="password"
                                                   class="form-control @error('new_password') is-invalid @enderror"
                                                   id="new_password"
                                                   placeholder="Masukkan password baru"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye" id="toggleIcon"></i>
                                            </button>
                                            @error('new_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> Minimal 8 karakter
                                        </small>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <label for="new_password_confirmation" class="col-md-4 col-lg-3 col-form-label">
                                        Konfirmasi Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-8 col-lg-9">
                                        <div class="input-group">
                                            <input name="new_password_confirmation"
                                                   type="password"
                                                   class="form-control"
                                                   id="new_password_confirmation"
                                                   placeholder="Ketik ulang password baru"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-8 col-lg-9 offset-md-4 offset-lg-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Ubah Password
                                        </button>
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <!-- End Change Password Form -->

                        </div>

                    </div><!-- End Bordered Tabs -->
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Script untuk Toggle Show/Hide Password -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Password Baru
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('new_password');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;

        if (type === 'password') {
            toggleIcon.className = 'bi bi-eye';
        } else {
            toggleIcon.className = 'bi bi-eye-slash';
        }
    });

    // Toggle Konfirmasi Password
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('new_password_confirmation');
    const toggleConfirmIcon = document.getElementById('toggleConfirmIcon');

    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
        confirmPasswordInput.type = type;

        if (type === 'password') {
            toggleConfirmIcon.className = 'bi bi-eye';
        } else {
            toggleConfirmIcon.className = 'bi bi-eye-slash';
        }
    });

    // Real-time password match validation
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value && this.value.length > 0) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});
</script>

<style>
/* Custom styling untuk profile card */
.profile-card h2 {
    font-size: 24px;
    font-weight: 700;
    margin: 10px 0;
    color: #012970;
}

.profile-card h3 {
    font-size: 16px;
    font-weight: 500;
    margin: 0;
}

.label {
    font-weight: 600;
    color: #495057;
}

/* Input group styling */
.input-group .btn-outline-secondary {
    border-left: 0;
}

.input-group .form-control:focus + .btn-outline-secondary {
    border-color: #86b7fe;
}

/* Tab styling */
.nav-tabs-bordered .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #899bbd;
    font-weight: 500;
}

.nav-tabs-bordered .nav-link:hover {
    color: #4154f1;
    border-color: #4154f1;
}

.nav-tabs-bordered .nav-link.active {
    color: #4154f1;
    border-color: #4154f1;
}
</style>

@endsection
