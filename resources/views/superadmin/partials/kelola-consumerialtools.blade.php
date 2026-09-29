@extends('templates.main')

@section('title', 'Kelola Kendaraan & Alat')

@section('content')

<div class="pagetitle">
    <h1>Manajemen Kendaraan & Alat</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Kendaraan & Alat</li>
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

<div class="card">
    <div class="card-body pt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">
                <i class="bi bi-truck me-2"></i>Daftar Kendaraan & Alat
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#deletedVehiclesCollapse" aria-expanded="false">
                    <i class="bi bi-trash me-1"></i>Data Terhapus
                </button>
                @can('permission', 'vehicle.create')
                <a href="{{ route('vehicles.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Tambah
                </a>
                @endcan
            </div>
        </div>

        <form method="GET" action="{{ route('vehicles') }}" class="mb-3" data-autofilter>
            <div class="row g-2">
                <div class="col-md-9">
                    <input type="text" class="form-control" name="search"
                           value="{{ $search }}"
                           placeholder="🔍 Cari berdasarkan kode, nama, atau catatan...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">-- Semua Tipe --</option>
                        <option value="KENDARAAN" {{ $type == 'KENDARAAN' ? 'selected' : '' }}>Kendaraan</option>
                        <option value="ALAT" {{ $type == 'ALAT' ? 'selected' : '' }}>Alat</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">Tipe</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Site</th>
                        <th width="80">Penumpang</th>
                        <th width="70">Tangki</th>
                        <th width="100">Jenis BBM</th>
                        <th width="110">Benchmark BBM</th>
                        <th width="100">Barcode</th>
                        <th width="140">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td><small class="text-muted">{{ $vehicle->id }}</small></td>
                        <td>
                            @if($vehicle->consumerial_type == 'KENDARAAN')
                                <span class="badge bg-primary">Kendaraan</span>
                            @else
                                <span class="badge bg-info text-dark">Alat</span>
                            @endif
                        </td>
                        <td><strong>{{ $vehicle->consumerial_code }}</strong></td>
                        <td>{{ $vehicle->consumerial_name }}</td>
                        <td><small>{{ $vehicle->detail?->site?->name ?? '-' }}</small></td>
                        <td><small>{{ $vehicle->detail?->kapasitas_penumpang ? $vehicle->detail->kapasitas_penumpang . ' org' : '-' }}</small></td>
                        <td><small>{{ $vehicle->detail?->volume_tangki ? $vehicle->detail->volume_tangki . ' L' : '-' }}</small></td>
                        <td>
                            @if($vehicle->detail?->jenis_bahan_bakar)
                                <span class="badge bg-secondary">{{ $vehicle->detail->jenis_bahan_bakar }}</span>
                            @else
                                <span class="badge bg-light text-muted border">-</span>
                            @endif
                        </td>
                        <td>
                            @if($vehicle->detail?->nominal_full_tank)
                                <small class="text-success fw-semibold">
                                    Rp {{ number_format($vehicle->detail->nominal_full_tank, 0, ',', '.') }}
                                </small>
                            @else
                                <span class="badge bg-light text-muted border">-</span>
                            @endif
                        </td>
                        <td>
                            @php $barcode = $vehicle->barcode ?? null; @endphp
                            @if($barcode && $barcode->url)
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <img src="{{ $barcode->url }}"
                                        alt="Barcode {{ $vehicle->consumerial_code }}"
                                        style="width:70px;height:70px;object-fit:contain;border:1px solid #E4E9F0;border-radius:4px;padding:2px;background:#fff;"
                                        title="{{ $vehicle->consumerial_code }}"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                                    <div style="display:none">
                                        <span class="badge bg-secondary text-white" style="font-size:0.65rem;">Gambar error</span>
                                    </div>
                                    <a href="{{ route('dispatcher.barcode.download', $vehicle->id) }}"
                                    class="btn btn-outline-secondary btn-sm py-0 px-1"
                                    style="font-size:0.7rem;" title="Unduh Barcode">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            @else
                                <span class="badge bg-light text-muted border">Belum ada</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('superadmin.vehicle.detail', $vehicle->id) }}"
                                   class="btn btn-info" title="Detail">
                                    <i class="bi bi-info-circle"></i>
                                </a>
                                @can('permission', 'vehicle.update')
                                <a href="{{ route('vehicles.edit', $vehicle->id) }}"
                                   class="btn btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('permission', 'vehicle.delete')
                                <form action="{{ route('vehicles.delete', $vehicle->id) }}"
                                  method="POST"
                                  style="display: inline;"
                                  onsubmit="return confirm('Yakin ingin menghapus {{ $vehicle->consumerial_type }} {{ $vehicle->consumerial_code }}?\n\nData dapat dipulihkan dari Data Terhapus.')">
                                @csrf
                                <button type="submit" class="btn btn-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 opacity-50 d-block mb-2"></i>
                                <p class="mb-0">Belum ada data kendaraan/alat</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $vehicles->appends([
                'type' => request('type', ''),
                'search' => request('search', '')
            ])->links() }}
        </div>

        {{-- Data Terhapus (collapsible, bukan tab) --}}
        <div class="collapse mt-4" id="deletedVehiclesCollapse">
            <div class="rounded border p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="bi bi-trash me-1"></i>Data Terhapus</h6>
                    <small class="text-muted">Data terhapus permanen setelah 30 hari</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Tipe</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Dibuat Oleh</th>
                                <th>Dihapus Pada</th>
                                <th width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deletedVehicles as $vehicle)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $vehicle->consumerial_type }}</span>
                                </td>
                                <td><strong>{{ $vehicle->consumerial_code }}</strong></td>
                                <td>{{ $vehicle->consumerial_name }}</td>
                                <td><small>{{ $vehicle->creator->nama_lengkap ?? 'Unknown' }}</small></td>
                                <td><small>{{ $vehicle->deleted_at->format('d/m/Y H:i') }}</small></td>
                                <td>
                                    @can('permission', 'vehicle.delete')
                                    <form action="{{ route('vehicles.restore', $vehicle->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Pulihkan {{ $vehicle->consumerial_type }} {{ $vehicle->consumerial_code }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Pulihkan">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle fs-1 opacity-50 d-block mb-2"></i>
                                        <p class="mb-0">Tidak ada data yang dihapus</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    function initAutoFilter() {
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
    }
    initAutoFilter();
});
</script>
@endsection
