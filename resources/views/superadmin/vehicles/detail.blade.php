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

@php
    $detail = $vehicle->detail;
    $statusBadge = function (?string $status) {
        return match ($status) {
            'expired' => '<span class="badge bg-danger">Sudah lewat</span>',
            'soon' => '<span class="badge bg-warning text-dark">Segera habis (&le;30 hari)</span>',
            'ok' => '<span class="badge bg-success">Masih berlaku</span>',
            default => '<span class="badge bg-light text-muted border">Belum diisi</span>',
        };
    };
@endphp

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
                @if($detail)
                <h6 class="fw-semibold mb-3 mt-2"><i class="bi bi-card-list me-2"></i>Identitas {{ $vehicle->consumerial_type == 'KENDARAAN' ? 'Kendaraan' : 'Alat' }}</h6>
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="220">Unit Pemilik</th>
                            <td>
                                @if($vehicle->unit)
                                    <span class="badge bg-success">{{ $vehicle->unit->name }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum ditentukan</span>
                                    <small class="text-muted d-block mt-1">Kendaraan tanpa unit tidak akan terlihat oleh driver unit mana pun.</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Site / Lokasi</th>
                            <td>{{ $detail->site?->name ?? '-' }}</td>
                        </tr>
                        @if($detail->jenis_kendaraan)
                        <tr>
                            <th>Jenis</th>
                            <td>{{ $detail->jenis_kendaraan }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Merk / Model</th>
                            <td>{{ trim(($detail->merk ?? '').' '.($detail->model ?? '')) ?: '-' }}</td>
                        </tr>
                        @if($detail->tahun_pembuatan)
                        <tr>
                            <th>Tahun Pembuatan</th>
                            <td>{{ $detail->tahun_pembuatan }}</td>
                        </tr>
                        @endif
                        @if($detail->warna)
                        <tr>
                            <th>Warna</th>
                            <td>{{ $detail->warna }}</td>
                        </tr>
                        @endif
                        @if($detail->kapasitas_mesin)
                        <tr>
                            <th>Kapasitas Mesin</th>
                            <td>{{ number_format($detail->kapasitas_mesin, 0, ',', '.') }} CC</td>
                        </tr>
                        @endif
                        @if($detail->odometer)
                        <tr>
                            <th>Odometer Terakhir</th>
                            <td>{{ number_format($detail->odometer, 0, ',', '.') }} km</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Status Kepemilikan</th>
                            <td>
                                @if($detail->status_kepemilikan_label)
                                    <span class="badge {{ $detail->status_kepemilikan === \App\Models\VehicleDetail::STATUS_SEWA ? 'bg-info text-dark' : 'bg-secondary' }}">
                                        {{ $detail->status_kepemilikan_label }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ $vehicle->consumerial_type == 'KENDARAAN' ? 'Nomor Rangka (VIN)' : 'Nomor Seri / Identitas' }}</th>
                            <td>{!! $detail->nomor_rangka ? '<code>'.e($detail->nomor_rangka).'</code>' : '<span class="text-muted">-</span>' !!}</td>
                        </tr>
                        <tr>
                            <th>{{ $vehicle->consumerial_type == 'KENDARAAN' ? 'Nomor Mesin' : 'Nomor Mesin / Model' }}</th>
                            <td>{!! $detail->nomor_mesin ? '<code>'.e($detail->nomor_mesin).'</code>' : '<span class="text-muted">-</span>' !!}</td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="fw-semibold mb-3 mt-4"><i class="bi bi-file-earmark-text me-2"></i>Dokumen & Legalitas</h6>
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="220">Nomor BPKB</th>
                            <td>{!! $detail->nomor_bpkb ? '<code>'.e($detail->nomor_bpkb).'</code>' : '<span class="text-muted">-</span>' !!}</td>
                        </tr>
                        <tr>
                            <th>Nomor STNK</th>
                            <td>{!! $detail->nomor_stnk ? '<code>'.e($detail->nomor_stnk).'</code>' : '<span class="text-muted">-</span>' !!}</td>
                        </tr>
                        <tr>
                            <th>Nomor KIR</th>
                            <td>{!! $detail->nomor_kir ? '<code>'.e($detail->nomor_kir).'</code>' : '<span class="text-muted">-</span>' !!}</td>
                        </tr>
                        <tr>
                            <th>Pajak Berlaku Sampai</th>
                            <td>
                                @if($detail->pajak_berlaku_sampai)
                                    {{ $detail->pajak_berlaku_sampai->format('d/m/Y') }}
                                    {!! $statusBadge($detail->pajak_status) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>KIR Berlaku Sampai</th>
                            <td>
                                @if($detail->kir_berlaku_sampai)
                                    {{ $detail->kir_berlaku_sampai->format('d/m/Y') }}
                                    {!! $statusBadge($detail->kir_status) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="fw-semibold mb-3 mt-4"><i class="bi bi-fuel-pump me-2"></i>Kapasitas & Budget BBM</h6>
                <table class="table table-bordered">
                    <tbody>
                        @if($detail->kapasitas_penumpang)
                        <tr>
                            <th width="220">Kapasitas Penumpang</th>
                            <td>{{ $detail->kapasitas_penumpang }} orang</td>
                        </tr>
                        @endif
                        @if($detail->kapasitas_bahan_bakar)
                        <tr>
                            <th>Kapasitas Bahan Bakar</th>
                            <td>{{ $detail->kapasitas_bahan_bakar }} liter</td>
                        </tr>
                        @endif
                        @if($detail->jenis_bahan_bakar)
                        <tr>
                            <th>Jenis Bahan Bakar</th>
                            <td>{{ $detail->jenis_bahan_bakar }}</td>
                        </tr>
                        @endif
                        @if($detail->volume_tangki)
                        <tr>
                            <th>Volume Tangki</th>
                            <td>{{ $detail->volume_tangki }} liter</td>
                        </tr>
                        @endif
                        @if($detail->nominal_full_tank)
                        <tr>
                            <th>Nominal Full Tank</th>
                            <td>Rp {{ number_format($detail->nominal_full_tank, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($detail->nominal_spd_default)
                        <tr>
                            <th>Default Nominal SPD</th>
                            <td>Rp {{ number_format($detail->nominal_spd_default, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($detail->kapasitas_bbm_tahunan)
                        <tr>
                            <th>Kuota BBM Tahunan</th>
                            <td>{{ $detail->kapasitas_bbm_tahunan }} liter</td>
                        </tr>
                        @endif
                        @if($detail->catatan)
                        <tr>
                            <th>Catatan</th>
                            <td style="white-space: pre-wrap;">{{ $detail->catatan }}</td>
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
                        <th>Kode / Plat</th>
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
                        <th>Unit Bisnis</th>
                        <td>
                            @if($vehicle->unit)
                                <span class="badge bg-success">{{ $vehicle->unit->name }}</span>
                            @else
                                <span class="badge bg-warning text-dark">Belum diisi</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Status Kepemilikan</th>
                        <td>
                            @if($detail?->status_kepemilikan_label)
                                {{ $detail->status_kepemilikan_label }}
                            @else
                                <span class="text-muted">-</span>
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
            <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit Kendaraan / Alat
            </a>
            <a href="{{ route('vehicles', ['tab' => 'list']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
            </a>
        </div>
    </div>
</div>
@endsection
