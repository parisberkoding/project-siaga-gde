@extends('templates.main')

@section('title', $title)

@section('content')
<div class="pagetitle">
    <h1>{{ $title }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Pengaturan</li>
            <li class="breadcrumb-item active">{{ $title }}</li>
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

<div class="alert alert-warning border-0 small">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Checklist ini menentukan hak akses <strong>per unit</strong> terhadap tiap fitur.
    Saat ini statusnya <strong>hanya menyimpan konfigurasi</strong> — penegakan akses secara runtime
    belum diaktifkan (<code>FEATURES_ENFORCE_UNIT_ACCESS=false</code>), sehingga belum ada user yang
    kehilangan akses.
</div>

@foreach($units as $unit)
    @php
        $unitAccess = $access[$unit->id] ?? collect();
        $writeCount = 0;
    @endphp

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center {{ $unit->is_central ? 'bg-dark text-white' : 'bg-primary text-white' }} rounded-top">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-diagram-3 me-2"></i>{{ $unit->name }}
                @if($unit->is_central)
                    <span class="badge bg-warning text-dark ms-1">Pusat</span>
                @endif
            </h5>
            <small class="opacity-75">code: {{ $unit->code }} · {{ $unit->sites_count }} site</small>
        </div>

        <form action="{{ route('superadmin.features.on_unit.update', $unit) }}" method="POST">
            @csrf
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:260px">Fitur</th>
                                <th class="text-center">View</th>
                                <th class="text-center">Create</th>
                                <th class="text-center">Update</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($features as $feature)
                                @php
                                    $row = $unitAccess->get($feature->id);
                                    $isView   = $row ? (bool) $row->can_view   : true;   // default: boleh lihat
                                    $isCreate = $row ? (bool) $row->can_create : false;
                                    $isUpdate = $row ? (bool) $row->can_update : false;
                                    $isDelete = $row ? (bool) $row->can_delete : false;

                                    if ($row && ($row->can_create || $row->can_update || $row->can_delete)) {
                                        $writeCount++;
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $feature->name }}</strong>
                                        <br><small class="text-muted">code: <code>{{ $feature->code }}</code></small>
                                        @if($feature->module)
                                            <span class="badge bg-secondary ms-1">{{ $feature->module->code }}</span>
                                        @endif
                                        @if($feature->stage === 'development')
                                            <span class="badge bg-warning text-dark ms-1" title="Masih development">dev</span>
                                        @endif
                                        <input type="hidden" name="access[{{ $feature->id }}][feature_id]" value="{{ $feature->id }}">
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="access[{{ $feature->id }}][can_view]" value="1" {{ $isView ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="access[{{ $feature->id }}][can_create]" value="1" {{ $isCreate ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="access[{{ $feature->id }}][can_update]" value="1" {{ $isUpdate ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="access[{{ $feature->id }}][can_delete]" value="1" {{ $isDelete ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    {{ $features->count() }} fitur · {{ $writeCount }} dengan hak tulis
                </small>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-save me-1"></i>Simpan Akses {{ $unit->code }}
                </button>
            </div>
        </form>
    </div>
@endforeach
@endsection
