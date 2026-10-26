@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>Site / Lokasi</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Site / Lokasi</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">

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

    {{-- Header actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('superadmin.units.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-diagram-3 me-1"></i> Kelola Unit
        </a>
        <a href="{{ route('superadmin.sites.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Site
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white rounded-top">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-geo-alt me-2"></i>Daftar Site</h5>
        </div>
        <div class="card-body">

            {{-- Filter realtime (tanpa tombol) --}}
            <form method="GET" action="{{ route('superadmin.sites.index') }}" class="mb-3" data-autofilter>
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search"
                               value="{{ $search }}"
                               placeholder="🔍 Cari kode atau nama site...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="unit_id">
                            <option value="">-- Semua Unit --</option>
                            @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="110">Kode</th>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Unit</th>
                            <th class="text-center">Kendaraan</th>
                            <th class="text-center">User</th>
                            <th class="text-center">Status</th>
                            <th width="140">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sites as $site)
                        <tr>
                            <td><strong>{{ $site->code }}</strong></td>
                            <td>{{ $site->name }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $site->typeLabel() }}</span></td>
                            <td>
                                @if($site->unit)
                                    <span class="badge bg-primary">{{ $site->unit->name }}</span>
                                @else
                                    <span class="badge bg-light text-muted border">Tanpa Unit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $site->vehicle_details_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $site->users_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($site->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('superadmin.sites.edit', $site) }}" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('superadmin.sites.delete', $site) }}" method="POST"
                                          onsubmit="return confirm('Yakin hapus site {{ $site->code }}?')">
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
                                    <i class="bi bi-geo-alt" style="font-size:2rem; opacity:0.5"></i>
                                    <div class="mt-2">Belum ada site. Klik "Tambah Site" untuk memulai.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $sites->appends(['search' => $search, 'unit_id' => $unitId])->links() }}
            </div>
        </div>
    </div>

    <div class="alert alert-warning border-0 small">
        <i class="bi bi-info-circle me-2"></i>
        Setiap <strong>Site</strong> wajib terhubung ke satu <strong>Unit</strong> (mis. Basecamp, Sumur, PTH-BC masuk ke Unit Patuha).
        Kelola unit lewat menu <strong>Kelola Unit</strong>.
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
@endsection