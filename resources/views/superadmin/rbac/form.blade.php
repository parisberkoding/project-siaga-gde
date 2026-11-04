@extends('templates.main')

@section('title', $title)

@section('content')

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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center">
        <div class="bg-dark rounded-3 p-3 me-3 shadow-sm">
            <i class="bi bi-shield-lock text-white fs-4"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold text-dark">{{ $title }}</h4>
            <small class="text-muted">Atur nama role dan permission yang diberikan</small>
        </div>
    </div>
    <a href="{{ route('superadmin.rbac.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form action="{{ isset($role) ? route('superadmin.rbac.update', $role->id) : route('superadmin.rbac.store') }}"
              method="POST">
            @csrf

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white rounded-top">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-info-circle me-2"></i>Informasi Role
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Kode Role <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('code') is-invalid @enderror"
                                   name="code"
                                   value="{{ old('code', $role->code ?? '') }}"
                                   placeholder="Contoh: bbm_officer"
                                   {{ isset($role) ? 'readonly' : '' }}
                                   required>
                            <small class="text-muted">Hanya huruf kecil, angka, underscore. Tidak bisa diubah setelah dibuat.</small>
                            @error('code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Nama Role <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name"
                                   value="{{ old('name', $role->name ?? '') }}"
                                   placeholder="Contoh: bbm_officer"
                                   required>
                            <small class="text-muted">Huruf kecil semua; spasi otomatis menjadi underscore.</small>
                            @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Cakupan Unit <small class="text-muted fw-normal">(opsional)</small>
                            </label>
                            <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
                                <option value="">Global — berlaku di semua unit</option>
                                @foreach($units ?? [] as $unit)
                                    <option value="{{ $unit->id }}"
                                        {{ (string) old('unit_id', $role->unit_id ?? '') === (string) $unit->id ? 'selected' : '' }}>
                                        {{ $unit->code }} — {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                <strong>Global</strong> = role berlaku di semua unit.
                                Pilih unit tertentu untuk membuat role yang <strong>hanya bisa diberikan
                                kepada user di unit itu</strong> — inilah cara memisahkan role Pusat dari role unit.
                            </small>
                            @error('unit_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Deskripsi <small class="text-muted fw-normal">(opsional)</small>
                            </label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Jelaskan fungsi dari role ini...">{{ old('description', $role->description ?? '') }}</textarea>
                        </div>
                        @if(isset($role))
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActive">
                                    Role Aktif
                                </label>
                                <small class="d-block text-muted">Nonaktifkan untuk mencabut akses semua user dengan role ini.</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white rounded-top">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-shield me-2"></i>Permission / Hak Akses
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Centang permission yang ingin diberikan ke role ini. Semua permission bersifat <strong>gabungan (OR)</strong> — user cukup punya salah satu permission untuk mengakses.
                    </p>

                    @forelse($groups as $group)
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $group->icon ?? 'bi-shield' }} fs-5"></i>
                            <strong>{{ $group->name }}</strong>
                            <span class="badge bg-light text-dark">{{ $group->permissions->count() }} permission</span>
                        </div>

                        <div class="row g-2">
                            @foreach($group->permissions as $perm)
                            @php
                                $checked = isset($rolePermissionIds) && in_array($perm->id, $rolePermissionIds);
                                if (old('permissions') !== null) {
                                    $checked = in_array($perm->id, old('permissions', []));
                                }
                            @endphp
                            <div class="col-md-4 col-lg-3">
                                <label class="perm-item {{ $checked ? 'checked' : '' }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                           {{ $checked ? 'checked' : '' }}
                                           onchange="this.parentElement.classList.toggle('checked')">
                                    <div class="perm-content">
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-shield-check text-primary"></i>
                                            <strong style="font-size:13px;">{{ $perm->action }}</strong>
                                        </div>
                                        <small class="text-muted d-block">{{ $perm->description }}</small>
                                        <code style="font-size:10px;">{{ $perm->code }}</code>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @if(!$loop->last)
                    <hr>
                    @endif
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield fs-1 mb-2 d-block"></i>
                        Belum ada permission yang terdefinisi. Jalankan seeder terlebih dahulu.
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-dark px-4">
                    <i class="bi bi-save me-2"></i>
                    {{ isset($role) ? 'Simpan Perubahan' : 'Buat Role' }}
                </button>
                <a href="{{ route('superadmin.rbac.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Batal
                </a>
            </div>

        </form>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 bg-light shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-question-circle me-2"></i>Tips
                </h6>
                <ul class="small mb-0" style="padding-left:16px;">
                    <li class="mb-2">Buat role spesifik sesuai fungsi, misal <strong>"bbm_officer"</strong> yang hanya bisa request BBM.</li>
                    <li class="mb-2">Role <strong>"view_only"</strong> hanya perlu permission <code>*.view</code>.</li>
                    <li class="mb-2">Role sistem tidak bisa diedit atau dihapus.</li>
                    <li>User bisa punya lebih dari satu role.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.perm-item {
    display: block;
    border: 1.5px solid #E4E9F0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    background: #fff;
}
.perm-item:hover {
    border-color: #0B50A1;
    background: #f8f9ff;
}
.perm-item.checked {
    border-color: #4A7A1C;
    background: #f0fdf4;
}
.perm-item input[type="checkbox"] {
    display: none;
}
.perm-content {
    padding: 8px 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.perm-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('checked');
                }
            }
        });
    });
});
</script>

@endsection