@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Pengaturan</li>
            <li class="breadcrumb-item active">Pemetaan Jabatan SSO</li>
        </ol>
    </nav>
</div>

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
    <strong>Terjadi kesalahan:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="alert alert-info border-0 small">
    <i class="bi bi-info-circle me-2"></i>
    Jabatan dari SSO menentukan <strong>role</strong>, dan role menentukan permission apa saja yang bisa diakses.
    Pemetaan berlaku per <strong>kombinasi jabatan &times; unit</strong>, karena dua orang berjabatan sama di unit
    berbeda bisa membutuhkan hak akses berbeda. Jabatan hanya menentukan role <em>awal</em> —
    penyesuaian per orang tetap bisa dilakukan di Manajemen User.
</div>

{{-- ═══════════════ ANTRIAN REVIEW ═══════════════ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-warning text-dark rounded-top d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-hourglass-split me-2"></i>Perlu Dipetakan
        </h5>
        <span class="badge bg-dark">{{ $pending->count() }} kombinasi</span>
    </div>
    <div class="card-body p-0">
        @if($pending->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-check2-circle" style="font-size:2rem; opacity:0.5"></i>
                <div class="mt-2">Tidak ada kombinasi jabatan &amp; unit yang menunggu pemetaan.</div>
            </div>
        @else
            <p class="small text-muted px-3 pt-3 mb-0">
                Kombinasi di bawah ini muncul dari user SSO yang <strong>belum memiliki role</strong>, sehingga
                saat ini mereka bisa login tetapi <strong>tidak bisa mengakses apa pun</strong>.
                Cukup isi satu baris per kombinasi — seluruh user pada kombinasi itu langsung berlaku.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jabatan (dari SSO)</th>
                            <th>Unit</th>
                            <th class="text-center">User terdampak</th>
                            <th width="380">Petakan ke Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['jabatan'] ?: '(jabatan kosong)' }}</strong>
                            </td>
                            <td>
                                @if($row['has_unit'])
                                    <span class="badge bg-primary">{{ $row['unit']->code }}</span>
                                @else
                                    <span class="badge bg-danger">unit belum ditentukan</span>
                                    <div class="small text-muted mt-1">
                                        Unit tidak bisa ditentukan dari data SSO. Tetapkan unit user-nya
                                        di Manajemen User terlebih dahulu.
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $row['user_count'] }} user</span>
                            </td>
                            <td>
                                @if($row['has_unit'])
                                <form action="{{ route('superadmin.sso.mappings.store') }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="jabatan" value="{{ $row['jabatan'] }}">
                                    <input type="hidden" name="unit_id" value="{{ $row['unit']->id }}">
                                    <select name="role_id" class="form-select form-select-sm" required>
                                        <option value="">— pilih role —</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">
                                                {{ $role->name }} ({{ $role->permissions_count }} permission)
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                        <i class="bi bi-check-lg"></i> Simpan
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════ PEMETAAN AKTIF ═══════════════ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-diagram-2 me-2"></i>Pemetaan Aktif</h5>
    </div>
    <div class="card-body p-0">
        @if($mappings->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox" style="font-size:2rem; opacity:0.5"></i>
                <div class="mt-2">Belum ada pemetaan. Tambahkan lewat formulir di bawah.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jabatan</th>
                        <th>Unit</th>
                        <th>Role</th>
                        <th>Permission</th>
                        <th class="text-center">Status</th>
                        <th width="200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mappings as $m)
                    <tr class="{{ $m->is_active ? '' : 'table-secondary' }}">
                        <td><strong>{{ $m->jabatan }}</strong></td>
                        <td><span class="badge bg-primary">{{ $m->unit->code ?? '—' }}</span></td>
                        <td><span class="badge bg-info text-dark">{{ $m->role->name ?? '—' }}</span></td>
                        <td>
                            <small class="text-muted">
                                {{ $m->role ? $m->role->permissions()->count() : 0 }} permission
                            </small>
                        </td>
                        <td class="text-center">
                            @if($m->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editModal{{ $m->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('superadmin.sso.mappings.delete', $m) }}" method="POST"
                                  class="d-inline" onsubmit="return confirm('Hapus pemetaan {{ $m->jabatan }}?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════ FORM TAMBAH ═══════════════ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light rounded-top">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-lg me-2"></i>Tambah Pemetaan</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('superadmin.sso.mappings.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label fw-semibold">Jabatan (seperti dikirim SSO) <span class="text-danger">*</span></label>
                <input type="text" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
                       value="{{ old('jabatan') }}" placeholder="Contoh: Asisten Manager" required>
                <small class="text-muted">Besar-kecil huruf dan spasi ganda diabaikan saat pencocokan.</small>
                @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                    <option value="">— pilih unit —</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->code }} — {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
                @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                    <option value="">— pilih role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }} ({{ $role->permissions_count }} permission)
                        </option>
                    @endforeach
                </select>
                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save me-1"></i>Tambah
                </button>
            </div>
            <div class="col-12">
                <label class="form-label">Catatan <small class="text-muted">(opsional)</small></label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}" maxlength="255">
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════ KONFLIK ═══════════════ --}}
@if($conflicts->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-danger text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-exclamation-octagon me-2"></i>Konflik Status Akun</h5>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-3 pt-3">
            Akun ini <strong>dinonaktifkan di SIAGA</strong> tetapi SSO masih menganggapnya aktif.
            Sistem tidak mengubah status apa pun secara otomatis — Pusat yang memutuskan.
        </p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Waktu</th><th>Akun</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    @foreach($conflicts as $log)
                    <tr>
                        <td><small>{{ $log->created_at?->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <strong>{{ $log->user->nama_lengkap ?? '(user terhapus)' }}</strong>
                            <br><small class="text-muted">{{ $log->user->username ?? $log->subject }}</small>
                        </td>
                        <td><small>{{ $log->note }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════ UPGRADE MENUNGGU REVIEW ═══════════════ --}}
@if($pendingUpgrades->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-secondary text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-exclamation me-2"></i>Perubahan Hak Akses Menunggu Persetujuan</h5>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-3 pt-3">
            Jabatan user ini di SSO berubah dan akan <strong>menambah</strong> hak aksesnya. Perubahan ditahan
            supaya promosi di SSO tidak diam-diam menaikkan akses. Hak akses lama masih berlaku.
            Setujui dengan mengubah role user di <strong>Manajemen User</strong>.
        </p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Waktu</th><th>Akun</th><th>Unit</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    @foreach($pendingUpgrades as $log)
                    <tr>
                        <td><small>{{ $log->created_at?->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <strong>{{ $log->user->nama_lengkap ?? '(user terhapus)' }}</strong>
                            <br><small class="text-muted">{{ $log->user->username ?? $log->subject }}</small>
                        </td>
                        <td><span class="badge bg-primary">{{ $log->user->unit->code ?? '—' }}</span></td>
                        <td><small>{{ $log->note }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════ MODAL EDIT ═══════════════ --}}
@foreach($mappings as $m)
<div class="modal fade" id="editModal{{ $m->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('superadmin.sso.mappings.update', $m) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Edit Pemetaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" value="{{ $m->jabatan }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit</label>
                    <select name="unit_id" class="form-select" required>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ $m->unit_id == $unit->id ? 'selected' : '' }}>
                                {{ $unit->code }} — {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role_id" class="form-select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $m->role_id == $role->id ? 'selected' : '' }}>
                                {{ $role->name }} ({{ $role->permissions_count }} permission)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control" value="{{ $m->note }}" maxlength="255">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="active{{ $m->id }}" {{ $m->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active{{ $m->id }}">Pemetaan aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
