@extends('templates.main')

@section('title', 'Master Harga BBM')

@section('content')
<div class="pagetitle">
    <h1>Master Harga BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Harga BBM</li>
        </ol>
    </nav>
</div>

<section class="section">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-fuel-pump me-1"></i> Daftar Harga BBM per Region</h5>
                    <form action="{{ route('master-gasoline.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-repeat me-1"></i> Sinkronisasi dari API
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Sumber: <a href="https://github.com/nasgunawann/bensin-api" target="_blank" rel="noopener">bensin-api</a>
                        (update harian otomatis pukul 06.00).
                        @if($lastSync)
                        Sinkronisasi terakhir: <strong>{{ \Carbon\Carbon::parse($lastSync)->translatedFormat('d M Y H:i') }}</strong>.
                        @endif
                    </p>

                    @if($prices->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Belum ada data harga BBM. Klik tombol "Sinkronisasi dari API" atau jalankan
                        <code>php artisan gasoline:sync</code>.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Region / Provinsi</th>
                                    <th>Jenis BBM</th>
                                    <th class="text-end">Harga per Liter</th>
                                    <th>Ketersediaan</th>
                                    <th>Update Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prices->groupBy('province_slug') as $slug => $group)
                                <tr>
                                    <td rowspan="{{ $group->count() }}">
                                        <strong>{{ $group->first()->province }}</strong>
                                    </td>
                                    @foreach($group as $i => $price)
                                        @if($i > 0)<tr>@endif
                                        <td>{{ $price->fuel_type }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($price->price_per_liter, 0, ',', '.') }}</td>
                                        <td>{{ $price->availability ?? '-' }}</td>
                                        <td>{{ $price->price_date ? \Carbon\Carbon::parse($price->price_date)->translatedFormat('d M Y') : '-' }}</td>
                                        @if($i < $group->count() - 1)</tr>@endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-speedometer2 me-1"></i> Benchmark BBM per Kendaraan</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Benchmark = kapasitas tangki (liter) &times; harga per liter region unit kendaraan,
                        dibulatkan ke kelipatan Rp 50.000 (min Rp 50.000, maks Rp 3.000.000).
                        Rentang nominal permohonan = benchmark &plusmn; Rp 500.000.
                    </p>

                    @if($vehicles->isEmpty())
                    <div class="alert alert-info">Belum ada kendaraan terdaftar.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kendaraan</th>
                                    <th>Unit / Region</th>
                                    <th class="text-end">Tangki (L)</th>
                                    <th class="text-end">Kap. Penumpang</th>
                                    <th>Jenis BBM</th>
                                    <th class="text-end">Benchmark</th>
                                    <th>Rentang Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicles as $vehicle)
                                <tr>
                                    <td>{{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}</td>
                                    <td>{{ $vehicle->detail?->site?->name ?? '-' }} ({{ $vehicle->detail?->site?->province_slug ?? 'jawa-barat' }})</td>
                                    <td class="text-end">{{ $vehicle->detail?->volume_tangki ?? '-' }}</td>
                                    <td class="text-end">{{ $vehicle->detail?->kapasitas_penumpang ?? '-' }}</td>
                                    <td>{{ $vehicle->detail?->jenis_bahan_bakar ?? '-' }}</td>
                                    <td class="text-end fw-bold">
                                        @if($vehicle->fuel_benchmark)
                                        Rp {{ number_format($vehicle->fuel_benchmark, 0, ',', '.') }}
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vehicle->fuel_benchmark)
                                        Rp {{ number_format($vehicle->fuel_benchmark_range['min'], 0, ',', '.') }}
                                        - Rp {{ number_format($vehicle->fuel_benchmark_range['max'], 0, ',', '.') }}
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
