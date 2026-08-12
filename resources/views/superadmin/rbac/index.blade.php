@extends('templates.main')

@section('title', $title)

@section('content')

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

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="pagetitle">
    <h1>Role & Hak Akses (RBAC)</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">RBAC & Hak Akses</li>
            <li class="breadcrumb-item active">Role</li>
        </ol>
    </nav>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('superadmin.rbac.create') }}" class="btn btn-dark">
        <i class="bi bi-plus-lg me-1"></i> Tambah Role Baru
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Daftar Role</h5>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Role</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Permission</th>
                        <th class="text-center">User</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">System</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td>
                            <strong>{{ $role->name }}</strong>
                            <br><small class="text-muted">code: <code>{{ $role->code }}</code></small>
                        </td>
                        <td><small>{{ $role->description ?? '—' }}</small></td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $role->permissions->count() }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $role->users_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($role->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($role->is_system)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-gear me-1"></i>Sistem
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('superadmin.rbac.edit', $role->id) }}"
                                   class="btn btn-warning btn-sm {{ $role->is_system ? 'disabled' : '' }}"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('superadmin.rbac.delete', $role->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus role {{ $role->name }}?')"
                                      style="display:inline;">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-danger btn-sm {{ $role->is_system ? 'disabled' : '' }}"
                                            title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-shield"></i>
                                <p>Belum ada role. Tambah role baru untuk memulai.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

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
</style>

@endsection