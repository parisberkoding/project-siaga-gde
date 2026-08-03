@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Employee Default</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Employee Default</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        @foreach($sites as $site)
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-primary mb-0">{{ $totalsBySite[$site->id] ?? 0 }}</h4>
                    <small class="text-muted">Default {{ $site->code }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body pt-3">

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <form method="GET" action="{{ route('lunch.employee-defaults.index') }}" class="row g-2">
                        <div class="col">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="form-control" placeholder="Cari nama / kode / alias…">
                        </div>
                        <div class="col-auto">
                            <select name="site_id" class="form-select">
                                <option value="">Semua Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>{{ $site->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('lunch.employee-defaults.export') }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Karyawan
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Divisi</th>
                            <th>Jabatan</th>
                            <th>Default Site</th>
                            <th>Alias</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr class="{{ $employee->is_active ? '' : 'table-light text-muted' }}">
                            <td>{{ $employee->employee_code ?? '-' }}</td>
                            <td class="fw-semibold">{{ $employee->employee_name }}</td>
                            <td>{{ $employee->division ?? '-' }}</td>
                            <td>{{ $employee->position ?? '-' }}</td>
                            <td>
                                @if($employee->defaultSite)
                                    <span class="badge bg-primary">{{ $employee->defaultSite->code }}</span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                @foreach($employee->aliases->take(3) as $alias)
                                    <span class="badge bg-light text-dark border">{{ $alias->alias }}</span>
                                @endforeach
                                @if($employee->aliases->count() > 3)
                                    <span class="badge bg-light text-muted border">+{{ $employee->aliases->count() - 3 }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $employee->is_active ? 'success' : 'secondary' }}">
                                    {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-id="{{ $employee->id }}"
                                        data-code="{{ $employee->employee_code }}"
                                        data-name="{{ $employee->employee_name }}"
                                        data-division="{{ $employee->division }}"
                                        data-position="{{ $employee->position }}"
                                        data-site="{{ $employee->default_site_id }}"
                                        data-aliases="{{ $employee->aliases->where('source', 'manual')->pluck('alias')->implode(', ') }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($employee->is_active)
                                <form method="POST" action="{{ route('lunch.employee-defaults.destroy', $employee) }}" class="d-inline"
                                      onsubmit="return confirm('Nonaktifkan {{ $employee->employee_name }} dari Lunch Management?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('lunch.employee-defaults.restore', $employee) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success"><i class="bi bi-person-check"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data karyawan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $employees->links('pagination::bootstrap-5') }}
        </div>
    </div>

</section>

{{-- MODAL: Tambah --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.employee-defaults.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('lunch.employee-defaults.form-fields', ['prefix' => 'add'])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Edit --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('lunch.employee-defaults.form-fields', ['prefix' => 'edit'])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Import --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.employee-defaults.import') }}"
              enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import Karyawan (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Format kolom sama dengan hasil <strong>Export CSV</strong>:
                    <code>employee_code, employee_name, division, position, default_site, aliases, is_active</code>.
                    Baris dengan <code>employee_code</code> yang sudah ada akan diperbarui.
                </p>
                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const form = document.getElementById('editForm');
        form.action = '{{ url('lunch/employee-defaults') }}/' + btn.dataset.id;
        form.querySelector('[name=employee_code]').value   = btn.dataset.code || '';
        form.querySelector('[name=employee_name]').value   = btn.dataset.name || '';
        form.querySelector('[name=division]').value        = btn.dataset.division || '';
        form.querySelector('[name=position]').value        = btn.dataset.position || '';
        form.querySelector('[name=default_site_id]').value = btn.dataset.site || '';
        form.querySelector('[name=aliases]').value         = btn.dataset.aliases || '';
    });
});
</script>
@endpush
