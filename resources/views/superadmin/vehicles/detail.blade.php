@extends('templates.main')

@section('title', 'Detail Kendaraan')

@section('content')
<div class="pagetitle">
    <h1>Detail Kendaraan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vehicles', ['tab' => 'list']) }}">Kendaraan & Alat</a></li>
            <li class="breadcrumb-item active">{{ $vehicle->consumerial_code }}</li>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>{{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}</h5>
                <a href="{{ route('superadmin.vehicle.detail.edit', $vehicle->id) }}" class="btn btn-light btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Detail
                </a>
            </div>
            <div class="card-body">
                @if($vehicle->detail)
                <table class="table table-bordered mt-3">
                    <tbody>
                        @if($vehicle->detail->jenis_kendaraan)
                        <tr>
                            <th width="200">Jenis Kendaraan</th>
                            <td>{{ $vehicle->detail->jenis_kendaraan }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->merk)
                        <tr>
                            <th>Merk</th>
                            <td>{{ $vehicle->detail->merk }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->model)
                        <tr>
                            <th>Model</th>
                            <td>{{ $vehicle->detail->model }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->tahun_pembuatan)
                        <tr>
                            <th>Tahun Pembuatan</th>
                            <td>{{ $vehicle->detail->tahun_pembuatan }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->nomor_rangka)
                        <tr>
                            <th>Nomor Rangka (VIN)</th>
                            <td><code>{{ $vehicle->detail->nomor_rangka }}</code></td>
                        </tr>
                        @endif
                        @if($vehicle->detail->nomor_mesin)
                        <tr>
                            <th>Nomor Mesin</th>
                            <td><code>{{ $vehicle->detail->nomor_mesin }}</code></td>
                        </tr>
                        @endif
                        @if($vehicle->detail->warna)
                        <tr>
                            <th>Warna</th>
                            <td>{{ $vehicle->detail->warna }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->kapasitas_penumpang)
                        <tr>
                            <th>Kapasitas Penumpang</th>
                            <td>{{ $vehicle->detail->kapasitas_penumpang }} orang</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->kapasitas_bahan_bakar)
                        <tr>
                            <th>Kapasitas Bahan Bakar</th>
                            <td>{{ $vehicle->detail->kapasitas_bahan_bakar }} liter</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->jenis_bahan_bakar)
                        <tr>
                            <th>Jenis Bahan Bakar</th>
                            <td>{{ $vehicle->detail->jenis_bahan_bakar }}</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->volume_tangki)
                        <tr>
                            <th>Volume Tangki</th>
                            <td>{{ $vehicle->detail->volume_tangki }} liter</td>
                        </tr>
                        @endif
                        @if($vehicle->detail->catatan)
                        <tr>
                            <th>Catatan</th>
                            <td style="white-space: pre-wrap;">{{ $vehicle->detail->catatan }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                    <p>Detail kendaraan belum diisi</p>
                    <a href="{{ route('superadmin.vehicle.detail.edit', $vehicle->id) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Lengkapi Detail
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Umum</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Kode</th>
                        <td>{{ $vehicle->consumerial_code }}</td>
                    </tr>
                    <tr>
                        <th>Nama</th>
                        <td>{{ $vehicle->consumerial_name }}</td>
                    </tr>
                    <tr>
                        <th>Tipe</th>
                        <td>
                            @if($vehicle->consumerial_type == 'KENDARAAN')
                                <span class="badge bg-primary">Kendaraan</span>
                            @else
                                <span class="badge bg-info text-dark">Alat</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($vehicle->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-warning text-dark">Tidak Aktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Approval</th>
                        <td>
                            @if($vehicle->is_approved)
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Dibuat Oleh</th>
                        <td><small>{{ $vehicle->creator->nama_lengkap ?? '-' }}</small></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('vehicles', ['tab' => 'list']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
            </a>
        </div>
    </div>
</div>
@endsection
