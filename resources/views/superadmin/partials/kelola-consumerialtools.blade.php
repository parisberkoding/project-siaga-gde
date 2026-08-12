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

@if($tab == 'list')
<!-- Main List Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center rounded-top">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-list-ul me-2"></i>Daftar Kendaraan & Alat
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('vehicles', ['tab' => 'deleted']) }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-trash me-1"></i>Data Terhapus
            </a>
            <a href="{{ route('vehicles.create') }}" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Tambah
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('vehicles') }}" class="mb-3" data-autofilter>
            <input type="hidden" name="tab" value="list">
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
                        <th width="50">ID</th>
                        <th width="100">Tipe</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Site</th>
                        <th width="70">Penumpang</th>
                        <th width="70">Tangki</th>
                        <th width="110">Jenis BBM</th>
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
                        @php
                            $barcode  = $vehicle->barcode ?? null;
                            $fullPath = $barcode ? storage_path('app/public/' . $barcode->barcode_path) : null;
                            $barcodeBase64 = ($fullPath && file_exists($fullPath))
                                ? 'data:image/png;base64,' . base64_encode(file_get_contents($fullPath))
                                : null;
                        @endphp
                        @if($barcodeBase64)
                            <div class="d-flex flex-column align-items-center gap-1">
                                <img src="{{ $barcodeBase64 }}"
                                    alt="Barcode {{ $vehicle->consumerial_code }}"
                                    style="width:70px;height:70px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;padding:2px;background:#fff;"
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
                                <a href="{{ route('vehicles.edit', $vehicle->id) }}"
                                   class="btn btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('vehicles.delete', $vehicle->id) }}"
                                  method="POST"
                                  style="display: inline;"
                                  onsubmit="return confirm('Yakin ingin menghapus {{ $vehicle->consumerial_type }} {{ $vehicle->consumerial_code }}?\n\nData dapat dipulihkan dari tab Data Terhapus.')">
                                @csrf
                                <button type="submit" class="btn btn-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
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
                'tab' => 'list',
                'type' => request('type', ''),
                'search' => request('search', '')
            ])->links() }}
        </div>
    </div>
</div>
@endif

@if($tab == 'deleted')
<!-- Deleted Data Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-secondary text-white rounded-top">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-trash me-2"></i>Data Terhapus
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info border-0 shadow-sm">
            <i class="bi bi-info-circle me-2"></i>
            Data yang dihapus masih dapat dipulihkan. Data akan terhapus permanen setelah 30 hari.
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tipe</th>
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
                            @if($vehicle->consumerial_type == 'KENDARAAN')
                                <span class="badge bg-secondary">Kendaraan</span>
                            @else
                                <span class="badge bg-secondary">Alat</span>
                            @endif
                        </td>
                        <td><strong>{{ $vehicle->consumerial_code }}</strong></td>
                        <td>{{ $vehicle->consumerial_name }}</td>
                        <td><small>{{ $vehicle->creator->nama_lengkap ?? 'Unknown' }}</small></td>
                        <td><small>{{ $vehicle->deleted_at->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <form action="{{ route('vehicles.restore', $vehicle->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Pulihkan {{ $vehicle->consumerial_type }} {{ $vehicle->consumerial_code }}?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" title="Pulihkan">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 opacity-50 d-block mb-2"></i>
                                <p class="mb-0">Tidak ada data yang dihapus</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $deletedVehicles->appends(['tab' => 'deleted'])->links() }}
        </div>
    </div>
</div>
@endif

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
