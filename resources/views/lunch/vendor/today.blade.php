@extends('templates.main')

@section('title', 'Daftar Kehadiran Lunch')

@section('content')
<div class="pagetitle">
    <h1>Daftar Siapa Makan Hari Ini</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Kehadiran Harian</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-0"><i class="bi bi-calendar-day me-2 text-primary"></i> Tanggal</h6>
            <small class="text-muted">Pilih tanggal untuk melihat kehadiran</small>
        </div>
        <form method="GET" action="{{ route('vendor.lunch.today') }}" class="d-flex gap-2">
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

@php $totalHadir = 0; @endphp

<div class="row g-3">
    @foreach($sites as $site)
        @php
            $hadir = $attendanceBySite->get($site->id, collect());
            $forecastCount = $forecastBySite->get($site->id, 0);
            $dist = $distributions->get($site->id);
            $totalHadir += $hadir->count();
        @endphp
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-geo-alt me-2"></i>{{ $site->name }} ({{ $site->code }})</strong>
                    <div class="small">
                        <span class="badge bg-info text-dark">Forecast: {{ $forecastCount }}</span>
                        @if($dist)
                            <span class="badge {{ $dist->is_locked ? 'bg-success' : 'bg-warning text-dark' }}">
                                Distribusi: {{ $dist->total_count }} {{ $dist->is_locked ? '(terkunci)' : '(draft)' }}
                            </span>
                        @endif
                        <span class="badge bg-primary">Hadir: {{ $hadir->count() }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($hadir->isEmpty())
                        <div class="text-center text-muted py-3 small">Belum ada yang scan QR di site ini.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Divisi</th>
                                        <th>Posisi</th>
                                        <th>Tipe Makan</th>
                                        <th>Waktu Scan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hadir as $i => $t)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td><strong>{{ $t->employee_name }}</strong></td>
                                            <td><small>{{ $t->division ?? '-' }}</small></td>
                                            <td><small>{{ $t->position ?? '-' }}</small></td>
                                            <td>
                                                @if($t->dine_type === 'take_away')
                                                    <span class="badge bg-warning text-dark">Bungkus</span>
                                                @elseif($t->dine_type === 'dine_in')
                                                    <span class="badge bg-success">Makan di tempat</span>
                                                @else
                                                    <span class="badge bg-light text-muted">-</span>
                                                @endif
                                            </td>
                                            <td><small>{{ optional($t->scanned_at)->format('H:i') }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="alert alert-light border mt-3">
    <i class="bi bi-people me-2"></i> Total kehadiran tanggal {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}:
    <strong>{{ $totalHadir }}</strong> orang
</div>
@endsection
