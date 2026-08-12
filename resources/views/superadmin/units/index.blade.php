@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>Unit Bisnis</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Unit Bisnis</li>
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

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('superadmin.units.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Unit
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white rounded-top">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-diagram-3 me-2"></i>Daftar Unit Bisnis</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Unit</th>
                        <th>Deskripsi</th>
                        <th>Site terkait</th>
                        <th class="text-center">Jumlah Site</th>
                        <th class="text-center">User</th>
                        <th class="text-center">Status</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr>
                        <td>
                            <strong>{{ $unit->name }}</strong>
                            <br><small class="text-muted">code: <code>{{ $unit->code }}</code></small>
                        </td>
                        <td><small>{{ $unit->description ?? '—' }}</small></td>
                        <td>
                            @forelse($unit->sites as $site)
                                <span class="badge bg-light text-dark me-1">{{ $site->code }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-center"><span class="badge bg-info">{{ $unit->sites_count }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $unit->users_count }}</span></td>
                        <td class="text-center">
                            @if($unit->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('superadmin.units.edit', $unit) }}" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('superadmin.units.delete', $unit) }}" method="POST"
                                      onsubmit="return confirm('Yakin hapus unit {{ $unit->name }}?')">
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
                        <td colspan="7">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size:2rem; opacity:0.5"></i>
                                <div class="mt-2">Belum ada unit. Klik "Tambah Unit" untuk memulai.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
